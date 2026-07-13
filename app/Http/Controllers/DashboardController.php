<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Statistik Utama
        $totalAsset = Asset::count();
        $assetUser  = Asset::where('status', 1)->count(); // Status 1: Terpakai
        $assetReady = Asset::where('status', 0)->count(); // Status 0: Standby

        // Perhitungan Persentase
        $percentUser  = $totalAsset > 0 ? round(($assetUser / $totalAsset) * 100) : 0;
        $percentReady = $totalAsset > 0 ? round(($assetReady / $totalAsset) * 100) : 0;

        // 2. Data Grafik Line (Tren 6 Bulan Terakhir)
        $months = [];
        $dataOut = [];
        $dataIn = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $months[] = $month->format('M');
            
            $dataOut[] = Transaction::where('type', 'OUT')->whereMonth('created_at', $month->month)->whereYear('created_at', $month->year)->count();
            $dataIn[]  = Transaction::where('type', 'IN')->whereMonth('created_at', $month->month)->whereYear('created_at', $month->year)->count();
        }

        // 3. Distribusi Kategori (Top 5)
        $categories = Category::withCount('assets')
            ->orderBy('assets_count', 'desc')
            ->take(5)
            ->get();

        // 4. Aktivitas Log Terakhir
        $latestActivities = TransactionDetail::with(['transaction.employee', 'asset'])
            ->latest()
            ->take(6)
            ->get();

       return view('components/dashboard', compact(
    'totalAsset', 'assetUser', 'assetReady', 
    'percentUser', 'percentReady', 'months', 
    'dataOut', 'dataIn', 'categories', 'latestActivities'
));
    }
}