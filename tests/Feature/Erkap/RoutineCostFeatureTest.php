<?php

namespace Tests\Feature\Erkap;

use App\Models\Division;
use App\Models\Erkap\BudgetRealization;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ActsAsSuperAdmin;
use Tests\TestCase;

class RoutineCostFeatureTest extends TestCase
{
    use RefreshDatabase, ActsAsSuperAdmin;

    private WorkProgram $workProgram;
    private CostCenter $costCenter;
    private CostElement $costElement;
    private int $rkapId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSuperAdmin();

        $division = Division::factory()->create();
        $ratingA = RatingCriteria::factory()->create(['rating' => 'A', 'qualification' => 'Sangat Baik']);

        $companyTarget = CompanyTarget::factory()->create([
            'erkap_rkap_id' => RKAP::factory()->create(['year' => 2026])->id,
        ]);
        $this->rkapId = $companyTarget->erkap_rkap_id;

        $deptTarget = DepartmentTarget::factory()->create([
            'division_id' => $division->id,
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
            'code' => 'WP-OPEX',
            'name' => 'Program Biaya Rutin',
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

        $this->costCenter = CostCenter::factory()->create(['code' => 'CC-FEAT']);
        $this->costElement = CostElement::factory()->create([
            'code' => 'CE-FEAT',
            'erkap_cost_element_category_id' => CostElementCategory::factory()->create()->id,
        ]);
    }

    public function test_index_exposes_subtotals_and_grand_total(): void
    {
        RoutineCost::factory()->create([
            'erkap_work_program_id' => $this->workProgram->id,
            'cost_center_id' => $this->costCenter->id,
            'erkap_cost_element_id' => $this->costElement->id,
            'total' => 50000,
        ]);
        RoutineCost::factory()->create([
            'erkap_work_program_id' => $this->workProgram->id,
            'cost_center_id' => $this->costCenter->id,
            'erkap_cost_element_id' => $this->costElement->id,
            'total' => 30000,
        ]);

        $response = $this->get(route('erkap.routine-costs.index'));

        $response->assertOk();
        $response->assertViewHas('subtotalByProgram');
        $response->assertViewHas('subtotalByElement');
        $response->assertViewHas('subtotalByCostCenter');
        $response->assertViewHas('grandTotal', 80000);
    }

    public function test_budget_preview_sums_budget_and_realized_by_program(): void
    {
        $costA = RoutineCost::factory()->create([
            'erkap_work_program_id' => $this->workProgram->id,
            'cost_center_id' => $this->costCenter->id,
            'erkap_cost_element_id' => $this->costElement->id,
            'total' => 100000,
        ]);
        $costB = RoutineCost::factory()->create([
            'erkap_work_program_id' => $this->workProgram->id,
            'cost_center_id' => $this->costCenter->id,
            'erkap_cost_element_id' => $this->costElement->id,
            'total' => 50000,
        ]);

        BudgetRealization::create([
            'erkap_rkap_id' => $this->rkapId,
            'erkap_routine_cost_id' => $costA->id,
            'month' => 1,
            'year' => now()->year,
            'budgeted' => 10000,
            'realized' => 40000,
        ]);
        BudgetRealization::create([
            'erkap_rkap_id' => $this->rkapId,
            'erkap_routine_cost_id' => $costB->id,
            'month' => 1,
            'year' => now()->year,
            'budgeted' => 5000,
            'realized' => 10000,
        ]);

        $preview = app(\App\Http\Controllers\Erkap\RoutineCostController::class)
            ->budgetPreviewByProgram();

        $this->assertArrayHasKey($this->workProgram->id, $preview);
        $this->assertSame(150000.0, $preview[$this->workProgram->id]['budget']);
        $this->assertSame(50000.0, $preview[$this->workProgram->id]['realized']);
        $this->assertSame(100000.0, $preview[$this->workProgram->id]['variance']);
    }

    public function test_create_page_passes_reactive_subtotals_and_budget_preview(): void
    {
        RoutineCost::factory()->create([
            'erkap_work_program_id' => $this->workProgram->id,
            'cost_center_id' => $this->costCenter->id,
            'erkap_cost_element_id' => $this->costElement->id,
            'total' => 75000,
        ]);

        $response = $this->get(route('erkap.routine-costs.create'));

        $response->assertOk();
        $response->assertViewHas('subtotalByProgram');
        $response->assertViewHas('subtotalByElement');
        $response->assertViewHas('subtotalByCostCenter');
        $response->assertViewHas('budgetPreview');
        $this->assertSame(75000.0, $response->viewData('budgetPreview')[$this->workProgram->id]['budget']);
    }

    public function test_subtotals_aggregate_correctly_with_large_dataset(): void
    {
        $otherElement = CostElement::factory()->create([
            'code' => 'CE-BULK',
            'erkap_cost_element_category_id' => $this->costElement->erkap_cost_element_category_id,
        ]);

        RoutineCost::factory()->count(500)
            ->create([
                'erkap_work_program_id' => $this->workProgram->id,
                'cost_center_id' => $this->costCenter->id,
                'cost_center_owner' => 'Bulk Owner',
                'erkap_cost_element_id' => $this->costElement->id,
                'total' => 1000,
            ]);

        RoutineCost::factory()->count(150)
            ->create([
                'erkap_work_program_id' => $this->workProgram->id,
                'cost_center_id' => $this->costCenter->id,
                'cost_center_owner' => 'Bulk Owner',
                'erkap_cost_element_id' => $otherElement->id,
                'total' => 2000,
            ]);

        $expectedTotal = (500 * 1000) + (150 * 2000);

        $response = $this->get(route('erkap.routine-costs.index'));

        $subtotalByProgram = $response->viewData('subtotalByProgram');
        $this->assertSame(
            800000.0,
            (float) $subtotalByProgram->firstWhere('erkap_work_program_id', $this->workProgram->id)->subtotal
        );

        $subtotalByElement = $response->viewData('subtotalByElement');
        $this->assertSame(
            500000.0,
            (float) $subtotalByElement->firstWhere('erkap_cost_element_id', $this->costElement->id)->subtotal
        );

        $response->assertOk();
        $response->assertViewHas('grandTotal', $expectedTotal);

        $subtotalByCostCenter = $response->viewData('subtotalByCostCenter');
        $this->assertSame(
            800000.0,
            (float) $subtotalByCostCenter->firstWhere('cost_center_id', $this->costCenter->id)->subtotal
        );
    }
}