<?php

namespace App\Support;

class CoaCode
{
    public const LENGTH = 16;

    /**
     * Pad a legacy numeric code (e.g. '6000') to the right with '0' until it
     * is 16 digits long. Returns null when the input is not 1-16 numeric digits.
     */
    public static function pad(string $code): ?string
    {
        $code = trim($code);

        if (! preg_match('/^\d{1,' . self::LENGTH . '}$/', $code)) {
            return null;
        }

        return str_pad($code, self::LENGTH, '0', STR_PAD_RIGHT);
    }

    public static function valid(string $code): bool
    {
        return preg_match('/^\d{' . self::LENGTH . '}$/', $code) === 1;
    }

    /**
     * Format a 16-digit code as XXXX-XXXX-XXXX-XXXX for display purposes.
     */
    public static function format(string $code): string
    {
        return preg_replace('/(\d{4})(?=\d)/', '$1-', $code);
    }
}