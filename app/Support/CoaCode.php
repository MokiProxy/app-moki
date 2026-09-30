<?php

namespace App\Support;

/**
 * Composer kode Chart of Account & Pusat Biaya (Pusat Biaya).
 *
 * Struktur a..e sesuai Pedoman RKAP:
 *
 *   X(a) - XX(b) - XXXXX(c) - XXX(d) - XXXX(e)
 *   a = Bisnis Unit     (1)  contoh: F = Penambangan, G = Rental
 *   b = Lokasi          (2)  contoh: 01 Umum, 02 Banko, 03 TAL
 *   c = Manajemen Area  (5)  contoh: 20200 Senior Manajer Keuangan
 *   d = Aktivitas       (3)  contoh: 110 Penambangan Swakelola
 *   e = Elemen Biaya    (4)  contoh: 6000 Pendapatan Jasa Kupas Tanah
 *
 * Pusat Biaya (cost center) = segmen a..d (11 karakter).
 * Chart of Account          = Pusat Biaya + segmen e (15 karakter).
 */
class CoaCode
{
    public const COST_CENTER_LENGTH = 11;

    public const LENGTH = 15;

    /** Panjang tiap segmen, urut dari a sampai e. */
    public const SEGMENT_LENGTHS = [
        'business_unit' => 1,
        'location' => 2,
        'management_area' => 5,
        'activity' => 3,
        'cost_element' => 4,
    ];

    public const COST_CENTER_SEGMENTS = ['business_unit', 'location', 'management_area', 'activity'];

    public const COA_SEGMENTS = ['business_unit', 'location', 'management_area', 'activity', 'cost_element'];

    public const PATTERN_COST_CENTER = '/^[A-Za-z0-9]\d{10}$/';

    public const PATTERN_COA = '/^[A-Za-z0-9]\d{14}$/';

    /**
     * Susun kode dari segmen a..d (Pusat Biaya).
     *
     * @param  array<string, string|null>  $segments
     */
    public static function composeCostCenter(array $segments): ?string
    {
        return self::compose($segments, self::COST_CENTER_SEGMENTS);
    }

    /**
     * Susun kode dari segmen a..e (Chart of Account).
     *
     * @param  array<string, string|null>  $segments
     */
    public static function compose(array $segments, array $keys = self::COA_SEGMENTS): ?string
    {
        $code = '';

        foreach ($keys as $key) {
            $part = self::normalize($segments[$key] ?? null, self::SEGMENT_LENGTHS[$key]);

            if ($part === null) {
                return null;
            }

            $code .= $part;
        }

        return $code;
    }

    public static function valid(string $code): bool
    {
        return preg_match(self::PATTERN_COA, trim($code)) === 1;
    }

    public static function validCostCenter(string $code): bool
    {
        return preg_match(self::PATTERN_COST_CENTER, trim($code)) === 1;
    }

    /**
     * Pisahkan kode ke segmen a..e (atau a..d bila kode Pusat Biaya).
     *
     * @return array<string, string>
     */
    public static function parse(string $code): array
    {
        $code = trim($code);
        $keys = strlen($code) === self::LENGTH ? self::COA_SEGMENTS : self::COST_CENTER_SEGMENTS;

        $segments = [];
        $offset = 0;

        foreach ($keys as $key) {
            $length = self::SEGMENT_LENGTHS[$key];
            $segments[$key] = substr($code, $offset, $length);
            $offset += $length;
        }

        return $segments;
    }

    /**
     * Format kode untuk tampilan: F-01-20200-110-6000.
     */
    public static function format(string $code): string
    {
        $code = trim($code);
        $offset = 0;
        $parts = [];

        foreach (self::SEGMENT_LENGTHS as $length) {
            $part = substr($code, $offset, $length);

            if ($part === false || $part === '') {
                break;
            }

            $parts[] = $part;
            $offset += $length;
        }

        return implode('-', $parts);
    }

    /**
     * 4 digit terakhir kode COA = segmen e (elemen biaya).
     */
    public static function elementCode(string $code): ?string
    {
        $code = trim($code);

        return strlen($code) === self::LENGTH ? substr($code, -self::SEGMENT_LENGTHS['cost_element']) : null;
    }

    /**
     * Kode Pusat Biaya yang menjadi prefix kode COA ini.
     */
    public static function costCenterCode(string $code): ?string
    {
        $code = trim($code);

        return strlen($code) === self::LENGTH ? substr($code, 0, self::COST_CENTER_LENGTH) : null;
    }

    private static function normalize(?string $value, int $length): ?string
    {
        $value = $value === null ? '' : trim($value);

        return strlen($value) === $length ? $value : null;
    }
}
