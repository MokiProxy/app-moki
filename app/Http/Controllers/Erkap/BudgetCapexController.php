<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateBudgetCapexRequest;
use App\Models\Division;
use App\Models\Erkap\BudgetCapex;
use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\RKAP;
use App\Services\ErkapAccess;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetCapexController extends Controller
{
    private const MONTHS = [
        'jan_plan', 'feb_plan', 'mar_plan', 'apr_plan', 'may_plan', 'jun_plan',
        'jul_plan', 'aug_plan', 'sep_plan', 'oct_plan', 'nov_plan', 'dec_plan',
    ];

    public function index()
    {
        $pageName = 'Anggaran Investasi (CAPEX)';
        $rkapList = RKAP::orderByDesc('year')->get();
        $budgetCapex = BudgetCapex::with(['rkap', 'division'])
            ->paginate(10);

        return view('erkap.budget-capex.index', compact('pageName', 'budgetCapex', 'rkapList'));
    }

    public function show(BudgetCapex $budgetCapex)
    {
        $pageName = 'Detail Anggaran Investasi';

        $investmentPlans = InvestmentPlan::with(['workProgram', 'investattionCategory', 'investationType', 'investationCriteria'])
            ->whereHas('workProgram.riskIdentification.departmentTarget', function ($query) use ($budgetCapex) {
                $query->where('division_id', $budgetCapex->division_id);
            })
            ->get()
            ->sortBy(fn (InvestmentPlan $plan) => $plan->priority_order ?? PHP_INT_MAX)
            ->values();

        $totalByMonth = $this->sumMonthly($investmentPlans);

        return view('erkap.budget-capex.show', compact('pageName', 'budgetCapex', 'investmentPlans', 'totalByMonth'));
    }

    public function update(UpdateBudgetCapexRequest $request, BudgetCapex $budgetCapex)
    {
        try {
            $budgetCapex->update($request->validated());

            return redirect()->route('erkap.budget-capex.show', $budgetCapex->id)
                ->with('success', 'Status anggaran investasi berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.budget-capex.show', $budgetCapex->id)
                ->with('error', $err->getMessage());
        }
    }

    public function summary(Request $request)
    {
        $pageName = 'Ringkasan Nilai Investasi (CAPEX)';
        $rkapList = RKAP::orderByDesc('year')->get();
        $rkap = $request->integer('erkap_rkap_id')
            ? RKAP::find($request->integer('erkap_rkap_id'))
            : $rkapList->first();

        $budgetsByDivision = BudgetCapex::query()
            ->when($rkap, fn ($query) => $query->where('erkap_rkap_id', $rkap->id))
            ->get()
            ->keyBy('division_id');

        $investmentPlans = InvestmentPlan::with([
            'workProgram.riskIdentification.departmentTarget.division',
            'investattionCategory',
            'investationType',
            'investationCriteria',
        ])
            ->when($rkap, function ($query) use ($budgetsByDivision) {
                $query->whereHas('workProgram.riskIdentification.departmentTarget', function ($q) use ($budgetsByDivision) {
                    $q->whereIn('division_id', $budgetsByDivision->keys()->all());
                });
            })
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->whereIn('erkap_work_program_id', ErkapAccess::workProgramIds());
            })
            ->get()
            ->sortBy(fn (InvestmentPlan $plan) => [
                $plan->workProgram?->riskIdentification?->departmentTarget?->division_id ?? PHP_INT_MAX,
                $plan->priority_order ?? PHP_INT_MAX,
            ])
            ->values();

        $rows = $investmentPlans->map(function (InvestmentPlan $plan) use ($budgetsByDivision) {
            $division = $plan->workProgram?->riskIdentification?->departmentTarget?->division;
            $budget = $budgetsByDivision->get($division?->id);
            $budgetCurrentYear = (float) ($budget->total_investment ?? 0);

            return [
                'division' => $division->name ?? '-',
                'category' => $plan->investattionCategory,
                'type' => $plan->investationType,
                'criteria' => $plan->investationCriteria,
                'work_program' => $plan->workProgram?->name ?? '-',
                'name' => $plan->name,
                'priority' => $plan->priority_order,
                'plan_total' => (float) $plan->total,
                'previous_year_remaining' => (float) ($budget->previous_year_remaining ?? 0),
                'budget_current_year' => $budgetCurrentYear,
                'budget_total' => (float) ($budget->previous_year_remaining ?? 0) + $budgetCurrentYear,
            ];
        });

        $byDivision = $rows->groupBy('division');

        return view('erkap.budget-capex.summary', compact('pageName', 'rkapList', 'rkap', 'rows', 'byDivision'));
    }

    public function paymentDistribution(Request $request)
    {
        $pageName = 'Distribusi Pembayaran Investasi (CAPEX)';
        $rkapList = RKAP::orderByDesc('year')->get();
        $rkap = $request->integer('erkap_rkap_id')
            ? RKAP::find($request->integer('erkap_rkap_id'))
            : $rkapList->first();

        $investmentPlans = InvestmentPlan::with([
            'workProgram.riskIdentification.departmentTarget.division',
        ])
            ->when($rkap, function ($query) use ($rkap) {
                $divisionIds = BudgetCapex::where('erkap_rkap_id', $rkap->id)->pluck('division_id');
                $query->whereHas('workProgram.riskIdentification.departmentTarget', function ($q) use ($divisionIds) {
                    $q->whereIn('division_id', $divisionIds);
                });
            })
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->whereIn('erkap_work_program_id', ErkapAccess::workProgramIds());
            })
            ->get()
            ->sortBy(fn (InvestmentPlan $plan) => [
                $plan->workProgram?->riskIdentification?->departmentTarget?->division_id ?? PHP_INT_MAX,
                $plan->priority_order ?? PHP_INT_MAX,
            ])
            ->values();

        $totalByMonth = $this->sumMonthly($investmentPlans);
        $grandTotal = (float) $investmentPlans->sum('total');

        return view('erkap.budget-capex.payment-distribution', compact('pageName', 'rkapList', 'rkap', 'investmentPlans', 'totalByMonth', 'grandTotal'));
    }

    public function consolidate(Request $request)
    {
        try {
            $request->validate([
                'erkap_rkap_id' => ['required', 'integer', 'exists:erkap_rkap,id'],
            ]);

            $rkap = RKAP::findOrFail($request->integer('erkap_rkap_id'));

            \App\Services\Erkap\ZBBReviewService::requireRationale($rkap);

            $investmentPlans = InvestmentPlan::with('workProgram.riskIdentification.departmentTarget')->get();

            $grouped = $investmentPlans->groupBy(function ($plan) {
                return $plan->workProgram?->riskIdentification?->departmentTarget?->division_id;
            });

            foreach ($grouped as $divisionId => $plans) {
                if (! $divisionId) {
                    continue;
                }

                $totalInvestment = $plans->sum('total');

                BudgetCapex::updateOrCreate(
                    ['erkap_rkap_id' => $request->integer('erkap_rkap_id'), 'division_id' => $divisionId],
                    ['total_investment' => $totalInvestment]
                );
            }

            return redirect()->route('erkap.budget-capex.index')
                ->with('success', 'Konsolidasi anggaran investasi berhasil dilakukan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.budget-capex.index')
                ->with('error', $err->getMessage());
        }
    }

    private function sumMonthly($investmentPlans): array
    {
        $result = [];
        foreach (self::MONTHS as $month) {
            $result[$month] = (float) $investmentPlans->sum($month);
        }

        return $result;
    }
}