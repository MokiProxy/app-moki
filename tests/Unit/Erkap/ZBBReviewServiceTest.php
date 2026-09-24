<?php

namespace Tests\Unit\Erkap;

use App\Models\ChartOfAccount;
use App\Models\Division;
use App\Models\Erkap\CompanyTarget;
use App\Models\Erkap\CostCenter;
use App\Models\Erkap\CostElement;
use App\Models\Erkap\CostElementCategory;
use App\Models\Erkap\DepartmentRiskStrategy;
use App\Models\Erkap\DepartmentTarget;
use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\RatingCriteria;
use App\Models\Erkap\RevenuePlan;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\RiskAppetite;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RiskTaxonomy;
use App\Models\Erkap\RiskType;
use App\Models\Erkap\RoutineCost;
use App\Models\Erkap\WorkProgram;
use App\Models\Erkap\ZBBReview;
use App\Services\Erkap\ZBBReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZBBReviewServiceTest extends TestCase
{
    use RefreshDatabase;

    private Division $division;
    private CostElement $costElement;
    private CostCenter $costCenter;
    private int $ratingId;
    private int $riskTypeId;
    private int $taxonomyId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->division = Division::factory()->create();
        $this->costCenter = CostCenter::factory()->create(['code' => 'CC-ZBB']);

        $rating = RatingCriteria::factory()->create(['rating' => 'A', 'qualification' => 'Sangat Baik']);
        $this->ratingId = $rating->id;

        $appetite = RiskAppetite::factory()->create(['threshold_score' => 12]);
        $taxonomy = RiskTaxonomy::factory()->create(['risk_appetite_id' => $appetite->id]);
        $this->taxonomyId = $taxonomy->id;
        $this->riskTypeId = RiskType::factory()->create(['risk_taxonomy_id' => $taxonomy->id])->id;

        $coa = ChartOfAccount::create([
            'code' => 'COA-ZBB',
            'name' => 'Beban ZBB',
            'type' => 'expense',
        ]);

        $this->costElement = CostElement::create([
            'code' => 'CE-ZBB',
            'name' => 'Elemen ZBB',
            'erkap_cost_element_category_id' => CostElementCategory::factory()->create()->id,
            'chart_of_account_id' => $coa->id,
        ]);
    }

    private function buildChain(int $year, string $wpCode): array
    {
        $rkap = RKAP::factory()->create(['year' => $year]);
        $companyTarget = CompanyTarget::factory()->create(['erkap_rkap_id' => $rkap->id]);
        $deptTarget = DepartmentTarget::factory()->create([
            'division_id' => $this->division->id,
            'erkap_rating_criteria_id' => $this->ratingId,
            'erkap_company_target_id' => $companyTarget->id,
        ]);
        $risk = RiskIdentification::factory()->create([
            'erkap_department_target_id' => $deptTarget->id,
            'erkap_risk_type_id' => $this->riskTypeId,
            'erkap_risk_taxonomy_id' => $this->taxonomyId,
        ]);
        DepartmentRiskStrategy::create(['erkap_risk_identification_id' => $risk->id, 'strategy' => 'reduction']);

        $workProgram = WorkProgram::create([
            'erkap_risk_identification_id' => $risk->id,
            'code' => $wpCode,
            'name' => "Program {$wpCode}",
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
            'need' => 'Kebutuhan ZBB',
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

    public function test_previous_rkap_resolves_immediate_predecessor(): void
    {
        [$rkap2025] = $this->buildChain(2025, 'WP-1');
        [$rkap2026] = $this->buildChain(2026, 'WP-2');
        RKAP::factory()->create(['year' => 2024]);

        $this->assertSame($rkap2025->id, ZBBReviewService::previousRkap($rkap2026)?->id);
    }

    public function test_build_reviews_computes_prior_delta_and_percent_for_increase(): void
    {
        [$rkap2025, $wp2025] = $this->buildChain(2025, 'WP-1');
        [$rkap2026, $wp2026] = $this->buildChain(2026, 'WP-2');

        $this->makeRoutineCost($wp2025, 100000);
        $this->makeRoutineCost($wp2026, 150000);

        $result = ZBBReviewService::buildReviews($rkap2026);

        $this->assertSame($rkap2026->id, $result['rkap_id']);
        $this->assertSame('2025', $result['previous_year']);
        $this->assertSame(2, $result['total']);
        $this->assertSame(1, $result['increase']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame(1, $result['blocking']);

        $review = ZBBReview::where('erkap_rkap_id', $rkap2026->id)->where('subject_type', 'routine_cost')->first();
        $this->assertSame(100000.0, $review->prior_year_amount);
        $this->assertSame(150000.0, $review->proposed_amount);
        $this->assertSame(50000.0, $review->delta_amount);
        $this->assertSame(50.0, $review->delta_percent);
        $this->assertSame('pending', $review->zbb_status);
        $this->assertTrue($review->blocksConsolidation());
    }

    public function test_build_reviews_auto_skips_when_proposed_equals_prior(): void
    {
        [$rkap2025, $wp2025] = $this->buildChain(2025, 'WP-1');
        [$rkap2026, $wp2026] = $this->buildChain(2026, 'WP-2');

        $this->makeRoutineCost($wp2025, 100000);
        $this->makeRoutineCost($wp2026, 100000);

        $result = ZBBReviewService::buildReviews($rkap2026);

        $this->assertSame(0, $result['increase']);
        $this->assertSame(2, $result['skipped']);
        $this->assertSame(0, $result['blocking']);

        $review = ZBBReview::where('erkap_rkap_id', $rkap2026->id)->first();
        $this->assertSame('skipped', $review->zbb_status);
        $this->assertFalse($review->blocksConsolidation());
    }

    public function test_build_reviews_returns_zero_for_proposed_zero(): void
    {
        [$rkap2025, $wp2025] = $this->buildChain(2025, 'WP-1');
        [$rkap2026, $wp2026] = $this->buildChain(2026, 'WP-2');

        $this->makeRoutineCost($wp2025, 0);
        $this->makeRoutineCost($wp2026, 0);

        $result = ZBBReviewService::buildReviews($rkap2026);

        $this->assertSame(0, $result['increase']);
        $this->assertSame(2, $result['skipped']);

        $review = ZBBReview::where('erkap_rkap_id', $rkap2026->id)->first();
        $this->assertSame(0.0, $review->delta_percent);
        $this->assertSame('skipped', $review->zbb_status);
    }

    public function test_require_rationale_throws_on_increase_without_approval(): void
    {
        [$rkap2025, $wp2025] = $this->buildChain(2025, 'WP-1');
        [$rkap2026, $wp2026] = $this->buildChain(2026, 'WP-2');

        $this->makeRoutineCost($wp2025, 100000);
        $this->makeRoutineCost($wp2026, 150000);
        ZBBReviewService::buildReviews($rkap2026);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Zero Based Budgeting belum selesai');

        ZBBReviewService::requireRationale($rkap2026);
    }

    public function test_require_rationale_throws_when_rationale_missing_even_if_status_approved(): void
    {
        [$rkap2025, $wp2025] = $this->buildChain(2025, 'WP-1');
        [$rkap2026, $wp2026] = $this->buildChain(2026, 'WP-2');

        $this->makeRoutineCost($wp2025, 100000);
        $this->makeRoutineCost($wp2026, 150000);
        ZBBReviewService::buildReviews($rkap2026);

        ZBBReview::where('erkap_rkap_id', $rkap2026->id)
            ->where('subject_type', 'routine_cost')
            ->update(['zbb_status' => 'approved', 'increase_rationale' => null]);

        $this->expectException(\RuntimeException::class);

        ZBBReviewService::requireRationale($rkap2026);
    }

    public function test_require_rationale_passes_after_review_completed(): void
    {
        [$rkap2025, $wp2025] = $this->buildChain(2025, 'WP-1');
        [$rkap2026, $wp2026] = $this->buildChain(2026, 'WP-2');

        $this->makeRoutineCost($wp2025, 100000);
        $this->makeRoutineCost($wp2026, 150000);
        ZBBReviewService::buildReviews($rkap2026);

        $review = ZBBReview::where('erkap_rkap_id', $rkap2026->id)->where('subject_type', 'routine_cost')->first();
        ZBBReviewService::review($rkap2026, $review->id, $this->createAdmin(), [
            'zbb_status' => 'approved',
            'increase_rationale' => 'Ekspansi kapasitas operasional untuk mendukung program baru.',
        ]);

        $review->refresh();
        $this->assertSame('approved', $review->zbb_status);
        $this->assertNotNull($review->reviewed_by);
        $this->assertNotNull($review->reviewed_at);
        $this->assertFalse($review->blocksConsolidation());

        ZBBReviewService::requireRationale($rkap2026);
        $this->assertTrue(true);
    }

    public function test_review_rejects_missing_rationale_for_increase(): void
    {
        [$rkap2025, $wp2025] = $this->buildChain(2025, 'WP-1');
        [$rkap2026, $wp2026] = $this->buildChain(2026, 'WP-2');

        $this->makeRoutineCost($wp2025, 100000);
        $this->makeRoutineCost($wp2026, 150000);
        ZBBReviewService::buildReviews($rkap2026);

        $review = ZBBReview::where('erkap_rkap_id', $rkap2026->id)->where('subject_type', 'routine_cost')->first();

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        ZBBReviewService::review($rkap2026, $review->id, $this->createAdmin(), ['zbb_status' => 'approved']);
    }

    public function test_require_rationale_is_noop_when_no_reviews_built(): void
    {
        [$rkap2026] = $this->buildChain(2026, 'WP-2');

        ZBBReviewService::requireRationale($rkap2026);
        $this->assertTrue(true);
    }

    public function test_auto_snapshot_persists_prior_year_amount_on_items(): void
    {
        [$rkap2025, $wp2025] = $this->buildChain(2025, 'WP-1');
        [$rkap2026, $wp2026] = $this->buildChain(2026, 'WP-2');

        $cost2025 = $this->makeRoutineCost($wp2025, 100000);
        $cost2026 = $this->makeRoutineCost($wp2026, 150000);

        ZBBReviewService::autoSnapshot();

        $this->assertSame(100000.0, (float) $cost2026->fresh()->prior_year_amount);
        $this->assertSame(0.0, (float) $cost2025->fresh()->prior_year_amount);
    }

    public function test_build_reviews_handles_revenue_plan_direct_mapping(): void
    {
        [$rkap2025] = $this->buildChain(2025, 'WP-1');
        [$rkap2026] = $this->buildChain(2026, 'WP-2');

        $coa = ChartOfAccount::create([
            'code' => 'COA-REV',
            'name' => 'Pendapatan ZBB',
            'type' => 'revenue',
        ]);

        RevenuePlan::create([
            'erkap_rkap_id' => $rkap2025->id,
            'division_id' => $this->division->id,
            'chart_of_account_id' => $coa->id,
            'description' => 'Pendapatan tahun lalu',
            'jan_plan' => 50000,
            'feb_plan' => 50000,
            'total' => 100000,
            'status' => 'draft',
        ]);

        RevenuePlan::create([
            'erkap_rkap_id' => $rkap2026->id,
            'division_id' => $this->division->id,
            'chart_of_account_id' => $coa->id,
            'description' => 'Pendapatan tahun ini',
            'jan_plan' => 75000,
            'feb_plan' => 75000,
            'total' => 150000,
            'status' => 'draft',
        ]);

        $result = ZBBReviewService::buildReviews($rkap2026);

        $this->assertSame(1, $result['increase']);

        $review = ZBBReview::where('erkap_rkap_id', $rkap2026->id)->where('subject_type', 'revenue_plan')->first();
        $this->assertSame(100000.0, $review->prior_year_amount);
        $this->assertSame(150000.0, $review->proposed_amount);
        $this->assertSame(50.0, $review->delta_percent);
    }

    public function test_build_reviews_groups_multiple_routine_costs_same_element(): void
    {
        [$rkap2025, $wp2025] = $this->buildChain(2025, 'WP-1');
        [$rkap2026, $wp2026] = $this->buildChain(2026, 'WP-2');

        $this->makeRoutineCost($wp2025, 40000);
        $this->makeRoutineCost($wp2025, 60000);
        $this->makeRoutineCost($wp2026, 100000);
        $this->makeRoutineCost($wp2026, 50000);

        $result = ZBBReviewService::buildReviews($rkap2026);

        $this->assertSame(2, $result['total']);
        $this->assertSame(1, $result['increase']);

        $review = ZBBReview::where('erkap_rkap_id', $rkap2026->id)->where('subject_type', 'routine_cost')->first();
        $this->assertSame(100000.0, $review->prior_year_amount);
        $this->assertSame(150000.0, $review->proposed_amount);
        $this->assertSame(50.0, $review->delta_percent);
    }

    private function createAdmin(): \App\Models\User
    {
        return \App\Models\User::factory()->create();
    }
}