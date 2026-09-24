<?php

namespace App\Services\Dashboard;

use App\Models\Erkap\WorkProgram;

class DependencyMapService
{
    public function data(?int $rkapId = null): array
    {
        $programs = WorkProgram::with('dependsOn')
            ->whereIn('id', DashboardScope::workProgramIds($rkapId) ?: [-1])
            ->get();

        $nodes = [];
        $edges = [];
        $included = [];

        foreach ($programs as $program) {
            $included[$program->id] = true;
            $nodes[] = [
                'id' => $program->id,
                'label' => $program->name,
                'code' => $program->code,
                'status' => $program->status,
            ];
        }

        foreach ($programs as $program) {
            if ($program->depends_on_work_program_id && isset($included[$program->depends_on_work_program_id])) {
                $edges[] = [
                    'from' => $program->depends_on_work_program_id,
                    'to' => $program->id,
                    'label' => 'tergantung',
                ];
            }
        }

        return [
            'nodes' => $nodes,
            'edges' => $edges,
        ];
    }
}