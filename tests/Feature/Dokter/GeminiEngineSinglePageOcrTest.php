<?php

namespace Tests\Feature\Dokter;

use App\Services\Ocr\GeminiEngine;
use FPDF;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use setasign\Fpdi\Fpdi;
use Tests\TestCase;

class GeminiEngineSinglePageOcrTest extends TestCase
{
    private string $tempDir;

    /**
     * @var list<string>
     */
    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir().'/gemini_ocr_test_'.uniqid('', true);
        mkdir($this->tempDir, 0777, true);

        config([
            'services.gemini.api_key' => 'test-key',
            'services.gemini.model' => 'gemini-3.1-flash-lite',
            'services.gemini.fallback_models' => '',
            'services.gemini.pdf_ocr_pages' => 1,
            'services.gemini.request_delay_ms' => 0,
            'services.gemini.retry_base_delay_ms' => 0,
            'services.gemini.max_retries' => 3,
            'services.gemini.max_tokens' => 1024,
            'services.gemini.media_resolution' => 'low',
            'services.gemini.thinking_level' => 'minimal',
            'services.gemini.response_mime_type' => 'application/json',
        ]);
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            @unlink($file);
        }

        foreach (glob($this->tempDir.'/*') ?: [] as $file) {
            @unlink($file);
        }

        @rmdir($this->tempDir);

        parent::tearDown();
    }

    public function test_pdf_ocr_only_sends_the_first_page(): void
    {
        $source = $this->makePdf(3);
        $uploaded = new UploadedFile($source, 'multi-page.pdf', 'application/pdf', null, true);

        $request = null;
        $this->fakeGemini($request);

        $tempBefore = glob(sys_get_temp_dir().'/ocr_pdf_*.pdf') ?: [];

        $result = (new GeminiEngine)->extractText($uploaded);

        $this->assertSame(3, $result['source_pages']);
        $this->assertSame(1, $result['ocr_pages_used']);
        $this->assertSame(1, $this->countPagesFromRequest($request));
        $this->assertEfficientGenerationConfig($request);

        $tempAfter = glob(sys_get_temp_dir().'/ocr_pdf_*.pdf') ?: [];
        $this->assertSame([], array_values(array_diff($tempAfter, $tempBefore)), 'File temp OCR harus dibersihkan.');
    }

    public function test_pdf_ocr_sends_all_pages_when_config_is_zero(): void
    {
        config(['services.gemini.pdf_ocr_pages' => 0]);

        $source = $this->makePdf(3);
        $uploaded = new UploadedFile($source, 'multi-page.pdf', 'application/pdf', null, true);

        $request = null;
        $this->fakeGemini($request);

        $result = (new GeminiEngine)->extractText($uploaded);

        $this->assertSame(3, $result['source_pages']);
        $this->assertSame(3, $result['ocr_pages_used']);
        $this->assertSame(3, $this->countPagesFromRequest($request));
    }

    public function test_single_page_pdf_is_sent_without_trimming(): void
    {
        $source = $this->makePdf(1);
        $uploaded = new UploadedFile($source, 'single-page.pdf', 'application/pdf', null, true);

        $request = null;
        $this->fakeGemini($request);

        $result = (new GeminiEngine)->extractText($uploaded);

        $this->assertSame(1, $result['source_pages']);
        $this->assertSame(1, $result['ocr_pages_used']);
        $this->assertSame(1, $this->countPagesFromRequest($request));
    }

    public function test_retries_when_gemini_returns_503(): void
    {
        $source = $this->makePdf(1);
        $uploaded = new UploadedFile($source, 'single-page.pdf', 'application/pdf', null, true);

        $attempts = 0;

        Http::fake(function () use (&$attempts) {
            $attempts++;

            if ($attempts === 1) {
                return Http::response([
                    'error' => [
                        'code' => 503,
                        'message' => 'This model is currently experiencing high demand.',
                        'status' => 'UNAVAILABLE',
                    ],
                ], 503);
            }

            return $this->geminiResponse();
        });

        $result = (new GeminiEngine)->extractText($uploaded);

        $this->assertSame(2, $attempts);
        $this->assertTrue($result['success']);
    }

    public function test_falls_back_to_secondary_model_after_503(): void
    {
        config(['services.gemini.fallback_models' => 'gemini-3.5-flash-lite,gemini-2.5-flash-lite']);

        $source = $this->makePdf(1);
        $uploaded = new UploadedFile($source, 'single-page.pdf', 'application/pdf', null, true);

        $urls = [];

        Http::fake(function (Request $request) use (&$urls) {
            $urls[] = $request->url();

            if (str_contains($request->url(), 'gemini-3.1-flash-lite')) {
                return Http::response([
                    'error' => [
                        'code' => 503,
                        'message' => 'This model is currently experiencing high demand.',
                        'status' => 'UNAVAILABLE',
                    ],
                ], 503);
            }

            return $this->geminiResponse();
        });

        $result = (new GeminiEngine)->extractText($uploaded);

        $this->assertTrue($result['success']);
        $this->assertCount(4, $urls, '3 percobaan model utama + 1 percobaan model cadangan.');
        $this->assertStringContainsString('gemini-3.1-flash-lite', $urls[0]);
        $this->assertStringContainsString('gemini-3.5-flash-lite', $urls[3]);
    }

    private function fakeGemini(?Request &$captured): void
    {
        Http::fake(function (Request $request) use (&$captured) {
            $captured = $request;

            return $this->geminiResponse();
        });
    }

    private function geminiResponse()
    {
        return Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'document_type' => 'INVOICE',
                                    'document_number' => '2604002',
                                    'document_date' => '20 Oct 26',
                                    'vendor_name' => 'PT CONTOH',
                                    'customer' => 'PT CONTOH',
                                    'keterangan' => 'Test',
                                    'uraian' => ['Test'],
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
            'usageMetadata' => [
                'promptTokenCount' => 100,
                'candidatesTokenCount' => 50,
                'totalTokenCount' => 150,
            ],
        ]);
    }

    private function assertEfficientGenerationConfig(?Request $request): void
    {
        $this->assertNotNull($request);

        $config = $request->data()['generationConfig'] ?? [];

        $this->assertSame('MEDIA_RESOLUTION_LOW', $config['mediaResolution'] ?? null);
        $this->assertSame('minimal', $config['thinkingConfig']['thinkingLevel'] ?? null);
        $this->assertSame('application/json', $config['responseMimeType'] ?? null);
        $this->assertSame(1024, $config['maxOutputTokens'] ?? null);
    }

    private function countPagesFromRequest(?Request $request): int
    {
        $this->assertNotNull($request, 'Request ke Gemini tidak terkirim.');

        $base64 = $request->data()['contents'][0]['parts'][1]['inline_data']['data'] ?? null;
        $this->assertNotNull($base64, 'Payload inline_data tidak ditemukan.');

        $path = $this->tempDir.'/payload_'.uniqid('', true).'.pdf';
        file_put_contents($path, base64_decode($base64, true));
        $this->temporaryFiles[] = $path;

        $fpdi = new Fpdi;

        return $fpdi->setSourceFile($path);
    }

    private function makePdf(int $pages): string
    {
        $path = $this->tempDir.'/source_'.$pages.'_'.uniqid('', true).'.pdf';

        $pdf = new FPDF;
        $pdf->SetAutoPageBreak(false);

        for ($page = 1; $page <= $pages; $page++) {
            $pdf->AddPage();
            $pdf->SetFont('Helvetica', '', 16);
            $pdf->Cell(0, 10, "Halaman {$page} dari {$pages}");
        }

        $pdf->Output('F', $path);

        $this->temporaryFiles[] = $path;

        return $path;
    }
}
