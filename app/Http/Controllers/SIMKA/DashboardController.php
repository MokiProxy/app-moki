<?php

namespace App\Http\Controllers\SIMKA;

use App\Http\Controllers\Controller;
use App\Models\Erkap\RKAP;
use App\Services\Simka\DashboardSummaryService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private DashboardSummaryService $summaryService)
    {
    }

    public function index(Request $request)
    {
        $rkaps = RKAP::orderByDesc('year')->get();

        $requestedRkapId = $request->integer('rkap_id');
        $selectedRkap = $requestedRkapId > 0
            ? $rkaps->firstWhere('id', $requestedRkapId)
            : null;
        $selectedRkap ??= $rkaps->first();

        $summary = $this->summaryService->build($selectedRkap);

        return view('simka.dashboard.index', compact('summary', 'rkaps', 'selectedRkap'));
    }
}
