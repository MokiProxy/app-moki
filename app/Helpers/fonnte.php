<?php

use App\Models\Whatsapp;

if (!function_exists('fonnte_api')) {
    function fonnte_api()
    {
        // Sesuaikan dengan gambar database Anda: key = 'fonnte_api'
        return Whatsapp::where('key', 'fonnte_api')->value('value');
    }
}