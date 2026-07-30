<?php

namespace App\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class OcrService
{
    protected string $apiKey;

    protected string $endpoint;

    protected int $engine;

    public function __construct()
    {
        $this->endpoint = config('services.ocr.api_endpoint');
        $this->apiKey = config('services.ocr.api_key');
        $this->engine = config('services.ocr.engine');
    }

    public function extractText(UploadedFile $file): array
    {
        $response = Http::timeout(60)
            ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
            ->post($this->endpoint, [
                'apikey' => $this->apiKey,
                'OCREngine' => $this->engine,
            ]);

        if ($response->failed()) {
            throw new Exception('OCR service error: '.$response->body());
        }

        $data = $response->json();

        if ($data['IsErroredOnProcessing'] ?? false) {
            $errorMsg = is_array($data['ErrorMessage'] ?? null)
                ? implode(', ', $data['ErrorMessage'])
                : ($data['ErrorMessage'] ?? 'Unknown error');
            throw new Exception('OCR processing error: '.$errorMsg);
        }

        $parsed = $data['ParsedResults'][0] ?? null;

        if (! $parsed || ($parsed['FileParseExitCode'] ?? 0) !== 1) {
            $parseError = is_array($parsed['ErrorMessage'] ?? null)
                ? implode(', ', $parsed['ErrorMessage'])
                : ($parsed['ErrorMessage'] ?? 'No results');
            throw new Exception('OCR parse failed: '.$parseError);
        }

        return [
            'success' => true,
            'text' => $parsed['ParsedText'] ?? '',
            'exit_code' => $data['OCRExitCode'] ?? null,
            'processing_time_ms' => $data['ProcessingTimeInMilliseconds'] ?? null,
        ];
    }
}
