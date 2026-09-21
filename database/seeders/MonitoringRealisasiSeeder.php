<?php

namespace Database\Seeders;

use App\Models\Erkap\BudgetRealization;
use App\Models\Erkap\DepartmentTarget;
use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\PerformanceScorecard;
use App\Models\Erkap\ProgramRealization;
use App\Models\Erkap\RiskAssessmentMonthly;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\RoutineCost;
use App\Models\Erkap\WorkProgram;
use Illuminate\Database\Seeder;

class MonitoringRealisasiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $rkap = RKAP::first() ?? RKAP::create(['year' => '2026']);
        $year = (int) $rkap->year;

        $routineCost = RoutineCost::first();
        $investmentPlan = InvestmentPlan::first();
        $workProgram = WorkProgram::first();
        $riskIdentification = RiskIdentification::first();
        $departmentTarget = DepartmentTarget::first();

        foreach ([1, 2, 3] as $month) {
            if ($routineCost) {
                $budgeted = (float) $routineCost->{"jan_cost"};
                BudgetRealization::updateOrCreate(
                    [
                        'erkap_rkap_id' => $rkap->id,
                        'erkap_routine_cost_id' => $routineCost->id,
                        'month' => $month,
                        'year' => $year,
                    ],
                    [
                        'budgeted' => $budgeted,
                        'realized' => round($budgeted * (0.7 + ($month * 0.05)), 2),
                        'source' => 'manual',
                    ]
                )->calculateVariance();
            }

            if ($investmentPlan) {
                $budgeted = (float) $investmentPlan->{"jan_plan"};
                BudgetRealization::updateOrCreate(
                    [
                        'erkap_rkap_id' => $rkap->id,
                        'erkap_investment_plan_id' => $investmentPlan->id,
                        'month' => $month,
                        'year' => $year,
                    ],
                    [
                        'budgeted' => $budgeted,
                        'realized' => round($budgeted * (0.5 + ($month * 0.1)), 2),
                        'source' => 'manual',
                    ]
                )->calculateVariance();
            }

            if ($workProgram) {
                $target = (float) ($workProgram->year_plan ?? 100);
                ProgramRealization::updateOrCreate(
                    [
                        'erkap_work_program_id' => $workProgram->id,
                        'month' => $month,
                        'year' => $year,
                    ],
                    [
                        'target' => $target,
                        'realized' => round($target * (0.6 + ($month * 0.05)), 2),
                        'notes' => 'Progres bulanan program kerja.',
                    ]
                )->calculatePercentComplete();
            }

            if ($riskIdentification) {
                RiskAssessmentMonthly::updateOrCreate(
                    [
                        'erkap_risk_identification_id' => $riskIdentification->id,
                        'month' => $month,
                        'year' => $year,
                    ],
                    [
                        'inherent_probability' => 4,
                        'inherent_impact' => 5,
                        'current_probability' => 3,
                        'current_impact' => 4,
                        'residual_probability' => 2,
                        'residual_impact' => 3,
                        'mitigation_plan' => 'Peningkatan kontrol & monitoring berkala.',
                        'risk_owner' => 'Divisi IT',
                    ]
                )->calculateScores();
            }
        }

        if ($departmentTarget) {
            foreach ([1, 2] as $quarter) {
                PerformanceScorecard::updateOrCreate(
                    [
                        'erkap_rkap_id' => $rkap->id,
                        'erkap_department_target_id' => $departmentTarget->id,
                        'quarter' => $quarter,
                        'year' => $year,
                        'kpi_name' => 'Penyelesaian Program Kerja',
                    ],
                    [
                        'kpi_target' => 100,
                        'kpi_actual' => 80,
                        'weight' => 30,
                    ]
                )->calculateWeightedScore();

                PerformanceScorecard::updateOrCreate(
                    [
                        'erkap_rkap_id' => $rkap->id,
                        'erkap_department_target_id' => $departmentTarget->id,
                        'quarter' => $quarter,
                        'year' => $year,
                        'kpi_name' => 'Efisiensi Anggaran',
                    ],
                    [
                        'kpi_target' => 10,
                        'kpi_actual' => 8,
                        'weight' => 20,
                    ]
                )->calculateWeightedScore();
            }
        }
    }
}