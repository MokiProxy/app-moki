<?php

namespace App\Enums;

/**
 * Satu-satunya sumber nilai rating sasaran (sesuai Pedoman RKAP Bagian 2 & 4).
 *
 * Nilai: AAA, AA, A, BBB, BB
 */
enum ErkapRatingLevel: string
{
    case AAA = 'AAA';
    case AA = 'AA';
    case A = 'A';
    case BBB = 'BBB';
    case BB = 'BB';

    public function qualification(): string
    {
        return match ($this) {
            self::AAA => 'Sangat Kritis',
            self::AA => 'Kritis',
            self::A => 'Cukup Kritis',
            self::BBB => 'Penting',
            self::BB => 'Cukup Penting',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::AAA => 'Sangat berpengaruh terhadap keberlangsungan hidup atau pertumbuhan perusahaan',
            self::AA => 'Dapat berpengaruh terhadap keberlangsungan hidup atau pertumbuhan perusahaan secara berkelanjutan',
            self::A => 'Sangat mendukung operasi perusahaan secara keseluruhan',
            self::BBB => 'Dapat mendukung operasi perusahaan secara keseluruhan',
            self::BB => 'Dapat mendukung sebagian besar operasi perusahaan',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::AAA => 'Sangat Penting (AAA)',
            self::AA => 'Sangat Penting (AA)',
            self::A => 'Sangat Penting (A)',
            self::BBB => 'Penting (BBB)',
            self::BB => 'Cukup Penting (BB)',
        };
    }

    /**
     * Urutan prioritas sasaran (1 = tertinggi).
     */
    public function order(): int
    {
        return match ($this) {
            self::AAA => 1,
            self::AA => 2,
            self::A => 3,
            self::BBB => 4,
            self::BB => 5,
        };
    }

    /**
     * Seluruh nilai rating yang diakui sistem.
     */
    public static function values(): array
    {
        return array_map(fn (self $level) => $level->value, self::cases());
    }

    /**
     * Rating yang memperbolehkan pembuatan Program Kerja (>= A).
     */
    public static function allowedForWorkProgram(): array
    {
        return [self::AAA->value, self::AA->value, self::A->value];
    }
}