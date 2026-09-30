<?php

namespace Tests\Feature\Erkap;

use App\Models\ChartOfAccount;
use App\Models\Division;
use App\Models\Erkap\BudgetOpex;
use App\Models\Erkap\CostCenter;
use App\Models\Erkap\CostElement;
use App\Models\Erkap\CostElementCategory;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\RoutineCost;
use App\Services\BudgetOpexConsolidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsErkapChain;
use Tests\TestCase;

/**
 * F8 — konsolidasi OPEX memakai COA hasil komposisi.
 *
 * Sebelum F8, `BudgetOpexConsolidationService` mencari COA lewat
 * `CostElement::chart_of_account_id` lalu `coaSuggestion()`. Keduanya menunjuk
 * satu COA sembarang dari elemen biaya, sehingga baris `erkap_budget_opex` bisa
 * menyimpan COA milik Pusat Biaya lain — indikator anggaran dan laporan P&L
 * jadi memakai kode yang tidak sesuai pusat biayanya.
 */
class BudgetOpexConsolidationCompositionTest extends TestCase
{
    use BuildsErkapChain, RefreshDatabase;

    private Division $division;

    private RKAP $rkap;

    private $workProgram;

    private CostCenter $costCenterA;

    private CostCenter $costCenterB;

    private CostElement $expenseElement;

    private CostElement $otherElement;

    protected function setUp(): void
    {
        parent::setUp();

        $chain = $this->buildErkapChain(['year' => 2027, 'code' => 'WP-OPEX']);
        $this->division = $chain['division'];
        $this->workProgram = $chain['workProgram'];
        $this->rkap = RKAP::findOrFail($chain['rkapId']);

        $this->costCenterA = CostCenter::factory()->create(['division_id' => $this->division->id]);
        $this->costCenterB = CostCenter::factory()->create();

        $category = CostElementCategory::factory()->create();
        $this->expenseElement = CostElement::factory()->create([
            'code' => '5001',
            'erkap_cost_element_category_id' => $category->id,
        ]);
        $this->otherElement = CostElement::factory()->create([
            'code' => '5002',
            'erkap_cost_element_category_id' => $category->id,
        ]);

        ChartOfAccount::factory()->composed($this->costCenterA, $this->expenseElement)->create();
        ChartOfAccount::factory()->composed($this->costCenterB, $this->expenseElement)->create();
        ChartOfAccount::factory()->composed($this->costCenterA, $this->otherElement)->create();
    }

    /* ---------------------------------------------------------------------
     | Penurunan COA dari pasangan Pusat Biaya + Elemen Biaya
     | ------------------------------------------------------------------ */

    public function test_coa_turun_dari_pasangan_bukan_dari_elemen_biaya(): void
    {
        // Tautan default elemen diarahkan ke COA milik Pusat Biaya lain: kalau
        // service masih memakainya, BudgetOpex akan menunjuk COA yang salah.
        $foreign = $this->coaFor($this->costCenterB, $this->expenseElement);
        $this->expenseElement->update(['chart_of_account_id' => $foreign->id]);

        $this->routineCost($this->costCenterA, $this->expenseElement, 1000);

        (new BudgetOpexConsolidationService)->consolidate($this->rkap);

        $this->assertDatabaseHas('erkap_budget_opex', [
            'division_id' => $this->division->id,
            'cost_center_id' => $this->costCenterA->id,
            'chart_of_account_id' => $this->coaFor($this->costCenterA, $this->expenseElement)->id,
            'budget_amount' => 1000,
        ]);

        $this->assertDatabaseMissing('erkap_budget_opex', [
            'chart_of_account_id' => $foreign->id,
        ]);
    }

    public function test_chart_of_account_tersimpan_tetap_dihormati(): void
    {
        $explicit = $this->coaFor($this->costCenterB, $this->expenseElement);

        $this->routineCost($this->costCenterA, $this->expenseElement, 750, $explicit->id);

        (new BudgetOpexConsolidationService)->consolidate($this->rkap);

        $this->assertDatabaseHas('erkap_budget_opex', [
            'cost_center_id' => $this->costCenterA->id,
            'chart_of_account_id' => $explicit->id,
            'budget_amount' => 750,
        ]);
    }

    public function test_baris_tanpa_coa_dilewati(): void
    {
        // Pasangan tanpa COA = gap sinkronisasi. Baris ini tidak boleh membuat
        // BudgetOpex dengan COA milik Pusat Biaya lain sebagai "cadangan".
        $orphan = CostElement::factory()->create([
            'code' => '5099',
            'erkap_cost_element_category_id' => CostElementCategory::factory()->create()->id,
        ]);

        $this->routineCost($this->costCenterA, $orphan, 500);

        $this->assertSame(0, (new BudgetOpexConsolidationService)->consolidate($this->rkap));
        $this->assertDatabaseCount('erkap_budget_opex', 0);
    }

