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

    protected function cleanOcrNoise(string $value): string
    {
        $cleaned = Str::of($value)
            ->replaceMatches('/[\x00-\x1F\x7F]/', ' ')
            ->replace(['\\', '/', ':', '*', '?', '"', '<', '>', '|', ','], '')
            ->replaceMatches('/\s{2,}/', ' ')
            ->trim();

        return (string) $cleaned;
    }
}
