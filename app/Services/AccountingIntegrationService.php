<?php

namespace App\Services;

use App\Models\Erkap\BudgetRealization;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\RoutineCost;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

class AccountingIntegrationService
{
    /**
     * Impor realisasi dari sistem akuntansi (eqtax_gl) untuk periode tertentu.
     */
    public function importRealization(int $erkapRkapId, int $month, int $year): int
    {
        $items = $this->fetchFromAccounting($month, $year);
        $imported = 0;

        foreach ($items as $item) {
            $routineCost = $this->matchRoutineCost($item['description']);

            if (! $routineCost) {
                continue;
            }

            BudgetRealization::updateOrCreate(
                [
                    'erkap_rkap_id' => $erkapRkapId,
                    'erkap_routine_cost_id' => $routineCost->id,
                    'month' => $month,
                    'year' => $year,
                ],
                [
                    'budgeted' => $this->monthlyBudget($routineCost, $month),
                    'realized' => $item['amount'],
                    'source' => 'accounting',
                ]
            )->calculateVariance();

            $imported++;
        }

        return $imported;
    }

    /**
     * Impor realisasi dari file Excel/CSV.
     * Format baris: [erkap_routine_cost_id|erkap_investment_plan_id, month, year, realized]
     */
    public function importFromFile(UploadedFile $file, int $month, int $year, int $erkapRkapId): int
    {
        $rows = Excel::toArray(null, $file);
        $imported = 0;

        foreach ($rows as $sheet) {
            foreach (array_slice($sheet, 1) as $row) {
                if (empty(array_filter($row))) {
                    continue;
                }

                $routineCostId = $row[0] ?? null;
                $investmentPlanId = $row[1] ?? null;
                $realized = (float) ($row[2] ?? 0);

                if (! $routineCostId && ! $investmentPlanId) {
                    continue;
                }

                $budgeted = 0;
                if ($routineCostId && $cost = RoutineCost::find($routineCostId)) {
                    $budgeted = $this->monthlyBudget($cost, $month);
                }

                BudgetRealization::updateOrCreate(
                    [
                        'erkap_rkap_id' => $erkapRkapId,
                        'erkap_routine_cost_id' => $routineCostId ?: null,
                        'erkap_investment_plan_id' => $investmentPlanId ?: null,
                        'month' => $month,
                        'year' => $year,
                    ],
                    [
                        'budgeted' => $budgeted,
                        'realized' => $realized,
                        'source' => 'manual',
                    ]
                )->calculateVariance();

                $imported++;
            }
        }

        return $imported;
    }

    /**
     * Ambil data realisasi dari tabel akuntansi (eqtax_gl) bila tersedia.
     */
    public function fetchFromAccounting(int $month, int $year): array
    {
        if (! Schema::hasTable('eqtax_gl')) {
            return [];
        }

        $monthPad = str_pad($month, 2, '0', STR_PAD_LEFT);

        return DB::table('eqtax_gl')
            ->where('jurnal_date', 'like', "%-{$monthPad}-{$year}%")
            ->select('invoice_no', 'invoice_item', 'dpp', 'ppn')
            ->get()
            ->map(fn ($row) => [
                'invoice_no' => $row->invoice_no,
                'description' => $row->invoice_item,
                'amount' => (float) $row->dpp + (float) $row->ppn,
            ])
            ->values()
            ->all();
    }

    protected function matchRoutineCost(?string $description): ?RoutineCost
    {
        if (blank($description)) {
            return null;
        }

        return RoutineCost::whereRaw('LOWER(need) LIKE ?', ['%' . strtolower(trim($description)) . '%'])->first();
    }

    protected function monthlyBudget(RoutineCost $routineCost, int $month): float
    {
        $column = $this->costColumn($month);

        return (float) $routineCost->{$column};
    }

    protected function costColumn(int $month): string
    {
        $names = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'des'];

        return $names[$month - 1] . '_cost';
    }
}