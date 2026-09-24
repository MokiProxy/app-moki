<?php

namespace App\Services\Dashboard;

use App\Models\Erkap\ExpensePlan;
use App\Services\ErkapAccess;

class ExpenseBreakdownService
{
    public function data(?int $rkapId = null): array
    {
        $rkap = $rkapId ? \App\Models\Erkap\RKAP::find($rkapId) : null;
        $isDivisionScoped = ErkapAccess::isDivisionScoped();

        $rows = ExpensePlan::with('chartOfAccount')
            ->when($rkap, fn ($q) => $q->where('erkap_rkap_id', $rkap->id))
            ->when($isDivisionScoped, fn ($q) => $q->where('division_id', ErkapAccess::divisionId()))
            ->get();

        $grouped = [];

        foreach ($rows as $row) {
            $name = $row->chartOfAccount?->name ?? $row->description ?? 'Beban Lainnya';

            if (! isset($grouped[$name])) {
                $grouped[$name]['label'] = $name;
                $grouped[$name]['monthly'] = array_fill(0, 12, 0.0);
            }

            foreach (ExpensePlan::monthColumns() as $index => $column) {
                $grouped[$name]['monthly'][$index] += (float) $row->{$column};
            }
        }

        $categories = collect($grouped)
            ->map(function ($group) {
                $monthly = array_values($group['monthly']);

                return [
                    'label' => $group['label'],
                    'total' => (float) array_sum($monthly),
                    'monthly' => $monthly,
                    'current_month' => (float) ($monthly[now()->month - 1] ?? 0),
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();

        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            'categories' => $categories,
            'total' => (float) collect($categories)->sum('total'),
            'current_month' => (float) collect($categories)->sum('current_month'),
        ];
    }
}