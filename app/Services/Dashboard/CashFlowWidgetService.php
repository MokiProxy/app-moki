<?php

namespace App\Services\Dashboard;

use App\Models\Erkap\BudgetRealization;
use App\Models\Erkap\ExpensePlan;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\RevenuePlan;
use App\Services\ErkapAccess;

class CashFlowWidgetService
{
    protected const MONTH_LABELS = [
        'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
    ];

    public function data(?int $rkapId = null, ?int $year = null): array
    {
        $year = $year ?? (int) date('Y');
        $rkap = $rkapId ? RKAP::find($rkapId) : null;
        $isDivisionScoped = ErkapAccess::isDivisionScoped();

        $revenueRows = RevenuePlan::query()
            ->when($rkap, fn ($q) => $q->where('erkap_rkap_id', $rkap->id))
            ->when($isDivisionScoped, fn ($q) => $q->where('division_id', ErkapAccess::divisionId()))
            ->get();

        $expenseRows = ExpensePlan::query()
            ->when($rkap, fn ($q) => $q->where('erkap_rkap_id', $rkap->id))
            ->when($isDivisionScoped, fn ($q) => $q->where('division_id', ErkapAccess::divisionId()))
            ->get();

        $operating = [];
        for ($index = 0; $index < 12; $index++) {
            $operating[$index] = (float) $revenueRows->sum(RevenuePlan::monthColumns()[$index])
                - (float) $expenseRows->sum(ExpensePlan::monthColumns()[$index]);
        }

        $investing = array_fill(0, 12, 0.0);
        $realizations = BudgetRealization::where('year', $year)
            ->when($rkap, fn ($q) => $q->where('erkap_rkap_id', $rkap->id))
            ->get();

        foreach ($realizations as $realization) {
            $index = max(0, min(11, (int) $realization->month - 1));
            if ($realization->erkap_investment_plan_id !== null) {
                $investing[$index] -= (float) $realization->realized;
            }
        }

        $financing = array_fill(0, 12, 0.0);
        $net = array_map(fn ($op, $inv, $fin) => $op + $inv + $fin, $operating, $investing, $financing);

        return [
            'labels' => self::MONTH_LABELS,
            'operating' => $operating,
            'investing' => $investing,
            'financing' => $financing,
            'net' => $net,
            'totals' => [
                'operating' => (float) array_sum($operating),
                'investing' => (float) array_sum($investing),
                'financing' => (float) array_sum($financing),
                'net' => (float) array_sum($net),
            ],
        ];
    }
}