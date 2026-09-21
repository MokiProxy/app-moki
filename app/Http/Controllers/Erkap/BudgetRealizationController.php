<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBudgetRealizationRequest;
use App\Models\Division;
use App\Models\Erkap\BudgetRealization;
use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\RoutineCost;
use App\Services\AccountingIntegrationService;
use App\Services\ErkapAccess;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BudgetRealizationController extends Controller
{
    protected $monthLabels = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    public function index(Request $request)
    {
        $pageName = 'Realisasi Anggaran (BvA)';
        $rkaps = RKAP::orderByDesc('year')->get();
        $selectedRkap = RKAP::find($request->integer('rkap_id')) ?? $rkaps->first();
        $month = $request->integer('month', now()->month);
        $year = $selectedRkap ? (int) $selectedRkap->year : now()->year;

        $realizations = BudgetRealization::with(['rkap', 'routineCost.workProgram.riskIdentification.departmentTarget.division', 'investmentPlan.workProgram.riskIdentification.departmentTarget.division'])
            ->where('year', $year)
            ->where('month', $month)
            ->latest()
            ->paginate(10);

        $bvaRows = $selectedRkap
            ? $this->calculateBvA($selectedRkap->id, $month, $year)
            : [];

        $monthLabels = $this->monthLabels;

        return view('erkap.budget-realizations.index', compact('pageName', 'rkaps', 'realizations', 'bvaRows', 'monthLabels', 'month', 'year', 'selectedRkap'));
    }

    public function create()
    {
        $pageName = 'Input Realisasi Anggaran';
        $rkaps = RKAP::orderByDesc('year')->get();
        $workProgramIds = ErkapAccess::workProgramIds();
        $routineCosts = RoutineCost::with('workProgram.riskIdentification.departmentTarget.division')
            ->whereIn('erkap_work_program_id', $workProgramIds)
            ->orderBy('id')
            ->get();
        $investmentPlans = InvestmentPlan::with('workProgram.riskIdentification.departmentTarget.division')
            ->whereIn('erkap_work_program_id', $workProgramIds)
            ->orderBy('id')
            ->get();
        $monthLabels = $this->monthLabels;

        return view('erkap.budget-realizations.create', compact('pageName', 'rkaps', 'routineCosts', 'investmentPlans', 'monthLabels'));
    }

    public function store(StoreBudgetRealizationRequest $request)
    {
        try {
            $data = $request->validated();

            $budgeted = 0;
            if (! empty($data['erkap_routine_cost_id'])) {
                $cost = RoutineCost::find($data['erkap_routine_cost_id']);
                $budgeted = $cost ? (float) $cost->{$this->costColumn($data['month'])} : 0;
            } elseif (! empty($data['erkap_investment_plan_id'])) {
                $plan = InvestmentPlan::find($data['erkap_investment_plan_id']);
                $budgeted = $plan ? (float) $plan->{$this->planColumn($data['month'])} : 0;
            }

            $realization = BudgetRealization::updateOrCreate(
                [
                    'erkap_rkap_id' => $data['erkap_rkap_id'],
                    'erkap_routine_cost_id' => $data['erkap_routine_cost_id'] ?? null,
                    'erkap_investment_plan_id' => $data['erkap_investment_plan_id'] ?? null,
                    'month' => $data['month'],
                    'year' => $data['year'],
                ],
                [
                    'budgeted' => $budgeted,
                    'realized' => $data['realized'],
                    'source' => $data['source'],
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]
            );
            $realization->calculateVariance();

            return redirect()->route('erkap.budget-realizations.index')
                ->with('success', 'Realisasi anggaran berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.budget-realizations.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function import(Request $request, AccountingIntegrationService $service)
    {
        try {
            $data = $request->validate([
                'erkap_rkap_id' => ['required', 'integer', 'exists:erkap_rkap,id'],
                'month' => ['required', 'integer', 'between:1,12'],
                'year' => ['required', 'integer'],
                'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
            ]);

            $count = $service->importFromFile(
                $request->file('file'),
                (int) $data['month'],
                (int) $data['year'],
                (int) $data['erkap_rkap_id']
            );

            return redirect()->route('erkap.budget-realizations.index')
                ->with('success', "Import realisasi berhasil: {$count} baris diproses.");
        } catch (Exception $err) {
            return redirect()->route('erkap.budget-realizations.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(BudgetRealization $budgetRealization)
    {
        try {
            $budgetRealization->delete();

            return redirect()->route('erkap.budget-realizations.index')
                ->with('success', 'Realisasi anggaran berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.budget-realizations.index')->with('error', $err->getMessage());
        }
    }

    protected function calculateBvA(int $rkapId, int $month, int $year): array
    {
        $routineColumn = $this->costColumn($month);
        $investmentColumn = $this->planColumn($month);
        $divisionNames = Division::pluck('name', 'id')->all();

        $budget = [];

        RoutineCost::with('workProgram.riskIdentification.departmentTarget')
            ->get()
            ->each(function ($cost) use (&$budget, $routineColumn, $year) {
                if ($this->rkapYearOf($cost->workProgram) !== $year) {
                    return;
                }
                $divisionId = $this->divisionIdOf($cost->workProgram);
                $budget[$divisionId] = ($budget[$divisionId] ?? 0) + (float) $cost->{$routineColumn};
            });

        InvestmentPlan::with('workProgram.riskIdentification.departmentTarget')
            ->get()
            ->each(function ($plan) use (&$budget, $investmentColumn, $year) {
                if ($this->rkapYearOf($plan->workProgram) !== $year) {
                    return;
                }
                $divisionId = $this->divisionIdOf($plan->workProgram);
                $budget[$divisionId] = ($budget[$divisionId] ?? 0) + (float) $plan->{$investmentColumn};
            });

        $actual = [];

        BudgetRealization::with('routineCost.workProgram.riskIdentification.departmentTarget', 'investmentPlan.workProgram.riskIdentification.departmentTarget')
            ->where('erkap_rkap_id', $rkapId)
            ->where('month', $month)
            ->where('year', $year)
            ->get()
            ->each(function ($realization) use (&$actual) {
                $workProgram = $realization->routineCost?->workProgram ?? $realization->investmentPlan?->workProgram;
                $divisionId = $this->divisionIdOf($workProgram);
                $actual[$divisionId] = ($actual[$divisionId] ?? 0) + (float) $realization->realized;
            });

        $rows = [];

        foreach (array_unique(array_merge(array_keys($budget), array_keys($actual))) as $divisionId) {
            if ($divisionId === null) {
                continue;
            }

            $budgeted = $budget[$divisionId] ?? 0;
            $realized = $actual[$divisionId] ?? 0;

            $rows[] = [
                'division_id' => $divisionId,
                'division_name' => $divisionNames[$divisionId] ?? '-',
                'budget' => $budgeted,
                'actual' => $realized,
                'variance' => $realized - $budgeted,
                'variance_percent' => $budgeted > 0
                    ? round((($realized - $budgeted) / $budgeted) * 100, 2)
                    : 0,
            ];
        }

        usort($rows, fn ($a, $b) => $a['division_name'] <=> $b['division_name']);

        return $rows;
    }

    protected function divisionIdOf($workProgram): ?int
    {
        if (! $workProgram || ! $workProgram->riskIdentification || ! $workProgram->riskIdentification->departmentTarget) {
            return null;
        }

        return $workProgram->riskIdentification->departmentTarget->division_id;
    }

    protected function rkapYearOf($workProgram): ?string
    {
        $departmentTarget = $workProgram?->riskIdentification?->departmentTarget;

        return $departmentTarget?->companyTarget?->rkap?->year;
    }

    protected function costColumn(int $month): string
    {
        $names = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'des'];

        return $names[$month - 1] . '_cost';
    }

    protected function planColumn(int $month): string
    {
        $names = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];

        return $names[$month - 1] . '_plan';
    }
}