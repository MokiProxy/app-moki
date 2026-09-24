<?php

namespace Tests\Feature\Erkap;

use App\Exports\Erkap\WorkProgramExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Concerns\ActsAsSuperAdmin;
use Tests\Concerns\BuildsErkapChain;
use Tests\TestCase;

class WorkProgramFeatureTest extends TestCase
{
    use ActsAsSuperAdmin, BuildsErkapChain, RefreshDatabase;

    private array $chain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSuperAdmin();
        $this->chain = $this->buildErkapChain();
    }

    public function test_index_displays_cumulative_percent_columns(): void
    {
        $this->get(route('erkap.work-programs.index'))
            ->assertOk()
            ->assertSee('Jan %')
            ->assertSee('Des %')
            ->assertSee('8,33%')
            ->assertSee('100,00%');
    }

    public function test_index_handles_zero_year_plan(): void
    {
        $this->chain['workProgram']->update(['year_plan' => 0]);

        $this->get(route('erkap.work-programs.index'))
            ->assertOk()
            ->assertSee('0,00%');
    }

    public function test_excel_export_contains_cumulative_percent_columns(): void
    {
        Excel::fake();
        Excel::matchByRegex();

        $this->get(route('erkap.work-programs.export'))
            ->assertOk();

        Excel::assertDownloaded('/^program-kerja-.*\.xlsx$/', function (WorkProgramExport $export) {
            $headings = $export->headings();

            return in_array('Kum Jan %', $headings, true)
                && in_array('Kum Des %', $headings, true);
        });
    }

    public function test_pdf_export_renders_cumulative_percent(): void
    {
        $this->get(route('erkap.work-programs.export-pdf'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }
}