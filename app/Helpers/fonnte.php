<?php

use App\Models\Whatsapp;

if (!function_exists('fonnte_api')) {
    function fonnte_api()
    {
        return Whatsapp::where('key', 'fonnte_api_key')->value('value');
    }
}