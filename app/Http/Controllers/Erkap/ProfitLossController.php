<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Erkap\ProfitLossStatement;
use App\Models\Erkap\RKAP;
use App\Services\ErkapAccess;
use App\Services\Erkap\ProfitLossService;
use App\Support\ErrorMessage;
use Exception;
use Illuminate\Http\Request;

class ProfitLossController extends Controller
{
    protected $monthLabels = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    protected $scenarios = [
        'best' => ['label' => 'Optimis (Best Case)', 'multiplier' => 1.1],
        'base' => ['label' => 'Realistis (Base Case)', 'multiplier' => 1.0],
        'worst' => ['label' => 'Pesimis (Worst Case)', 'multiplier' => 0.9],
    ];

    public function index()
    {
        $pageName = 'Laporan Laba Rugi (P&L)';
        $rkaps = RKAP::orderByDesc('year')->get();
        $divisions = $this->availableDivisions();
        $isDivisionScoped = ErkapAccess::isDivisionScoped();
        $statements = ProfitLossStatement::with(['rkap', 'division'])
            ->when($isDivisionScoped, function ($query) {
                $query->where('division_id', ErkapAccess::divisionId());
            })
            ->latest()
            ->paginate(10);

        return view('erkap.profit-loss.index', compact('pageName', 'rkaps', 'divisions', 'isDivisionScoped', 'statements'));
    }

    public function generate(Request $request)
    {
        try {
            $data = $request->validate([
                'erkap_rkap_id' => ['required', 'integer', 'exists:erkap_rkap,id'],
                'division_id' => ['nullable', 'integer', 'exists:divisions,id'],
            ]);

            ErkapAccess::assertDivisionAccess($data['division_id'] ?? null);

            $rkap = RKAP::findOrFail($data['erkap_rkap_id']);
            $result = ProfitLossService::calculate($rkap, $data['division_id'] ?? null);

            $statement = ProfitLossStatement::updateOrCreate(
                [
                    'erkap_rkap_id' => $data['erkap_rkap_id'],
                    'division_id' => $data['division_id'] ?? null,
                    'period' => 'yearly',
                ],
                [
                    'total_revenue' => $result['total_revenue'],
                    'total_expense' => $result['total_expense'],
                    'gross_profit' => $result['gross_profit'],
                    'net_profit' => $result['net_profit'],
                    'margin' => $result['margin'],
                ]
            );

            return redirect()->route('erkap.profit-loss.show', $statement->id)
                ->with('success', 'Laporan laba rugi berhasil dihitung dari anggaran operasional.');
        } catch (Exception $err) {
            return redirect()->route('erkap.profit-loss.index')
                ->withInput()
                ->with('error', ErrorMessage::from($err));
        }
    }

    public function show(ProfitLossStatement $profitLossStatement)
    {
        ErkapAccess::assertDivisionAccess($profitLossStatement->division_id);

        $pageName = 'Detail Laba Rugi';

        $rkap = RKAP::findOrFail($profitLossStatement->erkap_rkap_id);
        $revenueRows = ProfitLossService::fetchRevenueData($rkap, $profitLossStatement->division_id);
        $expenseRows = ProfitLossService::fetchExpenseData($rkap, $profitLossStatement->division_id);

        $monthlyRevenue = ProfitLossService::sumMonthly($revenueRows);
        $monthlyExpense = ProfitLossService::sumMonthly($expenseRows);
        $monthlyProfit = array_map(fn ($revenue, $expense) => $revenue - $expense, $monthlyRevenue, $monthlyExpense);
        $monthLabels = $this->monthLabels;

        return view('erkap.profit-loss.show', compact('pageName', 'profitLossStatement', 'revenueRows', 'expenseRows', 'monthlyRevenue', 'monthlyExpense', 'monthlyProfit', 'monthLabels'));
    }

    public function simulate(Request $request)
    {
        $pageName = 'Simulasi Skenario Laba Rugi';
        $rkaps = RKAP::orderByDesc('year')->get();
        $divisions = $this->availableDivisions();
        $isDivisionScoped = ErkapAccess::isDivisionScoped();
        $scenarios = $this->scenarios;
        $monthLabels = $this->monthLabels;
        $results = null;

        if ($request->filled('erkap_rkap_id')) {
            $divisionId = ErkapAccess::isDivisionScoped()
                ? ErkapAccess::divisionId()
                : ($request->filled('division_id') ? (int) $request->input('division_id') : null);

            ErkapAccess::assertDivisionAccess($divisionId);

            $rkap = RKAP::findOrFail((int) $request->input('erkap_rkap_id'));
            $revenueRows = ProfitLossService::fetchRevenueData($rkap, $divisionId);
            $expenseRows = ProfitLossService::fetchExpenseData($rkap, $divisionId);

            $monthlyRevenue = ProfitLossService::sumMonthly($revenueRows);
            $monthlyExpense = ProfitLossService::sumMonthly($expenseRows);

            $baseRevenue = array_sum($monthlyRevenue);
            $baseExpense = array_sum($monthlyExpense);

            foreach ($scenarios as $key => $scenario) {
                $multiplier = $scenario['multiplier'];
                $revenue = $baseRevenue * $multiplier;
                $expense = $baseExpense * $multiplier;
                $profit = $revenue - $expense;
                $results[$key] = [
                    'label' => $scenario['label'],
                    'multiplier' => $multiplier,
                    'revenue' => $revenue,
                    'expense' => $expense,
                    'profit' => $profit,
                    'margin' => $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0,
                    'monthly_revenue' => array_map(fn ($value) => $value * $multiplier, $monthlyRevenue),
                    'monthly_expense' => array_map(fn ($value) => $value * $multiplier, $monthlyExpense),
                ];
            }
        }

        return view('erkap.profit-loss.simulate', compact('pageName', 'rkaps', 'divisions', 'isDivisionScoped', 'scenarios', 'monthLabels', 'results'));
    }

    protected function availableDivisions()
    {
        return Division::query()
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->where('id', ErkapAccess::divisionId());
            })
            ->get();
    }
}
