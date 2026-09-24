<?php

namespace Tests\Feature\Erkap;

use App\Models\ChartOfAccount;
use App\Models\Division;
use App\Models\Erkap\BudgetCapex;
use App\Models\Erkap\CompanyTarget;
use App\Models\Erkap\CostCenter;
use App\Models\Erkap\CostElement;
use App\Models\Erkap\CostElementCategory;
use App\Models\Erkap\DepartmentRiskStrategy;
use App\Models\Erkap\DepartmentTarget;
use App\Models\Erkap\InvestattionCategory;
use App\Models\Erkap\InvestationCriteria;
use App\Models\Erkap\InvestationType;
use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\RatingCriteria;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\RiskAppetite;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RiskTaxonomy;
use App\Models\Erkap\RiskType;
use App\Models\Erkap\RoutineCost;
use App\Models\Erkap\WorkProgram;
use App\Models\Erkap\ZBBReview;
use App\Services\BudgetOpexConsolidationService;
use App\Services\Erkap\ZBBReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\ActsAsSuperAdmin;
use Tests\TestCase;

class ZBBReviewFeatureTest extends TestCase
{
    use RefreshDatabase, ActsAsSuperAdmin;

    private Division $division;
    private CostElement $costElement;
    private CostCenter $costCenter;
    private RKAP $rkap2025;
    private RKAP $rkap2026;
    private WorkProgram $wp2025;
    private WorkProgram $wp2026;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSuperAdmin();

        $this->division = Division::factory()->create();
        $this->costCenter = CostCenter::factory()->create(['code' => 'CC-ZBB-F']);

        $rating = RatingCriteria::factory()->create(['rating' => 'A', 'qualification' => 'Sangat Baik']);
        $appetite = RiskAppetite::factory()->create(['threshold_score' => 12]);
        $taxonomy = RiskTaxonomy::factory()->create(['risk_appetite_id' => $appetite->id]);
        $riskType = RiskType::factory()->create(['risk_taxonomy_id' => $taxonomy->id]);

        $coa = ChartOfAccount::create([
            'code' => 'COA-F',
            'name' => 'Beban Feature ZBB',
            'type' => 'expense',
        ]);

        $this->costElement = CostElement::create([
            'code' => 'CE-F',
            'name' => 'Elemen Feature ZBB',
            'erkap_cost_element_category_id' => CostElementCategory::factory()->create()->id,
            'chart_of_account_id' => $coa->id,
        ]);

