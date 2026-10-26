<?php

namespace Tests\Feature\Dokter;

use App\Models\DocumentMergeGroup;
use App\Models\DocumentType;
use App\Models\MergeFlow;
use App\Models\ScanLog;
use App\Services\MergeFlowService;
use App\Services\PdfMergeService;
use Exception;
use FPDF;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use Tests\TestCase;

class MergeFlowFanOutTest extends TestCase
{
    use RefreshDatabase;

    private string $tempDir;

    private DocumentType $invoiceType;

    private DocumentType $approvalType;

    private MergeFlow $flow;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.merge_flow_enabled' => true]);

        Storage::fake('ftp_final');

        $this->tempDir = sys_get_temp_dir().'/merge_flow_test_'.uniqid('', true);
        mkdir($this->tempDir, 0777, true);

        $this->invoiceType = DocumentType::create([
            'name' => 'SLIP PEMBUKUAN AP',
            'description' => 'Invoice',
            'filename_template' => '{vendor}_{number}.{ext}',
            'ftp_folder_template' => '{document_type}/{vendor}',
            'ftp_failed_folder' => 'FAILED',
            'vendor_search_enabled' => true,
        ]);

        $this->approvalType = DocumentType::create([
            'name' => 'PERSETUJUAN PEMBAYARAN',
            'description' => 'Approval',
            'filename_template' => 'APP_{vendor}_{number}.{ext}',
            'ftp_folder_template' => '{document_type}/{vendor}',
            'ftp_failed_folder' => 'FAILED',
            'vendor_search_enabled' => true,
        ]);

        $this->flow = MergeFlow::create([
            'name' => 'INV-APPROVAL',
            'slug' => 'invoice-approval-test',
            'description' => 'Test fan-out',
            'is_active' => true,
        ]);

