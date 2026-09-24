<?php

namespace App\Services\Dashboard;

use App\Models\Erkap\BudgetRealization;

class TopVarianceService
{
    public function data(?int $rkapId = null, ?int $year = null, int $limit = 10): array
    {
        $year = $year ?? (int) date('Y');
        $workProgramIds = DashboardScope::workProgramIds($rkapId);

        $realizations = BudgetRealization::with('routineCost.costElement', 'routineCost.workProgram', 'investmentPlan.workProgram')
            ->where('year', $year)
            ->when($rkapId, fn ($q) => $q->where('erkap_rkap_id', $rkapId))
            ->get()
            ->filter(function ($realization) use ($workProgramIds) {
                $workProgram = $realization->routineCost?->workProgram ?? $realization->investmentPlan?->workProgram;

                return $workProgram && in_array($workProgram->id, $workProgramIds, true);
            });

        $grouped = [];

        foreach ($realizations as $realization) {
            if ($realization->erkap_routine_cost_id !== null) {
                $key = $realization->routineCost->costElement->id ?? 0;
                $name = $realization->routineCost->costElement->name ?? 'Tanpa Elemen';
                $type = 'opex';
            } else {
                $key = 'capex';
                $name = 'Investasi (CAPEX)';
                $type = 'capex';
            }

            $grouped[$key]['name'] = $name;
            $grouped[$key]['type'] = $type;
            $grouped[$key]['budgeted'] = ($grouped[$key]['budgeted'] ?? 0) + (float) $realization->budgeted;
            $grouped[$key]['realized'] = ($grouped[$key]['realized'] ?? 0) + (float) $realization->realized;
        }

        $rows = collect($grouped)->map(function ($group) {
            $group['variance'] = $group['realized'] - $group['budgeted'];
            $group['variance_percent'] = $group['budgeted'] > 0
                ? round($group['variance'] / $group['budgeted'] * 100, 2)
                : 0;

            return $group;
        })
            ->sortByDesc(fn ($group) => abs($group['variance']))
            ->values()
            ->take($limit)
            ->all();

        return [
            'year' => $year,
            'rows' => $rows,
            'total_budgeted' => (float) collect($rows)->sum('budgeted'),
            'total_realized' => (float) collect($rows)->sum('realized'),
        ];
    }
}