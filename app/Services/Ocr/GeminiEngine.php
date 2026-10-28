<?php

namespace App\Services\Ocr;

use App\Models\DocumentType;
use App\Services\InvoiceNumberNormalizer;
use App\Services\Ocr\Contracts\OcrEngineInterface;
use App\Services\PdfPageExtractor;
use App\Services\PromptBuilder;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Imagick;
use Throwable;

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

    /**
     * Jumlah halaman PDF yang dikirim ke Gemini. 0 = semua halaman.
     */
    protected int $pdfOcrPages;

    /**
     * Resolusi media (low/medium/high). Semakin rendah semakin hemat token.
     */
    protected string $mediaResolution;

    /**
     * Level thinking untuk Gemini 3/4.
     */
    protected string $thinkingLevel;

    /**
     * Budget thinking untuk Gemini 2.5. Null = pakai default model.
     */
    protected ?int $thinkingBudget;

    /**
     * Mime type output. application/json mengurangi teks pembungkus.
     */
    protected string $responseMimeType;

    /**
     * Model cadangan saat model utama 503/high demand.
     *
     * @var list<string>
     */
    protected array $fallbackModels = [];

    /**
     * Model yang benar-benar dipakai pada request terakhir.
     */
    protected ?string $lastUsedModel = null;

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
        $this->pdfOcrPages = max(0, (int) config('services.gemini.pdf_ocr_pages', 1));
        $this->mediaResolution = (string) config('services.gemini.media_resolution', 'low');
        $this->thinkingLevel = (string) config('services.gemini.thinking_level', 'minimal');
        $this->responseMimeType = (string) config('services.gemini.response_mime_type', 'application/json');

        $thinkingBudget = config('services.gemini.thinking_budget');
        $this->thinkingBudget = ($thinkingBudget === null || $thinkingBudget === '')
            ? null
            : (int) $thinkingBudget;

        $fallbacks = config('services.gemini.fallback_models', '');
        $fallbacks = is_array($fallbacks) ? $fallbacks : explode(',', (string) $fallbacks);
        $fallbacks = array_map(fn ($model) => trim((string) $model), $fallbacks);
        $this->fallbackModels = array_values(array_unique(array_filter(
            $fallbacks,
            fn (string $model) => $model !== '' && $model !== $this->model
        )));
    }

    public function extractText(
        UploadedFile $file,
        ?DocumentType $documentType = null,
        ?int $pdfOcrPages = null
    ): array {
        $startTime = microtime(true);
        $requestedOcrPages = $pdfOcrPages ?? $this->pdfOcrPages;

        $originalPath = $file->getRealPath();
        $mimeType = $file->getMimeType() ?: mime_content_type($originalPath) ?: 'application/octet-stream';
        $isPdf = $this->isPdf($originalPath, $mimeType);

        $ocrPath = $originalPath;
        $temporaryPdf = null;
        $sourcePages = $isPdf ? null : 1;
        $ocrPagesUsed = $isPdf ? null : 1;
        $imageCompressed = false;

        try {
            if ($isPdf) {
                $mimeType = 'application/pdf';

                // Hanya input OCR yang dipotong. File asli tetap dipakai
                // untuk arsip/merge oleh ProcessScanFile.
                try {
                    $extraction = app(PdfPageExtractor::class)
                        ->extract($originalPath, $requestedOcrPages);

                    $sourcePages = $extraction['source_pages'];
                    $ocrPagesUsed = $extraction['ocr_pages'];
                    $temporaryPdf = $extraction['path'];

                    if ($temporaryPdf !== null) {
                        $ocrPath = $temporaryPdf;

                        Log::info('Gemini OCR PDF trimmed', [
                            'file' => $file->getClientOriginalName(),
                            'source_pages' => $sourcePages,
                            'ocr_pages' => $ocrPagesUsed,
                            'source_size' => filesize($originalPath),
                            'ocr_size' => filesize($temporaryPdf),
                        ]);
                    }
                } catch (Throwable $e) {
                    Log::warning('Gagal memotong PDF untuk OCR, memakai PDF utuh', [
                        'file' => $file->getClientOriginalName(),
                        'error' => $e->getMessage(),
                    ]);

                    $temporaryPdf = null;
                    $ocrPath = $originalPath;
                    $ocrPagesUsed = $sourcePages = null;
                }

                $fileContent = file_get_contents($ocrPath);
            } else {
                // Kompresi gambar sebelum dikirim ke API
                $compressedPath = $this->compressImage($originalPath);
                $fileContent = file_get_contents($compressedPath);

                $imageCompressed = $compressedPath !== $originalPath;
                $mimeType = $imageCompressed ? 'image/jpeg' : $mimeType;

                // Cleanup compressed file jika berbeda dari original
                if ($imageCompressed && file_exists($compressedPath)) {
                    @unlink($compressedPath);
                }
            }

            if ($fileContent === false) {
                throw new Exception('Gagal membaca file: '.$file->getClientOriginalName());
            }

            $prompt = PromptBuilder::buildPrompt();
            $base64Content = base64_encode($fileContent);

            $response = $this->callGeminiApi($prompt, $mimeType, $base64Content);

            $data = $response;

            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            if ($text === '') {
                Log::warning('Gemini returned empty text', ['response' => $data]);

                throw new Exception('Gemini API mengembalikan teks kosong');
            }

            $allowedFields = PromptBuilder::getAllowedFields();
            $ocrData = $this->parseJsonResponse($text, $allowedFields);
            $ocrData = $this->normalizeInvoiceNumbers($ocrData);

            // Approval multi-halaman: halaman 1 dipakai untuk mendeteksi tipe
            // dokumen. Jika terbukti approval, baca seluruh PDF sekali lagi
            // agar invoice_numbers di halaman 2..N tidak hilang.
            if ($this->shouldRunApprovalFullOcr($isPdf, $ocrData, $sourcePages, $requestedOcrPages)) {
                try {
                    $fullResult = $this->extractText($file, $documentType, 0);
                    $fullData = $fullResult['ocr_data'] ?? null;

                    if (is_array($fullData)) {
                        $ocrData['invoice_numbers'] = InvoiceNumberNormalizer::normalize(array_merge(
                            $ocrData['invoice_numbers'] ?? [],
                            $fullData['invoice_numbers'] ?? []
                        ));
                        $ocrPagesUsed = $fullResult['ocr_pages_used'] ?? $sourcePages;

                        Log::info('Gemini OCR approval full-page pass completed', [
                            'file' => $file->getClientOriginalName(),
                            'source_pages' => $sourcePages,
                            'ocr_pages_used' => $ocrPagesUsed,
                            'invoice_numbers' => $ocrData['invoice_numbers'],
                        ]);
                    }
                } catch (Throwable $e) {
                    Log::warning('Gemini OCR approval full-page pass failed, using first-page result', [
                        'file' => $file->getClientOriginalName(),
                        'source_pages' => $sourcePages,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if (
                $ocrData !== null
                && $pdfOcrPages !== 0
                && str_contains(strtoupper((string) ($ocrData['document_type'] ?? '')), 'PERSETUJUAN')
                && ($ocrData['invoice_numbers'] ?? []) === []
            ) {
                Log::warning('Approval document has no invoice_numbers', [
                    'document_type' => $ocrData['document_type'] ?? null,
                    'document_number' => $ocrData['document_number'] ?? null,
                ]);
            }

            $elapsed = (microtime(true) - $startTime) * 1000;

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
                'image_compressed' => $imageCompressed,
                'is_pdf' => $isPdf,
                'source_pages' => $sourcePages,
                'ocr_pages_used' => $ocrPagesUsed,
                'media_resolution' => $this->mediaResolution,
                'thinking_level' => $this->thinkingLevel,
                'model' => $this->lastUsedModel ?? $this->model,
            ]);

            return [
                'success' => true,
                'text' => trim($text),
                'ocr_data' => $ocrData,
                'processing_time_ms' => (int) round($elapsed),
                'source_pages' => $sourcePages,
                'ocr_pages_used' => $ocrPagesUsed,
                'model' => $this->lastUsedModel ?? $this->model,
            ];
        } finally {
            // File temp hanya untuk OCR dan wajib dibersihkan, termasuk
            // ketika API gagal atau terjadi exception.
            if ($temporaryPdf !== null && file_exists($temporaryPdf)) {
                @unlink($temporaryPdf);
            }
        }
    }

    /**
     * Apakah perlu pass kedua seluruh halaman untuk approval?
     */
    protected function shouldRunApprovalFullOcr(
        bool $isPdf,
        ?array $ocrData,
        ?int $sourcePages,
        int $requestedOcrPages
    ): bool {
        if (! $isPdf || $ocrData === null || $sourcePages === null || $sourcePages <= 1) {
            return false;
        }

        // 0 = seluruh halaman; bila limit sudah >= jumlah halaman,
        // pass pertama sebenarnya sudah membaca seluruh PDF.
        if ($requestedOcrPages === 0 || $requestedOcrPages >= $sourcePages) {
            return false;
        }

        return str_contains(
            strtoupper((string) ($ocrData['document_type'] ?? '')),
            'PERSETUJUAN'
        );
    }

    /**
     * Pastikan invoice_numbers selalu berupa list string yang bersih.
     */
    protected function normalizeInvoiceNumbers(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        $isApproval = str_contains(
            strtoupper((string) ($data['document_type'] ?? '')),
            'PERSETUJUAN'
        );

        if (! array_key_exists('invoice_numbers', $data) && $isApproval) {
            foreach (['invoice_number', 'invoice_no', 'no_invoice', 'no_inv'] as $alias) {
                if (array_key_exists($alias, $data)) {
                    $data['invoice_numbers'] = $data[$alias];

                    break;
                }
            }
        }

        if (array_key_exists('invoice_numbers', $data)) {
            $data['invoice_numbers'] = InvoiceNumberNormalizer::normalize($data['invoice_numbers']);
        }

        return $data;
    }

    /**
     * Deteksi PDF dari mime type atau ekstensi file.
     */
    protected function isPdf(string $path, ?string $mimeType): bool
    {
        return $mimeType === 'application/pdf'
            || strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf';
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

            $imagick = new Imagick;
            $imagick->readImage($filePath);
            $imagick->thumbnailImage($newWidth, $newHeight);
            $imagick->setImageFormat('jpeg');
            $imagick->setImageCompressionQuality($this->imageQuality);

            $compressedPath = $filePath.'.compressed.jpg';
            $imagick->writeImage($compressedPath);
            $imagick->clear();
            $imagick->destroy();

            Log::debug('Image compressed for Gemini API', [
                'original' => $filePath,
                'original_size' => $width.'x'.$height,
                'compressed_size' => $newWidth.'x'.$newHeight,
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

    protected function callGeminiApi(
        string $prompt,
        string $mimeType,
        string $base64Content,
        int $attempt = 1,
        ?string $model = null,
    ): array {
        $model ??= $this->model;

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
                    "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$this->apiKey}",
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
                        'generationConfig' => $this->buildGenerationConfig($model),
                    ]
                );

            // Retry untuk rate limit dan gangguan sementara (termasuk 503
            // "model is currently experiencing high demand").
            $retryableStatuses = [429, 500, 502, 503, 504];

            if (in_array($response->status(), $retryableStatuses, true) && $attempt < $this->maxRetries) {
                $delay = $this->retryDelayMs($attempt, $response->header('Retry-After'));

                Log::warning("Gemini API sementara tidak tersedia ({$response->status()}), retrying in {$delay}ms", [
                    'attempt' => $attempt,
                    'max_retries' => $this->maxRetries,
                    'status' => $response->status(),
                    'model' => $model,
                ]);
                usleep($delay * 1000);

                return $this->callGeminiApi($prompt, $mimeType, $base64Content, $attempt + 1, $model);
            }

            // Setelah retry habis, coba model cadangan bila ada.
            if (in_array($response->status(), $retryableStatuses, true)) {
                $fallback = $this->nextFallbackModel($model);

                if ($fallback !== null) {
                    Log::warning('Gemini model utama tidak tersedia, beralih ke model cadangan', [
                        'status' => $response->status(),
                        'from' => $model,
                        'to' => $fallback,
                    ]);

                    return $this->callGeminiApi($prompt, $mimeType, $base64Content, 1, $fallback);
                }
            }

            if ($response->failed()) {
                Log::error('Gemini API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'model' => $model,
                ]);

                throw new Exception('Gemini API error: '.$response->body());
            }

            $this->lastUsedModel = $model;

            return $response->json();
        } catch (Exception $e) {
            // Retry on connection errors
            if ($attempt < $this->maxRetries && ! str_contains($e->getMessage(), 'Gemini API error')) {
                $delay = $this->retryDelayMs($attempt);
                Log::warning("Gemini API connection error, retrying in {$delay}ms", [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'model' => $model,
                ]);
                usleep($delay * 1000);

                return $this->callGeminiApi($prompt, $mimeType, $base64Content, $attempt + 1, $model);
            }

            throw $e;
        }
    }

    /**
     * Model cadangan berikutnya setelah model saat ini.
     */
    protected function nextFallbackModel(string $currentModel): ?string
    {
        $chain = array_merge([$this->model], $this->fallbackModels);
        $index = array_search($currentModel, $chain, true);

        if ($index === false) {
            return $this->fallbackModels[0] ?? null;
        }

        return $chain[$index + 1] ?? null;
    }

    /**
     * Bangun generationConfig dengan pengaturan paling hemat token.
     *
     * @return array<string, mixed>
     */
    protected function buildGenerationConfig(?string $model = null): array
    {
        $config = [
            'temperature' => 0.1,
            'maxOutputTokens' => $this->maxTokens,
        ];

        if ($this->responseMimeType !== '') {
            $config['responseMimeType'] = $this->responseMimeType;
        }

        $mediaResolution = $this->normalizeMediaResolution($this->mediaResolution);
        if ($mediaResolution !== null) {
            $config['mediaResolution'] = $mediaResolution;
        }

        $thinkingConfig = $this->buildThinkingConfig($model);
        if ($thinkingConfig !== []) {
            $config['thinkingConfig'] = $thinkingConfig;
        }

        return $config;
    }

    /**
     * Normalisasi nilai config menjadi enum MediaResolution.
     */
    protected function normalizeMediaResolution(string $value): ?string
    {
        $value = strtoupper(trim($value));

        if ($value === '' || $value === 'UNSPECIFIED' || $value === 'MEDIA_RESOLUTION_UNSPECIFIED') {
            return null;
        }

        if (! str_starts_with($value, 'MEDIA_RESOLUTION_')) {
            $value = 'MEDIA_RESOLUTION_'.$value;
        }

        return in_array($value, [
            'MEDIA_RESOLUTION_LOW',
            'MEDIA_RESOLUTION_MEDIUM',
            'MEDIA_RESOLUTION_HIGH',
        ], true) ? $value : null;
    }

    /**
     * Gemini 3/4 memakai thinkingLevel; Gemini 2.5 memakai thinkingBudget.
     *
     * @return array<string, mixed>
     */
    protected function buildThinkingConfig(?string $model = null): array
    {
        $model ??= $this->model;

        if (str_contains($model, 'gemini-3') || str_contains($model, 'gemini-4')) {
            $level = strtolower(trim($this->thinkingLevel));

            return $level === '' ? [] : ['thinkingLevel' => $level];
        }

        if (str_contains($model, 'gemini-2.5')) {
            return ['thinkingBudget' => $this->thinkingBudget ?? 0];
        }

        return [];
    }

    /**
     * Hitung delay retry, hormati header Retry-After bila tersedia.
     */
    protected function retryDelayMs(int $attempt, ?string $retryAfter = null): int
    {
        if ($retryAfter !== null && is_numeric($retryAfter)) {
            return max(0, (int) $retryAfter * 1000);
        }

        $base = $this->retryBaseDelayMs * (2 ** ($attempt - 1));

        return $base + random_int(0, 250);
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
                'thoughts_tokens' => $usageMetadata['thoughtsTokenCount'] ?? 0,
                'total_tokens' => $usageMetadata['totalTokenCount'] ?? 0,
            ]);
        }
    }

    public function engineName(): string
    {
        return 'gemini';
    }
}