        [$this->rkap2025, $this->wp2025] = $this->chain(2025, $rating, $taxonomy, $riskType);
        [$this->rkap2026, $this->wp2026] = $this->chain(2026, $rating, $taxonomy, $riskType);
    }

    protected function chain(int $year, RatingCriteria $rating, RiskTaxonomy $taxonomy, RiskType $riskType): array
    {
        $rkap = RKAP::factory()->create(['year' => $year]);
        $companyTarget = CompanyTarget::factory()->create(['erkap_rkap_id' => $rkap->id]);
        $deptTarget = DepartmentTarget::factory()->create([
            'division_id' => $this->division->id,
            'erkap_rating_criteria_id' => $rating->id,
            'erkap_company_target_id' => $companyTarget->id,
        ]);
        $risk = RiskIdentification::factory()->create([
            'erkap_department_target_id' => $deptTarget->id,
            'erkap_risk_type_id' => $riskType->id,
            'erkap_risk_taxonomy_id' => $taxonomy->id,
        ]);
        DepartmentRiskStrategy::create(['erkap_risk_identification_id' => $risk->id, 'strategy' => 'reduction']);

        $workProgram = WorkProgram::create([
            'erkap_risk_identification_id' => $risk->id,
            'code' => "WP-F-{$year}",
            'name' => "Program Feature {$year}",
            'units' => 'Unit',
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
            'status' => 'draft',
        ]);

        return [$rkap, $workProgram];
    }

    private function makeRoutineCost(WorkProgram $workProgram, float $total): RoutineCost
    {
        return RoutineCost::create([
            'erkap_work_program_id' => $workProgram->id,
            'need' => 'Kebutuhan Feature ZBB',
            'cost_center_id' => $this->costCenter->id,
            'cost_center_owner' => 'Owner',
            'qty' => 1,
            'units' => 'Unit',
            'unit_price' => $total,
            'erkap_cost_element_id' => $this->costElement->id,
            'jan_cost' => $total,
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
            'total' => $total,
            'status' => 'draft',
        ]);
    }

    private function buildScenario(): void
    {
        $this->makeRoutineCost($this->wp2025, 100000);
        $this->makeRoutineCost($this->wp2026, 150000);

        InvestmentPlan::create([
            'erkap_work_program_id' => $this->wp2026->id,
            'erkap_investattion_category_id' => InvestattionCategory::factory()->create()->id,
            'erkap_investation_type_id' => InvestationType::factory()->create()->id,
            'erkap_investation_criteria_id' => InvestationCriteria::factory()->create()->id,
            'name' => 'Server Rack',
            'unit' => 'Unit',
            'qty' => 2,
            'unit_price' => 60000,
            'total' => 120000,
            'is_kumulatif' => false,
            'status' => 'draft',
        ]);

        Permission::firstOrCreate(['name' => 'erkap.zbb-reviews.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'erkap.zbb-reviews.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'erkap.zbb-reviews.edit', 'guard_name' => 'web']);

        $this->actingAs($this->user)
            ->post(route('erkap.zbb-reviews.build'), ['erkap_rkap_id' => $this->rkap2026->id])
            ->assertRedirect();
    }

    public function test_build_creates_reviews_for_rkap(): void
    {
        $this->buildScenario();

        $this->assertDatabaseHas('erkap_zbb_reviews', [
            'erkap_rkap_id' => $this->rkap2026->id,
            'subject_type' => 'routine_cost',
            'zbb_status' => 'pending',
            'delta_percent' => 50.00,
        ]);
    }

    public function test_index_lists_reviews(): void
    {
        $this->buildScenario();

        $this->actingAs($this->user)
            ->get(route('erkap.zbb-reviews.index'))
            ->assertOk()
            ->assertSee('Zero Based Budgeting')
            ->assertSee('Kebutuhan Feature ZBB');
    }

    public function test_show_displays_review_detail(): void
    {
        $this->buildScenario();

        $review = ZBBReview::where('erkap_rkap_id', $this->rkap2026->id)
            ->where('subject_type', 'routine_cost')
            ->firstOrFail();

        $this->actingAs($this->user)
            ->get(route('erkap.zbb-reviews.show', $review->id))
            ->assertOk()
            ->assertSee($review->display_name)
            ->assertSee('Justifikasi Kenaikan');
    }

    public function test_update_approves_review_with_rationale(): void
    {
        $this->buildScenario();

        $review = ZBBReview::where('erkap_rkap_id', $this->rkap2026->id)
            ->where('subject_type', 'routine_cost')
            ->firstOrFail();

        $this->actingAs($this->user)
            ->put(route('erkap.zbb-reviews.update', $review->id), [
                'zbb_status' => 'approved',
                'increase_rationale' => 'Penambahan kapasitas produksi.',
            ])
            ->assertRedirect(route('erkap.zbb-reviews.show', $review->id));

        $this->assertDatabaseHas('erkap_zbb_reviews', [
            'id' => $review->id,
            'zbb_status' => 'approved',
            'increase_rationale' => 'Penambahan kapasitas produksi.',
        ]);
    }

    public function test_update_requires_rationale_for_increase(): void
    {
        $this->buildScenario();

        $review = ZBBReview::where('erkap_rkap_id', $this->rkap2026->id)
            ->where('subject_type', 'routine_cost')
            ->firstOrFail();

        $this->actingAs($this->user)
            ->put(route('erkap.zbb-reviews.update', $review->id), [
                'zbb_status' => 'approved',
            ])
            ->assertSessionHasErrors('increase_rationale');

        $this->assertDatabaseMissing('erkap_zbb_reviews', [
            'id' => $review->id,
            'zbb_status' => 'approved',
        ]);
    }

    public function test_build_requires_create_permission(): void
    {
        $this->makeRoutineCost($this->wp2026, 150000);

        Permission::firstOrCreate(['name' => 'erkap.zbb-reviews.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'erkap.zbb-reviews.create', 'guard_name' => 'web']);

        $user = \App\Models\User::factory()->create();
        $user->givePermissionTo('erkap.zbb-reviews.view');

        $this->actingAs($user)
            ->post(route('erkap.zbb-reviews.build'), ['erkap_rkap_id' => $this->rkap2026->id])
            ->assertForbidden();

        $this->assertDatabaseCount('erkap_zbb_reviews', 0);
    }

    public function test_capex_consolidation_blocked_until_zbb_approved(): void
    {
        $this->buildScenario();

        $this->actingAs($this->user)
            ->post(route('erkap.budget-capex.consolidate'), ['erkap_rkap_id' => $this->rkap2026->id])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('erkap_budget_capex', ['erkap_rkap_id' => $this->rkap2026->id]);
    }

    public function test_capex_consolidation_allowed_after_zbb_approved(): void
    {
        $this->buildScenario();

        foreach (ZBBReview::where('erkap_rkap_id', $this->rkap2026->id)->get() as $review) {
            ZBBReviewService::review($this->rkap2026, $review->id, $this->user, [
                'zbb_status' => 'approved',
                'increase_rationale' => 'Penambahan kapasitas produksi.',
            ]);
        }

        $this->actingAs($this->user)
            ->post(route('erkap.budget-capex.consolidate'), ['erkap_rkap_id' => $this->rkap2026->id])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('erkap_budget_capex', ['erkap_rkap_id' => $this->rkap2026->id]);
    }

    public function test_opex_consolidation_blocked_until_zbb_approved(): void
    {
        $this->buildScenario();

        $this->expectException(\RuntimeException::class);

        $service = new BudgetOpexConsolidationService();
        $service->consolidate($this->rkap2026);
    }

    public function test_opex_consolidation_allowed_after_zbb_approved(): void
    {
        $this->buildScenario();

        foreach (ZBBReview::where('erkap_rkap_id', $this->rkap2026->id)->get() as $review) {
            ZBBReviewService::review($this->rkap2026, $review->id, $this->user, [
                'zbb_status' => 'approved',
                'increase_rationale' => 'Penambahan kapasitas produksi.',
            ]);
        }

        $service = new BudgetOpexConsolidationService();
        $count = $service->consolidate($this->rkap2026);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('erkap_budget_opex', ['erkap_rkap_id' => $this->rkap2026->id]);
    }
}