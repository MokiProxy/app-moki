<?php

namespace Tests\Unit\Erkap;

use App\Models\ChartOfAccount;
use App\Models\Division;
use App\Models\Erkap\BudgetOpex;
use App\Models\Erkap\CompanyTarget;
use App\Models\Erkap\CostElement;
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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BudgetOpexTest extends TestCase
{
    use RefreshDatabase;

    private RKAP $rkap;
    private Division $division;
    private CostElement $costElement;
    private WorkProgram $workProgram;
    private \App\Models\Erkap\CostCenter $costCenter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->division = Division::factory()->create();
        $ratingA = RatingCriteria::factory()->create(['rating' => 'A', 'qualification' => 'Sangat Baik']);

        $this->rkap = RKAP::factory()->create(['year' => 2025]);

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
            'strategy' => 'avoidance',
        ]);

        $this->workProgram = WorkProgram::create([
            'erkap_risk_identification_id' => $risk->id,
            'code' => 'WP-CONS',
            'name' => 'Program Konsolidasi',
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

        $this->costCenter = \App\Models\Erkap\CostCenter::factory()->create(['code' => 'CC-CONS']);

        $chartOfAccount = ChartOfAccount::create([
            'code' => 'COA-OPEX',
            'name' => 'Beban Operasional',
            'type' => 'expense',
        ]);

        $this->costElement = CostElement::create([
            'code' => 'CE-CONS',
            'name' => 'Elemen Konsolidasi',
            'erkap_cost_element_category_id' => \App\Models\Erkap\CostElementCategory::factory()->create()->id,
            'chart_of_account_id' => $chartOfAccount->id,
        ]);
    }

    public function test_budget_opex_can_be_created(): void
    {
        $opex = BudgetOpex::create([
            'erkap_rkap_id' => $this->rkap->id,
            'division_id' => $this->division->id,
            'cost_center_id' => $this->costCenter->id,
            'chart_of_account_id' => $this->costElement->chart_of_account_id,
            'budget_amount' => 100000,
            'realization_amount' => 0,
            'variance' => 0,
            'status' => 'draft',
        ]);

        $this->assertNotNull($opex->id);
        $this->assertEquals(100000, $opex->fresh()->budget_amount);
    }

    public function test_budget_opex_unique_constraint(): void
    {
        BudgetOpex::create([
            'erkap_rkap_id' => $this->rkap->id,
            'division_id' => $this->division->id,
            'cost_center_id' => $this->costCenter->id,
            'chart_of_account_id' => $this->costElement->chart_of_account_id,
            'budget_amount' => 100000,
            'status' => 'draft',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        BudgetOpex::create([
            'erkap_rkap_id' => $this->rkap->id,
            'division_id' => $this->division->id,
            'cost_center_id' => $this->costCenter->id,
            'chart_of_account_id' => $this->costElement->chart_of_account_id,
            'budget_amount' => 50000,
            'status' => 'draft',
        ]);
    }

    public function test_consolidation_service_aggregates_routine_cost(): void
    {
        RoutineCost::create([
            'erkap_work_program_id' => $this->workProgram->id,
            'need' => 'Kebutuhan Operasional',
            'cost_center_id' => $this->costCenter->id,
            'cost_center_owner' => 'Owner',
            'qty' => 10,
            'units' => 'Unit',
            'unit_price' => 10000,
            'erkap_cost_element_id' => $this->costElement->id,
            'jan_cost' => 10000,
            'feb_cost' => 10000,
            'mar_cost' => 10000,
            'apr_cost' => 10000,
            'may_cost' => 10000,
            'jun_cost' => 10000,
            'jul_cost' => 10000,
            'aug_cost' => 10000,
            'sep_cost' => 10000,
            'oct_cost' => 10000,
            'nov_cost' => 10000,
            'des_cost' => 0,
            'total' => 100000,
            'status' => 'draft',
        ]);

        $service = new BudgetOpexConsolidationService();
        $count = $service->consolidate($this->rkap);

        $this->assertSame(1, $count);

        $this->assertDatabaseHas('erkap_budget_opex', [
            'erkap_rkap_id' => $this->rkap->id,
            'division_id' => $this->division->id,
            'cost_center_id' => $this->costCenter->id,
            'chart_of_account_id' => $this->costElement->chart_of_account_id,
            'budget_amount' => 100000,
        ]);
    }

    public function test_consolidation_service_merges_multiple_costs_same_group(): void
    {
        for ($i = 0; $i < 3; $i++) {
            RoutineCost::create([
                'erkap_work_program_id' => $this->workProgram->id,
                'need' => "Kebutuhan {$i}",
                'cost_center_id' => $this->costCenter->id,
                'cost_center_owner' => 'Owner',
                'qty' => 1,
                'units' => 'Unit',
                'unit_price' => 50000,
                'erkap_cost_element_id' => $this->costElement->id,
                'jan_cost' => 50000,
                'feb_cost' => 0,
                'mar_cost' => 0,
                'apr_cost' => 0,
                'may_cost' => 0,
                'jun_cost' => 0,
                'jul_cost' => 0,
                'aug_cost' => 0,
                'sep_cost' => 0,
                'oct_cost' => 0,
                'nov_cost' => 0,
                'des_cost' => 0,
                'total' => 50000,
                'status' => 'draft',
            ]);
        }

        $service = new BudgetOpexConsolidationService();
        $count = $service->consolidate($this->rkap);

        $this->assertSame(1, $count);

        $this->assertDatabaseHas('erkap_budget_opex', [
            'erkap_rkap_id' => $this->rkap->id,
            'division_id' => $this->division->id,
            'budget_amount' => 150000,
        ]);
    }

    public function test_recalculate_variance(): void
    {
        $opex = BudgetOpex::create([
            'erkap_rkap_id' => $this->rkap->id,
            'division_id' => $this->division->id,
            'cost_center_id' => $this->costCenter->id,
            'chart_of_account_id' => $this->costElement->chart_of_account_id,
            'budget_amount' => 100000,
            'realization_amount' => 120000,
            'variance' => 0,
            'status' => 'draft',
        ]);

        $service = new BudgetOpexConsolidationService();
        $service->recalculateVariance($this->rkap);

        $this->assertEquals(20000, $opex->fresh()->variance);
    }
}