<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePerformanceScorecardRequest;
use App\Models\Erkap\DepartmentTarget;
use App\Models\Erkap\PerformanceScorecard;
use App\Models\Erkap\RKAP;
use App\Services\ErkapAccess;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PerformanceScorecardController extends Controller
{
    public function index(Request $request)
    {
        $pageName = 'Performance Scorecard (KPI)';
        $rkaps = RKAP::orderByDesc('year')->get();
        $selectedRkap = RKAP::find($request->integer('rkap_id')) ?? $rkaps->first();
        $year = $selectedRkap ? (int) $selectedRkap->year : now()->year;

        $scorecards = PerformanceScorecard::with(['rkap', 'departmentTarget.division'])
            ->where('year', $year)
            ->when($request->filled('quarter'), fn ($q) => $q->where('quarter', $request->integer('quarter')))
            ->latest()
            ->paginate(10);

        $totalWeighted = PerformanceScorecard::where('year', $year)
            ->when($request->filled('quarter'), fn ($q) => $q->where('quarter', $request->integer('quarter')))
            ->sum('weighted_score');

        return view('erkap.performance-scorecards.index', compact('pageName', 'rkaps', 'scorecards', 'totalWeighted', 'year', 'selectedRkap'));
    }

    public function create()
    {
        $pageName = 'Input Performa KPI';
        $rkaps = RKAP::orderByDesc('year')->get();
        $departmentTargets = DepartmentTarget::with('division')
            ->whereIn('id', ErkapAccess::departmentTargetIds())
            ->orderBy('id')
            ->get();

        return view('erkap.performance-scorecards.create', compact('pageName', 'rkaps', 'departmentTargets'));
    }

    public function store(StorePerformanceScorecardRequest $request)
    {
        try {
            ErkapAccess::assertDepartmentTargetAccess($request->integer('erkap_department_target_id'));

            $data = $request->validated();
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            $scorecard = PerformanceScorecard::updateOrCreate(
                [
                    'erkap_rkap_id' => $data['erkap_rkap_id'],
                    'erkap_department_target_id' => $data['erkap_department_target_id'],
                    'quarter' => $data['quarter'],
                    'year' => $data['year'],
                    'kpi_name' => $data['kpi_name'],
                ],
                $data
            );
            $scorecard->calculateWeightedScore();

            return redirect()->route('erkap.performance-scorecards.index')
                ->with('success', 'Scorecard KPI berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.performance-scorecards.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(PerformanceScorecard $performanceScorecard)
    {
        try {
            $performanceScorecard->delete();

            return redirect()->route('erkap.performance-scorecards.index')
                ->with('success', 'Scorecard KPI berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.performance-scorecards.index')->with('error', $err->getMessage());
        }
    }
}