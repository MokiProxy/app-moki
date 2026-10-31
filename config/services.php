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

    /*
    |--------------------------------------------------------------------------
    | Microsoft Entra ID (Azure AD)
    |--------------------------------------------------------------------------
    |
    | Driver Socialite `azure` membaca `config('services.microsoft')`. Tanpa
    | blok ini, halaman `/login/microsoft` gagal saat membaca konfigurasi.
    |
    | `default_role` sengaja kosong. Memberi role otomatis tanpa whitelist
    | berarti siapa pun yang lolos autentikasi tenant mendapat akses modul
    | lain (reset password AMS, hapus tiket helpdesk, berkas dokter) hanya
    | karena belum punya role. Isi nama role hanya bila seluruh anggota
    | tenant memang boleh masuk dengan hak akses tersebut, dan role ERKAP
    | tetap tidak boleh diberikan lewat sini.
    |
    */

    'microsoft' => [
        'tenant' => env('MICROSOFT_TENANT_ID'),
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'redirect' => env('MICROSOFT_REDIRECT_URI', env('APP_URL').'/login/microsoft/callback'),

        'auto_provision' => env('MICROSOFT_AUTO_PROVISION', true),
        'default_role' => env('MICROSOFT_DEFAULT_ROLE'),
    ],

    'file_conversion' => [
        'pdf_dpi' => env('FILE_CONVERSION_PDF_DPI', 150),
        'pdf_quality' => env('FILE_CONVERSION_PDF_QUALITY', 90),
        'image_format' => env('FILE_CONVERSION_IMAGE_FORMAT', 'jpg'),
        'max_pages' => env('FILE_CONVERSION_MAX_PAGES', 20),
    ],
    'scanner' => [
        'min_file_age' => env('SCANNER_MIN_FILE_AGE', 15),

        // false = PDF dikirim utuh sebagai satu job (default).
        // true  = perilaku lama: pecah PDF menjadi gambar per halaman.
        //          PDF asli akan dihapus dan diganti gambar halaman.
        'split_pdf_pages' => env('SCANNER_SPLIT_PDF_PAGES', false),
    ],

    'gemini' => [
        'api_key' => env('GOOGLE_GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),

        // Model cadangan (dipisah koma) yang dicoba otomatis saat model
        // utama mengembalikan 503/500/502/504 (mis. high demand).
        'fallback_models' => env('GEMINI_FALLBACK_MODELS', ''),
        'prompt_template' => <<<'PROMPT'
Extract data into a flat JSON object:
- Missing values: "".
- document_date: DD Mon YY.
- uraian: array of strings.
- vendor_name: company name from customer (text between commas) or Account Name; drop "PT." prefix.
- Secondary document numbers: add as top-level snake_case keys using their labels.
- document_number: first number on the head of the document.
- Document Type is the title of the document. If the document type is "Persetujuan Pembayaran", return "invoice_numbers" as an array containing EVERY invoice number listed. Split each number into its own string; never combine them into one string. Omit this field for other document types.
- no_po: get from keterangan. get set of characters on the start of keterangan
Return ONLY valid JSON.
Fields: ["document_type","document_number","document_date","vendor_name","customer","keterangan","uraian","invoice_numbers", no_po]
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
        'max_tokens' => env('GEMINI_MAX_TOKENS', 1024),
        'image_max_width' => env('GEMINI_IMAGE_MAX_WIDTH', 1024),
        'image_quality' => env('GEMINI_IMAGE_QUALITY', 85),
        'request_delay_ms' => env('GEMINI_REQUEST_DELAY_MS', 100),
        'max_retries' => env('GEMINI_MAX_RETRIES', 3),
        'retry_base_delay_ms' => env('GEMINI_RETRY_BASE_DELAY_MS', 1000),
        'batch_size' => env('GEMINI_BATCH_SIZE', 5),
        'batch_delay_ms' => env('GEMINI_BATCH_DELAY_MS', 500),

        // Jumlah halaman PDF yang dikirim ke Gemini untuk OCR.
        // 0 = semua halaman (perilaku lama), 1 = halaman pertama saja.
        'pdf_ocr_pages' => env('GEMINI_PDF_OCR_PAGES', 1),

        // Resolusi media: low | medium | high | unspecified.
        // `low` memakai token paling sedikit (Gemini 3: ±280 token/halaman PDF).
        'media_resolution' => env('GEMINI_MEDIA_RESOLUTION', 'low'),

        // Kontrol thinking untuk Gemini 3/4: minimal | low | medium | high.
        // `minimal` = paling hemat dan cukup untuk ekstraksi field.
        'thinking_level' => env('GEMINI_THINKING_LEVEL', 'minimal'),

        // Kontrol thinking untuk Gemini 2.5 (0 = mati, -1 = dinamis).
        'thinking_budget' => env('GEMINI_THINKING_BUDGET'),

        // Paksa output JSON agar tidak ada markdown/penjelasan tambahan.
        'response_mime_type' => env('GEMINI_RESPONSE_MIME_TYPE', 'application/json'),
    ],

];
