<?php

namespace App\Services\Dashboard;

use App\Models\Erkap\BudgetCapex;
use App\Models\Erkap\BudgetRealization;
use App\Models\Erkap\ExpensePlan;
use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\ProfitLossStatement;
use App\Models\Erkap\RevenuePlan;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\RoutineCost;
use App\Services\ErkapAccess;
use Illuminate\Support\Collection;

class DrilldownService
{
    public const TYPES = ['routine-cost', 'investment-plan', 'realization', 'budget', 'pnl'];

    public function resolve(int $id, ?string $type = null): array
    {
        if ($type) {
            return [$type => $this->detail($type, $id)];
        }

        return [
            'routine-cost' => $this->routineCostDetail($id),
            'investment-plan' => $this->investmentPlanDetail($id),
            'realization' => $this->realizationDetail($id),
            'budget' => $this->budgetDetail($id),
            'pnl' => $this->pnlDetail($id),
        ];
    }

    protected function detail(string $type, int $id): array
    {
        return match ($type) {
            'routine-cost' => $this->routineCostDetail($id),
            'investment-plan' => $this->investmentPlanDetail($id),
            'realization' => $this->realizationDetail($id),
            'budget' => $this->budgetDetail($id),
            'pnl' => $this->pnlDetail($id),
            default => abort(404, 'Jenis drilldown tidak dikenali.'),
        };
    }

    public function rows(string $type, int $id): Collection
    {
        return $this->detail($type, $id)['rows'] ?? collect();
    }

    public function routineCostDetail(int $id): array
    {
        $item = RoutineCost::with('workProgram.riskIdentification.departmentTarget.division', 'costElement', 'costCenter')
            ->findOrFail($id);

        ErkapAccess::assertWorkProgramAccess($item->erkap_work_program_id);

        $realizations = $item->budgetRealizations;

        return [
            'title' => 'Detail Biaya Rutin',
            'attributes' => [
                'Kebutuhan' => $item->need,
                'Program Kerja' => $item->workProgram->name ?? '-',
                'Elemen Biaya' => $item->costElement->name ?? '-',
                'Pusat Biaya' => $item->costCenter->name ?? '-',
                'Qty x Harga' => sprintf('%s x %s', $item->qty, number_format((float) $item->unit_price, 0, ',', '.')),
                'Total Anggaran' => $item->total,
                'Status' => $item->statusLabel(),
            ],
            'rows' => $realizations,
        ];
    }

    public function investmentPlanDetail(int $id): array
    {
        $item = InvestmentPlan::with('workProgram.riskIdentification.departmentTarget.division', 'investattionCategory')
            ->findOrFail($id);

        ErkapAccess::assertWorkProgramAccess($item->erkap_work_program_id);

        return [
            'title' => 'Detail Rencana Investasi (CAPEX)',
            'attributes' => [
                'Nama Investasi' => $item->name,
                'Program Kerja' => $item->workProgram->name ?? '-',
                'Kategori' => $item->investattionCategory?->name ?? '-',
                'Deskripsi' => $item->description,
                'Total Investasi' => $item->total,
                'Kumulatif' => $item->is_kumulatif ? 'Ya' : 'Tidak',
                'Status' => $item->statusLabel(),
            ],
            'rows' => $item->budgetRealizations,
        ];
    }

    public function realizationDetail(int $id): array
    {
        $item = BudgetRealization::with('routineCost.workProgram.riskIdentification.departmentTarget.division', 'investmentPlan.workProgram', 'rkap')
            ->findOrFail($id);

        return [
            'title' => 'Detail Realisasi Anggaran',
            'attributes' => [
                'Periode' => sprintf('%s %s', $item->month, $item->year),
                'RKAP' => $item->rkap?->year ?? '-',
                'Jenis' => $item->erkap_routine_cost_id !== null ? 'OPEX (Biaya Rutin)' : 'CAPEX (Investasi)',
                'Sumber' => $item->source,
            ],
            'rows' => collect([$item]),
        ];
    }

    public function budgetDetail(int $id): array
    {
        $item = BudgetCapex::with('rkap', 'division')->findOrFail($id);

        $investmentPlans = InvestmentPlan::with('workProgram.riskIdentification.departmentTarget.division')
            ->whereHas('workProgram.riskIdentification.departmentTarget', function ($q) use ($item) {
                $q->whereHas('companyTarget', fn ($cq) => $cq->where('erkap_rkap_id', $item->erkap_rkap_id));
                if ($item->division_id) {
                    $q->where('division_id', $item->division_id);
                }
            })
            ->get();

        return [
            'title' => 'Detail Ringkasan Investasi (Budget CAPEX)',
            'attributes' => [
                'Periode RKAP' => $item->rkap?->year ?? '-',
                'Divisi' => $item->division?->name ?? 'Semua Divisi',
                'Total Investasi' => $item->total_investment,
                'Catatan' => $item->notes,
                'Status' => $item->status ?? 'draft',
            ],
            'rows' => $investmentPlans,
        ];
    }

    public function pnlDetail(int $id): array
    {
        $statement = ProfitLossStatement::with('rkap', 'division')->findOrFail($id);
        ErkapAccess::assertDivisionAccess($statement->division_id);

        $data = [
            'erkap_rkap_id' => $statement->erkap_rkap_id,
            'division_id' => $statement->division_id,
        ];

        $revenueRows = RevenuePlan::with('chartOfAccount')
            ->where('erkap_rkap_id', $data['erkap_rkap_id'])
            ->when($data['division_id'], fn ($q) => $q->where('division_id', $data['division_id']))
            ->get();

        $expenseRows = ExpensePlan::with('chartOfAccount')
            ->where('erkap_rkap_id', $data['erkap_rkap_id'])
            ->when($data['division_id'], fn ($q) => $q->where('division_id', $data['division_id']))
            ->get();

        return [
            'title' => 'Detail Laba Rugi (P&L)',
            'attributes' => [
                'Periode' => $statement->period,
                'Divisi' => $statement->division?->name ?? 'Semua Divisi',
                'Total Pendapatan' => $statement->total_revenue,
                'Total Beban' => $statement->total_expense,
                'Laba Bersih' => $statement->net_profit,
                'Margin' => $statement->margin . '%',
            ],
            'rows' => $revenueRows->concat($expenseRows),
            'meta' => [
                'revenue_count' => $revenueRows->count(),
                'expense_count' => $expenseRows->count(),
            ],
        ];
    }
}