<?php

namespace Tests\Unit\Dokter;

use App\Services\PdfPageExtractor;
use FPDF;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use setasign\Fpdi\Fpdi;
use Throwable;

class PdfPageExtractorTest extends TestCase
{
    private string $tempDir;

    private PdfPageExtractor $extractor;

    /**
     * @var list<string>
     */
    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir().'/pdf_extractor_test_'.uniqid('', true);
        mkdir($this->tempDir, 0777, true);

        $this->extractor = new PdfPageExtractor;
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

    public function test_extracts_only_the_first_page_by_default(): void
    {
        $source = $this->makePdf(3);

        $result = $this->extractor->extract($source, 1);

        $this->assertSame(3, $result['source_pages']);
        $this->assertSame(1, $result['ocr_pages']);
        $this->assertNotNull($result['path']);
        $this->track($result['path']);

        $this->assertFileExists($result['path']);
        $this->assertSame(1, $this->countPages($result['path']));
    }

    public function test_extracts_requested_number_of_pages(): void
    {
        $source = $this->makePdf(4);

        $result = $this->extractor->extract($source, 2);

        $this->assertSame(4, $result['source_pages']);
        $this->assertSame(2, $result['ocr_pages']);
        $this->assertNotNull($result['path']);
        $this->track($result['path']);

        $this->assertSame(2, $this->countPages($result['path']));
    }

    public function test_does_not_create_temp_file_when_all_pages_are_requested(): void
    {
        $source = $this->makePdf(3);

        $result = $this->extractor->extract($source, 3);

        $this->assertSame(3, $result['source_pages']);
        $this->assertSame(3, $result['ocr_pages']);
        $this->assertNull($result['path']);
    }

    public function test_limit_zero_means_all_pages(): void
    {
        $source = $this->makePdf(3);

        $result = $this->extractor->extract($source, 0);

        $this->assertSame(3, $result['source_pages']);
        $this->assertSame(3, $result['ocr_pages']);
        $this->assertNull($result['path']);
    }

    public function test_extract_first_pages_returns_null_when_pdf_has_one_page(): void
    {
        $source = $this->makePdf(1);

        $this->assertNull($this->extractor->extractFirstPages($source, 1));
    }

    public function test_extract_first_pages_returns_path_for_multi_page_pdf(): void
    {
        $source = $this->makePdf(3);

        $path = $this->extractor->extractFirstPages($source, 1);

        $this->assertNotNull($path);
        $this->track($path);
        $this->assertSame(1, $this->countPages($path));
    }

    public function test_missing_file_throws_exception(): void
    {
        $this->expectException(RuntimeException::class);

        $this->extractor->extract($this->tempDir.'/tidak-ada.pdf', 1);
    }

    public function test_invalid_pdf_throws_exception(): void
    {
        $invalid = $this->tempDir.'/invalid.pdf';
        file_put_contents($invalid, 'bukan pdf');
        $this->temporaryFiles[] = $invalid;

        $this->expectException(Throwable::class);

        $this->extractor->extract($invalid, 1);
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

    private function countPages(string $path): int
    {
        $fpdi = new Fpdi;

        return $fpdi->setSourceFile($path);
    }

    private function track(string $path): void
    {
        $this->temporaryFiles[] = $path;
    }
}
