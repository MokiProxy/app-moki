<?php

namespace App\Console\Commands\Dokter;

use App\Jobs\ProcessScanFile;
use App\Models\DocumentType;
use App\Services\FileConversionService;
use App\Services\OcrSearchService;
use App\Services\OcrService;
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
     *
     * @return int
     */
    public function handle(OcrService $ocr, OcrSearchService $search, FileConversionService $converter): int
    {
        $documentTypes = DocumentType::all();

        if ($documentTypes->isEmpty()) {
            $this->error('No document types configured. Please create at least one document type.');

            return self::FAILURE;
        }

        $this->processRootFiles($documentTypes, $ocr, $search, $converter);
        $this->processSubfolderFiles($documentTypes, $converter);

        Log::debug('Scanner monitor cycle completed');

        return self::SUCCESS;
    }

    protected function processRootFiles($documentTypes, OcrService $ocr, OcrSearchService $search, FileConversionService $converter): void
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

                continue;
            }

            $content = Storage::disk('ftp_scanner')->get($file);
            $localPath = "scanner/incoming/{$filename}";
            Storage::disk('local')->put($localPath, $content);
            Storage::disk('ftp_scanner')->delete($file);

            $fullPath = storage_path("app/private/{$localPath}");
            $docType = $this->detectDocumentType($fullPath, $filename, $documentTypes, $ocr, $search);

            if ($docType === null) {
                $this->warn("Could not detect document type for: {$filename}. Skipping.");
                Storage::disk('local')->delete($localPath);
                Log::warning('Auto-detect failed for root file', ['file' => $filename]);

                continue;
            }

            $this->info("Auto-detected: {$docType->name} for {$filename}");

            if ($converter->isPdf($fullPath)) {
                try {
                    $images = $converter->pdfToImages($fullPath);
                    Storage::disk('local')->delete($localPath);

                    if (empty($images)) {
                        $this->warn("Failed to convert PDF: {$filename}");
                        Log::warning('PDF conversion failed', ['file' => $filename]);
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
                    Log::warning('PDF to images conversion not available, processing PDF directly', [
                        'file' => $filename,
                        'error' => $e->getMessage(),
                    ]);
                    ProcessScanFile::dispatch($filename, $docType->id);
                    $this->info("OCR job dispatched for PDF: {$filename} (document type: {$docType->name})");
                }
            } else {
                ProcessScanFile::dispatch($filename, $docType->id);
                $this->info("OCR job dispatched for: {$filename} (document type: {$docType->name})");
            }
        }
    }

    protected function processSubfolderFiles($documentTypes, FileConversionService $converter): void
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
                $this->processFile($file, $docType, $converter);
            }
        }
    }

    protected function processFile(string $file, DocumentType $documentType, FileConversionService $converter): void
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if (! in_array($extension, $this->allowedExtensions)) {
            $this->warn("Skipping unsupported file: {$file}");
            Storage::disk('ftp_scanner')->delete($file);

            return;
        }

        $this->info("Downloading: {$file}");

        $content = Storage::disk('ftp_scanner')->get($file);
        $filename = basename($file);
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
                    Log::warning('PDF conversion failed', ['file' => $filename]);

                    return;
                }

                foreach ($images as $imagePath) {
                    $imageFilename = basename($imagePath);
                    ProcessScanFile::dispatch($imageFilename, $documentType->id);
                    $this->info("OCR job dispatched for: {$imageFilename} (document type: {$documentType->name})");
                }
            } catch (Exception $e) {
                $this->warn("PDF local conversion unavailable, processing PDF directly: {$filename}");
                Log::warning('PDF to images conversion not available, processing PDF directly', [
                    'file' => $filename,
                    'error' => $e->getMessage(),
                ]);
                ProcessScanFile::dispatch($filename, $documentType->id);
                $this->info("OCR job dispatched for PDF: {$filename} (document type: {$documentType->name})");
            }
        } else {
            ProcessScanFile::dispatch($filename, $documentType->id);
            $this->info("OCR job dispatched for: {$filename} (document type: {$documentType->name})");
        }
    }

    protected function detectDocumentType(string $filePath, string $filename, $documentTypes, OcrService $ocr, OcrSearchService $search): ?DocumentType
    {
        $uploadedFile = new UploadedFile($filePath, $filename, mime_content_type($filePath), null, true);

        try {
            $result = $ocr->extractText($uploadedFile);
        } catch (\Exception $e) {
            $this->warn("OCR failed: {$e->getMessage()}");

            return null;
        }

        if (empty($result['success'])) {
            return null;
        }

        $ocrText = $result['text'] ?? '';
        $bestDocType = null;
        $bestScore = 0;

        foreach ($documentTypes as $docType) {
            $score = $this->scoreDocumentType($docType, $ocrText, $search);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestDocType = $docType;
            }
        }

        return $bestDocType;
    }

    protected function scoreDocumentType(DocumentType $docType, string $ocrText, OcrSearchService $search): int
    {
        $score = 0;

        $pattern = $docType->number_regex ?? null;

        if ($pattern && @preg_match($pattern, $ocrText)) {
            $score += 10;
        }

        $vendorNames = $docType->vendors()->pluck('name')->toArray();

        if (! empty($vendorNames)) {
            $match = $search->searchData(
                [['text' => $ocrText]],
                $vendorNames,
            )->first();

            if ($match) {
                $score += 5;
            }
        }

        return $score;
    }
}
