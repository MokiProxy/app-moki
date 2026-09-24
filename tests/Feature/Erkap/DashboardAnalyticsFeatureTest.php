<?php

namespace Tests\Feature\Erkap;

use App\Exports\Erkap\ConsolidatedRkapExport;
use App\Models\ChartOfAccount;
use App\Models\Division;
use App\Models\Erkap\BudgetCapex;
use App\Models\Erkap\BudgetRealization;
use App\Models\Erkap\CostCenter;
use App\Models\Erkap\CostElement;
use App\Models\Erkap\CostElementCategory;
use App\Models\Erkap\ExpensePlan;
use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\ReportItem;
use App\Models\Erkap\RevenuePlan;
use App\Models\Erkap\RiskAssessmentMonthly;
use App\Models\Erkap\RoutineCost;
use App\Services\Reporting\ReportGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\ActsAsSuperAdmin;
use Tests\Concerns\BuildsErkapChain;
use Tests\TestCase;

class DashboardAnalyticsFeatureTest extends TestCase
{
    use RefreshDatabase, ActsAsSuperAdmin, BuildsErkapChain;

    private array $chain;
    private RoutineCost $routineCost;
    private InvestmentPlan $investmentPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSuperAdmin();
        $this->chain = $this->buildErkapChain(['year' => 2026]);

        $costCenter = CostCenter::factory()->create(['code' => 'CC-FEA']);
        $costElement = CostElement::factory()->create([
            'code' => 'CE-FEA',
            'erkap_cost_element_category_id' => CostElementCategory::factory()->create()->id,
        ]);

        $this->routineCost = RoutineCost::factory()->create([
            'erkap_work_program_id' => $this->chain['workProgram']->id,
            'cost_center_id' => $costCenter->id,
            'erkap_cost_element_id' => $costElement->id,
            'total' => 1000,
            'jan_cost' => 1000,
        ]);

        $this->investmentPlan = InvestmentPlan::factory()->create([
            'erkap_work_program_id' => $this->chain['workProgram']->id,
            'total' => 2000,
            'jan_plan' => 2000,
            'qty' => 1,
            'unit_price' => 2000,
        ]);

        BudgetRealization::factory()->opex($this->routineCost->id)->create([
            'erkap_rkap_id' => $this->chain['rkapId'],
            'month' => 1,
            'year' => 2026,
            'budgeted' => 500,
            'realized' => 450,
        ]);

        $coa = ChartOfAccount::create(['code' => 'REV-FEA', 'name' => 'Pendapatan', 'type' => 'revenue']);
        $coaExpense = ChartOfAccount::create(['code' => 'EXP-FEA', 'name' => 'Beban', 'type' => 'expense']);

        RevenuePlan::create([
            'erkap_rkap_id' => $this->chain['rkapId'],
            'division_id' => $this->chain['division']->id,
            'chart_of_account_id' => $coa->id,
            'description' => 'Pendapatan Uji',
            'jan_plan' => 1000,
            'total' => 1000,
        ]);

        ExpensePlan::create([
            'erkap_rkap_id' => $this->chain['rkapId'],
            'division_id' => $this->chain['division']->id,
            'chart_of_account_id' => $coaExpense->id,
            'description' => 'Beban Uji',
            'jan_plan' => 800,
            'total' => 800,
        ]);

