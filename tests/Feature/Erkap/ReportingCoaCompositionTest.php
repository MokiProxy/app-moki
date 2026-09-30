<?php

namespace Tests\Feature\Erkap;

use App\Exports\Erkap\InvestmentPlanExport;
use App\Exports\Erkap\RoutineCostExport;
use App\Exports\Erkap\DrilldownExport;
use App\Models\ChartOfAccount;
use App\Models\Erkap\CostCenter;
use App\Models\Erkap\BudgetRealization;
use App\Models\Erkap\CostElement;
use App\Models\Erkap\CostElementCategory;
use App\Models\Erkap\ExpensePlan;
use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\ProfitLossStatement;
use App\Models\Erkap\RevenuePlan;
use App\Models\Erkap\RoutineCost;
use App\Services\Dashboard\CostCenterHeatmapService;
use App\Services\Dashboard\DrilldownService;
use App\Services\Dashboard\TopVarianceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Concerns\ActsAsSuperAdmin;
use Tests\Concerns\BuildsErkapChain;
use Tests\TestCase;

/**
 * F8 — laporan, export, dan dashboard mengikuti relasi hasil komposisi.
 *
 * Konsumen COA lama membaca `chartOfAccount->code` polos dan `costCenter->name`
 * polos, serta dereference `->chartOfAccount` tanpa null-check. Setelah F2–F7
 * `chart_of_account_id` boleh kosong (mis. rencana investasi tingkat perusahaan)
 * dan kode sudah 15 karakter hasil komposisi a..e, sehingga laporan harus
 * menampilkan kode tersegmentasi dan tidak boleh gagal pada data yang sah.
 */
class ReportingCoaCompositionTest extends TestCase
{
    use ActsAsSuperAdmin, BuildsErkapChain, RefreshDatabase;

    private array $chain;

    private CostCenter $costCenter;

    private CostElement $costElement;

    private ChartOfAccount $coa;

    private CostElement $orphanElement;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSuperAdmin();
        $this->chain = $this->buildErkapChain(['year' => 2026, 'code' => 'WP-RPT']);

        $this->costCenter = CostCenter::factory()->create([
            'division_id' => $this->chain['division']->id,
            'name' => 'Pusat Biaya Pelaporan',
        ]);
        $this->costElement = CostElement::factory()->create([
            'code' => '5001',
            'name' => 'Beban Materialitas',
            'erkap_cost_element_category_id' => CostElementCategory::factory()->create()->id,
        ]);
        $this->coa = ChartOfAccount::factory()
            ->composed($this->costCenter, $this->costElement)
            ->create(['name' => 'Beban Materialitas']);

