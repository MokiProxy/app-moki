<?php

namespace App\Services;

use App\Models\DocumentType;
use Illuminate\Support\Str;

class DocumentTypeProcessor
{
    protected OcrSearchService $search;

    public function __construct(OcrSearchService $search)
    {
        $this->search = $search;
    }

    public function extractDocumentNumber(DocumentType $docType, string $ocrText): ?string
    {
        // Algoritma 1: number_regex (default, sesuai konfigurasi jenis dokumen).
        $pattern = $docType->number_regex
            ?? '/No\s+Inv\s*\n?\s*:\s*(.+)/i';

        if (preg_match($pattern, $ocrText, $matches)) {
            if (isset($matches[1])) {
                $raw = trim($matches[1]);
                $cleaned = $this->cleanOcrNoise($raw);

                if ($cleaned !== '') {
                    return $cleaned;
                }
            }
        }

        // Algoritma 2 (fallback): ambil nilai dari baris setelah "TAX CODR".
        return $this->extractNumberFromTaxCode($ocrText);
    }

    protected function extractNumberFromTaxCode(string $ocrText): ?string
    {
        if (preg_match('/TAX\s*CODR\s*\R\s*:?\s*([^\r\n]+)/i', $ocrText, $matches)) {
            $cleaned = $this->cleanOcrNoise($matches[1]);

            if ($cleaned !== '' && strtolower($cleaned) !== 'null') {
                return $cleaned;
            }
        }

        return null;
    }

    public function extractTanggal(DocumentType $docType, string $ocrText): ?string
    {
        if (! ($docType->tanggal_enabled ?? true)) {
            return null;
        }

        // Algoritma 1: tanggal_regex (default, sesuai konfigurasi jenis dokumen).
        $pattern = $docType->tanggal_regex
            ?? '/Tgl\s*\n?\s*:\s*(.+)/i';

        if (preg_match($pattern, $ocrText, $matches)) {
            if (isset($matches[1])) {
                $raw = trim($matches[1]);
                $cleaned = $this->cleanTanggal($raw);

                if ($cleaned !== '') {
                    return $cleaned;
                }
            }
        }

        // Algoritma 2 (fallback): ambil nilai pada baris ke-3 setelah "TAX CODR".
        return $this->extractTanggalFromTaxCode($ocrText);
    }

    protected function extractTanggalFromTaxCode(string $ocrText): ?string
    {
        $pattern = '/TAX\s*CODR\s*\R\s*:?\s*[^\r\n]+\s*\R\s*:?\s*[^\r\n]+\s*\R\s*:?\s*([^\r\n]+)/i';

        if (preg_match($pattern, $ocrText, $matches)) {
            $cleaned = $this->cleanTanggal($matches[1]);

            if ($cleaned !== '' && strtolower($cleaned) !== 'null') {
                return $cleaned;
            }
        }

        return null;
    }

    public function extractKeterangan(DocumentType $docType, string $ocrText, ?string $vendorName = null): ?string
    {
        if (! ($docType->keterangan_enabled ?? true)) {
            return null;
        }

        // Algoritma 1: keterangan_regex (default, sesuai konfigurasi jenis dokumen).
        $pattern = $docType->keterangan_regex
            ?? '/Keterangan\s*:\s*(.+)/i';

        if (preg_match($pattern, $ocrText, $matches)) {
            if (isset($matches[1])) {
                $raw = trim($matches[1]);
                $cleaned = $this->cleanKeterangan($raw);

                if ($cleaned !== '') {
                    return $cleaned;
                }
            }
        }

        // Algoritma 2 (fallback): ambil baris tepat setelah posisi vendor di OCR.
        return $this->extractKeteranganFromVendor($ocrText, $vendorName);
    }

    protected function extractKeteranganFromVendor(string $ocrText, ?string $vendorName): ?string
    {
        if ($vendorName === null || $vendorName === '') {
            return null;
        }

        $pos = stripos($ocrText, $vendorName);

        if ($pos === false) {
            return null;
        }

        $segment = substr($ocrText, $pos);

        $firstNl = strpos($segment, "\n");

        if ($firstNl === false) {
            return null;
        }

        $secondNl = strpos($segment, "\n", $firstNl + 1);

        if ($secondNl === false) {
            return null;
        }

        $line = substr($segment, $firstNl + 1, $secondNl - $firstNl - 1);
        $line = preg_replace('/^\s*:?\s*/', '', $line);
        $cleaned = $this->cleanKeterangan($line);

        return $cleaned !== '' ? $cleaned : null;
    }

    public function extractUraian(DocumentType $docType, string $ocrText): ?string
    {
        if (! ($docType->uraian_enabled ?? true)) {
            return null;
        }

        $pattern = $docType->uraian_regex
            ?? '/URAIAN\s*\n(.+?)\n\s*TOTAL/si';

        if (preg_match($pattern, $ocrText, $matches)) {
            if (isset($matches[1])) {
                $cleaned = $this->cleanUraian($matches[1]);

                if ($cleaned !== '') {
                    return $cleaned;
                }
            }
        }

        return null;
    }

    public function matchVendor(DocumentType $docType, string $ocrText): ?string
    {
        if (! ($docType->vendor_search_enabled ?? true)) {
            return null;
        }

        $vendorNames = $docType->vendors()->pluck('name')->toArray();

        if (empty($vendorNames)) {
            return null;
        }

        $match = $this->search->searchData(
            [['text' => $ocrText]],
            $vendorNames
        )->first();

        if ($match) {
            $keyword = strtoupper($match['matches']->first()['keyword'] ?? '');

            return $keyword !== '' ? $keyword : null;
        }

        return null;
    }

    public function generateFilename(DocumentType $docType, ?string $vendorName, ?string $number, string $originalExtension): string
    {
        return $docType->resolveFilename($vendorName, $number, $originalExtension);
    }

    public function resolveFtpPath(DocumentType $docType, ?string $vendorName, ?string $number, ?string $filename): string
    {
        $folder = $docType->resolveFtpFolder($vendorName, $number, $filename);

        return "{$folder}/{$filename}";
    }

    public function resolveFailedPath(DocumentType $docType, string $uploadFilename): string
    {
        $failedFolder = $docType->ftp_failed_folder ?? 'FAILED';

        return "{$failedFolder}/{$uploadFilename}";
    }

    protected function cleanOcrNoise(string $value): string
    {
        return Str::of($value)
            ->replaceMatches('/[\x00-\x1F\x7F]/', ' ')
            ->replace(['\\', '/', ':', '*', '?', '"', '<', '>', '|', ','], '')
            ->replaceMatches('/\s{2,}/', ' ')
            ->trim()
            ->__toString();
    }

    protected function cleanKeterangan(string $value): string
    {
        return Str::of($value)
            ->replaceMatches('/\s{2,}/', ' ')
            ->trim()
            ->__toString();
    }

    protected function cleanTanggal(string $value): string
    {
        return Str::of($value)
            ->replace('|', '')
            ->replaceMatches('/\s{2,}/', ' ')
            ->trim()
            ->__toString();
    }

    protected function cleanUraian(string $value): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $value);

        $lines = array_map('trim', $lines);
        $lines = array_filter($lines, fn ($line) => $line !== '');

        return implode(' | ', $lines);
    }
}