        $this->flow->steps()->createMany([
            [
                'document_type_id' => $this->invoiceType->id,
                'order' => 1,
                'link_regex' => null,
                'link_label' => null,
                'link_field' => null,
            ],
            [
                'document_type_id' => $this->approvalType->id,
                'order' => 2,
                'link_regex' => null,
                'link_label' => 'Invoice Numbers',
                'link_field' => 'invoice_numbers',
            ],
        ]);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tempDir.'/*') ?: [] as $file) {
            @unlink($file);
        }

        @rmdir($this->tempDir);

        parent::tearDown();
    }

    public function test_approval_fans_out_to_existing_invoices_and_creates_one_final_pdf_per_invoice(): void
    {
        $invoices = [
            $this->createInvoice('2512014'),
            $this->createInvoice('2604001'),
            $this->createInvoice('2604002'),
        ];

        $originalContents = [];

        foreach ($invoices as $invoice) {
            $originalContents[$invoice->ftp_path] = Storage::disk('ftp_final')->get($invoice->ftp_path);
            $this->mergeService()->processAfterUpload($invoice);
        }

        $this->assertSame(3, DocumentMergeGroup::count());
        $this->assertSame(3, DocumentMergeGroup::where('status', 0)->count());

        $approval = $this->createApproval(['2512014', '2604001', '2604002']);
        $originalApproval = Storage::disk('ftp_final')->get($approval->ftp_path);

        $this->mergeService()->processAfterUpload($approval);

        $this->assertSame(3, DocumentMergeGroup::where('status', 2)->count());
        $this->assertSame(
            ['2512014', '2604001', '2604002'],
            $approval->refresh()->linked_numbers['Invoice Numbers']
        );

        foreach (DocumentMergeGroup::with('items')->get() as $group) {
            $this->assertNotNull($group->final_pdf_path);
            $this->assertTrue(Storage::disk('ftp_final')->exists($group->final_pdf_path));
            $this->assertStringStartsWith('FINAL/DUTA WAHANA PERSADA/', $group->final_pdf_path);
            $this->assertCount(2, $group->items);
            $this->assertSame([1, 2], $group->items->sortBy('order')->pluck('order')->values()->all());
        }

        $this->assertSame(3, ScanLog::where('event', 'merge_final')->count());

        foreach ($originalContents as $path => $content) {
            $this->assertSame($content, Storage::disk('ftp_final')->get($path), 'File invoice asli tidak boleh berubah.');
        }

        $this->assertSame(
            $originalApproval,
            Storage::disk('ftp_final')->get($approval->ftp_path),
            'File approval asli tidak boleh berubah.'
        );
    }

    public function test_approval_before_invoice_creates_pending_groups_then_completes_when_invoice_arrives(): void
    {
        $approval = $this->createApproval(['2512014', '2604001', '2604002']);

        $this->mergeService()->processAfterUpload($approval);

        $this->assertSame(3, DocumentMergeGroup::where('status', 0)->count());
        $this->assertSame(0, DocumentMergeGroup::where('status', 2)->count());

        foreach (['2512014', '2604001', '2604002'] as $number) {
            $invoice = $this->createInvoice($number);

            $this->mergeService()->processAfterUpload($invoice);

            $group = DocumentMergeGroup::where('root_document_number', $number)->firstOrFail();
            $this->assertSame(2, $group->status);
            $this->assertNotNull($group->final_pdf_path);
        }

        $this->assertSame(3, DocumentMergeGroup::where('status', 2)->count());
    }

    public function test_only_available_invoice_is_merged_while_the_rest_stay_pending(): void
    {
        $invoice = $this->createInvoice('2512014');
        $this->mergeService()->processAfterUpload($invoice);

        $approval = $this->createApproval(['2512014', '2604001', '2604002']);
        $this->mergeService()->processAfterUpload($approval);

        $this->assertSame(1, DocumentMergeGroup::where('status', 2)->count());
        $this->assertSame(2, DocumentMergeGroup::where('status', 0)->count());
        $this->assertSame(1, ScanLog::where('event', 'merge_final')->count());
    }

    public function test_duplicate_invoice_numbers_in_approval_do_not_create_duplicate_groups(): void
    {
        foreach (['2512014', '2604001', '2604002'] as $number) {
            $this->mergeService()->processAfterUpload($this->createInvoice($number));
        }

        $approval = $this->createApproval([
            '2512014',
            '2512014',
            '2604001',
            '2604001',
            '2604002',
        ]);

        $this->mergeService()->processAfterUpload($approval);

        $this->assertSame(3, DocumentMergeGroup::count());
        $this->assertSame(3, DocumentMergeGroup::where('status', 2)->count());
        $this->assertSame(3, ScanLog::where('event', 'merge_final')->count());
    }

    public function test_rescanning_approval_does_not_create_duplicate_final_pdf(): void
    {
        $invoice = $this->createInvoice('2512014');
        $approval = $this->createApproval(['2512014']);

        $this->mergeService()->processAfterUpload($invoice);
        $this->mergeService()->processAfterUpload($approval);

        $group = DocumentMergeGroup::firstOrFail();
        $firstFinalPath = $group->final_pdf_path;

        $this->mergeService()->processAfterUpload($approval->refresh());

        $this->assertSame(1, DocumentMergeGroup::count());
        $this->assertSame(2, $group->refresh()->status);
        $this->assertSame($firstFinalPath, $group->final_pdf_path);
        $this->assertSame(1, ScanLog::where('event', 'merge_final')->count());
    }

    public function test_approval_without_invoice_numbers_does_not_create_a_group(): void
    {
        Log::spy();

        $approval = $this->createApproval([]);

        $this->mergeService()->processAfterUpload($approval);

        $this->assertSame(0, DocumentMergeGroup::count());
        $this->assertSame('success', $approval->refresh()->status);

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message) => str_contains($message, 'Could not extract linked numbers'))
            ->once();
    }

    public function test_invoice_and_approval_from_different_vendors_are_not_merged(): void
    {
        $invoice = $this->createInvoice('2512014', 'VENDOR A');
        $approval = $this->createApproval(['2512014'], 'VENDOR B');

        $this->mergeService()->processAfterUpload($invoice);
        $this->mergeService()->processAfterUpload($approval);

        $this->assertSame(2, DocumentMergeGroup::count());
        $this->assertSame(0, DocumentMergeGroup::where('status', 2)->count());
        $this->assertSame(0, ScanLog::where('event', 'merge_final')->count());
    }

    public function test_merge_failure_sets_group_status_to_failed(): void
    {
        $this->mock(PdfMergeService::class, function ($mock) {
            $mock->shouldReceive('mergePdfs')
                ->once()
                ->andThrow(new Exception('FPDI failed'));
        });

        $invoice = $this->createInvoice('2512014');
        $approval = $this->createApproval(['2512014']);

        $this->mergeService()->processAfterUpload($invoice);
        $this->mergeService()->processAfterUpload($approval);

        $group = DocumentMergeGroup::firstOrFail();

        $this->assertSame(3, $group->status);
        $this->assertNull($group->final_pdf_path);
        $this->assertSame(0, ScanLog::where('event', 'merge_final')->count());
    }

    public function test_failed_group_can_be_retried_by_reprocessing_the_document(): void
    {
        $this->mock(PdfMergeService::class, function ($mock) {
            $mock->shouldReceive('mergePdfs')
                ->once()
                ->andThrow(new Exception('FPDI failed'));
        });

        $invoice = $this->createInvoice('2512014');
        $approval = $this->createApproval(['2512014']);

        $this->mergeService()->processAfterUpload($invoice);
        $this->mergeService()->processAfterUpload($approval);

        $this->assertSame(3, DocumentMergeGroup::firstOrFail()->status);

        $this->app->instance(PdfMergeService::class, new PdfMergeService);

        $this->mergeService()->processAfterUpload($approval->refresh());

        $group = DocumentMergeGroup::firstOrFail();

        $this->assertSame(2, $group->status);
        $this->assertNotNull($group->final_pdf_path);
        $this->assertTrue(Storage::disk('ftp_final')->exists($group->final_pdf_path));
    }

    public function test_final_pdf_page_order_is_invoice_then_approval(): void
    {
        $invoice = $this->createInvoice('2512014', 'DUTA WAHANA PERSADA', 1, 100, 150);
        $approval = $this->createApproval(['2512014'], 'DUTA WAHANA PERSADA', 2, 200, 250);

        $this->mergeService()->processAfterUpload($invoice);
        $this->mergeService()->processAfterUpload($approval);

        $group = DocumentMergeGroup::firstOrFail();
        $this->assertNotNull($group->final_pdf_path);

        $sizes = $this->finalPageSizes($group->final_pdf_path);

        $this->assertCount(3, $sizes);
        $this->assertLessThan(
            $sizes[1][0],
            $sizes[0][0],
            'Halaman invoice harus berada di depan approval.'
        );
        $this->assertSame($sizes[1], $sizes[2], 'Dua halaman approval harus berurutan.');

        $finalLog = ScanLog::where('event', 'merge_final')->firstOrFail();
        $metadata = $finalLog->metadata;

        $this->assertSame(3, $finalLog->total_pages);
        $this->assertSame($group->root_document_number, $metadata['group_key']);
        $this->assertSame('2512014', $metadata['group_key']);
        $this->assertSame(['SLIP PEMBUKUAN AP', 'PERSETUJUAN PEMBAYARAN'], $metadata['page_order']);
        $this->assertSame(
            '724/SBS-HO/DIR/PP/VII/2026',
            $metadata['approval_document_number']
        );
        $this->assertCount(2, $metadata['item_ids']);
    }

    private function mergeService(): MergeFlowService
    {
        return app(MergeFlowService::class);
    }

    private function createInvoice(
        string $number,
        string $vendor = 'DUTA WAHANA PERSADA',
        int $pages = 1,
        float $width = 100,
        float $height = 150
    ): ScanLog {
        $path = "SLIP PEMBUKUAN AP/{$vendor}/invoice_{$number}.pdf";
        $pdf = $this->makePdf($pages, $width, $height, "Invoice {$number}");
        Storage::disk('ftp_final')->put($path, file_get_contents($pdf));

        return ScanLog::create([
            'source' => 'scanner',
            'event' => 'job_completed',
            'status' => 'success',
            'filename' => "invoice_{$number}.pdf",
            'extension' => 'pdf',
            'document_type_id' => $this->invoiceType->id,
            'document_type_name' => $this->invoiceType->name,
            'document_number' => $number,
            'tanggal' => '20 Oct 26',
            'vendor_name' => $vendor,
            'ftp_path' => $path,
            'metadata' => [
                'ocr_data' => [
                    'document_type' => $this->invoiceType->name,
                    'document_number' => $number,
                    'vendor_name' => $vendor,
                ],
            ],
        ]);
    }

    private function createApproval(
        array|string $invoiceNumbers,
        string $vendor = 'DUTA WAHANA PERSADA',
        int $pages = 2,
        float $width = 200,
        float $height = 250
    ): ScanLog {
        $path = "PERSETUJUAN PEMBAYARAN/{$vendor}/approval.pdf";
        $pdf = $this->makePdf($pages, $width, $height, 'Persetujuan Pembayaran');
        Storage::disk('ftp_final')->put($path, file_get_contents($pdf));

        return ScanLog::create([
            'source' => 'scanner',
            'event' => 'job_completed',
            'status' => 'success',
            'filename' => 'approval.pdf',
            'extension' => 'pdf',
            'document_type_id' => $this->approvalType->id,
            'document_type_name' => $this->approvalType->name,
            'document_number' => '724/SBS-HO/DIR/PP/VII/2026',
            'tanggal' => '20 Oct 26',
            'vendor_name' => $vendor,
            'ftp_path' => $path,
            'metadata' => [
                'ocr_data' => [
                    'document_type' => $this->approvalType->name,
                    'document_number' => '724/SBS-HO/DIR/PP/VII/2026',
                    'vendor_name' => $vendor,
                    'invoice_numbers' => $invoiceNumbers,
                ],
            ],
        ]);
    }

    private function makePdf(int $pages, float $width, float $height, string $label): string
    {
        $path = $this->tempDir.'/pdf_'.uniqid('', true).'.pdf';

        $pdf = new FPDF;
        $pdf->SetAutoPageBreak(false);

        for ($page = 1; $page <= $pages; $page++) {
            $pdf->AddPage('P', [$width, $height]);
            $pdf->SetFont('Helvetica', '', 12);
            $pdf->Cell(0, 10, "{$label} - halaman {$page}");
        }

        $pdf->Output('F', $path);

        return $path;
    }

    /**
     * @return list<array{0: float, 1: float}>
     */
    private function finalPageSizes(string $finalPath): array
    {
        $localPath = $this->tempDir.'/final_'.uniqid('', true).'.pdf';
        file_put_contents($localPath, Storage::disk('ftp_final')->get($finalPath));

        $fpdi = new Fpdi;
        $pageCount = $fpdi->setSourceFile($localPath);
        $sizes = [];

        for ($page = 1; $page <= $pageCount; $page++) {
            $size = $fpdi->getTemplateSize($fpdi->ImportPage($page));
            $sizes[] = [round((float) $size['width'], 2), round((float) $size['height'], 2)];
        }

        return $sizes;
    }
}
