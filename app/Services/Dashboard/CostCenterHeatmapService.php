<?php

namespace App\Services\Dashboard;

use App\Models\Erkap\BudgetRealization;

class CostCenterHeatmapService
{
    public function data(?int $rkapId = null, ?int $year = null, int $limit = 12): array
    {
        $year = $year ?? (int) date('Y');
        $workProgramIds = DashboardScope::workProgramIds($rkapId);

        $realizations = BudgetRealization::with('routineCost.costCenter', 'routineCost.workProgram')
            ->where('year', $year)
            ->whereNotNull('erkap_routine_cost_id')
            ->whereIn('erkap_routine_cost_id', function ($q) use ($workProgramIds) {
                $q->select('id')->from('erkap_routine_costs')->whereIn('erkap_work_program_id', $workProgramIds ?: [-1]);
            })
            ->when($rkapId, function ($q) use ($rkapId) {
                $q->where('erkap_rkap_id', $rkapId);
            })
            ->get();

        $matrix = [];
        $labels = [];

        foreach ($realizations as $realization) {
            $costCenter = $realization->routineCost?->costCenter;
            $key = $costCenter ? $costCenter->id : 0;
            $name = $costCenter ? $costCenter->name : 'Tanpa Cost Center';
            $index = max(0, min(11, (int) $realization->month - 1));

            $matrix[$key]['id'] = $key;
            $matrix[$key]['name'] = $name;
            $matrix[$key]['monthly'][$index]['budgeted'] = ($matrix[$key]['monthly'][$index]['budgeted'] ?? 0) + (float) $realization->budgeted;
            $matrix[$key]['monthly'][$index]['realized'] = ($matrix[$key]['monthly'][$index]['realized'] ?? 0) + (float) $realization->realized;
        }

        $rows = [];
        foreach ($matrix as $key => $item) {
            $cells = array_fill(0, 12, null);

            foreach ($item['monthly'] as $index => $values) {
                $budgeted = $values['budgeted'];
                $realized = $values['realized'];
                $cells[$index] = [
                    'budgeted' => $budgeted,
                    'realized' => $realized,
                    'variance' => $realized - $budgeted,
                    'variance_percent' => $budgeted > 0 ? round(($realized - $budgeted) / $budgeted * 100, 2) : 0,
                ];
            }

            $rows[] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'cells' => $cells,
                'budgeted' => (float) collect($item['monthly'])->sum('budgeted'),
                'realized' => (float) collect($item['monthly'])->sum('realized'),
            ];
        }

        usort($rows, fn ($a, $b) => $a['name'] <=> $b['name']);
        $rows = array_slice($rows, 0, $limit);

        return [
            'year' => $year,
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            'rows' => $rows,
        ];
    }
}