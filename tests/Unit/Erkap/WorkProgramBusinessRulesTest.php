<?php

namespace Tests\Unit\Erkap;

use App\Models\Division;
use App\Models\Erkap\DepartmentRiskStrategy;
use App\Models\Erkap\DepartmentTarget;
use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RiskType;
use App\Models\Erkap\RiskTaxonomy;
use App\Models\Erkap\WorkProgram;
use App\Models\Erkap\RatingCriteria;
use App\Models\Erkap\RoutineCost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkProgramBusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->division = Division::factory()->create();
        $this->ratingCriteriaA = RatingCriteria::factory()->create(['rating' => 'A', 'qualification' => 'Sangat Baik']);
        $this->ratingCriteriaB = RatingCriteria::factory()->create(['rating' => 'BBB', 'qualification' => 'Penting']);
        
        $this->companyTarget = \App\Models\Erkap\CompanyTarget::factory()->create([
            'erkap_rkap_id' => \App\Models\Erkap\RKAP::factory()->create(['year' => 2024])->id,
        ]);
        
        $this->deptTargetA = DepartmentTarget::factory()->create([
            'division_id' => $this->division->id,
            'erkap_rating_criteria_id' => $this->ratingCriteriaA->id,
            'erkap_company_target_id' => $this->companyTarget->id,
        ]);
        
        $this->deptTargetB = DepartmentTarget::factory()->create([
            'division_id' => $this->division->id,
            'erkap_rating_criteria_id' => $this->ratingCriteriaB->id,
            'erkap_company_target_id' => $this->companyTarget->id,
        ]);
        
        $this->riskType = RiskType::factory()->create();
        $this->riskTaxonomy = RiskTaxonomy::factory()->create();
        
        $this->riskA = RiskIdentification::factory()->create([
            'erkap_department_target_id' => $this->deptTargetA->id,
            'erkap_risk_type_id' => $this->riskType->id,
            'erkap_risk_taxonomy_id' => $this->riskTaxonomy->id,
        ]);
        
        DepartmentRiskStrategy::create([
            'erkap_risk_identification_id' => $this->riskA->id,
            'strategy' => 'avoidance',
        ]);
        
        $this->riskB = RiskIdentification::factory()->create([
            'erkap_department_target_id' => $this->deptTargetB->id,
            'erkap_risk_type_id' => $this->riskType->id,
            'erkap_risk_taxonomy_id' => $this->riskTaxonomy->id,
        ]);
        
        DepartmentRiskStrategy::create([
            'erkap_risk_identification_id' => $this->riskB->id,
            'strategy' => 'avoidance',
        ]);
    }

    public function test_work_program_requires_rating_a_or_above(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        try {
            WorkProgram::create([
                'erkap_risk_identification_id' => $this->riskB->id,
                'code' => 'WP-001',
                'name' => 'Test Program',
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertStringContainsString('Program Kerja hanya bisa dibuat untuk Sasaran dengan Rating A ke atas', $e->validator->errors()->first('rating'));
            throw $e;
        }
    }

    public function test_work_program_allows_rating_a(): void
    {
        $program = WorkProgram::create([
            'erkap_risk_identification_id' => $this->riskA->id,
            'code' => 'WP-001',
            'name' => 'Test Program',
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

        $this->assertNotNull($program->id);
    }

    public function test_work_program_requires_budget_before_approval(): void
    {
        $program = WorkProgram::create([
            'erkap_risk_identification_id' => $this->riskA->id,
            'code' => 'WP-002',
            'name' => 'Test Program',
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

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        try {
            $program->canSubmitForApproval();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertStringContainsString('Program Kerja wajib memiliki anggaran (Form 3 atau Form 4) sebelum disetujui', $e->validator->errors()->first('budget'));
            throw $e;
        }
    }

    public function test_work_program_can_submit_with_routine_cost(): void
    {
        $program = WorkProgram::create([
            'erkap_risk_identification_id' => $this->riskA->id,
            'code' => 'WP-003',
            'name' => 'Test Program',
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

        $costCenter = \App\Models\Erkap\CostCenter::factory()->create(['code' => 'CC001']);
        $costElement = \App\Models\Erkap\CostElement::factory()->create(['code' => 'CE001']);

        RoutineCost::create([
            'erkap_work_program_id' => $program->id,
            'need' => 'Test Need',
            'cost_center_id' => $costCenter->id,
            'cost_center_owner' => 'Owner',
            'qty' => 10,
            'units' => 'Unit',
            'unit_price' => 1000,
            'erkap_cost_element_id' => $costElement->id,
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
            'des_cost' => 10000,
            'total' => 120000,
            'status' => 'draft',
        ]);

        $this->assertTrue($program->canSubmitForApproval());
    }

    public function test_work_program_can_submit_with_investment_plan(): void
    {
        $program = WorkProgram::create([
            'erkap_risk_identification_id' => $this->riskA->id,
            'code' => 'WP-004',
            'name' => 'Test Program',
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

        InvestmentPlan::create([
            'erkap_work_program_id' => $program->id,
            'erkap_investattion_category_id' => \App\Models\Erkap\InvestattionCategory::factory()->create()->id,
            'erkap_investation_type_id' => \App\Models\Erkap\InvestationType::factory()->create()->id,
            'erkap_investation_criteria_id' => \App\Models\Erkap\InvestationCriteria::factory()->create()->id,
            'name' => 'Investment',
            'unit' => 'Unit',
            'qty' => 5,
            'unit_price' => 10000,
            'jan_plan' => 4166,
            'feb_plan' => 4166,
            'mar_plan' => 4166,
            'apr_plan' => 4166,
            'may_plan' => 4166,
            'jun_plan' => 4166,
            'jul_plan' => 4166,
            'aug_plan' => 4166,
            'sep_plan' => 4166,
            'oct_plan' => 4166,
            'nov_plan' => 4166,
            'dec_plan' => 4174,
            'total' => 50000, // qty * unit_price = 5 * 10000 = 50000
            'status' => 'draft',
        ]);

        $this->assertTrue($program->canSubmitForApproval());
    }

    public function test_monthly_breakdown_validation(): void
    {
        // First create a valid program
        $program = WorkProgram::create([
            'erkap_risk_identification_id' => $this->riskA->id,
            'code' => 'WP-005',
            'name' => 'Test Program',
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

        // Now modify the program to have invalid monthly breakdown
        $program->year_plan = 100;
        $program->dec_plan = 5;

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        try {
            $program->validateMonthlyBreakdown();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertStringContainsString('Total bulanan harus sama dengan target tahunan', $e->validator->errors()->first('monthly_breakdown'));
            throw $e;
        }
    }

    public function test_monthly_cumulative_percents_matches_yearly_total(): void
    {
        $program = new WorkProgram([
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
        ]);

        $expected = [
            'jan_plan' => 8.33,
            'feb_plan' => 16.67,
            'mar_plan' => 25.0,
            'apr_plan' => 33.33,
            'may_plan' => 41.67,
            'jun_plan' => 50.0,
            'jul_plan' => 58.33,
            'aug_plan' => 66.67,
            'sep_plan' => 75.0,
            'oct_plan' => 83.33,
            'nov_plan' => 91.67,
            'dec_plan' => 100.0,
        ];

        $this->assertSame($expected, $program->monthlyCumulativePercents());
        $this->assertSame(8.33, $program->cumulativePercentAt('jan_plan'));
        $this->assertSame(100.0, $program->cumulativePercentAt('dec_plan'));
    }

    public function test_monthly_cumulative_percents_is_zero_when_year_plan_zero(): void
    {
        $program = new WorkProgram([
            'year_plan' => 0,
            'jan_plan' => 10,
            'feb_plan' => 10,
            'mar_plan' => 10,
        ]);

        $this->assertSame(
            array_fill_keys(\App\Models\Erkap\WorkProgram::MONTH_COLUMNS, 0.0),
            $program->monthlyCumulativePercents()
        );
    }

    public function test_monthly_cumulative_percents_handles_null_monthly_values(): void
    {
        $program = new WorkProgram([
            'year_plan' => 100,
            'jan_plan' => 50,
            'feb_plan' => null,
            'mar_plan' => 50,
        ]);

        $this->assertSame(50.0, $program->cumulativePercentAt('jan_plan'));
        $this->assertSame(50.0, $program->cumulativePercentAt('feb_plan'));
        $this->assertSame(100.0, $program->cumulativePercentAt('mar_plan'));
    }
}