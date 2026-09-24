<?php

namespace App\Http\Controllers\Erkap;

use App\Exports\Erkap\DrilldownExport;
use App\Http\Controllers\Controller;
use App\Services\Dashboard\DrilldownService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DashboardDrilldownController extends Controller
{
    public function show(Request $request, string $type, int $id)
    {
        if (! in_array($type, DrilldownService::TYPES, true)) {
            abort(404);
        }

        $data = app(DrilldownService::class)->resolve($id, $type)[$type];

        $data['type'] = $type;
        $data['id'] = $id;

        return view('erkap.dashboard.drilldown.show', $data);
    }

    public function export(Request $request, string $type, int $id)
    {
        if (! in_array($type, DrilldownService::TYPES, true)) {
            abort(404);
        }

        $service = app(DrilldownService::class);
        $rows = $service->rows($type, $id);
        $title = $service->resolve($id, $type)[$type]['title'] ?? 'Detail';

        return Excel::download(
            new DrilldownExport($rows, $title),
            'drilldown-' . $type . '-' . $id . '.xlsx'
        );
    }
}