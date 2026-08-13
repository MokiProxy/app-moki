<?php

namespace App\Console\Commands\Dokter;

use App\Jobs\ProcessScanFile;
use App\Models\DocumentType;
use App\Services\FileConversionService;
use App\Services\ScanLogger;
use Exception;
use Illuminate\Console\Command;
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

    protected int $batchSize = 5;

    protected int $batchDelayMs = 500;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->batchSize = config('services.gemini.batch_size', 5);
        $this->batchDelayMs = config('services.gemini.batch_delay_ms', 500);
    }

    /**
     * Execute the console command.
     */
    public function handle(FileConversionService $converter, ScanLogger $logger): int
    {
        if (DocumentType::count() === 0) {
            $this->error('No document types configured. Please create at least one document type.');

            return self::FAILURE;
        }

        $this->processRootFiles($converter, $logger);
        $this->processSubfolderFiles($converter, $logger);

        Log::debug('Scanner monitor cycle completed');

        return self::SUCCESS;
    }

    /**
     * Process files di root folder FTP scanner.
     * Document type akan dideteksi otomatis oleh ProcessScanFile (single-pass Gemini).
     */
    protected function processRootFiles(FileConversionService $converter, ScanLogger $logger): void
    {
        $files = Storage::disk('ftp_scanner')->files();

        if (empty($files)) {
            return;
        }

        $batches = array_chunk($files, $this->batchSize);
        $batchCount = count($batches);

        foreach ($batches as $batchIndex => $batch) {
            Log::debug("Processing batch ".($batchIndex + 1)." of {$batchCount}", [
                'files_in_batch' => count($batch),
            ]);

            foreach ($batch as $file) {
                $this->processRootFile($file, $converter, $logger);
            }

            if ($batchIndex < $batchCount - 1 && $this->batchDelayMs > 0) {
                usleep($this->batchDelayMs * 1000);
            }
        }
    }

    /**
     * Process single file dari root folder.
     * Dispatch ProcessScanFile tanpa documentTypeId — deteksi dilakukan dalam 1 Gemini call.
     */
    protected function processRootFile(string $file, FileConversionService $converter, ScanLogger $logger): void
    {
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

            return;
        }

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
                        'message' => 'Konversi PDF ke gambar gagal',
                    ]);

                    return;
                }

                foreach ($images as $imagePath) {
                    $imageFilename = basename($imagePath);
                    ProcessScanFile::dispatch($imageFilename);
                    $this->info("OCR job dispatched for: {$imageFilename}");
                }
            } catch (Exception $e) {
                $this->warn("PDF local conversion unavailable, processing PDF directly: {$filename}");
                ProcessScanFile::dispatch($filename);
                $this->info("OCR job dispatched for PDF: {$filename}");
            }
        } else {
            ProcessScanFile::dispatch($filename);
            $this->info("OCR job dispatched for: {$filename}");
        }
    }

    /**
     * Process files di subfolder FTP scanner.
     * Document type diketahui dari nama folder, tidak perlu deteksi.
     */
    protected function processSubfolderFiles(FileConversionService $converter, ScanLogger $logger): void
    {
        $directories = Storage::disk('ftp_scanner')->directories();
        $documentTypes = DocumentType::all();

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
}
