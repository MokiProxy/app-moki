<?php

namespace App\Http\Controllers\EQTax;

use App\Http\Controllers\Controller;
use App\Models\EQTAXCoretaxSPT;
use App\Models\EQTAXEqualizationResult;
use App\Models\EQTAXGL;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $totalSpt = EQTAXCoretaxSPT::count();
        $totalGl = EQTAXGL::count();
        $totalPpnSpt = EQTAXCoretaxSPT::sum('ppn');
        $totalPpnGl = EQTAXGL::sum('ppn');
        $totalDppSpt = EQTAXCoretaxSPT::sum('dpp');
        $totalDppGl = EQTAXGL::sum('dpp');

        $entitySummary = EQTAXGL::select('entity', DB::raw('COUNT(*) as count'), DB::raw('SUM(ppn) as total_ppn'))
            ->groupBy('entity')
            ->get();

        // Filter parameters
        $filterMonthFrom = $request->input('month_from');
        $filterMonthTo = $request->input('month_to');
        $filterYear = $request->input('year');
        $filterEntity = $request->input('entity');
        $filterStatus = $request->input('status');

        // Build equalization query with filters
        $equalizationQuery = EQTAXEqualizationResult::query();

        // Filter by year (period format: YYYY-MM)
        if ($filterYear) {
            $equalizationQuery->where('period', 'like', "{$filterYear}%");
        }

        // Filter by month range (period format: YYYY-MM)
        if ($filterMonthFrom && $filterMonthTo) {
            $periodFrom = $filterYear ? "{$filterYear}-{$filterMonthFrom}" : $filterMonthFrom;
            $periodTo = $filterYear ? "{$filterYear}-{$filterMonthTo}" : $filterMonthTo;
            $equalizationQuery->whereBetween('period', [$periodFrom, $periodTo]);
        } elseif ($filterMonthFrom) {
            $periodFrom = $filterYear ? "{$filterYear}-{$filterMonthFrom}" : $filterMonthFrom;
            $equalizationQuery->where('period', '>=', $periodFrom);
        } elseif ($filterMonthTo) {
            $periodTo = $filterYear ? "{$filterYear}-{$filterMonthTo}" : $filterMonthTo;
            $equalizationQuery->where('period', '<=', $periodTo);
        }

        // Filter by entity
        if ($filterEntity) {
            $equalizationQuery->where('entity', $filterEntity);
        }

        // Filter by status
        if ($filterStatus) {
            $equalizationQuery->where('status', $filterStatus);
        }

        // Hitung selisih berdasarkan data yang difilter
        $filteredResults = $equalizationQuery->get();
        $selisihKurangBayar = $filteredResults->where('selisih_ppn', '>', 0)->sum('selisih_ppn');
        $selisihLebihBayar = $filteredResults->where('selisih_ppn', '<', 0)->sum('selisih_ppn');
        $countKurangBayar = $filteredResults->where('selisih_ppn', '>', 0)->count();
        $countLebihBayar = $filteredResults->where('selisih_ppn', '<', 0)->count();

        // Hitung total data berdasarkan status
        $statusSummary = [
            'MATCH' => $filteredResults->where('status', 'MATCH')->count(),
            'GL_ONLY' => $filteredResults->where('status', 'GL_ONLY')->count(),
            'SPT_ONLY' => $filteredResults->where('status', 'SPT_ONLY')->count(),
            'TO_BE_CHECK' => $filteredResults->where('status', 'TO_BE_CHECK')->count(),
        ];

        // Data untuk chart trend selisih per periode
        $chartData = $filteredResults->groupBy('period')->map(function ($items) {
            $kurangBayar = $items->where('selisih_ppn', '>', 0)->sum('selisih_ppn');
            $lebihBayar = abs($items->where('selisih_ppn', '<', 0)->sum('selisih_ppn'));
            return [
                'kurang_bayar' => $kurangBayar,
                'lebih_bayar' => $lebihBayar,
            ];
        })->sortKeys();

        $chartLabels = $chartData->keys()->toArray();
        $chartKurangBayar = $chartData->pluck('kurang_bayar')->toArray();
        $chartLebihBayar = $chartData->pluck('lebih_bayar')->toArray();

        $recentEqualization = $equalizationQuery->orderByDesc('period')->orderByDesc('selisih_ppn')->paginate(20)->appends($request->query());

        // Get distinct values for filters
        $distinctPeriods = EQTAXEqualizationResult::select('period')
            ->distinct()
            ->orderBy('period', 'desc')
            ->pluck('period');

        $distinctEntities = EQTAXEqualizationResult::select('entity')
            ->distinct()
            ->whereNotNull('entity')
            ->orderBy('entity')
            ->pluck('entity');

        // Extract unique years and months from periods
        $years = $distinctPeriods->map(fn($p) => substr($p, 0, 4))->unique()->values();
        $months = $distinctPeriods->map(fn($p) => substr($p, 5, 2))->unique()->sort()->values();

        return view('eqtax.dashboard.index', compact(
            'totalSpt',
            'totalGl',
            'totalPpnSpt',
            'totalPpnGl',
            'totalDppSpt',
            'totalDppGl',
            'selisihKurangBayar',
            'selisihLebihBayar',
            'countKurangBayar',
            'countLebihBayar',
            'statusSummary',
            'chartLabels',
            'chartKurangBayar',
            'chartLebihBayar',
            'entitySummary',
            'recentEqualization',
            'years',
            'months',
            'distinctEntities',
            'filterYear',
            'filterMonthFrom',
            'filterMonthTo',
            'filterEntity',
            'filterStatus'
        ));
    }

    public function getFilteredData(Request $request)
    {
        $type = $request->input('type');
        $filterYear = $request->input('year');
        $filterMonthFrom = $request->input('month_from');
        $filterMonthTo = $request->input('month_to');
        $filterEntity = $request->input('entity');
        $filterStatus = $request->input('status');

        $query = EQTAXEqualizationResult::query();

        if ($type === 'kurang_bayar') {
            $query->where('selisih_ppn', '>', 0);
        } elseif ($type === 'lebih_bayar') {
            $query->where('selisih_ppn', '<', 0);
        }

        if ($filterYear) {
            $query->where('period', 'like', "{$filterYear}%");
        }

        if ($filterMonthFrom && $filterMonthTo) {
            $periodFrom = $filterYear ? "{$filterYear}-{$filterMonthFrom}" : $filterMonthFrom;
            $periodTo = $filterYear ? "{$filterYear}-{$filterMonthTo}" : $filterMonthTo;
            $query->whereBetween('period', [$periodFrom, $periodTo]);
        } elseif ($filterMonthFrom) {
            $periodFrom = $filterYear ? "{$filterYear}-{$filterMonthFrom}" : $filterMonthFrom;
            $query->where('period', '>=', $periodFrom);
        } elseif ($filterMonthTo) {
            $periodTo = $filterYear ? "{$filterYear}-{$filterMonthTo}" : $filterMonthTo;
            $query->where('period', '<=', $periodTo);
        }

        if ($filterEntity) {
            $query->where('entity', $filterEntity);
        }

        if ($filterStatus) {
            $query->where('status', $filterStatus);
        }

        $results = $query->orderByDesc('period')
                         ->orderByDesc('selisih_ppn')
                         ->paginate(20)
                         ->appends($request->query());

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
