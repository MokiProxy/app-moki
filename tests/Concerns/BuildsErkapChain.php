<?php

namespace Tests\Concerns;

use App\Models\Division;
use App\Models\Erkap\CompanyTarget;
use App\Models\Erkap\DepartmentRiskStrategy;
use App\Models\Erkap\DepartmentTarget;
use App\Models\Erkap\RatingCriteria;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\RiskAppetite;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RiskTaxonomy;
use App\Models\Erkap\RiskType;
use App\Models\Erkap\WorkProgram;

trait BuildsErkapChain
{
    protected function buildErkapChain(array $options = []): array
    {
        $division = Division::factory()->create($options['division'] ?? []);
        $rating = RatingCriteria::factory()->create(['rating' => 'A', 'qualification' => 'Sangat Baik']);

        $companyTarget = CompanyTarget::factory()->create([
            'erkap_rkap_id' => RKAP::factory()->create([
                'year' => $options['year'] ?? 2026,
                'status' => 'approved',
            ])->id,
        ]);
        $rkapId = $companyTarget->erkap_rkap_id;

        $deptTarget = DepartmentTarget::factory()->create([
            'division_id' => $division->id,
            'erkap_rating_criteria_id' => $rating->id,
            'erkap_company_target_id' => $companyTarget->id,
        ]);

        $appetite = RiskAppetite::factory()->create(['threshold_score' => $options['threshold'] ?? 12]);
        $taxonomy = RiskTaxonomy::factory()->create(['risk_appetite_id' => $appetite->id]);
        $riskType = RiskType::factory()->create(['risk_taxonomy_id' => $taxonomy->id]);

        $risk = RiskIdentification::factory()->create([
            'erkap_department_target_id' => $deptTarget->id,
            'erkap_risk_type_id' => $riskType->id,
            'erkap_risk_taxonomy_id' => $taxonomy->id,
        ]);

        DepartmentRiskStrategy::create([
            'erkap_risk_identification_id' => $risk->id,
            'strategy' => $options['strategy'] ?? 'reduction',
        ]);

        $workProgram = WorkProgram::create([
            'erkap_risk_identification_id' => $risk->id,
            'code' => $options['code'] ?? 'WP-CHAIN',
            'name' => $options['program_name'] ?? 'Program Kerja Uji',
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
            'depends_on_work_program_id' => $options['depends_on'] ?? null,
        ]);

        return compact('division', 'companyTarget', 'deptTarget', 'risk', 'workProgram', 'appetite', 'taxonomy', 'riskType', 'rkapId');
    }
}