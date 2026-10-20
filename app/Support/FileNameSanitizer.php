<?php

namespace App\Support;

class FileNameSanitizer
{
    /**
     * Ubah nilai menjadi satu segmen nama file/folder yang aman.
     *
     * Pemisah path ("/" dan "\") ikut diganti agar nilai seperti nomor surat
     * "001/PBT/M/03/26" tidak berubah menjadi pohon folder.
     */
    public static function sanitize(?string $value, string $fallback = ''): string
    {
        if ($value === null) {
            return $fallback;
        }

        $value = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $value) ?? '';
        $value = str_replace(['/', '\\'], '_', $value);
        $value = preg_replace('/[:*?"<>|]/', '_', $value) ?? '';
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';
        $value = preg_replace('/\.{2,}/', '.', $value) ?? '';
        $value = trim($value, " .\t\n\r\0\x0B");

        return $value === '' ? $fallback : $value;
    }
}
