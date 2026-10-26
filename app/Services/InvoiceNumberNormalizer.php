<?php

namespace App\Services;

class InvoiceNumberNormalizer
{
    /**
     * Normalisasi nilai nomor invoice dari OCR/DB menjadi list unik.
     *
     * Menerima array, string dengan pemisah koma/newline/titik koma,
     * integer, maupun nilai null.
     *
     * @return list<string>
     */
    public static function normalize(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        if (is_string($value)) {
            $value = preg_split('/[\r\n,;]+/', $value) ?: [];
        }

        if (! is_array($value)) {
            $value = [$value];
        }

        $numbers = [];

        foreach ($value as $item) {
            if (is_array($item)) {
                $numbers = array_merge($numbers, self::normalize($item));

                continue;
            }

            $item = (string) $item;

            // String di dalam array juga bisa berisi beberapa nomor,
            // misalnya ["2512014, 2604001"].
            if (preg_match('/[\r\n,;]/', $item)) {
                $numbers = array_merge($numbers, self::normalize($item));

                continue;
            }

            $item = trim($item);
            $item = preg_replace('/[\x00-\x1F\x7F]/', ' ', $item) ?? '';
            $item = preg_replace('/\s+/', ' ', $item) ?? '';
            $item = trim($item, " \t\n\r\0\x0B,;");

            if ($item !== '') {
                $numbers[] = strtoupper($item);
            }
        }

        return array_values(array_unique($numbers));
    }

    /**
     * Kunci canonical untuk matching group (nomor invoice).
     */
    public static function normalizeGroupKey(string $value): string
    {
        return self::normalizeText($value);
    }

    /**
     * Kunci canonical vendor untuk group.
     */
    public static function normalizeVendor(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return self::normalizeText($value);
    }

    private static function normalizeText(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/[\x00-\x1F\x7F]/', ' ', $value) ?? '';
        $value = preg_replace('/\s+/', ' ', $value) ?? '';
        $value = trim($value, " \t\n\r\0\x0B,;");

        return strtoupper($value);
    }
}
