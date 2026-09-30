<?php

namespace Tests\Unit\Erkap;

use App\Models\Division;
use App\Models\Erkap\CompanyTarget;
use App\Models\Erkap\DepartmentRiskStrategy;
use App\Models\Erkap\DepartmentTarget;
use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\InvestattionCategory;
use App\Models\Erkap\InvestationCriteria;
use App\Models\Erkap\InvestationType;
use App\Models\Erkap\RatingCriteria;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RiskType;
use App\Models\Erkap\RiskTaxonomy;
use App\Models\Erkap\WorkProgram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InvestmentPlanPaymentScheduleTest extends TestCase
{
    use RefreshDatabase;

    private WorkProgram $workProgram;

    protected function setUp(): void
    {
        parent::setUp();

        $division = Division::factory()->create();
        $ratingA = RatingCriteria::factory()->create(['rating' => 'A', 'qualification' => 'Sangat Baik']);

        $companyTarget = CompanyTarget::factory()->create([
            'erkap_rkap_id' => RKAP::factory()->create(['year' => 2026])->id,
        ]);

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
            'code' => 'WP-INVEST',
            'name' => 'Program Investasi',
            'units' => 'Unit',
            'year_plan' => 100,
            'jan_plan' => 8,
            'feb_plan' => 8,
            'mar_plan' => 8,
            'apr_plan' => 8,
            'may_plan' => 8,
            'jun_plan' => 8,
            'jul_plan' => 8,
            'aug_plan' => 8,
            'sep_plan' => 8,
            'oct_plan' => 8,
            'nov_plan' => 8,
            'dec_plan' => 12,
            'status' => 'draft',
        ]);
    }

    private function makePlan(array $overrides = []): InvestmentPlan
    {
        return InvestmentPlan::create(array_merge([
            'erkap_work_program_id' => $this->workProgram->id,
            'erkap_investattion_category_id' => InvestattionCategory::factory()->create()->id,
            'erkap_investation_type_id' => InvestationType::factory()->create()->id,
            'erkap_investation_criteria_id' => InvestationCriteria::factory()->create()->id,
            'name' => 'Investasi Feature',
            'unit' => 'Unit',
            'qty' => 1,
            'unit_price' => 120000,
            'jan_plan' => 10000,
            'feb_plan' => 10000,
            'mar_plan' => 10000,
            'apr_plan' => 10000,
            'may_plan' => 10000,
            'jun_plan' => 10000,
            'jul_plan' => 10000,
            'aug_plan' => 10000,
            'sep_plan' => 10000,
            'oct_plan' => 10000,
            'nov_plan' => 10000,
            'dec_plan' => 10000,
            'total' => 120000,
            'is_kumulatif' => false,
            'status' => 'draft',
        ], $overrides));
    }

    public function test_validate_payment_schedule_rejects_mismatch(): void
    {
        $plan = $this->makePlan(['dec_plan' => 5000]);

        $this->expectException(ValidationException::class);

        try {
            $plan->validatePaymentSchedule();
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Jadwal pembayaran bulanan harus sama dengan total pembayaran', $e->validator->errors()->first('payment_schedule'));
            throw $e;
        }
    }

    public function test_validate_payment_schedule_accepts_total_match(): void
    {
        $plan = $this->makePlan();

        $plan->validatePaymentSchedule();
        $this->assertTrue(true);
    }

    public function test_validate_payment_schedule_skips_kumulatif_plans(): void
    {
        $plan = $this->makePlan([
            'is_kumulatif' => true,
            'jan_plan' => 120000,
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
        ]);

        $plan->validatePaymentSchedule();
        $this->assertTrue(true);
    }
}