        $this->orphanElement = CostElement::factory()->create([
            'code' => '5099',
            'name' => 'Beban Belum Terklasifikasi',
            'erkap_cost_element_category_id' => CostElementCategory::factory()->create()->id,
        ]);
    }


    /* ---------------------------------------------------------------------
     | Export Biaya Rutin
     | ------------------------------------------------------------------ */

    public function test_routine_cost_export_menampilkan_kode_tersegmentasi(): void
    {
        $routine = $this->routineCost();

        $export = new RoutineCostExport(collect([$routine]));
        $row = $export->map($routine);

        $this->assertSame($this->coa->label, $row[4]);
        $this->assertSame($this->costCenter->label, $row[5]);
        $this->assertStringContainsString($this->costCenter->formatted_code, $row[5]);
    }

    public function test_routine_cost_export_tahan_biaya_tanpa_coa_dan_pusat_biaya(): void
    {
        $routine = $this->bareRoutineCost();

        $row = (new RoutineCostExport(collect([$routine])))->map($routine);

        $this->assertSame($this->orphanElement->label, $row[3]);
        $this->assertSame('-', $row[4]);
        $this->assertSame('-', $row[5]);
        $this->assertSame('Non-Terpusat', $row[6]);
    }

    public function test_laporan_pdf_biaya_rutin_tahan_data_kosong(): void
    {
        $html = view('erkap.exports.routine-cost-pdf', [
            'routineCosts' => new Collection([$this->bareRoutineCost()]),
        ])->render();

        $this->assertStringContainsString('Biaya belum terklasifikasi', $html);
    }


    public function test_route_export_biaya_rutin_tersedia(): void
    {
        Excel::fake();
        $this->routineCost();

        $this->get(route('erkap.routine-costs.export'))->assertOk();

        Excel::assertDownloaded(
            'biaya-rutin-'.date('Y-m-d-Hi').'.xlsx',
            function (RoutineCostExport $export) {
                $row = $export->map($export->collection()->first());

                return $row[4] === $this->coa->label;
            }
        );
    }

    /* ---------------------------------------------------------------------
     | Export Rencana Investasi
     | ------------------------------------------------------------------ */

    public function test_investment_plan_export_menampilkan_pusat_biaya_dan_coa(): void
    {
        $plan = InvestmentPlan::factory()->create([
            'erkap_work_program_id' => $this->chain['workProgram']->id,
            'cost_center_id' => $this->costCenter->id,
            'chart_of_account_id' => $this->coa->id,
        ]);

        $export = new InvestmentPlanExport(collect([$plan]));
        $row = $export->map($plan);

        $this->assertContains('Pusat Biaya', $export->headings());
        $this->assertSame($this->costCenter->label, $row[5]);
        $this->assertSame($this->coa->label, $row[6]);
        $this->assertCount(count($export->headings()), $row);
    }

    public function test_investment_plan_export_tahan_rencana_tanpa_pusat_biaya(): void
    {
        $plan = InvestmentPlan::factory()->create([
            'erkap_work_program_id' => $this->chain['workProgram']->id,
            'cost_center_id' => null,
            'chart_of_account_id' => null,
        ]);

        $row = (new InvestmentPlanExport(collect([$plan])))->map($plan);

        $this->assertSame('-', $row[5]);
        $this->assertSame('-', $row[6]);
    }

    /* ---------------------------------------------------------------------
     | Drilldown
     | ------------------------------------------------------------------ */

    public function test_drilldown_biaya_rutin_menampilkan_label_komposisi(): void
    {
        $routine = $this->routineCost();

        $detail = (new DrilldownService)->routineCostDetail($routine->id);

        $this->assertSame($this->coa->label, $detail['attributes']['Chart of Account']);
        $this->assertSame($this->costCenter->label, $detail['attributes']['Pusat Biaya']);
        $this->assertSame($this->costElement->label, $detail['attributes']['Elemen Biaya']);
    }

    public function test_drilldown_biaya_rutin_tahan_baris_kosong(): void
    {
        $detail = (new DrilldownService)->routineCostDetail($this->bareRoutineCost()->id);

        $this->assertSame('-', $detail['attributes']['Chart of Account']);
        $this->assertSame('-', $detail['attributes']['Pusat Biaya']);
    }


    public function test_drilldown_laba_rugi_menormalkan_baris_rkao_dan_rkap(): void
    {
        $statement = ProfitLossStatement::create([
            'erkap_rkap_id' => $this->chain['rkapId'],
            'division_id' => $this->chain['division']->id,
            'period' => 'yearly',
            'total_revenue' => 1000,
            'total_expense' => 800,
            'net_profit' => 200,
            'margin' => 20,
        ]);

        $revenue = ChartOfAccount::factory()
            ->composed($this->costCenter, CostElement::factory()->create([
                'code' => '6001',
                'erkap_cost_element_category_id' => CostElementCategory::factory()->create()->id,
            ]))
            ->revenue()
            ->create(['name' => 'Pendapatan jasa']);

        RevenuePlan::create([
            'erkap_rkap_id' => $this->chain['rkapId'],
            'division_id' => $this->chain['division']->id,
            'chart_of_account_id' => $revenue->id,
            'description' => 'Pendapatan uji',
            'jan_plan' => 1000,
            'total' => 1000,
        ]);

        ExpensePlan::create([
            'erkap_rkap_id' => $this->chain['rkapId'],
            'division_id' => $this->chain['division']->id,
            'chart_of_account_id' => $this->coa->id,
            'description' => 'Beban uji',
            'jan_plan' => 800,
            'total' => 800,
        ]);

        $detail = (new DrilldownService)->pnlDetail($statement->id);

        $this->assertCount(2, $detail['rows']);

        // Kedua tabel punya jumlah kolom berbeda (`prior_year_amount` hanya di
        // RKAO). Setelah dinormalkan, heading export pun tidak bergeser.
        $columns = array_keys($detail['rows']->first());
        $this->assertSame($columns, array_keys($detail['rows']->last()));

        $this->assertSame(['jenis', 'divisi', 'chart_of_account', 'keterangan', 'total'], $columns);
        $this->assertSame('Pendapatan', $detail['rows']->first()['jenis']);
        $this->assertSame($revenue->label, $detail['rows']->first()['chart_of_account']);
        $this->assertSame('Beban', $detail['rows']->last()['jenis']);
        $this->assertSame($this->coa->label, $detail['rows']->last()['chart_of_account']);

        $export = new DrilldownExport($detail['rows'], 'P&L');
        $this->assertSame($columns, $export->headings());
        $this->assertCount(2, $export->collection());
    }

    /* ---------------------------------------------------------------------
     | Widget dashboard
     | ------------------------------------------------------------------ */

    public function test_heatmap_pusat_biaya_memakai_label_komposisi(): void
    {
        $routine = $this->routineCost();
        BudgetRealization::factory()->opex($routine->id)->create([
            'erkap_rkap_id' => $this->chain['rkapId'],
            'month' => 1,
            'year' => 2026,
            'budgeted' => 500,
            'realized' => 450,
        ]);

        $rows = collect((new CostCenterHeatmapService)->data($this->chain['rkapId'], 2026)['rows']);

        $this->assertSame($this->costCenter->label, $rows->first()['name']);
    }

    public function test_top_variance_memakai_label_elemen_biaya(): void

    {
        $routine = $this->routineCost();

        BudgetRealization::factory()->opex($routine->id)->create([
            'erkap_rkap_id' => $this->chain['rkapId'],
            'month' => 1,
            'year' => 2026,
            'budgeted' => 500,
            'realized' => 450,
        ]);

        $data = (new TopVarianceService)->data($this->chain['rkapId'], 2026);

        $this->assertSame($this->costElement->label, $data['rows'][0]['name']);
    }

    /* ---------------------------------------------------------------------
     | Helper
     | ------------------------------------------------------------------ */

    private function routineCost(): RoutineCost
    {
        return RoutineCost::create([
            'erkap_work_program_id' => $this->chain['workProgram']->id,
            'need' => 'Kebutuhan pelaporan',
            'cost_center_id' => $this->costCenter->id,
            'cost_center_owner' => 'Kepala Departemen',
            'qty' => 1,
            'units' => 'Unit',
            'unit_price' => 1000,
            'erkap_cost_element_id' => $this->costElement->id,
            'chart_of_account_id' => $this->coa->id,
            'jan_cost' => 1000,
            'total' => 1000,
            'status' => 'draft',
        ]);
    }

    /**
     * Biaya rutin yang belum terklasifikasi: elemen biaya ada, tapi belum ada
     * COA hasil komposisi untuk Pusat Biaya tersebut. `cost_center_id` dan
     * `chart_of_account_id` nullable sejak F7, jadi laporan harus tetap jalan.
     */
    private function bareRoutineCost(): RoutineCost
    {
        return RoutineCost::create([
            'erkap_work_program_id' => $this->chain['workProgram']->id,
            'need' => 'Biaya belum terklasifikasi',
            'cost_center_id' => null,
            'cost_center_owner' => 'Kepala Departemen',
            'qty' => 1,
            'units' => 'Unit',
            'unit_price' => 1000,
            'erkap_cost_element_id' => $this->orphanElement->id,
            'chart_of_account_id' => null,
            'jan_cost' => 1000,
            'total' => 1000,
            'status' => 'draft',
        ]);
    }
}

