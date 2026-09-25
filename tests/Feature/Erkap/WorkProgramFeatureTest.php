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

    public function test_index_displays_percentage_plan_columns(): void
    {
        $this->get(route('erkap.work-programs.index'))
            ->assertOk()
            ->assertSee('Jan (%)')
            ->assertSee('Des (%)')
            ->assertSee('8,00%')
            ->assertSee('100,00%')
            ->assertDontSee('8,33%')
            ->assertDontSee('Kum Jan')
            ->assertDontSee('Kum Des');
    }

    public function test_index_handles_zero_year_plan(): void
    {
        $this->chain['workProgram']->update([
            'year_plan' => 0,
            'jan_plan' => 0,
            'feb_plan' => 0,
            'mar_plan' => 0,
            'apr_plan' => 0,
            'may_plan' => 0,
            'jun_plan' => 0,
            'jul_plan' => 0,
            'aug_plan' => 0,
            'sep_plan' => 0,
            'oct_plan' => 0,
            'nov_plan' => 0,
            'dec_plan' => 0,
        ]);

        $this->get(route('erkap.work-programs.index'))
            ->assertOk()
            ->assertSee('0,00%');
    }

    public function test_excel_export_contains_percentage_plan_columns_without_cumulative_columns(): void
    {
        Excel::fake();
        Excel::matchByRegex();

        $this->get(route('erkap.work-programs.export'))
            ->assertOk();

        Excel::assertDownloaded('/^program-kerja-.*\.xlsx$/', function (WorkProgramExport $export) {
            $headings = $export->headings();

            return in_array('Jan (%)', $headings, true)
                && in_array('Des (%)', $headings, true)
                && ! in_array('Kum Jan %', $headings, true)
                && ! in_array('Kum Des %', $headings, true)
                && count($headings) === 20;
        });
    }

    public function test_pdf_export_renders_percentage_plan(): void
    {
        $this->get(route('erkap.work-programs.export-pdf'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_store_rejects_percentages_above_one_hundred(): void
    {
        $this->post(route('erkap.work-programs.store'), $this->validWorkProgramPayload([
            'year_plan' => 101,
            'jan_plan' => 101,
            'dec_plan' => 3,
        ]))->assertSessionHasErrors(['year_plan', 'jan_plan']);

        $this->assertDatabaseCount('erkap_work_programs', 1);
    }

    public function test_store_rejects_monthly_total_that_does_not_match_year_plan(): void
    {
        $this->post(route('erkap.work-programs.store'), $this->validWorkProgramPayload([
            'dec_plan' => 13,
        ]))->assertSessionHasErrors('year_plan');

        $this->assertDatabaseCount('erkap_work_programs', 1);
    }

    public function test_update_rejects_monthly_total_that_does_not_match_year_plan(): void
    {
        $workProgram = $this->chain['workProgram'];

        $this->put(route('erkap.work-programs.update', $workProgram), $this->validWorkProgramPayload([
            'dec_plan' => 13,
        ]))->assertSessionHasErrors('year_plan');

        $this->assertSame(100.0, (float) $workProgram->fresh()->year_plan);
        $this->assertSame(12.0, (float) $workProgram->fresh()->dec_plan);
    }

    private function validWorkProgramPayload(array $overrides = []): array
    {
        return array_merge([
            'erkap_risk_identification_id' => $this->chain['risk']->id,
            'name' => 'Program Kerja Persentase',
            'units' => 'Unit',
            'year_plan' => 100,
            'jan_plan' => 8,
            'feb_plan' => 8,
            'mar_plan' => 8,
            'apr_plan' => 8,
            'may_plan' => 8,
            'jun_plan' => 8,
            'jul_plan' => 8,
            'aug_plan' => 8,
            'sep_plan' => 8,
            'oct_plan' => 8,
            'nov_plan' => 8,
            'dec_plan' => 12,
        ], $overrides);
    }
}