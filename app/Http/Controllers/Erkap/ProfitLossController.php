<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Erkap\ExpensePlan;
use App\Models\Erkap\ProfitLossStatement;
use App\Models\Erkap\RevenuePlan;
use App\Models\Erkap\RKAP;
use App\Services\ErkapAccess;
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

            $revenueRows = $this->planFor($data, 'revenue');
            $expenseRows = $this->planFor($data, 'expense');

            $monthlyRevenue = $this->sumMonthly($revenueRows);
            $monthlyExpense = $this->sumMonthly($expenseRows);

            $totalRevenue = array_sum($monthlyRevenue);
            $totalExpense = array_sum($monthlyExpense);
            $grossProfit = $totalRevenue - $totalExpense;
            $margin = $totalRevenue > 0 ? round(($grossProfit / $totalRevenue) * 100, 2) : 0;

            $statement = ProfitLossStatement::updateOrCreate(
                [
                    'erkap_rkap_id' => $data['erkap_rkap_id'],
                    'division_id' => $data['division_id'] ?? null,
                    'period' => 'yearly',
                ],
                [
                    'total_revenue' => $totalRevenue,
                    'total_expense' => $totalExpense,
                    'gross_profit' => $grossProfit,
                    'net_profit' => $grossProfit,
                    'margin' => $margin,
                ]
            );

            return redirect()->route('erkap.profit-loss.show', $statement->id)
                ->with('success', 'Laporan laba rugi berhasil dibuat dari rencana pendapatan & beban!');
        } catch (Exception $err) {
            return redirect()->route('erkap.profit-loss.index')
                ->withInput()
                ->with('error', $err->getMessage());
        }
    }

    public function show(ProfitLossStatement $profitLossStatement)
    {
        ErkapAccess::assertDivisionAccess($profitLossStatement->division_id);

        $pageName = 'Detail Laba Rugi';

        $data = [
            'erkap_rkap_id' => $profitLossStatement->erkap_rkap_id,
            'division_id' => $profitLossStatement->division_id,
        ];

        $revenueRows = $this->planFor($data, 'revenue');
        $expenseRows = $this->planFor($data, 'expense');

        $monthlyRevenue = $this->sumMonthly($revenueRows);
        $monthlyExpense = $this->sumMonthly($expenseRows);
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
            $data = [
                'erkap_rkap_id' => (int) $request->input('erkap_rkap_id'),
                'division_id' => ErkapAccess::isDivisionScoped()
                    ? ErkapAccess::divisionId()
                    : ($request->filled('division_id') ? (int) $request->input('division_id') : null),
            ];

            ErkapAccess::assertDivisionAccess($data['division_id']);

            $revenueRows = $this->planFor($data, 'revenue');
            $expenseRows = $this->planFor($data, 'expense');

            $monthlyRevenue = $this->sumMonthly($revenueRows);
            $monthlyExpense = $this->sumMonthly($expenseRows);

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

    protected function planFor(array $data, string $type)
    {
        $query = $type === 'revenue'
            ? RevenuePlan::with('chartOfAccount')->where('erkap_rkap_id', $data['erkap_rkap_id'])
            : ExpensePlan::with('chartOfAccount')->where('erkap_rkap_id', $data['erkap_rkap_id']);

        if (! empty($data['division_id'])) {
            $query->where('division_id', $data['division_id']);
        }

        return $query->get();
    }

    protected function sumMonthly($plans): array
    {
        $result = array_fill(0, 12, 0.0);

        foreach (RevenuePlan::monthColumns() as $index => $month) {
            foreach ($plans as $plan) {
                $result[$index] += (float) $plan->{$month};
            }
        }

        return $result;
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
