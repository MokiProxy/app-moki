<?php

namespace Tests\Unit\Erkap;

use App\Models\ChartOfAccount;
use App\Models\Erkap\BudgetRealization;
use App\Models\Erkap\CostCenter;
use App\Models\Erkap\CostElement;
use App\Models\Erkap\CostElementCategory;
use App\Models\Erkap\ExpensePlan;
use App\Models\Erkap\RevenuePlan;
use App\Models\Erkap\RiskAssessmentMonthly;
use App\Models\Erkap\RoutineCost;
use App\Services\Dashboard\CashFlowWidgetService;
use App\Services\Dashboard\CostCenterHeatmapService;
use App\Services\Dashboard\RiskAppetiteWidgetService;
use App\Services\Dashboard\RiskTrendWidgetService;
use App\Services\Dashboard\ScenarioComparisonService;
use App\Services\Dashboard\TopVarianceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ActsAsSuperAdmin;
use Tests\Concerns\BuildsErkapChain;
use Tests\TestCase;

class DashboardWidgetServicesTest extends TestCase
{
    use RefreshDatabase, BuildsErkapChain, ActsAsSuperAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSuperAdmin();
    }

    public function test_cashflow_service_computes_operating_and_investing(): void
    {
        $chain = $this->buildErkapChain(['year' => 2026]);
        $rkap = \App\Models\Erkap\RKAP::find($chain['rkapId']);
        $division = $chain['division'];
        $coa = ChartOfAccount::create(['code' => 'REV-001', 'name' => 'Pendapatan', 'type' => 'revenue']);
        $coaExpense = ChartOfAccount::create(['code' => 'EXP-001', 'name' => 'Beban', 'type' => 'expense']);

        RevenuePlan::create([
            'erkap_rkap_id' => $chain['rkapId'],
            'division_id' => $division->id,
            'chart_of_account_id' => $coa->id,
            'description' => 'Pendapatan A',
            'jan_plan' => 1000,
            'feb_plan' => 1000,
            'total' => 2000,
        ]);

        ExpensePlan::create([
            'erkap_rkap_id' => $chain['rkapId'],
            'division_id' => $division->id,
            'chart_of_account_id' => $coaExpense->id,
            'description' => 'Beban A',
            'jan_plan' => 400,
            'feb_plan' => 400,
            'total' => 800,
        ]);

        $investmentPlan = \App\Models\Erkap\InvestmentPlan::factory()->create([
            'erkap_work_program_id' => $chain['workProgram']->id,
            'total' => 500,
            'jan_plan' => 500,
            'qty' => 1,
            'unit_price' => 500,
        ]);

        BudgetRealization::factory()->capex($investmentPlan->id)->create([
            'erkap_rkap_id' => $chain['rkapId'],
            'month' => 2,
            'year' => 2026,
            'realized' => 300,
        ]);

        $data = app(CashFlowWidgetService::class)->data($chain['rkapId'], 2026);

        $this->assertSame(600.0, $data['operating'][0]); // pendapatan - beban
        $this->assertSame(-300.0, $data['investing'][1]);
        $this->assertSame(300.0, $data['net'][1]);
        $this->assertSame(1200.0, $data['totals']['operating']);
    }

    public function test_risk_trend_service_buckets_scores_into_levels(): void
    {
        $chain = $this->buildErkapChain(['year' => 2026]);

        RiskAssessmentMonthly::create([
            'erkap_risk_identification_id' => $chain['risk']->id,
            'month' => 3,
            'year' => 2026,
            'inherent_probability' => 4,
            'inherent_impact' => 4,
            'mitigation_status' => 'on_progress',
        ]);
        RiskAssessmentMonthly::create([
            'erkap_risk_identification_id' => $chain['risk']->id,
            'month' => 4,
            'year' => 2026,
            'inherent_probability' => 2,
            'inherent_impact' => 2,
            'mitigation_status' => 'on_progress',
        ]);

        $data = app(RiskTrendWidgetService::class)->data($chain['rkapId'], 2026);

        $this->assertSame(1, $data['series']['H'][2]);  // 16
        $this->assertSame(1, $data['series']['VL'][3]);  // 4
        $this->assertSame(0, $data['series']['VH'][0]);
    }

    public function test_risk_appetite_service_detects_breach_against_threshold(): void
    {
        $chain = $this->buildErkapChain(['year' => 2026, 'threshold' => 12]);

        RiskAssessmentMonthly::create([
            'erkap_risk_identification_id' => $chain['risk']->id,
            'month' => 1,
            'year' => 2026,
            'inherent_probability' => 5,
            'inherent_impact' => 5,
            'mitigation_status' => 'on_progress',
        ]);
        RiskAssessmentMonthly::create([
            'erkap_risk_identification_id' => $chain['risk']->id,
            'month' => 2,
            'year' => 2026,
            'inherent_probability' => 1,
            'inherent_impact' => 1,
            'mitigation_status' => 'on_progress',
        ]);

        $data = app(RiskAppetiteWidgetService::class)->data($chain['rkapId'], 2026);

        $this->assertNotEmpty($data['breaches']);
        $this->assertSame(50.0, $data['breaches'][0]['percentage']);
        $this->assertSame('yellow', $data['breaches'][0]['zone']);
        $this->assertSame('yellow', $data['gauge']['zone']);
        $this->assertSame(50.0, $data['gauge']['value']);
    }

    public function test_cost_center_heatmap_groups_variance_by_cost_center(): void
    {
        $chain = $this->buildErkapChain(['year' => 2026]);
        $costCenter = CostCenter::factory()->create(['code' => 'CC-HT']);
        $costElement = CostElement::factory()->create([
            'code' => 'CE-HT',
            'erkap_cost_element_category_id' => CostElementCategory::factory()->create()->id,
        ]);

        $routineCost = RoutineCost::factory()->create([
            'erkap_work_program_id' => $chain['workProgram']->id,
            'cost_center_id' => $costCenter->id,
            'erkap_cost_element_id' => $costElement->id,
            'total' => 1000,
        ]);

        BudgetRealization::factory()->opex($routineCost->id)->create([
            'erkap_rkap_id' => $chain['rkapId'],
            'month' => 5,
            'year' => 2026,
            'budgeted' => 100,
            'realized' => 130,
        ]);

        $data = app(CostCenterHeatmapService::class)->data($chain['rkapId'], 2026);

        $this->assertCount(1, $data['rows']);
        $this->assertSame($costCenter->name, $data['rows'][0]['name']);
        $this->assertSame(30.0, $data['rows'][0]['cells'][4]['variance_percent']);
    }

    public function test_top_variance_service_highlights_capex_bucket(): void
    {
        $chain = $this->buildErkapChain(['year' => 2026]);

        $investmentPlan = \App\Models\Erkap\InvestmentPlan::factory()->create([
            'erkap_work_program_id' => $chain['workProgram']->id,
            'total' => 1000,
            'jan_plan' => 1000,
            'qty' => 1,
            'unit_price' => 1000,
        ]);

        BudgetRealization::factory()->capex($investmentPlan->id)->create([
            'erkap_rkap_id' => $chain['rkapId'],
            'month' => 1,
            'year' => 2026,
            'budgeted' => 1000,
            'realized' => 1500,
        ]);

        $data = app(TopVarianceService::class)->data($chain['rkapId'], 2026);

        $this->assertCount(1, $data['rows']);
        $this->assertSame('capex', $data['rows'][0]['type']);
        $this->assertSame(500.0, $data['rows'][0]['variance']);
    }

    public function test_scenario_comparison_applies_multipliers(): void
    {
        $chain = $this->buildErkapChain(['year' => 2026]);
        $division = $chain['division'];
        $coa = ChartOfAccount::create(['code' => 'REV-002', 'name' => 'Pendapatan', 'type' => 'revenue']);
        $coaExpense = ChartOfAccount::create(['code' => 'EXP-002', 'name' => 'Beban', 'type' => 'expense']);

        RevenuePlan::create([
            'erkap_rkap_id' => $chain['rkapId'],
            'division_id' => $division->id,
            'chart_of_account_id' => $coa->id,
            'description' => 'Pendapatan X',
            'jan_plan' => 1000,
            'total' => 1000,
        ]);

        ExpensePlan::create([
            'erkap_rkap_id' => $chain['rkapId'],
            'division_id' => $division->id,
            'chart_of_account_id' => $coaExpense->id,
            'description' => 'Beban X',
            'jan_plan' => 400,
            'total' => 400,
        ]);

        $data = app(ScenarioComparisonService::class)->data($chain['rkapId']);

        $this->assertSame(1100.0, $data['scenarios']['best']['revenue']);
        $this->assertSame(440.0, $data['scenarios']['best']['expense']);
        $this->assertSame(660.0, $data['scenarios']['best']['profit']);
        $this->assertSame(900.0, $data['scenarios']['worst']['revenue']);
    }
}