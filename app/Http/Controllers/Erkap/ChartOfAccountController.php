<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\Erkap\CostElement;
use Exception;
use Illuminate\Http\Request;

class ChartOfAccountController extends Controller
{
    public function index(Request $request)
    {
        $pageName = 'Chart of Accounts';

        $query = ChartOfAccount::withCount('costElements')
            ->when($request->filled('type') && in_array($request->type, ['revenue', 'expense']), function ($query) use ($request) {
                $query->where('type', $request->type);
            })
            ->orderBy('code');

        $chartOfAccounts = $query->paginate(15)->withQueryString();

        $totalRevenue = ChartOfAccount::revenue()->count();
        $totalExpense = ChartOfAccount::expense()->count();

        return view('erkap.chart-of-account.index', compact('pageName', 'chartOfAccounts', 'totalRevenue', 'totalExpense'));
    }

    public function show(ChartOfAccount $chartOfAccount)
    {
        $pageName = 'Detail Chart of Account';

        $costElements = CostElement::with('costElementCategory')
            ->where('chart_of_account_id', $chartOfAccount->id)
            ->paginate(10);

        return view('erkap.chart-of-account.show', compact('pageName', 'chartOfAccount', 'costElements'));
    }

    public function sync()
    {
        try {
            $costElements = CostElement::whereNull('chart_of_account_id')->get();
            $updated = 0;

            foreach ($costElements as $costElement) {
                $chartOfAccount = ChartOfAccount::where('code', $costElement->code)->first();

                if ($chartOfAccount) {
                    $costElement->update(['chart_of_account_id' => $chartOfAccount->id]);
                    $updated++;
                }
            }

            return redirect()->route('erkap.chart-of-accounts.index')
                ->with('success', "Sinkronisasi berhasil! {$updated} elemen biaya ditautkan ke Chart of Account.");
        } catch (Exception $err) {
            return redirect()->route('erkap.chart-of-accounts.index')
                ->with('error', $err->getMessage());
        }
    }
}