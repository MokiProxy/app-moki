<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChartOfAccountRequest;
use App\Models\ChartOfAccount;
use App\Models\Erkap\CostElement;
use App\Support\CoaCode;
use Exception;
use Illuminate\Http\Request;

class ChartOfAccountController extends Controller
{
    public function index(Request $request)
    {
        $pageName = 'Chart of Accounts';

        $query = ChartOfAccount::withCount('costElements')
            ->search($request->query('search'))
            ->when($request->filled('type') && in_array($request->type, ['revenue', 'expense']), function ($query) use ($request) {
                $query->where('type', $request->type);
            })
            ->orderBy('code');

        $chartOfAccounts = $query->paginate(15)->withQueryString();

        $totalRevenue = ChartOfAccount::revenue()->count();
        $totalExpense = ChartOfAccount::expense()->count();

        return view('erkap.chart-of-account.index', compact('pageName', 'chartOfAccounts', 'totalRevenue', 'totalExpense'));
    }

    public function create()
    {
        $pageName = 'Buat Chart of Account';

        return view('erkap.chart-of-account.create', compact('pageName'));
    }

    public function store(StoreChartOfAccountRequest $request)
    {
        try {
            ChartOfAccount::create($request->validated());

            return redirect()->route('erkap.chart-of-accounts.index')
                ->with('success', 'Chart of Account baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.chart-of-accounts.create')
                ->withInput()
                ->with('error', $err->getMessage());
        }
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
            $created = 0;

            foreach ($costElements as $costElement) {
                $chartOfAccount = $costElement->coaSuggestion();

                if (! $chartOfAccount && $paddedCode = CoaCode::pad($costElement->code)) {
                    $chartOfAccount = ChartOfAccount::create([
                        'code' => $paddedCode,
                        'name' => $costElement->name,
                        'type' => 'expense',
                        'description' => 'Dibuat otomatis dari sinkronisasi elemen biaya.',
                    ]);
                    $created++;
                }

                if ($chartOfAccount) {
                    $costElement->update(['chart_of_account_id' => $chartOfAccount->id]);
                    $updated++;
                }
            }

            $message = "Sinkronisasi berhasil! {$updated} elemen biaya ditautkan ke Chart of Account.";

            if ($created > 0) {
                $message .= " {$created} Chart of Account baru dibuat.";
            }

            return redirect()->route('erkap.chart-of-accounts.index')
                ->with('success', $message);
        } catch (Exception $err) {
            return redirect()->route('erkap.chart-of-accounts.index')
                ->with('error', $err->getMessage());
        }
    }
}