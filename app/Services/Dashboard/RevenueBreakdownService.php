<?php

namespace App\Services\Dashboard;

use App\Models\Erkap\RevenuePlan;
use App\Services\ErkapAccess;

class RevenueBreakdownService
{
    public function data(?int $rkapId = null): array
    {
        $rkap = $rkapId ? \App\Models\Erkap\RKAP::find($rkapId) : null;
        $isDivisionScoped = ErkapAccess::isDivisionScoped();

        $rows = RevenuePlan::with('chartOfAccount')
            ->when($rkap, fn ($q) => $q->where('erkap_rkap_id', $rkap->id))
            ->when($isDivisionScoped, fn ($q) => $q->where('division_id', ErkapAccess::divisionId()))
            ->get();

        $grouped = [];

        foreach ($rows as $row) {
            $name = $row->chartOfAccount?->name ?? $row->description ?? 'Pendapatan Lainnya';

            if (! isset($grouped[$name])) {
                $grouped[$name]['label'] = $name;
                $grouped[$name]['monthly'] = array_fill(0, 12, 0.0);
            }

            foreach (RevenuePlan::monthColumns() as $index => $column) {
                $grouped[$name]['monthly'][$index] += (float) $row->{$column};
            }
        }

        $categories = collect($grouped)
            ->map(function ($group) {
                return [
                    'label' => $group['label'],
                    'total' => (float) array_sum($group['monthly']),
                    'monthly' => array_values($group['monthly']),
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();

        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            'categories' => $categories,
            'total' => (float) collect($categories)->sum('total'),
        ];
    }
}