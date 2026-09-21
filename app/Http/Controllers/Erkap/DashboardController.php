<?php

namespace App\Http\Controllers\Erkap;

use App\Exports\Erkap\BudgetConsolidationExport;
use App\Http\Controllers\Controller;
use App\Models\Erkap\BudgetRealization;
use App\Models\Erkap\DepartmentTarget;
use App\Models\Erkap\ExpensePlan;
use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\ProgramRealization;
use App\Models\Erkap\RevenuePlan;
use App\Models\Erkap\RiskAnalysis;
use App\Models\Erkap\RiskAssessmentMonthly;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\RoutineCost;
use App\Models\Erkap\WorkProgram;
use App\Services\ErkapAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    protected $monthLabels = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    private const ROUTINE_MONTHS = [
        'jan_cost', 'feb_cost', 'mar_cost', 'apr_cost', 'may_cost', 'jun_cost',
        'jul_cost', 'aug_cost', 'sep_cost', 'oct_cost', 'nov_cost', 'des_cost',
    ];

    private const PLAN_MONTHS = [
        'jan_plan', 'feb_plan', 'mar_plan', 'apr_plan', 'may_plan', 'jun_plan',
        'jul_plan', 'aug_plan', 'sep_plan', 'oct_plan', 'nov_plan', 'dec_plan',
    ];

    public function index(Request $request)
    {
        $pageName = 'Dashboard E-RKAP';
        $rkaps = RKAP::orderByDesc('year')->get();
        $selectedRkap = RKAP::find($request->integer('rkap_id')) ?? $rkaps->first();
        $year = $request->integer('year', (int) ($selectedRkap?->year ?? now()->year));
        $isDivisionScoped = ErkapAccess::isDivisionScoped();

        $scope = $this->scopeIds($selectedRkap);
        [$executiveSummary, $budget, $heatMap, $riskSummary, $program, $profitLoss] = $this->overview($selectedRkap, $scope, $year);
        $monthLabels = $this->monthLabels;

        $programGantt = WorkProgram::with('riskIdentification.departmentTarget.division')
            ->whereIn('id', $scope['workProgramIds'] ?: [-1])
            ->orderBy('year_plan', 'desc')
            ->limit(6)
            ->get();
        $programGantt = $this->attachRealization($programGantt);

        return view('erkap.dashboard.index', compact(
            'pageName', 'rkaps', 'selectedRkap', 'year', 'isDivisionScoped', 'monthLabels',
            'executiveSummary', 'budget', 'heatMap', 'riskSummary', 'program', 'programGantt', 'profitLoss'
        ));
    }

    public function riskDetail(Request $request)
    {
        $probability = $request->integer('probability');
        $impact = $request->integer('impact');
        $year = $request->integer('year', now()->year);
        $month = $request->integer('month', 0);

        if ($probability < 0 || $probability > 5 || $impact < 0 || $impact > 5) {
            return response('<div class="alert alert-warning">Parameter tidak valid.</div>');
        }

        $assessments = RiskAssessmentMonthly::with('riskIdentification.departmentTarget.division')
            ->whereIn('erkap_risk_identification_id', ErkapAccess::riskIdentificationIds() ?: [-1])
            ->where('year', $year)
            ->when($month > 0, fn ($q) => $q->where('month', $month))
            ->when($probability > 0, fn ($q) => $q->where('inherent_probability', $probability))
            ->when($impact > 0, fn ($q) => $q->where('inherent_impact', $impact))
            ->orderByDesc('inherent_score')
            ->limit(50)
            ->get();

        $summarized = $this->summarizeHeatMap($assessments);

        return view('erkap.dashboard.modals.risk-detail', compact('assessments', 'summarized', 'probability', 'impact', 'year', 'month'));
    }

    public function budgetDetail(Request $request)
    {
        $type = in_array($request->input('type'), ['opex', 'capex'], true) ? $request->input('type') : 'opex';
        $year = $request->integer('year', now()->year);
        $month = $request->integer('month', now()->month);

        $realizations = BudgetRealization::with([
            'routineCost.workProgram.riskIdentification.departmentTarget.division',
            'investmentPlan.workProgram.riskIdentification.departmentTarget.division',
        ])
            ->where('year', $year)
            ->where('month', $month)
            ->when($type === 'opex', fn ($q) => $q->whereNotNull('erkap_routine_cost_id'))
            ->when($type === 'capex', fn ($q) => $q->whereNotNull('erkap_investment_plan_id'))
            ->latest()
            ->limit(100)
            ->get();

        $totals = [
            'budgeted' => (float) $realizations->sum('budgeted'),
            'realized' => (float) $realizations->sum('realized'),
            'variance' => (float) $realizations->sum('variance'),
        ];

        return view('erkap.dashboard.modals.budget-detail', compact('realizations', 'totals', 'type', 'year', 'month'));
    }

    public function exportBudget(Request $request)
    {
        $rkap = RKAP::find($request->integer('rkap_id')) ?? RKAP::orderByDesc('year')->first();
        $year = $request->integer('year', (int) ($rkap?->year ?? now()->year));
        $divisionId = ErkapAccess::isDivisionScoped() ? ErkapAccess::divisionId() : ($request->integer('division_id') ?: null);

        return Excel::download(
            new BudgetConsolidationExport($rkap, $year, $divisionId),
            'konsolidasi-anggaran-' . ($rkap?->year ?? $year) . '.xlsx'
        );
    }

    public function exportBudgetPdf(Request $request)
    {
        $rkap = RKAP::find($request->integer('rkap_id')) ?? RKAP::orderByDesc('year')->first();
        $year = $request->integer('year', (int) ($rkap?->year ?? now()->year));
        $divisionId = ErkapAccess::isDivisionScoped() ? ErkapAccess::divisionId() : ($request->integer('division_id') ?: null);

        $export = new BudgetConsolidationExport($rkap, $year, $divisionId);
        $data = $export->exportData();

        $pdf = Pdf::loadView('erkap.exports.budget-consolidation-pdf', $data);
        $pdf->setOption('isRemoteEnabled', true);

        return $pdf->download('konsolidasi-anggaran-' . ($rkap?->year ?? $year) . '.pdf');
    }

    protected function overview(?RKAP $rkap, array $scope, int $year): array
    {
        $budget = $this->budgetData($rkap, $scope['workProgramIds'], $year);
        $heatMap = $this->riskHeatMap($scope['riskIdentificationIds'], $year);
        $riskSummary = $this->riskSummary($scope, $year);
        $program = $this->programData($scope);

        $riskDirectionCount = RiskIdentification::whereIn('id', $scope['riskIdentificationIds'] ?: [-1])
            ->selectRaw('risk_direction, COUNT(*) as total')
            ->groupBy('risk_direction')
            ->pluck('total', 'risk_direction')
            ->all();

        $executiveSummary = [
            'totalBudget' => $budget['opexBudget'] + $budget['capexBudget'],
            'totalRealized' => $budget['opexRealized'] + $budget['capexRealized'],
            'opexBudget' => $budget['opexBudget'],
            'capexBudget' => $budget['capexBudget'],
            'opexRealized' => $budget['opexRealized'],
            'capexRealized' => $budget['capexRealized'],
            'utilization' => round($budget['totalBudget'] > 0 ? ($budget['totalRealized'] / $budget['totalBudget']) * 100 : 0, 2),
            'totalWorkPrograms' => $program['totalPrograms'],
            'approvedPrograms' => $program['approvedPrograms'],
            'totalRisks' => $riskSummary['total'],
            'positiveRisks' => $riskDirectionCount['positive'] ?? 0,
            'negativeRisks' => $riskDirectionCount['negative'] ?? 0,
            'averageScore' => $riskSummary['averageScore'],
            'levelCounts' => $riskSummary['levelCounts'],
        ];

        return [$executiveSummary, $budget, $heatMap, $riskSummary, $program, $this->profitLoss($rkap)];
    }

    protected function scopeIds(?RKAP $rkap): array
    {
        $departmentTargetIds = DepartmentTarget::whereIn('id', ErkapAccess::departmentTargetIds())
            ->when($rkap, fn ($q) => $q->whereHas('companyTarget', fn ($q2) => $q2->where('erkap_rkap_id', $rkap->id)))
            ->pluck('id')
            ->all();

        $riskIdentificationIds = RiskIdentification::whereIn('id', ErkapAccess::riskIdentificationIds())
            ->whereIn('erkap_department_target_id', $departmentTargetIds ?: [-1])
            ->pluck('id')
            ->all();

        $workProgramIds = WorkProgram::whereIn('id', ErkapAccess::workProgramIds())
            ->whereIn('erkap_risk_identification_id', $riskIdentificationIds ?: [-1])
            ->pluck('id')
            ->all();

        return [
            'departmentTargetIds' => $departmentTargetIds,
            'riskIdentificationIds' => $riskIdentificationIds,
            'workProgramIds' => $workProgramIds,
        ];
    }

    protected function budgetData(?RKAP $rkap, array $workProgramIds, int $year): array
    {
        $routineCosts = RoutineCost::whereIn('erkap_work_program_id', $workProgramIds ?: [-1])
            ->when($rkap, fn ($q) => $q->whereHas('workProgram.riskIdentification.departmentTarget.companyTarget', fn ($q2) => $q2->where('erkap_rkap_id', $rkap->id)))
            ->get();

        $investmentPlans = InvestmentPlan::whereIn('erkap_work_program_id', $workProgramIds ?: [-1])
            ->when($rkap, fn ($q) => $q->whereHas('workProgram.riskIdentification.departmentTarget.companyTarget', fn ($q2) => $q2->where('erkap_rkap_id', $rkap->id)))
            ->get();

        $opexBudgetMonthly = array_fill(0, 12, 0.0);
        $capexBudgetMonthly = array_fill(0, 12, 0.0);

        foreach (self::ROUTINE_MONTHS as $index => $column) {
            $opexBudgetMonthly[$index] = (float) $routineCosts->sum($column);
        }

        foreach (self::PLAN_MONTHS as $index => $column) {
            $capexBudgetMonthly[$index] = (float) $investmentPlans->sum($column);
        }

        $opexBudget = (float) $routineCosts->sum('total');
        $capexBudget = (float) $investmentPlans->sum('total');
        $totalBudget = $opexBudget + $capexBudget;

        $realizations = BudgetRealization::where('year', $year)
            ->when($rkap, fn ($q) => $q->where('erkap_rkap_id', $rkap->id))
            ->get();

        $opexRealizedMonthly = array_fill(0, 12, 0.0);
        $capexRealizedMonthly = array_fill(0, 12, 0.0);

        foreach ($realizations as $realization) {
            $index = max(0, min(11, (int) $realization->month - 1));

            if ($realization->erkap_routine_cost_id !== null) {
                $opexRealizedMonthly[$index] += (float) $realization->realized;
            } else {
                $capexRealizedMonthly[$index] += (float) $realization->realized;
            }
        }

        $opexRealized = (float) $realizations->where('erkap_routine_cost_id', '!==', null)->sum('realized');
        $capexRealized = (float) $realizations->where('erkap_investment_plan_id', '!==', null)->sum('realized');
        $totalRealized = $opexRealized + $capexRealized;

        return compact(
            'opexBudget', 'capexBudget', 'totalBudget', 'opexRealized', 'capexRealized', 'totalRealized',
            'opexBudgetMonthly', 'capexBudgetMonthly', 'opexRealizedMonthly', 'capexRealizedMonthly'
        );
    }

    protected function riskHeatMap(array $riskIdentificationIds, int $year): array
    {
        $assessments = RiskAssessmentMonthly::whereIn('erkap_risk_identification_id', $riskIdentificationIds ?: [-1])
            ->where('year', $year)
            ->selectRaw('inherent_probability, inherent_impact, COUNT(*) as total')
            ->groupBy('inherent_probability', 'inherent_impact')
            ->get();

        if ($assessments->isEmpty()) {
            $analyses = RiskAnalysis::with('riskProbability', 'riskImpact')
                ->whereHas('riskIdentification', fn ($q) => $q->whereIn('id', $riskIdentificationIds ?: [-1]))
                ->get();

            $assessments = $analyses->groupBy(fn ($item) => (int) $item->riskProbability?->point . '-' . (int) $item->riskImpact?->point)
                ->map(fn ($group) => [
                    'inherent_probability' => (int) $group->first()->riskProbability->point,
                    'inherent_impact' => (int) $group->first()->riskImpact->point,
                    'total' => $group->count(),
                ])
                ->values();
        }

        return $this->normalizeHeatMap($assessments);
    }

    protected function normalizeHeatMap($assessments): array
    {
        $matrix = [];

        for ($probability = 1; $probability <= 5; $probability++) {
            for ($impact = 1; $impact <= 5; $impact++) {
                $matrix[$probability][$impact] = [
                    'probability' => $probability,
                    'impact' => $impact,
                    'score' => $probability * $impact,
                    'total' => 0,
                ];
            }
        }

        foreach ($assessments as $assessment) {
            $probability = (int) ($assessment->inherent_probability ?? 0);
            $impact = (int) ($assessment->inherent_impact ?? 0);

            if ($probability < 1 || $probability > 5 || $impact < 1 || $impact > 5) {
                continue;
            }

            $matrix[$probability][$impact]['total'] = (int) $assessment->total;
        }

        return $matrix;
    }

    protected function riskSummary(array $scope, int $year): array
    {
        $query = RiskAssessmentMonthly::whereIn('erkap_risk_identification_id', $scope['riskIdentificationIds'] ?: [-1])
            ->where('year', $year);

        $rows = (clone $query)
            ->selectRaw('inherent_probability * inherent_impact as score, COUNT(*) as total')
            ->groupBy('score')
            ->get();

        $total = (int) $rows->sum('total');
        $levelCounts = ['VL' => 0, 'L' => 0, 'M' => 0, 'H' => 0, 'VH' => 0];
        $scoreSum = 0;

        foreach ($rows as $row) {
            $score = (int) $row->score;
            $count = (int) $row->total;
            $scoreSum += $score * $count;
            $levelCounts[$this->scoreLevel($score)]++;
        }

        return [
            'total' => $total,
            'averageScore' => $total > 0 ? round($scoreSum / $total, 2) : 0,
            'levelCounts' => $levelCounts,
        ];
    }

    protected function summarizeHeatMap($assessments): array
    {
        $total = (int) $assessments->count();
        $scoreSum = (int) $assessments->sum('inherent_score');

        return [
            'total' => $total,
            'averageScore' => $total > 0 ? round($scoreSum / $total, 2) : 0,
        ];
    }

    protected function programData(array $scope): array
    {
        $programs = WorkProgram::whereIn('id', $scope['workProgramIds'] ?: [-1])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status')
            ->all();

        $approvedTotal = (int) array_sum(array_intersect_key($programs, ['approved' => true]));

        return [
            'statusCounts' => $programs,
            'totalPrograms' => (int) array_sum($programs),
            'approvedPrograms' => $approvedTotal,
        ];
    }

    protected function profitLoss(?RKAP $rkap): array
    {
        $isDivisionScoped = ErkapAccess::isDivisionScoped();

        $revenueRows = RevenuePlan::query()
            ->when($rkap, fn ($q) => $q->where('erkap_rkap_id', $rkap->id))
            ->when($isDivisionScoped, fn ($q) => $q->where('division_id', ErkapAccess::divisionId()))
            ->get();

        $expenseRows = ExpensePlan::query()
            ->when($rkap, fn ($q) => $q->where('erkap_rkap_id', $rkap->id))
            ->when($isDivisionScoped, fn ($q) => $q->where('division_id', ErkapAccess::divisionId()))
            ->get();

        $monthlyRevenue = $this->sumMonthly($revenueRows, RevenuePlan::monthColumns());
        $monthlyExpense = $this->sumMonthly($expenseRows, ExpensePlan::monthColumns());
        $monthlyProfit = array_map(fn ($revenue, $expense) => $revenue - $expense, $monthlyRevenue, $monthlyExpense);

        $totalRevenue = (float) array_sum($monthlyRevenue);
        $totalExpense = (float) array_sum($monthlyExpense);
        $netProfit = $totalRevenue - $totalExpense;

        return [
            'monthlyRevenue' => $monthlyRevenue,
            'monthlyExpense' => $monthlyExpense,
            'monthlyProfit' => $monthlyProfit,
            'totalRevenue' => $totalRevenue,
            'totalExpense' => $totalExpense,
            'netProfit' => $netProfit,
            'margin' => $totalRevenue > 0 ? round(($netProfit / $totalRevenue) * 100, 2) : 0,
        ];
    }

    protected function sumMonthly($plans, array $columns): array
    {
        $result = array_fill(0, 12, 0.0);

        foreach ($columns as $index => $month) {
            $result[$index] = (float) $plans->sum($month);
        }

        return $result;
    }

    protected function attachRealization($programs)
    {
        $programIds = $programs->pluck('id')->all();
        $realizations = ProgramRealization::whereIn('erkap_work_program_id', $programIds ?: [-1])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get()
            ->keyBy('erkap_work_program_id');

        return $programs->map(function ($program) use ($realizations) {
            $program->setRelation('latestRealization', $realizations->get($program->id));

            return $program;
        });
    }

    protected function scoreLevel(int $score): string
    {
        return match (true) {
            $score >= 21 => 'VH',
            $score >= 16 => 'H',
            $score >= 11 => 'M',
            $score >= 6 => 'L',
            default => 'VL',
        };
    }
}