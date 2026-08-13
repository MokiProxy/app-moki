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
Extract data into a flat JSON object with these rules:
1. Missing fields: use "".
2. document_date format: DD Mon YY.
3. uraian: format as string array.
4. vendor_name: extract text between commas in customer (e.g., "0023/, PT NAME, PT" -> "PT NAME").
5. Extra numbers: if there are secondary document numbers, add them as top-level JSON keys using their identifier labels (no nesting).
6. Main document number: use the first number found in the text as the main document_number.
7. Make all json key using snake case.
8. Return ONLY valid JSON.
Fields: ["document_type","document_number","document_date","vendor_name","customer","keterangan","uraian"]
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
        'image_max_width' => env('GEMINI_IMAGE_MAX_WIDTH', 1024),
        'image_quality' => env('GEMINI_IMAGE_QUALITY', 85),
        'request_delay_ms' => env('GEMINI_REQUEST_DELAY_MS', 100),
        'max_retries' => env('GEMINI_MAX_RETRIES', 3),
        'retry_base_delay_ms' => env('GEMINI_RETRY_BASE_DELAY_MS', 1000),
        'batch_size' => env('GEMINI_BATCH_SIZE', 5),
        'batch_delay_ms' => env('GEMINI_BATCH_DELAY_MS', 500),
    ],

];
