<?php

if (!function_exists('format_number')) {
    /**
     * Format angka dengan pemisah ribuan (titik) dan desimal (koma).
     * Desimal hanya ditampilkan jika nilainya memiliki bagian pecahan,
     * sehingga angka bulat tidak diakhiri dengan ",00".
     *
     * Contoh:
     * - 2546190.84  -> 2.546.190,84
     * - 323812437.3 -> 323.812.437,3
     * - 2698436978  -> 2.698.436.978
     */
    function format_number($value)
    {
        $value = (float) $value;

        // Jika nilai adalah bilangan bulat, tampilkan tanpa desimal
        if (floor($value) == $value) {
            return number_format($value, 0, ',', '.');
        }

        // Tampilkan 2 angka desimal, lalu buang nol di belakang koma
        $formatted = number_format($value, 2, ',', '.');
        return rtrim(rtrim($formatted, '0'), ',');
    }
}

if (!function_exists('format_rupiah')) {
    /**
     * Format angka sebagai nilai Rupiah (Rp) dengan pemisah ribuan
     * dan desimal yang utuh bila ada.
     */
    function format_rupiah($value)
    {
        return 'Rp ' . format_number($value);
    }
}
