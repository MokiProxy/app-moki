<?php

namespace App\Services\Simka;

use App\Models\Erkap\BudgetCapex;
use App\Models\Erkap\BudgetOpex;
use App\Models\Erkap\BudgetRealization;
use App\Models\Erkap\ExpensePlan;
use App\Models\Erkap\RevenuePlan;
use App\Models\Erkap\RKAP;
use App\Models\KategoriPendapatan;
use App\Models\KategoriTarif;
use App\Models\Spph16368Tarif;
use App\Models\Spph2287Tarif;
use App\Models\Spph773Tarif;
use App\Services\ErkapAccess;

/**
 * Ringkasan gabungan domain Pendapatan (SIMKA) dan Pengeluaran (E-RKAP).
 *
 * Catatan penting:
 * - Tarif SPPH adalah tarif satuan (Rp/unit), bukan nilai pendapatan. Karena
 *   SIMKA belum menyimpan volume produksi, angka pendapatan diambil dari
 *   `erkap_revenue_plans` (rencana pendapatan per RKAP).
 * - Angka pengeluaran memakai rencana pengeluaran (`erkap_expense_plans`) dan
 *   anggaran terkonsolidasi (`erkap_budget_opex` + `erkap_budget_capex`).
 * - Realisasi memakai `erkap_budget_realizations` dan ikut dibatasi divisi
 *   apabila pengguna adalah cost-owner yang ter-scope divisi.
 */
class DashboardSummaryService
{
    private const MONTHS = [
        'jan', 'feb', 'mar', 'apr', 'may', 'jun',
        'jul', 'aug', 'sep', 'oct', 'nov', 'dec',
    ];

    public function build(?RKAP $rkap = null, ?int $divisionId = null): array
    {
        $rkap ??= RKAP::orderByDesc('year')->first();

        $divisionId = ErkapAccess::isDivisionScoped()
            ? ErkapAccess::divisionId()
            : $divisionId;

        $rkapId = $rkap?->id;

        $revenueRows = $this->planRows(RevenuePlan::query(), $rkapId, $divisionId);
        $expenseRows = $this->planRows(ExpensePlan::query(), $rkapId, $divisionId);

        $revenuePlan = (float) $revenueRows->sum('total');
        $expensePlan = (float) $expenseRows->sum('total');

        $opexBudget = $this->budgetSum(BudgetOpex::query(), 'budget_amount', $rkapId, $divisionId);
        $capexBudget = $this->budgetSum(BudgetCapex::query(), 'total_investment', $rkapId, $divisionId);
        $realized = $this->realizedSum($rkapId, $divisionId);

        $expenseBudget = $opexBudget + $capexBudget;
        // Prioritaskan anggaran terkonsolidasi (Opex + Capex). Rencana
        // pengeluaran hanya menjadi fallback bila anggaran belum tersedia.
        $expenseTotal = $expenseBudget > 0 ? $expenseBudget : $expensePlan;
        $balance = $revenuePlan - $expenseTotal;

        $monthly = [];
        foreach (self::MONTHS as $month) {
            $monthly[] = [
                'label' => ucfirst($month),
                'revenue' => (float) $revenueRows->sum($month . '_plan'),
                'expense' => (float) $expenseRows->sum($month . '_plan'),
            ];
        }

        return [
            'rkap' => $rkap,
            'division_id' => $divisionId,
            'revenue_plan' => $revenuePlan,
            'expense_plan' => $expensePlan,
            'opex_budget' => $opexBudget,
            'capex_budget' => $capexBudget,
            'expense_budget' => $expenseBudget,
            'expense_total' => $expenseTotal,
            'realized' => $realized,
            'balance' => $balance,
            'absorption' => $expenseTotal > 0
                ? round(($realized / $expenseTotal) * 100, 2)
                : 0.0,
            'monthly' => $monthly,
            'spph' => [
                'schemes' => 4,
                'tarif_entries' => Spph773Tarif::count()
                    + Spph2287Tarif::count()
                    + Spph16368Tarif::count(),
                'revenue_categories' => KategoriPendapatan::count(),
                'tariff_categories' => KategoriTarif::count(),
            ],
        ];
    }

    /**
     * Baris rencana pendapatan/pengeluaran untuk RKAP & divisi terpilih.
     */
    private function planRows($query, ?int $rkapId, ?int $divisionId)
    {
        return $query
            ->when($rkapId, fn ($q) => $q->where('erkap_rkap_id', $rkapId))
            ->when($divisionId, fn ($q) => $q->where('division_id', $divisionId))
            ->get();
    }

    private function budgetSum($query, string $column, ?int $rkapId, ?int $divisionId): float
    {
        return (float) $query
            ->when($rkapId, fn ($q) => $q->where('erkap_rkap_id', $rkapId))
            ->when($divisionId, fn ($q) => $q->where('division_id', $divisionId))
            ->sum($column);
    }

    /**
     * Realisasi tidak punya kolom `division_id`, sehingga pembatasan divisi
     * dilakukan lewat relasi biaya rutin / rencana investasi.
     */
    private function realizedSum(?int $rkapId, ?int $divisionId): float
    {
        return (float) BudgetRealization::query()
            ->when($rkapId, fn ($q) => $q->where('erkap_rkap_id', $rkapId))
            ->when($divisionId, function ($query) use ($divisionId) {
                $query->where(function ($scope) use ($divisionId) {
                    $scope
                        ->whereHas(
                            'routineCost.workProgram.riskIdentification.departmentTarget',
                            fn ($target) => $target->where('division_id', $divisionId)
                        )
                        ->orWhereHas(
                            'investmentPlan.workProgram.riskIdentification.departmentTarget',
                            fn ($target) => $target->where('division_id', $divisionId)
                        );
                });
            })
            ->sum('realized');
    }
}
