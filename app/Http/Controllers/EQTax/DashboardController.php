<?php

namespace App\Http\Controllers\EQTax;

use App\Http\Controllers\Controller;
use App\Models\EQTAXCoretaxSPT;
use App\Models\EQTAXEqualizationResult;
use App\Models\EQTAXGL;
use Illuminate\Http\Request;

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

        // Filter parameters
        $filterMonthFrom = $request->input('month_from');
        $filterMonthTo = $request->input('month_to');
        $filterYear = $request->input('year');
        $filterStatus = $request->input('status');
        $filterSearch = $request->input('search');

        // Build equalization query with filters
        $equalizationQuery = EQTAXEqualizationResult::query();

        // Filter by search keyword (no faktur, nama penjual, periode, status)
        if ($filterSearch) {
            $searchTerm = '%' . trim($filterSearch) . '%';
            $equalizationQuery->where(function ($q) use ($searchTerm) {
                $q->where('no_faktur_pajak', 'like', $searchTerm)
                    ->orWhere('nama_penjual', 'like', $searchTerm)
                    ->orWhere('period', 'like', $searchTerm)
                    ->orWhere('status', 'like', $searchTerm);
            });
        }

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
            'recentEqualization',
            'years',
            'months',
            'filterYear',
            'filterMonthFrom',
            'filterMonthTo',
            'filterStatus',
            'filterSearch'
        ));
    }

    public function getFilteredData(Request $request)
    {
        $type = $request->input('type');
        $filterYear = $request->input('year');
        $filterMonthFrom = $request->input('month_from');
        $filterMonthTo = $request->input('month_to');
        $filterStatus = $request->input('status');
        $search = $request->input('search');

        $query = EQTAXEqualizationResult::query();

        if ($type === 'kurang_bayar') {
            $query->where('selisih_ppn', '>', 0);
        } elseif ($type === 'lebih_bayar') {
            $query->where('selisih_ppn', '<', 0);
        }

        if ($search) {
            $searchTerm = '%' . trim($search) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('no_faktur_pajak', 'like', $searchTerm)
                    ->orWhere('nama_penjual', 'like', $searchTerm)
                    ->orWhere('period', 'like', $searchTerm)
                    ->orWhere('status', 'like', $searchTerm);
            });
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
