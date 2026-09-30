<?php

namespace App\Services;

use App\Models\Erkap\BudgetOpex;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\RoutineCost;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class BudgetOpexConsolidationService
{
    /** Batas jumlah Pusat Biaya per query saat mencari COA hasil komposisi. */
    private const PAIR_LOOKUP_CHUNK = 100;

    /**
     * Consolidate RoutineCost data into BudgetOpex table
     */
    public function consolidate(RKAP $rkap): int
    {
        \App\Services\Erkap\ZBBReviewService::requireRationale($rkap);

        $routineCosts = RoutineCost::query()
            ->join('erkap_work_programs', 'erkap_routine_costs.erkap_work_program_id', '=', 'erkap_work_programs.id')
            ->join('erkap_risk_identifications', 'erkap_work_programs.erkap_risk_identification_id', '=', 'erkap_risk_identifications.id')
            ->join('erkap_department_targets', 'erkap_risk_identifications.erkap_department_target_id', '=', 'erkap_department_targets.id')
            ->join('erkap_company_targets', 'erkap_department_targets.erkap_company_target_id', '=', 'erkap_company_targets.id')
            ->where('erkap_company_targets.erkap_rkap_id', $rkap->id)
            ->select(
                'erkap_routine_costs.*',
                'erkap_department_targets.division_id',
                'erkap_routine_costs.cost_center_id'
            )
            ->get();

        $accountIds = $this->accountIdByPair($routineCosts);

        // Group by effective Chart of Account. Setelah F7, COA Biaya Rutin
        // diturunkan dari pasangan Pusat Biaya + Elemen Biaya, jadi fallback
        // memakai peta hasil komposisi — bukan `CostElement::chart_of_account_id`
        // yang bisa menunjuk COA milik Pusat Biaya lain.
        $consolidated = $routineCosts->groupBy(function ($cost) use ($accountIds) {
            $chartOfAccountId = $this->resolveAccountId($cost, $accountIds);

            return "{$cost->division_id}-{$cost->cost_center_id}-{$chartOfAccountId}";
        });

        $count = 0;
        DB::transaction(function () use ($rkap, $consolidated, $accountIds, &$count) {
            foreach ($consolidated as $group) {
                $first = $group->first();

                $chartOfAccountId = $this->resolveAccountId($first, $accountIds);

                if (! $chartOfAccountId || ! $first->division_id || ! $first->cost_center_id) {
                    continue;
                }

                $totalAmount = $group->sum('total');

                BudgetOpex::updateOrCreate(
                    [
                        'erkap_rkap_id' => $rkap->id,
                        'division_id' => $first->division_id,
                        'cost_center_id' => $first->cost_center_id,
                        'chart_of_account_id' => $chartOfAccountId,
                    ],
                    [
                        'budget_amount' => $totalAmount,
                        'status' => 'draft',
                    ]
                );

                $count++;
            }
        });

        return $count;
    }

    /**
     * COA efektif sebuah baris Biaya Rutin: pilihan eksplisit bila ada, jika
     * tidak COA hasil komposisi pasangan Pusat Biaya + Elemen Biaya.
     *
     * @param  array<string, int>  $accountIds
     */
    private function resolveAccountId(RoutineCost $cost, array $accountIds): ?int
    {
        return $cost->chart_of_account_id
            ?? $accountIds[self::pairKey($cost->cost_center_id, $cost->erkap_cost_element_id)]
            ?? null;
    }

    /**
     * Peta "cost_center_id-cost_element_id" => chart_of_account_id untuk seluruh
     * pasangan yang muncul pada kumpulan baris, diambil dalam satu query.
     *
     * @param  Collection<int, RoutineCost>  $routineCosts
     * @return array<string, int>
     */
    private function accountIdByPair(Collection $routineCosts): array
    {
        $pairs = $routineCosts
            ->map(fn (RoutineCost $cost) => [
                $cost->cost_center_id,
                $cost->erkap_cost_element_id,
            ])
            ->filter(fn (array $pair) => $pair[0] && $pair[1])
            ->unique()
            ->values();

        if ($pairs->isEmpty()) {
            return [];
        }

        $costCenterIds = $pairs->pluck(0)->unique()->values();
        $costElementIds = $pairs->pluck(1)->unique()->values();

        $accounts = $costCenterIds
            ->chunk(self::PAIR_LOOKUP_CHUNK)
            ->flatMap(fn (Collection $chunk) => ChartOfAccount::query()
                ->whereIn('cost_center_id', $chunk)
                ->whereIn('cost_element_id', $costElementIds)
                ->get(['id', 'cost_center_id', 'cost_element_id']));

        return $accounts
            ->mapWithKeys(fn (ChartOfAccount $account) => [
                self::pairKey($account->cost_center_id, $account->cost_element_id) => $account->id,
            ])
            ->all();
    }

    private static function pairKey(?int $costCenterId, ?int $costElementId): string
    {
        return "{$costCenterId}-{$costElementId}";
    }

    /**
     * Recalculate variance for all BudgetOpex records in a budget year
     */
    public function recalculateVariance(RKAP $rkap): void
    {
        BudgetOpex::where('erkap_rkap_id', $rkap->id)
            ->chunk(100, function ($records) {
                foreach ($records as $record) {
                    $record->variance = $record->realization_amount - $record->budget_amount;
                    $record->save();
                }
            });
    }

    /**
     * Get budget vs realization report for a budget year
     *
     * Divisi, Pusat Biaya, dan COA sudah di-eager-load supaya pemetaan per grup
     * tidak memicu query di dalam loop.
     */
    public function getReport(RKAP $rkap, ?int $divisionId = null): Collection
    {
        $records = BudgetOpex::query()
            ->where('erkap_rkap_id', $rkap->id)
            ->when($divisionId, fn ($query) => $query->where('division_id', $divisionId))
            ->with([
                'division',
                'costCenter.businessUnit',
                'costCenter.location',
                'costCenter.managementArea',
                'costCenter.activity',
                'chartOfAccount.costElement',
            ])
            ->get();

        return $records->groupBy('division_id')->map(function (Collection $items, $divisionId) {
            $division = $items->first()->division;

            return [
                'division' => $division,
                'items' => $items->map(fn (BudgetOpex $item) => [
                    'cost_center' => $item->costCenter,
                    'chart_of_account' => $item->chartOfAccount,
                    'budget_amount' => $item->budget_amount,
                    'realization_amount' => $item->realization_amount,
                    'variance' => $item->variance,
                    'variance_percentage' => $item->budget_amount > 0
                        ? round(($item->variance / $item->budget_amount) * 100, 2)
                        : 0,
                    'status' => $item->status,
                ]),
                'totals' => [
                    'budget' => $items->sum('budget_amount'),
                    'realization' => $items->sum('realization_amount'),
                    'variance' => $items->sum('variance'),
                ],
            ];
        });
    }
}