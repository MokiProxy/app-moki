<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'file_conversion' => [
        'pdf_dpi' => env('FILE_CONVERSION_PDF_DPI', 150),
        'pdf_quality' => env('FILE_CONVERSION_PDF_QUALITY', 90),
        'image_format' => env('FILE_CONVERSION_IMAGE_FORMAT', 'jpg'),
        'max_pages' => env('FILE_CONVERSION_MAX_PAGES', 20),
    ],
    'gemini' => [
        'api_key' => env('GOOGLE_GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
        'prompt_template' => <<<'PROMPT'
Analisis dokumen ini dan ekstrak informasi berikut dalam format JSON:
Aturan:
1. Kembalikan HANYA JSON tanpa penjelasan tambahan
2. Jika field tidak ditemukan, gunakan string kosong ""
3. Pastikan format tanggal konsisten (DD Mon YY)
4. Uraian harus berupa array meskipun hanya ada 1 item
5. Field vendor name diambil dari nama dari perusahaan di customer diambil di antara 2 tanda koma. contoh: 0023/,MADHANI TALATAH NUSANTARA, PT. ambil yang MADHANI TALATAH NUSANTARA sebagai vendor name
6. field document_number diambil dari nomor dokumen paling atas
Ekstrak data nya dengan format berikut:
PROMPT,
        'default_fields' => [
            'document_type',
            'document_number',
            'document_date',
            'vendor_name',
            'customer',
            'keterangan',
            'uraian',
        ],
        'max_tokens' => env('GEMINI_MAX_TOKENS', 8192),
    ],

];
