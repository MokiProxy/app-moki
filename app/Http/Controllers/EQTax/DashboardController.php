<?php

namespace App\Http\Controllers\EQTax;

use App\Http\Controllers\Controller;
use App\Models\EQTAXCoretaxSPT;
use App\Models\EQTAXGL;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalSpt = EQTAXCoretaxSPT::count();
        $totalGl = EQTAXGL::count();
        $totalPpnSpt = EQTAXCoretaxSPT::sum('ppn');
        $totalPpnGl = EQTAXGL::sum('ppn');
        $totalDppSpt = EQTAXCoretaxSPT::sum('dpp');
        $totalDppGl = EQTAXGL::sum('dpp');
        $selisihPpn = $totalPpnSpt - $totalPpnGl;

        $entitySummary = EQTAXGL::select('entity', DB::raw('COUNT(*) as count'), DB::raw('SUM(ppn) as total_ppn'))
            ->groupBy('entity')
            ->get();

        $recentSpt = EQTAXCoretaxSPT::orderBy('created_at', 'desc')->limit(5)->get();
        $recentGl = EQTAXGL::orderBy('created_at', 'desc')->limit(5)->get();

        return view('eqtax.dashboard.index', compact(
            'totalSpt',
            'totalGl',
            'totalPpnSpt',
            'totalPpnGl',
            'totalDppSpt',
            'totalDppGl',
            'selisihPpn',
            'entitySummary',
            'recentSpt',
            'recentGl'
        ));
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
