<?php

namespace App\Services\Dashboard;

use App\Models\Erkap\RKAP;
use App\Models\Erkap\RevenuePlan;
use App\Models\Erkap\ExpensePlan;
use App\Services\ErkapAccess;

class ScenarioComparisonService
{
    protected const SCENARIOS = [
        'best' => ['label' => 'Optimis (Best Case)', 'multiplier' => 1.1],
        'base' => ['label' => 'Realistis (Base Case)', 'multiplier' => 1.0],
        'worst' => ['label' => 'Pesimis (Worst Case)', 'multiplier' => 0.9],
    ];

    public function data(?int $rkapId = null, ?int $divisionId = null): array
    {
        $rkap = $rkapId ? RKAP::find($rkapId) : null;
        $isDivisionScoped = ErkapAccess::isDivisionScoped();

        if ($isDivisionScoped) {
            $divisionId = ErkapAccess::divisionId();
        }

        $revenueRows = RevenuePlan::query()
            ->when($rkap, fn ($q) => $q->where('erkap_rkap_id', $rkap->id))
            ->when($divisionId, fn ($q) => $q->where('division_id', $divisionId))
            ->get();

        $expenseRows = ExpensePlan::query()
            ->when($rkap, fn ($q) => $q->where('erkap_rkap_id', $rkap->id))
            ->when($divisionId, fn ($q) => $q->where('division_id', $divisionId))
            ->get();

        $monthlyRevenue = $this->sumMonthly($revenueRows, RevenuePlan::monthColumns());
        $monthlyExpense = $this->sumMonthly($expenseRows, ExpensePlan::monthColumns());

        $baseRevenue = (float) array_sum($monthlyRevenue);
        $baseExpense = (float) array_sum($monthlyExpense);

        $results = [];

        foreach (self::SCENARIOS as $key => $scenario) {
            $multiplier = $scenario['multiplier'];
            $revenue = round($baseRevenue * $multiplier, 2);
            $expense = round($baseExpense * $multiplier, 2);
            $profit = round($revenue - $expense, 2);

            $results[$key] = [
                'label' => $scenario['label'],
                'multiplier' => $multiplier,
                'revenue' => $revenue,
                'expense' => $expense,
                'profit' => $profit,
                'margin' => $revenue > 0 ? round($profit / $revenue * 100, 2) : 0,
            ];
        }

        return [
            'scenarios' => $results,
            'labels' => ['Pendapatan', 'Beban', 'Laba Rugi'],
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
}