<?php

namespace App\Services;

use App\Models\Erkap\BudgetOpex;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\RoutineCost;
use App\Models\Erkap\WorkProgram;
use App\Models\Erkap\CostCenter;
use App\Models\Division;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class BudgetOpexConsolidationService
{
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
            ->with('costElement')
            ->select(
                'erkap_routine_costs.*',
                'erkap_department_targets.division_id',
                'erkap_routine_costs.cost_center_id'
            )
            ->get();

        // Group by effective Chart of Account: direct selection overrides the
        // cost element's COA, falling back to it when the routine cost has none.
        $consolidated = $routineCosts->groupBy(function ($cost) {
            $chartOfAccountId = $cost->chart_of_account_id
                ?? $cost->costElement?->chart_of_account_id
                ?? $cost->costElement?->coaSuggestion()?->id;

            return "{$cost->division_id}-{$cost->cost_center_id}-{$chartOfAccountId}";
        });

        $count = 0;
        DB::transaction(function () use ($rkap, $consolidated, &$count) {
            foreach ($consolidated as $group) {
                $first = $group->first();

                $chartOfAccountId = $first->chart_of_account_id
                    ?? $first->costElement?->chart_of_account_id
                    ?? $first->costElement?->coaSuggestion()?->id;

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
     */
    public function getReport(RKAP $rkap, ?int $divisionId = null): Collection
    {
        $query = BudgetOpex::query()
            ->where('erkap_rkap_id', $rkap->id)
            ->with(['division', 'costCenter', 'chartOfAccount']);

        if ($divisionId) {
            $query->where('division_id', $divisionId);
        }

        return $query->get()->groupBy('division_id')->map(function ($items, $divisionId) {
            $division = \App\Models\Division::find($divisionId);
            
            return [
                'division' => $division,
                'items' => $items->map(function ($item) {
                    return [
                        'cost_center' => $item->costCenter,
                        'chart_of_account' => $item->chartOfAccount,
                        'budget_amount' => $item->budget_amount,
                        'realization_amount' => $item->realization_amount,
                        'variance' => $item->variance,
                        'variance_percentage' => $item->budget_amount > 0 
                            ? round(($item->variance / $item->budget_amount) * 100, 2)
                            : 0,
                        'status' => $item->status,
                    ];
                }),
                'totals' => [
                    'budget' => $items->sum('budget_amount'),
                    'realization' => $items->sum('realization_amount'),
                    'variance' => $items->sum('variance'),
                ],
            ];
        });
    }
}