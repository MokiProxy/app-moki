<?php

namespace App\Services\Ocr;

use App\Models\DocumentType;
use App\Services\Ocr\Contracts\OcrEngineInterface;
use App\Services\PromptBuilder;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiEngine implements OcrEngineInterface
{
    protected string $apiKey;

    protected string $model;

    protected string $defaultPromptTemplate;

    protected int $maxTokens;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model', 'gemini-1.5-flash');
        $this->defaultPromptTemplate = config('services.gemini.prompt_template');
        $this->maxTokens = config('services.gemini.max_tokens', 8192);
    }

    public function extractText(UploadedFile $file, ?DocumentType $documentType = null): array
    {
        $startTime = microtime(true);

        $fileContent = file_get_contents($file->getRealPath());

        if ($fileContent === false) {
            throw new Exception('Gagal membaca file: '.$file->getClientOriginalName());
        }

        $prompt = PromptBuilder::buildPrompt($documentType);

        $mimeType = $file->getMimeType();
        $base64Content = base64_encode($fileContent);

        $response = Http::timeout(120)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}",
                [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                                [
                                    'inline_data' => [
                                        'mime_type' => $mimeType,
                                        'data' => $base64Content,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'maxOutputTokens' => $this->maxTokens,
                    ],
                ]
            );

        if ($response->failed()) {
            Log::error('Gemini API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new Exception('Gemini API error: '.$response->body());
        }

        $data = $response->json();

        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if ($text === '') {
            Log::warning('Gemini returned empty text', ['response' => $data]);

            throw new Exception('Gemini API mengembalikan teks kosong');
        }

        $elapsed = (microtime(true) - $startTime) * 1000;

        $allowedFields = PromptBuilder::getAllowedFields($documentType);
        $ocrData = $this->parseJsonResponse($text, $allowedFields);

        Log::info('Gemini OCR completed', [
            'prompt' => $prompt,
            'file' => $file->getClientOriginalName(),
            'document_type' => $documentType?->name,
            'prompt_length' => strlen($prompt),
            'text_length' => strlen($text),
            'has_structured_data' => $ocrData !== null,
            'allowed_fields' => $allowedFields,
            'processing_time_ms' => (int) round($elapsed),
        ]);

        return [
            'success' => true,
            'text' => trim($text),
            'ocr_data' => $ocrData,
            'processing_time_ms' => (int) round($elapsed),
        ];
    }

    protected function parseJsonResponse(string $text, array $allowedFields): ?array
    {
        $jsonString = preg_replace('/```json\s*|\s*```/', '', $text);
        $jsonString = trim($jsonString);

        $data = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('Failed to parse Gemini JSON response', [
                'error' => json_last_error_msg(),
                'text' => $text,
            ]);

            return null;
        }

        $validated = [];
        foreach ($allowedFields as $field) {
            $validated[$field] = $data[$field] ?? '';
        }

        return $validated;
    }

    public function engineName(): string
    {
        return 'gemini';
    }
}