        RiskAssessmentMonthly::create([
            'erkap_risk_identification_id' => $this->chain['risk']->id,
            'month' => 1,
            'year' => 2026,
            'inherent_probability' => 3,
            'inherent_impact' => 4,
            'mitigation_status' => 'on_progress',
        ]);
    }

    public function test_analytics_page_renders_all_widgets(): void
    {
        $response = $this->get(route('erkap.dashboard.widgets', ['rkap_id' => $this->chain['rkapId'], 'year' => 2026]));

        $response->assertOk();
        $response->assertViewHas('cashFlow');
        $response->assertViewHas('riskAppetite');
        $response->assertViewHas('riskTrend');
        $response->assertViewHas('gantt');
        $response->assertViewHas('dependencyMap');
        $response->assertViewHas('costCenterHeatmap');
        $response->assertViewHas('topVariance');
        $response->assertViewHas('revenueBreakdown');
        $response->assertViewHas('expenseBreakdown');
        $response->assertViewHas('scenarioComparison');
        $response->assertSee('chart-cash-flow');
        $response->assertSee('chart-cost-center-heatmap');
    }

    public function test_routine_cost_drilldown_page_renders_rows(): void
    {
        $response = $this->get(route('erkap.dashboard.drilldown', ['type' => 'routine-cost', 'id' => $this->routineCost->id]));

        $response->assertOk();
        $response->assertSee('Detail Biaya Rutin');
        $response->assertSee($this->routineCost->need);
    }

    public function test_realization_drilldown_page_renders(): void
    {
        $realization = $this->routineCost->budgetRealizations->first();

        $response = $this->get(route('erkap.dashboard.drilldown', ['type' => 'realization', 'id' => $realization->id]));

        $response->assertOk();
        $response->assertSee('Detail Realisasi Anggaran');
    }

    public function test_budget_drilldown_page_renders_investment_summary(): void
    {
        $budgetCapex = BudgetCapex::create([
            'erkap_rkap_id' => $this->chain['rkapId'],
            'division_id' => $this->chain['division']->id,
            'total_investment' => 2000,
            'status' => 'approved',
            'notes' => 'Ringkasan investasi',
        ]);

        $response = $this->get(route('erkap.dashboard.drilldown', ['type' => 'budget', 'id' => $budgetCapex->id]));

        $response->assertOk();
        $response->assertSee('Detail Ringkasan Investasi (Budget CAPEX)');
    }

    public function test_pnl_drilldown_page_renders_revenue_and_expense_rows(): void
    {
        $statement = \App\Models\Erkap\ProfitLossStatement::create([
            'erkap_rkap_id' => $this->chain['rkapId'],
            'division_id' => $this->chain['division']->id,
            'period' => 'yearly',
            'total_revenue' => 1000,
            'total_expense' => 800,
            'net_profit' => 200,
            'margin' => 20,
        ]);

        $response = $this->get(route('erkap.dashboard.drilldown', ['type' => 'pnl', 'id' => $statement->id]));

        $response->assertOk();
        $response->assertSee('Detail Laba Rugi (P&L)');
        $response->assertSee('Pendapatan Uji');
        $response->assertSee('Beban Uji');
    }

    public function test_drilldown_export_returns_excel(): void
    {
        Excel::fake();

        $response = $this->get(route('erkap.dashboard.drilldown.export', ['type' => 'routine-cost', 'id' => $this->routineCost->id]));

        $response->assertOk();
        Excel::assertDownloaded('drilldown-routine-cost-' . $this->routineCost->id . '.xlsx');
    }

    public function test_report_center_list_and_previews(): void
    {
        ReportItem::factory()->create(['title' => 'Laporan Perfomance Test', 'file_path' => 'reports/test.pdf']);

        $this->get(route('erkap.reports.index'))->assertOk()->assertSee('Laporan Perfomance Test');

        foreach (ReportGenerator::TYPES as $type) {
            $response = $this->get(route('erkap.reports.preview', ['type' => $type, 'year' => 2026]));
            $response->assertOk();
        }
    }

    public function test_manual_report_generation_persists_report_item(): void
    {
        Storage::fake('public');
        Excel::fake();

        $response = $this->post(route('erkap.reports.generate'), [
            'report_type' => 'realization',
            'format' => 'excel',
            'rkap_id' => $this->chain['rkapId'],
            'year' => 2026,
            'month' => 1,
        ]);

        $response->assertRedirect(route('erkap.reports.index'));

        $this->assertDatabaseHas('erkap_report_items', [
            'report_type' => 'realization',
            'status' => 'generated',
            'year' => 2026,
        ]);
    }

    public function test_consolidated_export_contains_eight_sheets(): void
    {
        Excel::fake();

        $rkap = RKAP::find($this->chain['rkapId']);
        $response = $this->get(route('erkap.dashboard.export-consolidated', ['rkap_id' => $rkap->id, 'year' => 2026]));

        $response->assertOk();
        Excel::assertDownloaded('konsolidasi-rkap-2026.xlsx', function (ConsolidatedRkapExport $export) use ($rkap) {
            return count($export->sheets()) === 8
                && $export->sheets()[0]->title() === 'Form 1'
                && $export->sheets()[6]->title() === 'Konsolidasi RKAP';
        });
    }
}