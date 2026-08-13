<?php

namespace App\Jobs;

use App\Models\DocumentType;
use App\Services\DocumentTypeProcessor;
use App\Services\FileConversionService;
use App\Services\MergeFlowService;
use App\Services\Ocr\GeminiEngine;
use App\Services\PdfMergeService;
use App\Services\ScanLogger;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessScanFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public int $backoff = 30;

    public function __construct(
        public string $filename,
        public ?int $documentTypeId = null,
    ) {}

    public function handle(
        DocumentTypeProcessor $processor,
        FileConversionService $converter,
        PdfMergeService $merger,
        ScanLogger $logger,
    ): void {
        $incomingPath = "scanner/incoming/{$this->filename}";
        $fullPath = storage_path("app/private/{$incomingPath}");

        if (! file_exists($fullPath)) {
            Log::warning('File not found for OCR processing', ['file' => $this->filename]);
            $logger->log('job_failed', 'failed', [
                'filename' => $this->filename,
                'message' => 'File tidak ditemukan di storage lokal',
            ]);

            return;
        }

        $uploadedFile = new UploadedFile($fullPath, $this->filename, mime_content_type($fullPath), null, true);

        $ocr = new GeminiEngine();

        // Single-pass: 1 Gemini call mengembalikan document_type + semua field
        $result = $ocr->extractText($uploadedFile);

        if (empty($result['success'])) {
            throw new Exception('OCR failed: '.json_encode($result));
        }

        $ocrText = $result['text'] ?? '';
        $ocrData = $result['ocr_data'] ?? null;

        Log::info('OCR DATA', [
            'data' => $ocrData,
        ]);

        // Resolve document type dari Gemini response
        $documentType = $this->resolveDocumentType($this->documentTypeId, $ocrData);

        if ($documentType === null) {
            throw new Exception("Document type not found for: {$this->filename}");
        }

        if ($ocrData !== null) {
            $documentNumber = $ocrData['document_number'] ?? null;
            $vendorName = $this->sanitizeFilename($ocrData['vendor_name'] ?? null);
            $tanggal = $ocrData['document_date'] ?? null;
            $keterangan = $ocrData['keterangan'] ?? null;
            $uraian = is_array($ocrData['uraian'] ?? null) ? json_encode($ocrData['uraian']) : ($ocrData['uraian'] ?? null);

            Log::info('Using structured data from Gemini', [
                'file' => $this->filename,
                'document_type' => $documentType->name,
                'document_number' => $documentNumber,
                'vendor_name' => $vendorName,
            ]);
        } else {
            $vendorName = $processor->matchVendor($documentType, $ocrText);
            $documentNumber = null;
            $tanggal = null;
            $keterangan = null;
            $uraian = null;
        }

        $originalExtension = pathinfo($this->filename, PATHINFO_EXTENSION);
        $targetFilename = $processor->generateFilename($documentType, $vendorName, $documentNumber, $originalExtension);
        $targetFilename = $targetFilename ?: $this->filename;

        $ocrTemp = [
            'filename' => $this->filename,
            'text' => $ocrText,
            'processing_time_ms' => $result['processing_time_ms'] ?? null,
            'processed_at' => now()->toIso8601String(),
        ];

        $ocrData = array_merge($ocrTemp, $ocrData ?? []);

        Storage::disk('local')->put(
            "scanner/ocr-results/{$this->filename}.json",
            json_encode($ocrData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $content = file_get_contents($fullPath);

        if ($content === false) {
            throw new Exception("Failed to read file: {$fullPath}");
        }

        if ($converter->isPdf($fullPath)) {
            $ftpContent = $content;
            $pdfFilename = $targetFilename;
        } else {
            $pdfFilename = str_replace(
                pathinfo($targetFilename, PATHINFO_EXTENSION),
                'pdf',
                $targetFilename
            );

            try {
                $pdfPath = $converter->imageToPdf(
                    $fullPath,
                    storage_path("app/private/scanner/converted/{$pdfFilename}")
                );
                $ftpContent = file_get_contents($pdfPath);
                Storage::disk('local')->delete("scanner/converted/{$pdfFilename}");
            } catch (Exception $e) {
                Log::warning('Image to PDF conversion failed, uploading original image', [
                    'file' => $targetFilename,
                    'error' => $e->getMessage(),
                ]);
                $ftpContent = $content;
                $pdfFilename = $targetFilename;
            }
        }

        if (! $vendorName) {
            $vendorExpected = $documentType->vendor_search_enabled && $documentType->vendors()->exists();
            $ftpPath = $vendorExpected
                ? $processor->resolveFailedPath($documentType, $pdfFilename)
                : $processor->resolveFtpPath($documentType, $vendorName, $documentNumber, $pdfFilename);
        } else {
            $ftpPath = $processor->resolveFtpPath($documentType, $vendorName, $documentNumber, $pdfFilename);
        }

        $ftpDisk = Storage::disk('ftp_final');
        $ftpAdapter = $ftpDisk->getAdapter();

        $mergeStatus = 'new';
        $totalPages = 1;

        $existingContent = null;
        try {
            if ($ftpDisk->exists($ftpPath)) {
                $existingContent = $ftpDisk->get($ftpPath);
            }
        } catch (Exception $e) {
            Log::warning('Failed to check existing file on FTP', [
                'ftp_path' => $ftpPath,
                'error' => $e->getMessage(),
            ]);
        }

        if ($existingContent !== null) {
            $tempExisting = storage_path('app/private/scanner/temp/existing_' . $pdfFilename);
            $tempNew = storage_path('app/private/scanner/temp/new_' . $pdfFilename);
            $mergedDir = storage_path('app/private/scanner/merged');
            $mergedPath = $mergedDir . '/' . $pdfFilename;

            $this->ensureDirectoryExists(dirname($tempExisting));
            $this->ensureDirectoryExists($mergedDir);

            file_put_contents($tempExisting, $existingContent);
            file_put_contents($tempNew, $ftpContent);

            try {
                $merger->mergePdfs(
                    [$tempExisting, $tempNew],
                    $mergedPath
                );

                $ftpContent = file_get_contents($mergedPath);
                $totalPages = $merger->getPageCount($mergedPath);
                $mergeStatus = 'merged';

                Log::info('PDF merged successfully', [
                    'file' => $pdfFilename,
                    'total_pages' => $totalPages,
                ]);

                @unlink($mergedPath);
            } catch (Exception $e) {
                Log::warning('PDF merge failed, uploading new file only', [
                    'file' => $pdfFilename,
                    'error' => $e->getMessage(),
                ]);

                $mergeStatus = 'merge_failed';
            }

            @unlink($tempExisting);
            @unlink($tempNew);
        }

        $lastException = null;

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $ftpAdapter->disconnect();
                $ftpDisk->put($ftpPath, $ftpContent);

                $lastException = null;

                break;
            } catch (Exception $e) {
                $lastException = $e;
                Log::warning("FTP upload attempt {$attempt} failed", [
                    'file' => $pdfFilename,
                    'ftp_path' => $ftpPath,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < 3) {
                    sleep(5);
                }
            }
        }

        if ($lastException !== null) {
            throw new Exception("FTP upload failed after 3 attempts: {$lastException->getMessage()}", 0, $lastException);
        }

        Storage::disk('local')->delete($incomingPath);

        $mergeMessage = match ($mergeStatus) {
            'merged' => "File digabung, total {$totalPages} pages",
            'merge_failed' => 'File merge gagal, file baru diupload saja',
            default => 'File baru diupload (1 page)',
        };

        $scanLog = $logger->log('job_completed', 'success', [
            'filename' => $this->filename,
            'document_type_id' => $documentType->id,
            'document_type_name' => $documentType->name,
            'document_number' => $documentNumber,
            'vendor_name' => $vendorName,
            'tanggal' => $tanggal,
            'keterangan' => $keterangan,
            'uraian' => $uraian,
            'ftp_path' => $ftpPath,
            'processing_time_ms' => $ocrData['processing_time_ms'],
            'merge_status' => $mergeStatus,
            'total_pages' => $totalPages,
            'message' => $mergeMessage,
            'ocr_text' => $ocrText,
        ]);

        $existingMetadata = $scanLog->metadata ?? [];
        $existingMetadata['ocr_data'] = $ocrData;
        $scanLog->update(['metadata' => $existingMetadata]);

        app(MergeFlowService::class)->processAfterUpload($scanLog);

        Log::info('OCR processed successfully', [
            'filename' => $this->filename,
            'document_type' => strtoupper($documentType->name),
            'document_number' => $documentNumber,
            'vendor_name' => $vendorName,
            'tanggal' => $tanggal,
            'keterangan' => $keterangan,
            'uraian' => $uraian,
            'ftp_path' => $ftpPath,
            'processing_time_ms' => $ocrData['processing_time_ms'],
            'merge_status' => $mergeStatus,
            'total_pages' => $totalPages,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        app(ScanLogger::class)->log('job_failed', 'failed', [
            'filename' => $this->filename,
            'message' => $exception?->getMessage() ?? 'Job gagal permanen',
        ]);

        Log::error('OCR job failed permanently', [
            'file' => $this->filename,
            'error' => $exception?->getMessage(),
        ]);
    }

    protected function ensureDirectoryExists(string $directory): void
    {
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    protected function sanitizeFilename(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return preg_replace('#[/\\\\:*?"<>|]#', '_', $value);
    }

    /**
     * Resolve document type dari ID atau Gemini response.
     *
     * Jika documentTypeId ada, gunakan itu (subfolder flow).
     * Jika null, deteksi dari document_type di Gemini response (root folder flow — single-pass).
     */
    protected function resolveDocumentType(?int $documentTypeId, ?array $ocrData): ?DocumentType
    {
        // Prioritas: gunakan ID yang sudah diketahui (dari subfolder)
        if ($documentTypeId !== null) {
            return DocumentType::find($documentTypeId);
        }

        // Single-pass: deteksi dari Gemini response
        $documentTypeName = strtoupper($ocrData['document_type'] ?? '');

        if ($documentTypeName === '') {
            Log::warning('document_type kosong dari Gemini', [
                'file' => $this->filename,
            ]);

            return null;
        }

        $docType = DocumentType::whereRaw('UPPER(name) = ?', [$documentTypeName])->first();

        if ($docType === null) {
            Log::warning("document_type '{$documentTypeName}' tidak ditemukan di database", [
                'file' => $this->filename,
            ]);
        }

        return $docType;
    }
}
