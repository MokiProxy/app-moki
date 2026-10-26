<?php

namespace Tests\Feature\Dokter;

use App\Services\Ocr\GeminiEngine;
use FPDF;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use setasign\Fpdi\Fpdi;
use Tests\TestCase;

class GeminiEngineApprovalMultiPageTest extends TestCase
{
    private string $tempDir;

    /**
     * @var list<string>
     */
    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir().'/gemini_approval_test_'.uniqid('', true);
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

    public function test_multipage_approval_runs_a_full_pdf_second_pass_and_merges_invoice_numbers(): void
    {
        $source = $this->makePdf(3);
        $uploaded = new UploadedFile($source, 'approval.pdf', 'application/pdf', null, true);

        $pageCounts = [];

        Http::fake(function (Request $request) use (&$pageCounts) {
            $pageCounts[] = $this->countPagesFromRequest($request);

            if (count($pageCounts) === 1) {
                return $this->approvalResponse(['2512014']);
            }

            return $this->approvalResponse(['2604001', '2604002']);
        });

        $result = (new GeminiEngine)->extractText($uploaded);

        $this->assertTrue($result['success']);
        $this->assertSame([1, 3], $pageCounts, 'Pass pertama 1 halaman, pass kedua seluruh PDF.');
        $this->assertSame(3, $result['source_pages']);
        $this->assertSame(3, $result['ocr_pages_used']);
        $this->assertSame(
            ['2512014', '2604001', '2604002'],
            $result['ocr_data']['invoice_numbers']
        );
    }

    public function test_single_page_approval_normalizes_comma_separated_invoice_numbers(): void
    {
        $source = $this->makePdf(1);
        $uploaded = new UploadedFile($source, 'approval.pdf', 'application/pdf', null, true);

        $requests = 0;

        Http::fake(function () use (&$requests) {
            $requests++;

            return $this->approvalResponse('2512014, 2604001');
        });

        $result = (new GeminiEngine)->extractText($uploaded);

        $this->assertSame(1, $requests);
        $this->assertSame(1, $result['ocr_pages_used']);
        $this->assertSame(
            ['2512014', '2604001'],
            $result['ocr_data']['invoice_numbers']
        );
    }

    private function approvalResponse(array|string $invoiceNumbers)
    {
        return Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'document_type' => 'Persetujuan Pembayaran',
                                    'document_number' => '724/SBS-HO/DIR/PP/VII/2026',
                                    'document_date' => '20 Oct 26',
                                    'vendor_name' => 'DUTA WAHANA PERSADA',
                                    'customer' => 'DUTA WAHANA PERSADA',
                                    'keterangan' => 'Test',
                                    'uraian' => ['Test'],
                                    'invoice_numbers' => $invoiceNumbers,
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

    private function countPagesFromRequest(Request $request): int
    {
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
            $pdf->Cell(0, 10, "Approval halaman {$page} dari {$pages}");
        }

        $pdf->Output('F', $path);

        $this->temporaryFiles[] = $path;

        return $path;
    }
}
