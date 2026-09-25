<?php

namespace Tests\Unit\Erkap;

use App\Models\Division;
use App\Models\Erkap\CostCenter;
use App\Models\Erkap\CostElement;
use App\Models\Erkap\CostElementCategory;
use App\Models\Erkap\DepartmentRiskStrategy;
use App\Models\Erkap\DepartmentTarget;
use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RiskType;
use App\Models\Erkap\RiskTaxonomy;
use App\Models\Erkap\WorkProgram;
use App\Models\Erkap\RatingCriteria;
use App\Models\Erkap\RoutineCost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyBreakdownValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->division = Division::factory()->create();
        $this->ratingCriteriaA = RatingCriteria::factory()->create(['rating' => 'A', 'qualification' => 'Sangat Baik']);
        
        $this->companyTarget = \App\Models\Erkap\CompanyTarget::factory()->create([
            'erkap_rkap_id' => \App\Models\Erkap\RKAP::factory()->create(['year' => 2024])->id,
        ]);
        
        $this->deptTargetA = DepartmentTarget::factory()->create([
            'division_id' => $this->division->id,
            'erkap_rating_criteria_id' => $this->ratingCriteriaA->id,
            'erkap_company_target_id' => $this->companyTarget->id,
        ]);
        
        $this->riskType = RiskType::factory()->create();
        $this->riskTaxonomy = RiskTaxonomy::factory()->create();
        
        $this->risk = RiskIdentification::factory()->create([
            'erkap_department_target_id' => $this->deptTargetA->id,
            'erkap_risk_type_id' => $this->riskType->id,
            'erkap_risk_taxonomy_id' => $this->riskTaxonomy->id,
        ]);

        DepartmentRiskStrategy::create([
            'erkap_risk_identification_id' => $this->risk->id,
            'strategy' => 'avoidance',
        ]);

        $this->costCenter = CostCenter::factory()->create(['code' => 'CC001']);
        $this->costElementCategory = CostElementCategory::factory()->create();
        $this->costElement = CostElement::factory()->create([
            'code' => 'CE001',
            'erkap_cost_element_category_id' => $this->costElementCategory->id,
        ]);
    }

    public function test_work_program_monthly_breakdown_validation(): void
    {
        $program = WorkProgram::create([
            'erkap_risk_identification_id' => $this->risk->id,
            'code' => 'WP-MB-001',
            'name' => 'Test Program',
            'units' => 'Unit',
            'year_plan' => 100,
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
            $program->validateMonthlyBreakdown();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertStringContainsString('Total bulanan harus sama dengan target tahunan persentase', $e->validator->errors()->first('monthly_breakdown'));
            throw $e;
        }
    }

    public function test_work_program_valid_monthly_breakdown(): void
    {
        $program = WorkProgram::create([
            'erkap_risk_identification_id' => $this->risk->id,
            'code' => 'WP-MB-002',
            'name' => 'Test Program',
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

        $program->validateMonthlyBreakdown();
        $this->assertTrue(true);
    }

    public function test_routine_cost_monthly_breakdown_validation(): void
    {
        $program = WorkProgram::create([
            'erkap_risk_identification_id' => $this->risk->id,
            'code' => 'WP-MB-003',
            'name' => 'Test Program',
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

        $cost = RoutineCost::create([
            'erkap_work_program_id' => $program->id,
            'need' => 'Test Need',
            'cost_center_id' => $this->costCenter->id,
            'cost_center_owner' => 'Owner',
            'qty' => 10,
            'units' => 'Unit',
            'unit_price' => 1000,
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
            'des_cost' => 15000,
            'total' => 120000,
            'status' => 'draft',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        try {
            $cost->validateMonthlyBreakdown();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertStringContainsString('Total bulanan harus sama dengan total biaya', $e->validator->errors()->first('monthly_breakdown'));
            throw $e;
        }
    }

    public function test_routine_cost_valid_monthly_breakdown(): void
    {
        $program = WorkProgram::create([
            'erkap_risk_identification_id' => $this->risk->id,
            'code' => 'WP-MB-004',
            'name' => 'Test Program',
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

        $cost = RoutineCost::create([
            'erkap_work_program_id' => $program->id,
            'need' => 'Test Need',
            'cost_center_id' => $this->costCenter->id,
            'cost_center_owner' => 'Owner',
            'qty' => 10,
            'units' => 'Unit',
            'unit_price' => 1000,
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
            'des_cost' => 20000,
            'total' => 130000,
            'status' => 'draft',
        ]);

        $cost->validateMonthlyBreakdown();
        $this->assertTrue(true);
    }

    public function test_investment_plan_monthly_breakdown_validation(): void
    {
        $program = WorkProgram::create([
            'erkap_risk_identification_id' => $this->risk->id,
            'code' => 'WP-MB-005',
            'name' => 'Test Program',
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

$plan = InvestmentPlan::create([
            'erkap_work_program_id' => $program->id,
            'erkap_investattion_category_id' => \App\Models\Erkap\InvestattionCategory::factory()->create()->id,
            'erkap_investation_type_id' => \App\Models\Erkap\InvestationType::factory()->create()->id,
            'erkap_investation_criteria_id' => \App\Models\Erkap\InvestationCriteria::factory()->create()->id,
            'name' => 'Investment',
            'unit' => 'Unit',
            'qty' => 5,
            'unit_price' => 10000,
            'jan_plan' => 5000,
            'feb_plan' => 5000,
            'mar_plan' => 5000,
            'apr_plan' => 5000,
            'may_plan' => 5000,
            'jun_plan' => 5000,
            'jul_plan' => 5000,
            'aug_plan' => 5000,
            'sep_plan' => 5000,
            'oct_plan' => 5000,
            'nov_plan' => 5000,
            'dec_plan' => 5000,
            'total' => 50000,
            'status' => 'draft',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        try {
            $plan->validateMonthlyBreakdown();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertStringContainsString('Total bulanan harus sama dengan total investasi', $e->validator->errors()->first('monthly_breakdown'));
            throw $e;
        }
    }

    public function test_investment_plan_valid_monthly_breakdown(): void
    {
        $program = WorkProgram::create([
            'erkap_risk_identification_id' => $this->risk->id,
            'code' => 'WP-MB-006',
            'name' => 'Test Program',
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

        $plan = InvestmentPlan::create([
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
            'total' => 50000,
            'status' => 'draft',
        ]);

        $plan->validateMonthlyBreakdown();
        $this->assertTrue(true);
    }
}