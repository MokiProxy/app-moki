<?php

namespace Tests\Unit\Erkap;

use App\Models\Division;
use App\Models\Erkap\DepartmentRiskStrategy;
use App\Models\Erkap\DepartmentTarget;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RiskType;
use App\Models\Erkap\RiskTaxonomy;
use App\Models\Erkap\WorkProgram;
use App\Models\Erkap\RatingCriteria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskIdentificationBusinessRulesTest extends TestCase
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
    }

    public function test_risk_cannot_be_deleted_if_has_strategies(): void
    {
        $risk = RiskIdentification::factory()->create([
            'erkap_department_target_id' => $this->deptTargetA->id,
            'erkap_risk_type_id' => $this->riskType->id,
            'erkap_risk_taxonomy_id' => $this->riskTaxonomy->id,
        ]);

        DepartmentRiskStrategy::create([
            'erkap_risk_identification_id' => $risk->id,
            'strategy' => 'avoidance',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        try {
            $risk->delete();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertStringContainsString('Risiko tidak bisa dihapus karena memiliki strategi mitigasi', $e->validator->errors()->first('strategies'));
            throw $e;
        }
    }

    public function test_risk_cannot_be_deleted_if_has_work_programs(): void
    {
        $risk = RiskIdentification::factory()->create([
            'erkap_department_target_id' => $this->deptTargetA->id,
            'erkap_risk_type_id' => $this->riskType->id,
            'erkap_risk_taxonomy_id' => $this->riskTaxonomy->id,
        ]);

        DepartmentRiskStrategy::create([
            'erkap_risk_identification_id' => $risk->id,
            'strategy' => 'avoidance',
        ]);

        $program = WorkProgram::create([
            'erkap_risk_identification_id' => $risk->id,
            'code' => 'WP-RS-001',
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

        $this->assertNotNull($program->id);
        $this->assertEquals($risk->id, $program->erkap_risk_identification_id);
        $this->assertTrue($risk->workPrograms()->exists());

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        try {
            $risk->delete();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertStringContainsString('Risiko tidak bisa dihapus karena memiliki Program Kerja', $e->validator->errors()->first('work_programs'));
            $this->assertStringContainsString('Risiko tidak bisa dihapus karena memiliki strategi mitigasi', $e->validator->errors()->first('strategies'));
            throw $e;
        }
    }

    public function test_risk_can_be_deleted_if_no_strategies_or_programs(): void
    {
        $risk = RiskIdentification::factory()->create([
            'erkap_department_target_id' => $this->deptTargetA->id,
            'erkap_risk_type_id' => $this->riskType->id,
            'erkap_risk_taxonomy_id' => $this->riskTaxonomy->id,
        ]);

        $result = $risk->delete();
        $this->assertTrue($result);
    }

    public function test_strategy_requires_valid_risk(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        try {
            DepartmentRiskStrategy::create([
                'erkap_risk_identification_id' => 99999,
                'strategy' => 'avoidance',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertStringContainsString('Strategi Mitigasi wajib terhubung ke Risiko yang valid', $e->validator->errors()->first('risk_identification'));
            throw $e;
        }
    }

    public function test_work_program_requires_risk_with_strategy(): void
    {
        $risk = RiskIdentification::factory()->create([
            'erkap_department_target_id' => $this->deptTargetA->id,
            'erkap_risk_type_id' => $this->riskType->id,
            'erkap_risk_taxonomy_id' => $this->riskTaxonomy->id,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        try {
            WorkProgram::create([
                'erkap_risk_identification_id' => $risk->id,
                'code' => 'WP-001',
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertStringContainsString('Program Kerja hanya bisa dibuat untuk Risiko yang sudah memiliki Strategi Mitigasi', $e->validator->errors()->first('risk_strategy'));
            throw $e;
        }
    }

    public function test_work_program_allows_risk_with_strategy(): void
    {
        $risk = RiskIdentification::factory()->create([
            'erkap_department_target_id' => $this->deptTargetA->id,
            'erkap_risk_type_id' => $this->riskType->id,
            'erkap_risk_taxonomy_id' => $this->riskTaxonomy->id,
        ]);

        DepartmentRiskStrategy::create([
            'erkap_risk_identification_id' => $risk->id,
            'strategy' => 'avoidance',
        ]);

        $program = WorkProgram::create([
            'erkap_risk_identification_id' => $risk->id,
            'code' => 'WP-RS-002',
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

        $this->assertNotNull($program->id);
    }
}