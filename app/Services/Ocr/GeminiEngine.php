<?php

namespace App\Services\Ocr;

use App\Models\DocumentType;
use App\Services\Ocr\Contracts\OcrEngineInterface;
use App\Services\PromptBuilder;
use Exception;
use Imagick;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiEngine implements OcrEngineInterface
{
    protected string $apiKey;

    protected string $model;

    protected string $defaultPromptTemplate;

    protected int $maxTokens;

    protected int $imageMaxWidth;

    protected int $imageQuality;

    protected int $requestDelayMs;

    protected int $maxRetries;

    protected int $retryBaseDelayMs;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model', 'gemini-1.5-flash');
        $this->defaultPromptTemplate = config('services.gemini.prompt_template');
        $this->maxTokens = config('services.gemini.max_tokens', 8192);
        $this->imageMaxWidth = config('services.gemini.image_max_width', 1024);
        $this->imageQuality = config('services.gemini.image_quality', 85);
        $this->requestDelayMs = config('services.gemini.request_delay_ms', 100);
        $this->maxRetries = config('services.gemini.max_retries', 3);
        $this->retryBaseDelayMs = config('services.gemini.retry_base_delay_ms', 1000);
    }

    public function extractText(UploadedFile $file, ?DocumentType $documentType = null): array
    {
        $startTime = microtime(true);

        // Kompresi gambar sebelum dikirim ke API
        $compressedPath = $this->compressImage($file->getRealPath());

        $fileContent = file_get_contents($compressedPath);

        if ($fileContent === false) {
            throw new Exception('Gagal membaca file: '.$file->getClientOriginalName());
        }

        // Cleanup compressed file jika berbeda dari original
        if ($compressedPath !== $file->getRealPath() && file_exists($compressedPath)) {
            @unlink($compressedPath);
        }

        $prompt = PromptBuilder::buildPrompt();

        $mimeType = $compressedPath !== $file->getRealPath() ? 'image/jpeg' : $file->getMimeType();
        $base64Content = base64_encode($fileContent);

        $response = $this->callGeminiApi($prompt, $mimeType, $base64Content);

        $data = $response;

        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if ($text === '') {
            Log::warning('Gemini returned empty text', ['response' => $data]);

            throw new Exception('Gemini API mengembalikan teks kosong');
        }

        $elapsed = (microtime(true) - $startTime) * 1000;

        $allowedFields = PromptBuilder::getAllowedFields();
        $ocrData = $this->parseJsonResponse($text, $allowedFields);

        // Log token usage untuk monitoring
        $this->logTokenUsage($data);

        Log::info('Gemini OCR completed', [
            'prompt' => $prompt,
            'file' => $file->getClientOriginalName(),
            'document_type' => $documentType?->name,
            'prompt_length' => strlen($prompt),
            'text_length' => strlen($text),
            'has_structured_data' => $ocrData !== null,
            'allowed_fields' => $allowedFields,
            'processing_time_ms' => (int) round($elapsed),
            'image_compressed' => $compressedPath !== $file->getRealPath(),
        ]);

        return [
            'success' => true,
            'text' => trim($text),
            'ocr_data' => $ocrData,
            'processing_time_ms' => (int) round($elapsed),
        ];
    }

    protected function compressImage(string $filePath): string
    {
        $imageInfo = @getimagesize($filePath);
        if ($imageInfo === false) {
            return $filePath; // Bukan gambar, return as-is
        }

        [$width, $height] = $imageInfo;
        if ($width <= $this->imageMaxWidth) {
            return $filePath; // Sudah kecil, tidak perlu kompres
        }

        try {
            $ratio = $this->imageMaxWidth / $width;
            $newWidth = $this->imageMaxWidth;
            $newHeight = (int) ($height * $ratio);

            $imagick = new Imagick();
            $imagick->readImage($filePath);
            $imagick->thumbnailImage($newWidth, $newHeight);
            $imagick->setImageFormat('jpeg');
            $imagick->setImageCompressionQuality($this->imageQuality);

            $compressedPath = $filePath . '.compressed.jpg';
            $imagick->writeImage($compressedPath);
            $imagick->clear();
            $imagick->destroy();

            Log::debug('Image compressed for Gemini API', [
                'original' => $filePath,
                'original_size' => $width . 'x' . $height,
                'compressed_size' => $newWidth . 'x' . $newHeight,
                'quality' => $this->imageQuality,
            ]);

            return $compressedPath;
        } catch (Exception $e) {
            Log::warning('Image compression failed, using original', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);

            return $filePath;
        }
    }

    protected function callGeminiApi(string $prompt, string $mimeType, string $base64Content, int $attempt = 1): array
    {
        // Rate limiting - delay sebelum request
        if ($this->requestDelayMs > 0) {
            usleep($this->requestDelayMs * 1000);
        }

        try {
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

            // Handle rate limiting (429)
            if ($response->status() === 429 && $attempt < $this->maxRetries) {
                $delay = $this->retryBaseDelayMs * pow(2, $attempt - 1);
                Log::warning("Rate limited by Gemini API, retrying in {$delay}ms", [
                    'attempt' => $attempt,
                    'max_retries' => $this->maxRetries,
                ]);
                usleep($delay * 1000);

                return $this->callGeminiApi($prompt, $mimeType, $base64Content, $attempt + 1);
            }

            if ($response->failed()) {
                Log::error('Gemini API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new Exception('Gemini API error: '.$response->body());
            }

            return $response->json();
        } catch (Exception $e) {
            // Retry on connection errors
            if ($attempt < $this->maxRetries && !str_contains($e->getMessage(), 'Gemini API error')) {
                $delay = $this->retryBaseDelayMs * pow(2, $attempt - 1);
                Log::warning("Gemini API connection error, retrying in {$delay}ms", [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);
                usleep($delay * 1000);

                return $this->callGeminiApi($prompt, $mimeType, $base64Content, $attempt + 1);
            }

            throw $e;
        }
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

        // Validasi field wajib — log warning jika kosong
        foreach ($allowedFields as $field) {
            if (! array_key_exists($field, $data) || $data[$field] === '') {
                Log::warning("Missing or empty field from Gemini: {$field}", [
                    'field' => $field,
                ]);
            }
        }

        // Keep semua field dari Gemini (termasuk field tambahan seperti no_inv, no_voucher)
        return $data;
    }

    protected function logTokenUsage(array $response): void
    {
        $usageMetadata = $response['usageMetadata'] ?? null;
        if ($usageMetadata) {
            Log::info('Gemini token usage', [
                'prompt_tokens' => $usageMetadata['promptTokenCount'] ?? 0,
                'completion_tokens' => $usageMetadata['candidatesTokenCount'] ?? 0,
                'total_tokens' => $usageMetadata['totalTokenCount'] ?? 0,
            ]);
        }
    }

    public function engineName(): string
    {
        return 'gemini';
    }
}
