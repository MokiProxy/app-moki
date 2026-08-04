<?php

namespace App\Console\Commands\Dokter;

use App\Jobs\ProcessScanFile;
use App\Models\DocumentType;
use App\Services\FileConversionService;
use App\Services\OcrService;
use App\Services\ScanLogger;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MonitorScanner extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:monitor-scanner';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor FTP Scanner and dispatch OCR jobs';

    protected $allowedExtensions = ['png', 'jpg', 'jpeg', 'webp', 'pdf'];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(OcrService $ocr, FileConversionService $converter, ScanLogger $logger): int
    {
        $documentTypes = DocumentType::all();

        if ($documentTypes->isEmpty()) {
            $this->error('No document types configured. Please create at least one document type.');

            return self::FAILURE;
        }

        $this->processRootFiles($documentTypes, $ocr, $converter, $logger);
        $this->processSubfolderFiles($documentTypes, $converter, $logger);

        Log::debug('Scanner monitor cycle completed');

        return self::SUCCESS;
    }

    protected function processRootFiles($documentTypes, OcrService $ocr, FileConversionService $converter, ScanLogger $logger): void
    {
        $files = Storage::disk('ftp_scanner')->files();

        if (empty($files)) {
            return;
        }

        foreach ($files as $file) {
            $filename = basename($file);
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (! in_array($extension, $this->allowedExtensions)) {
                $this->warn("Skipping non-image file: {$file}");
                Storage::disk('ftp_scanner')->delete($file);
                $logger->log('file_skipped', 'skipped', [
                    'filename' => $filename,
                    'extension' => $extension,
                    'message' => 'Ekstensi file tidak didukung: '.$extension,
                ]);

                continue;
            }

            $content = Storage::disk('ftp_scanner')->get($file);
            $localPath = "scanner/incoming/{$filename}";
            Storage::disk('local')->put($localPath, $content);
            Storage::disk('ftp_scanner')->delete($file);

            $fullPath = storage_path("app/private/{$localPath}");
            $docType = $this->detectDocumentType($fullPath, $filename, $documentTypes, $ocr);

            if ($docType === null) {
                $this->warn("Could not detect document type for: {$filename}. Moving to FAILED folder.");
                $moved = $this->uploadToFailedFolder($filename, $content);
                Storage::disk('local')->delete($localPath);
                $logger->log('detection_failed', 'failed', [
                    'filename' => $filename,
                    'extension' => $extension,
                    'message' => $moved
                        ? 'Header match gagal, file dipindah ke folder FAILED'
                        : 'Header match gagal, upload ke folder FAILED gagal',
                ]);

                continue;
            }

            $this->info("Auto-detected: {$docType->name} for {$filename}");

            if ($converter->isPdf($fullPath)) {
                try {
                    $images = $converter->pdfToImages($fullPath);
                    Storage::disk('local')->delete($localPath);

                    if (empty($images)) {
                        $this->warn("Failed to convert PDF: {$filename}");
                        $logger->log('job_failed', 'failed', [
                            'filename' => $filename,
                            'extension' => $extension,
                            'document_type_id' => $docType->id,
                            'document_type_name' => $docType->name,
                            'message' => 'Konversi PDF ke gambar gagal',
                        ]);

                        continue;
                    }

                    foreach ($images as $imagePath) {
                        $imageFilename = basename($imagePath);
                        $this->info("PDF converted to image: {$imageFilename}");
                        ProcessScanFile::dispatch($imageFilename, $docType->id);
                        $this->info("OCR job dispatched for: {$imageFilename} (document type: {$docType->name})");
                    }
                } catch (Exception $e) {
                    $this->warn("PDF local conversion unavailable, processing PDF directly: {$filename}");
                    ProcessScanFile::dispatch($filename, $docType->id);
                    $this->info("OCR job dispatched for PDF: {$filename} (document type: {$docType->name})");
                }
            } else {
                ProcessScanFile::dispatch($filename, $docType->id);
                $this->info("OCR job dispatched for: {$filename} (document type: {$docType->name})");
            }
        }
    }

    protected function processSubfolderFiles($documentTypes, FileConversionService $converter, ScanLogger $logger): void
    {
        $directories = Storage::disk('ftp_scanner')->directories();

        foreach ($directories as $directory) {
            $slug = basename($directory);
            $docType = $documentTypes->firstWhere('slug', $slug);

            if (! $docType) {
                $this->warn("No document type found for subfolder: {$slug}");

                continue;
            }

            $files = Storage::disk('ftp_scanner')->files($directory);

            foreach ($files as $file) {
                $this->processFile($file, $docType, $converter, $logger);
            }
        }
    }

    protected function processFile(string $file, DocumentType $documentType, FileConversionService $converter, ScanLogger $logger): void
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $filename = basename($file);

        if (! in_array($extension, $this->allowedExtensions)) {
            $this->warn("Skipping unsupported file: {$file}");
            Storage::disk('ftp_scanner')->delete($file);
            $logger->log('file_skipped', 'skipped', [
                'filename' => $filename,
                'extension' => $extension,
                'document_type_id' => $documentType->id,
                'document_type_name' => $documentType->name,
                'message' => 'Ekstensi file tidak didukung: '.$extension,
            ]);

            return;
        }

        $this->info("Downloading: {$file}");

        $content = Storage::disk('ftp_scanner')->get($file);
        $localPath = "scanner/incoming/{$filename}";
        Storage::disk('local')->put($localPath, $content);
        Storage::disk('ftp_scanner')->delete($file);

        $fullPath = storage_path("app/private/{$localPath}");

        if ($converter->isPdf($fullPath)) {
            try {
                $images = $converter->pdfToImages($fullPath);
                Storage::disk('local')->delete($localPath);

                if (empty($images)) {
                    $this->warn("Failed to convert PDF: {$filename}");
                    $logger->log('job_failed', 'failed', [
                        'filename' => $filename,
                        'extension' => $extension,
                        'document_type_id' => $documentType->id,
                        'document_type_name' => $documentType->name,
                        'message' => 'Konversi PDF ke gambar gagal',
                    ]);

                    return;
                }

                foreach ($images as $imagePath) {
                    $imageFilename = basename($imagePath);
                    ProcessScanFile::dispatch($imageFilename, $documentType->id);
                    $this->info("OCR job dispatched for: {$imageFilename} (document type: {$documentType->name})");
                }
            } catch (Exception $e) {
                $this->warn("PDF local conversion unavailable, processing PDF directly: {$filename}");
                ProcessScanFile::dispatch($filename, $documentType->id);
                $this->info("OCR job dispatched for PDF: {$filename} (document type: {$documentType->name})");
            }
        } else {
            ProcessScanFile::dispatch($filename, $documentType->id);
            $this->info("OCR job dispatched for: {$filename} (document type: {$documentType->name})");
        }
    }

    protected function detectDocumentType(string $filePath, string $filename, $documentTypes, OcrService $ocr): ?DocumentType
    {
        $uploadedFile = new UploadedFile($filePath, $filename, mime_content_type($filePath), null, true);

        try {
            $result = $ocr->extractText($uploadedFile);
        } catch (Exception $e) {
            $this->warn("OCR failed: {$e->getMessage()}");

            return null;
        }

        if (empty($result['success'])) {
            return null;
        }

        $ocrText = $result['text'] ?? '';

        // Header Match (Primary Identifier) - satu-satunya algoritma deteksi jenis dokumen.
        foreach ($documentTypes as $docType) {
            if ($this->matchHeader($docType, $ocrText)) {
                Log::info('Document type detected via header match', [
                    'file' => $filename,
                    'detected_type' => $docType->name,
                ]);

                return $docType;
            }
        }

        return null;
    }

    protected function matchHeader(DocumentType $docType, string $ocrText): bool
    {
        $pattern = $docType->header_regex ?? null;

        if ($pattern === null || $pattern === '') {
            return false;
        }

        $result = @preg_match($pattern, $ocrText);

        if ($result === false) {
            Log::warning('Invalid header_regex', [
                'doc_type' => $docType->name,
                'regex' => $pattern,
            ]);

            return false;
        }

        return $result === 1;
    }

    protected function uploadToFailedFolder(string $filename, string $content): bool
    {
        $failedPath = "FAILED/{$filename}";
        $ftpDisk = Storage::disk('ftp_final');
        $ftpAdapter = $ftpDisk->getAdapter();

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $ftpAdapter->disconnect();
                $ftpDisk->put($failedPath, $content);
                $this->info("File dipindah ke FAILED folder: {$failedPath}");

                return true;
            } catch (Exception $e) {
                Log::warning("FTP upload attempt {$attempt} failed (FAILED folder)", [
                    'file' => $filename,
                    'ftp_path' => $failedPath,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < 3) {
                    sleep(5);
                }
            }
        }

        return false;
    }
}
