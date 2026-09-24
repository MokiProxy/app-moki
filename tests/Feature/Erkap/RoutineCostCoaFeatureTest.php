<?php

namespace Tests\Feature\Erkap;

use App\Models\ChartOfAccount;
use App\Models\Division;
use App\Models\Erkap\CompanyTarget;
use App\Models\Erkap\CostCenter;
use App\Models\Erkap\CostElement;
use App\Models\Erkap\CostElementCategory;
use App\Models\Erkap\DepartmentRiskStrategy;
use App\Models\Erkap\DepartmentTarget;
use App\Models\Erkap\RatingCriteria;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RiskType;
use App\Models\Erkap\RiskTaxonomy;
use App\Models\Erkap\RoutineCost;
use App\Models\Erkap\WorkProgram;
use App\Services\BudgetOpexConsolidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ActsAsSuperAdmin;
use Tests\TestCase;

class RoutineCostCoaFeatureTest extends TestCase
{
    use RefreshDatabase, ActsAsSuperAdmin;

    private WorkProgram $workProgram;
    private CostCenter $costCenter;
    private CostElement $costElement;
    private Division $division;
    private RKAP $rkap;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSuperAdmin();

        $this->division = Division::factory()->create();
        $ratingA = RatingCriteria::factory()->create(['rating' => 'A', 'qualification' => 'Sangat Baik']);

        $this->rkap = RKAP::factory()->create(['year' => 2026]);

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

        $this->workProgram = WorkProgram::create([
            'erkap_risk_identification_id' => $risk->id,
            'code' => 'WP-COA',
            'name' => 'Program Uji CoA',
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

        $this->costCenter = CostCenter::factory()->create(['code' => 'CC-COA']);
        $this->costElement = CostElement::factory()->create([
            'code' => 'CE-COA',
            'erkap_cost_element_category_id' => CostElementCategory::factory()->create()->id,
        ]);
    }

    private function storePayload(array $overrides = []): array
    {
        return array_merge([
            'erkap_work_program_id' => $this->workProgram->id,
            'need' => 'Kebutuhan Uji CoA',
            'cost_center_id' => $this->costCenter->id,
            'cost_center_owner' => 'Owner Uji',
            'qty' => 12,
            'units' => 'Unit',
            'unit_price' => 1000,
            'erkap_cost_element_id' => $this->costElement->id,
            'jan_cost' => 1000,
            'feb_cost' => 1000,
            'mar_cost' => 1000,
            'apr_cost' => 1000,
            'may_cost' => 1000,
            'jun_cost' => 1000,
            'jul_cost' => 1000,
            'aug_cost' => 1000,
            'sep_cost' => 1000,
            'oct_cost' => 1000,
            'nov_cost' => 1000,
            'des_cost' => 1000,
            'total' => 12000,
            'is_kumulatif' => 0,
        ], $overrides);
    }

    public function test_store_saves_explicit_chart_of_account_selection(): void
    {
        $coa = ChartOfAccount::factory()->create(['code' => '7000000000000000']);

        $this->post(route('erkap.routine-costs.store'), $this->storePayload([
            'chart_of_account_id' => $coa->id,
        ]))->assertRedirect(route('erkap.routine-costs.index'));

        $this->assertDatabaseHas('erkap_routine_costs', [
            'erkap_work_program_id' => $this->workProgram->id,
            'erkap_cost_element_id' => $this->costElement->id,
            'chart_of_account_id' => $coa->id,
            'total' => 12000,
        ]);
    }

    public function test_store_falls_back_to_cost_element_chart_of_account(): void
    {
        $coa = ChartOfAccount::factory()->create(['code' => '7001000000000000']);
        $this->costElement->update(['chart_of_account_id' => $coa->id]);

        $this->post(route('erkap.routine-costs.store'), $this->storePayload())
            ->assertRedirect(route('erkap.routine-costs.index'));

        $this->assertDatabaseHas('erkap_routine_costs', [
            'erkap_work_program_id' => $this->workProgram->id,
            'chart_of_account_id' => $coa->id,
        ]);
    }

    public function test_consolidation_uses_explicit_coa_over_cost_element_coa(): void
    {
        $elementCoa = ChartOfAccount::factory()->create(['code' => '7100000000000000']);
        $explicitCoa = ChartOfAccount::factory()->create(['code' => '7200000000000000']);
        $this->costElement->update(['chart_of_account_id' => $elementCoa->id]);

        RoutineCost::factory()->create([
            'erkap_work_program_id' => $this->workProgram->id,
            'cost_center_id' => $this->costCenter->id,
            'cost_center_owner' => 'Owner',
            'erkap_cost_element_id' => $this->costElement->id,
            'chart_of_account_id' => $explicitCoa->id,
            'total' => 50000,
            'status' => 'draft',
        ]);

        $count = (new BudgetOpexConsolidationService())->consolidate($this->rkap);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('erkap_budget_opex', [
            'erkap_rkap_id' => $this->rkap->id,
            'division_id' => $this->division->id,
            'cost_center_id' => $this->costCenter->id,
            'chart_of_account_id' => $explicitCoa->id,
            'budget_amount' => 50000,
        ]);
    }

    public function test_factory_produces_sixteen_digit_codes(): void
    {
        $coa = ChartOfAccount::factory()->create();

        $this->assertMatchesRegularExpression('/^\d{16}$/', $coa->code);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{4}-\d{4}-\d{4}$/', $coa->formattedCode);
    }
}