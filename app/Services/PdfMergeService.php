<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Fpdi;

class PdfMergeService
{
    protected Fpdi $fpdi;

    public function __construct()
    {
        $this->fpdi = new Fpdi;
    }

    /**
     * Merge multiple PDF files into one PDF with multiple pages.
     *
     * @param  array  $pdfPaths  Array of file paths to merge (in order)
     * @param  string  $outputPath  Path untuk output merged file
     * @return string Path ke merged file
     *
     * @throws Exception
     */
    public function mergePdfs(array $pdfPaths, string $outputPath): string
    {
        if (empty($pdfPaths)) {
            throw new Exception('No PDF files provided for merging');
        }

        $this->fpdi = new Fpdi;

        foreach ($pdfPaths as $pdfPath) {
            if (! file_exists($pdfPath)) {
                throw new Exception("PDF file not found: {$pdfPath}");
            }

            if (! $this->isValidPdf($pdfPath)) {
                Log::warning('Invalid PDF file skipped during merge', ['file' => $pdfPath]);

                continue;
            }

            $this->addPagesFromPdf($pdfPath);
        }

        $this->fpdi->Output($outputPath, 'F');

        return $outputPath;
    }

    /**
     * Add all pages from an existing PDF file to the current PDF.
     *
     * @param  string  $pdfPath  Path ke PDF yang akan di-import
     *
     * @throws Exception
     */
    public function addPagesFromPdf(string $pdfPath): void
    {
        $pageCount = $this->fpdi->setSourceFile($pdfPath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $this->fpdi->ImportPage($pageNo);
            $size = $this->fpdi->getTemplateSize($templateId);

            $this->fpdi->AddPage();

            $this->fpdi->UseTemplate($templateId, 0, 0, $size['width'], $size['height']);
        }
    }

    /**
     * Get the number of pages in a PDF file.
     *
     * @param  string  $pdfPath  Path ke PDF file
     * @return int Jumlah halaman
     *
     * @throws Exception
     */
    public function getPageCount(string $pdfPath): int
    {
        if (! file_exists($pdfPath)) {
            throw new Exception("PDF file not found: {$pdfPath}");
        }

        $tmpFpdi = new Fpdi;

        return $tmpFpdi->setSourceFile($pdfPath);
    }

    /**
     * Validate if a file is a valid PDF.
     *
     * @param  string  $pdfPath  Path atau content PDF
     */
    public function isValidPdf(string $pdfPath): bool
    {
        try {
            if (! file_exists($pdfPath)) {
                return false;
            }

            $handle = fopen($pdfPath, 'r');

            if ($handle === false) {
                return false;
            }

            $header = fread($handle, 5);
            fclose($handle);

            return $header === '%PDF-';
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Merge two PDF files and return the merged content as string.
     *
     * @param  string  $existingPdfContent  Content PDF existing (binary)
     * @param  string  $newPdfContent  Content PDF baru (binary)
     * @return string Merged PDF content (binary)
     *
     * @throws Exception
     */
    public function mergePdfContents(string $existingPdfContent, string $newPdfContent): string
    {
        $tempExisting = storage_path('app/private/scanner/temp/merge_existing_'.uniqid().'.pdf');
        $tempNew = storage_path('app/private/scanner/temp/merge_new_'.uniqid().'.pdf');
        $tempOutput = storage_path('app/private/scanner/temp/merge_output_'.uniqid().'.pdf');

        try {
            file_put_contents($tempExisting, $existingPdfContent);
            file_put_contents($tempNew, $newPdfContent);

            $this->mergePdfs([$tempExisting, $tempNew], $tempOutput);

            $mergedContent = file_get_contents($tempOutput);

            if ($mergedContent === false) {
                throw new Exception('Failed to read merged PDF output');
            }

            return $mergedContent;
        } finally {
            @unlink($tempExisting);
            @unlink($tempNew);
            @unlink($tempOutput);
        }
    }
}
