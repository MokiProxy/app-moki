<?php

namespace Tests\Feature\Erkap;

use App\Models\Division;
use App\Models\Erkap\BudgetCapex;
use App\Models\Erkap\CompanyTarget;
use App\Models\Erkap\DepartmentRiskStrategy;
use App\Models\Erkap\DepartmentTarget;
use App\Models\Erkap\InvestattionCategory;
use App\Models\Erkap\InvestationCriteria;
use App\Models\Erkap\InvestationType;
use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\RatingCriteria;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RiskType;
use App\Models\Erkap\RiskTaxonomy;
use App\Models\Erkap\WorkProgram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ActsAsSuperAdmin;
use Tests\TestCase;

class BudgetCapexFeatureTest extends TestCase
{
    use RefreshDatabase, ActsAsSuperAdmin;

    private RKAP $rkap;
    private Division $division;
    private InvestmentPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSuperAdmin();

        $this->rkap = RKAP::factory()->create(['year' => 2026, 'status' => 'draft']);
        $this->division = Division::factory()->create();
        $ratingA = RatingCriteria::factory()->create(['rating' => 'A', 'qualification' => 'Sangat Baik']);

        $companyTarget = CompanyTarget::factory()->create([
            'erkap_rkap_id' => $this->rkap->id,
        ]);

        $deptTarget = DepartmentTarget::factory()->create([
            'division_id' => $this->division->id,
            'erkap_rating_criteria_id' => $ratingA->id,
            'erkap_company_target_id' => $companyTarget->id,
        ]);

        $risk = RiskIdentification::factory()->create([
            'erkap_department_target_id' => $deptTarget->id,
            'erkap_risk_type_id' => RiskType::factory()->create()->id,
            'erkap_risk_taxonomy_id' => RiskTaxonomy::factory()->create()->id,
        ]);

        DepartmentRiskStrategy::create([
            'erkap_risk_identification_id' => $risk->id,
            'strategy' => 'reduction',
        ]);

        $workProgram = WorkProgram::create([
            'erkap_risk_identification_id' => $risk->id,
            'code' => 'WP-CAPEX',
            'name' => 'Program Investasi',
            'units' => 'Unit',
            'year_plan' => 120,
            'jan_plan' => 10,
            'feb_plan' => 10,
            'mar_plan' => 10,
            'apr_plan' => 10,
            'may_plan' => 10,
            'jun_plan' => 10,
            'jul_plan' => 10,
            'aug_plan' => 10,
            'sep_plan' => 10,
            'oct_plan' => 10,
            'nov_plan' => 10,
            'dec_plan' => 10,
            'status' => 'draft',
        ]);

        BudgetCapex::create([
            'erkap_rkap_id' => $this->rkap->id,
            'division_id' => $this->division->id,
            'total_investment' => 120000,
            'status' => 'draft',
        ]);

        $this->plan = $this->createPlan($workProgram);
    }

    private function createPlan(WorkProgram $workProgram): InvestmentPlan
    {
        return InvestmentPlan::create([
            'erkap_work_program_id' => $workProgram->id,
            'erkap_investattion_category_id' => InvestattionCategory::factory()->create()->id,
            'erkap_investation_type_id' => InvestationType::factory()->create()->id,
            'erkap_investation_criteria_id' => InvestationCriteria::factory()->create()->id,
            'name' => 'Server Rack',
            'unit' => 'Unit',
            'qty' => 2,
            'unit_price' => 60000,
            'jan_plan' => 20000,
            'feb_plan' => 20000,
            'mar_plan' => 20000,
            'apr_plan' => 20000,
            'may_plan' => 20000,
            'jun_plan' => 20000,
            'jul_plan' => 0,
            'aug_plan' => 0,
            'sep_plan' => 0,
            'oct_plan' => 0,
            'nov_plan' => 0,
            'dec_plan' => 0,
            'total' => 120000,
            'is_kumulatif' => false,
            'status' => 'draft',
        ]);
    }

    public function test_summary_groups_investment_plans_by_division(): void
    {
        $response = $this->get(route('erkap.budget-capex.summary', ['erkap_rkap_id' => $this->rkap->id]));

        $response->assertOk();
        $response->assertViewHas('rows');
        $response->assertViewHas('byDivision');

        $rows = $response->viewData('rows');
        $this->assertCount(1, $rows);
        $this->assertSame($this->division->name, $rows->first()['division']);
        $this->assertSame(120000.0, $rows->first()['plan_total']);
        $this->assertSame(120000.0, $rows->first()['budget_current_year']);
    }

    public function test_payment_distribution_aggregates_monthly_totals(): void
    {
        $response = $this->get(route('erkap.budget-capex.payment-distribution', ['erkap_rkap_id' => $this->rkap->id]));

        $response->assertOk();
        $response->assertViewHas('totalByMonth');
        $response->assertViewHas('grandTotal');

        $totalByMonth = $response->viewData('totalByMonth');
        $this->assertSame(20000.0, $totalByMonth['jan_plan']);
        $this->assertSame(20000.0, $totalByMonth['jun_plan']);
        $this->assertSame(0.0, $totalByMonth['dec_plan']);
        $this->assertSame(120000.0, $response->viewData('grandTotal'));
    }

    public function test_index_lists_budget_capex(): void
    {
        $response = $this->get(route('erkap.budget-capex.index'));

        $response->assertOk();
        $response->assertViewHas('budgetCapex');
        $this->assertSame(1, $response->viewData('budgetCapex')->total());
    }
}