<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateBudgetCapexRequest;
use App\Models\Division;
use App\Models\Erkap\BudgetCapex;
use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\RKAP;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetCapexController extends Controller
{
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
            ->get();

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

    public function consolidate(Request $request)
    {
        try {
            $request->validate([
                'erkap_rkap_id' => ['required', 'integer', 'exists:erkap_rkap,id'],
            ]);

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
        $months = [
            'jan_plan', 'feb_plan', 'mar_plan', 'apr_plan', 'may_plan', 'jun_plan',
            'jul_plan', 'aug_plan', 'sep_plan', 'oct_plan', 'nov_plan', 'dec_plan',
        ];

        $result = [];
        foreach ($months as $month) {
            $result[$month] = $investmentPlans->sum($month);
        }

        return $result;
    }
}