<?php

namespace App\Services;

use Illuminate\Support\Str;

class InvoiceNumberExtractor
{
    protected string $pattern = '/No\s+Inv\s*\n?\s*:\s*(.+)/i';

    public function extract(string $text): ?string
    {
        if (preg_match($this->pattern, $text, $matches)) {
            $raw = trim($matches[1]);

            $cleaned = $this->cleanOcrNoise($raw);

            if ($cleaned !== '') {
                return $cleaned;
            }
        }

        return null;
    }

    public function generateS3Filename(?string $vendorName, ?string $invoiceNumber, string $originalExtension): string
    {
        if ($invoiceNumber === null) {
            return '';
        }

        $safeExtension = match (strtolower($originalExtension)) {
            'png', 'webp' => 'jpg',
            default => $originalExtension,
        };

        return "INV_{$vendorName}_{$invoiceNumber}.{$safeExtension}";
    }

    protected function cleanOcrNoise(string $value): string
    {
        $cleaned = Str::of($value)
            ->replace(['\\', '/'], '')
            ->replace(',', '')
            ->replaceMatches('/\s{2,}/', ' ')
            ->trim();

        return (string) $cleaned;
    }
}
