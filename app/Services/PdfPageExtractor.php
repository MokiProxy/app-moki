<?php

namespace App\Services;

use RuntimeException;
use setasign\Fpdi\Fpdi;
use Throwable;

/**
 * Memotong PDF menjadi beberapa halaman pertama untuk input OCR.
 *
 * File asli tidak diubah. Pemanggil bertanggung jawab menghapus file
 * sementara yang dikembalikan oleh service ini.
 */
class PdfPageExtractor
{
    /**
     * Ekstrak sejumlah halaman pertama dari sebuah PDF.
     *
     * `path` bernilai null jika PDF tidak perlu dipotong.
     *
     * @param  int  $limit  Jumlah halaman yang diambil. 0 = semua halaman.
     * @return array{path: string|null, source_pages: int, ocr_pages: int}
     *
     * @throws Throwable
     */
    public function extract(string $pdfPath, int $limit): array
    {
        if (! is_file($pdfPath)) {
            throw new RuntimeException("PDF file not found: {$pdfPath}");
        }

        $fpdi = new Fpdi;
        $sourcePages = $fpdi->setSourceFile($pdfPath);

        if ($sourcePages < 1) {
            throw new RuntimeException("PDF has no pages: {$pdfPath}");
        }

        $limit = max(0, $limit);
        $ocrPages = $limit === 0 ? $sourcePages : min($limit, $sourcePages);

        // Tidak perlu membuat file baru jika seluruh halaman tetap dikirim.
        if ($ocrPages >= $sourcePages) {
            return [
                'path' => null,
                'source_pages' => $sourcePages,
                'ocr_pages' => $sourcePages,
            ];
        }

        for ($page = 1; $page <= $ocrPages; $page++) {
            $templateId = $fpdi->importPage($page);
            $size = $fpdi->getTemplateSize($templateId);

            $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $fpdi->useTemplate($templateId, 0, 0, $size['width'], $size['height']);
        }

        $tempPath = $this->createTemporaryPath();

        try {
            $fpdi->Output('F', $tempPath);
        } catch (Throwable $e) {
            @unlink($tempPath);

            throw $e;
        }

        return [
            'path' => $tempPath,
            'source_pages' => $sourcePages,
            'ocr_pages' => $ocrPages,
        ];
    }

    /**
     * Ambil hanya path file hasil potongan.
     *
     * @return string|null Null jika PDF tidak perlu dipotong.
     *
     * @throws Throwable
     */
    public function extractFirstPages(string $pdfPath, int $limit): ?string
    {
        return $this->extract($pdfPath, $limit)['path'];
    }

    /**
     * Buat path file sementara dengan ekstensi .pdf.
     */
    protected function createTemporaryPath(): string
    {
        $base = tempnam(sys_get_temp_dir(), 'ocr_pdf_');

        if ($base === false) {
            throw new RuntimeException('Gagal membuat file sementara untuk OCR PDF');
        }

        // tempnam() membuat file kosong tanpa ekstensi. Hapus agar FPDI
        // bisa menulis file baru dengan ekstensi .pdf.
        @unlink($base);

        return $base.'.pdf';
    }
}
