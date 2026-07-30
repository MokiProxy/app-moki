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
        $pattern = $docType->number_regex
            ?? '/No\s+Inv\s*\n?\s*:\s*(.+)/i';

        if (preg_match($pattern, $ocrText, $matches)) {
            $raw = trim($matches[1]);
            $cleaned = $this->cleanOcrNoise($raw);

            if ($cleaned !== '') {
                return $cleaned;
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

    public function generateS3Filename(DocumentType $docType, ?string $vendorName, ?string $number, string $originalExtension): string
    {
        return $docType->resolveS3Filename($vendorName, $number, $originalExtension);
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
            ->replace(['\\', '/'], '')
            ->replace(',', '')
            ->replaceMatches('/\s{2,}/', ' ')
            ->trim()
            ->__toString();
    }
}
