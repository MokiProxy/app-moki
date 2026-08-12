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
}