    public function test_baris_tanpa_pusat_biaya_dilewati(): void
    {
        $this->routineCost(null, $this->expenseElement, 500);

        $this->assertSame(0, (new BudgetOpexConsolidationService)->consolidate($this->rkap));
        $this->assertDatabaseCount('erkap_budget_opex', 0);
    }

    /* ---------------------------------------------------------------------
     | Pengelompokan
     | ------------------------------------------------------------------ */

    public function test_elemen_berbeda_pada_pusat_biaya_sama_terpisah(): void
    {
        $this->routineCost($this->costCenterA, $this->expenseElement, 100);
        $this->routineCost($this->costCenterA, $this->otherElement, 250);
        $this->routineCost($this->costCenterA, $this->expenseElement, 50);

        $this->assertSame(2, (new BudgetOpexConsolidationService)->consolidate($this->rkap));

        $this->assertDatabaseHas('erkap_budget_opex', [
            'chart_of_account_id' => $this->coaFor($this->costCenterA, $this->expenseElement)->id,
            'budget_amount' => 150,
        ]);
        $this->assertDatabaseHas('erkap_budget_opex', [
            'chart_of_account_id' => $this->coaFor($this->costCenterA, $this->otherElement)->id,
            'budget_amount' => 250,
        ]);
    }

    public function test_pusat_biaya_berbeda_dengan_elemen_sama_terpisah(): void
    {
        $this->routineCost($this->costCenterA, $this->expenseElement, 100);
        $this->routineCost($this->costCenterB, $this->expenseElement, 400);

        $this->assertSame(2, (new BudgetOpexConsolidationService)->consolidate($this->rkap));

        $this->assertDatabaseHas('erkap_budget_opex', [
            'cost_center_id' => $this->costCenterA->id,
            'budget_amount' => 100,
        ]);
        $this->assertDatabaseHas('erkap_budget_opex', [
            'cost_center_id' => $this->costCenterB->id,
            'budget_amount' => 400,
        ]);
    }

    public function test_konsolidasi_ulang_tidak_menggandakan_baris(): void
    {
        $this->routineCost($this->costCenterA, $this->expenseElement, 100);
        $this->routineCost($this->costCenterA, $this->expenseElement, 100);

        $service = new BudgetOpexConsolidationService;

        $service->consolidate($this->rkap);
        $service->consolidate($this->rkap);

        $this->assertSame(1, BudgetOpex::count());
        $this->assertDatabaseHas('erkap_budget_opex', ['budget_amount' => 200]);
    }

    /* ---------------------------------------------------------------------
     | Laporan
     | ------------------------------------------------------------------ */

    public function test_laporan_kelompokkan_per_divisi_dan_bawa_label_komposisi(): void
    {
        $this->routineCost($this->costCenterA, $this->expenseElement, 300);

        (new BudgetOpexConsolidationService)->consolidate($this->rkap);

        $report = (new BudgetOpexConsolidationService)->getReport($this->rkap);

        $this->assertCount(1, $report);

        $group = $report->get($this->division->id);

        $this->assertSame($this->division->name, $group['division']->name);
        $this->assertSame(300.0, (float) $group['totals']['budget']);

        $item = $group['items']->first();
        $expected = $this->coaFor($this->costCenterA, $this->expenseElement);

        $this->assertSame($this->costCenterA->id, $item['cost_center']->id);
        $this->assertSame($expected->id, $item['chart_of_account']->id);
        $this->assertSame($expected->label, $item['chart_of_account']->label);
        $this->assertSame(15, strlen((string) $expected->code));
        $this->assertStringContainsString($expected->formatted_code, $expected->label);
    }

    public function test_laporan_bisa_dibatasi_satu_divisi(): void
    {
        $this->routineCost($this->costCenterA, $this->expenseElement, 300);

        (new BudgetOpexConsolidationService)->consolidate($this->rkap);

        $otherDivision = Division::factory()->create();
        $report = (new BudgetOpexConsolidationService)->getReport($this->rkap, $otherDivision->id);

        $this->assertCount(0, $report);
    }

    /* ---------------------------------------------------------------------
     | Helper
     | ------------------------------------------------------------------ */

    private function coaFor(CostCenter $costCenter, CostElement $element): ChartOfAccount
    {
        return ChartOfAccount::query()->forPair($costCenter->id, $element->id)->firstOrFail();
    }

    private function routineCost(
        ?CostCenter $costCenter,
        CostElement $element,
        float $total,
        ?int $chartOfAccountId = null
    ): RoutineCost {
        return RoutineCost::create([
            'erkap_work_program_id' => $this->workProgram->id,
            'need' => 'Kebutuhan '.uniqid(),
            'cost_center_id' => $costCenter?->id,
            'cost_center_owner' => 'Kepala Departemen',
            'qty' => 1,
            'units' => 'Unit',
            'unit_price' => $total,
            'erkap_cost_element_id' => $element->id,
            'chart_of_account_id' => $chartOfAccountId,
            'jan_cost' => $total,
            'total' => $total,
            'status' => 'draft',
        ]);
    }
}
