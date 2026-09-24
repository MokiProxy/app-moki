<?php

namespace App\Services\Dashboard;

use App\Models\Erkap\ProgramRealization;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\WorkProgram;

class GanttWidgetService
{
    protected const PLAN_MONTHS = [
        'jan_plan', 'feb_plan', 'mar_plan', 'apr_plan', 'may_plan', 'jun_plan',
        'jul_plan', 'aug_plan', 'sep_plan', 'oct_plan', 'nov_plan', 'dec_plan',
    ];

    public function data(?int $rkapId = null, int $limit = 8): array
    {
        $programs = WorkProgram::with('riskIdentification.departmentTarget.division', 'dependsOn')
            ->whereIn('id', DashboardScope::workProgramIds($rkapId) ?: [-1])
            ->orderByDesc('year_plan')
            ->limit($limit)
            ->get();

        $programIds = $programs->pluck('id')->all();
        $realizations = ProgramRealization::whereIn('erkap_work_program_id', $programIds ?: [-1])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get()
            ->keyBy('erkap_work_program_id');

        $items = $programs->map(function (WorkProgram $program) use ($realizations) {
            $start = null;
            $end = null;

            foreach (self::PLAN_MONTHS as $index => $column) {
                if ((float) $program->{$column} > 0) {
                    $start = $start ?? $index;
                    $end = $index;
                }
            }

            return [
                'id' => $program->id,
                'name' => $program->name,
                'code' => $program->code,
                'division' => $program->riskIdentification?->departmentTarget?->division?->name ?? '-',
                'start_month' => $start,
                'end_month' => $end,
                'progress' => (float) ($realizations->get($program->id)?->percent_complete ?? 0),
                'status' => $program->status,
                'depends_on_work_program_id' => $program->depends_on_work_program_id,
            ];
        })->values()->all();

        return [
            'month_labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            'items' => $items,
        ];
    }
}