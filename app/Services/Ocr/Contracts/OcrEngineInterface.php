<?php

namespace App\Services\Ocr\Contracts;

use App\Models\DocumentType;
use Illuminate\Http\UploadedFile;

interface OcrEngineInterface
{
    /**
     * Ekstraksi teks dari file.
     *
     * @param  UploadedFile  $file
     * @param  DocumentType|null  $documentType  Document type untuk custom prompt
     * @return array{success: bool, text: string, ocr_data: ?array, processing_time_ms: ?int}
     */
    public function extractText(UploadedFile $file, ?DocumentType $documentType = null): array;

    /**
     * Nama engine untuk logging.
     */
    public function engineName(): string;
}
