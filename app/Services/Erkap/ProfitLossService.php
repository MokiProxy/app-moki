<?php

namespace App\Services\Erkap;

use App\Models\ChartOfAccount;
use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\RoutineCost;
use Illuminate\Support\Collection;

class ProfitLossService
{
    public static function calculate(RKAP $rkap, ?int $divisionId = null): array
    {
        $revenueData = static::fetchRevenueData($rkap, $divisionId);
        $expenseData = static::fetchExpenseData($rkap, $divisionId);

        $monthlyRevenue = static::sumMonthly($revenueData);
        $monthlyExpense = static::sumMonthly($expenseData);

        $totalRevenue = array_sum($monthlyRevenue);
        $totalExpense = array_sum($monthlyExpense);
        $grossProfit = $totalRevenue - $totalExpense;
        $margin = $totalRevenue > 0 ? round(($grossProfit / $totalRevenue) * 100, 2) : 0;

        return [
            'monthly_revenue' => $monthlyRevenue,
            'monthly_expense' => $monthlyExpense,
            'total_revenue' => $totalRevenue,
            'total_expense' => $totalExpense,
            'gross_profit' => $grossProfit,
            'net_profit' => $grossProfit,
            'margin' => $margin,
            'revenue_details' => $revenueData,
            'expense_details' => $expenseData,
        ];
    }

    public static function fetchRevenueData(RKAP $rkap, ?int $divisionId = null): Collection
    {
        return static::fetchOperationalData($rkap, $divisionId, 'revenue');
    }

    public static function fetchExpenseData(RKAP $rkap, ?int $divisionId = null): Collection
    {
        return static::fetchOperationalData($rkap, $divisionId, 'expense');
    }

    protected static function fetchOperationalData(RKAP $rkap, ?int $divisionId, string $type): Collection
    {
        $routineItems = static::queryRoutineCosts($rkap, $divisionId, $type);
        $investmentItems = static::queryInvestmentPlans($rkap, $divisionId, $type);

        return static::mergeAndAggregate($routineItems, $investmentItems);
    }

    protected static function queryRoutineCosts(RKAP $rkap, ?int $divisionId, string $type): Collection
    {
        return RoutineCost::query()
            ->select([
                'erkap_routine_costs.chart_of_account_id',
                'erkap_routine_costs.jan_cost',
                'erkap_routine_costs.feb_cost',
                'erkap_routine_costs.mar_cost',
                'erkap_routine_costs.apr_cost',
                'erkap_routine_costs.may_cost',
                'erkap_routine_costs.jun_cost',
                'erkap_routine_costs.jul_cost',
                'erkap_routine_costs.aug_cost',
                'erkap_routine_costs.sep_cost',
                'erkap_routine_costs.oct_cost',
                'erkap_routine_costs.nov_cost',
                'erkap_routine_costs.dec_cost',
                'erkap_routine_costs.total',
            ])
            ->selectRaw('erkap_department_targets.division_id as division_id')
            ->join('erkap_work_programs', 'erkap_routine_costs.erkap_work_program_id', '=', 'erkap_work_programs.id')
            ->join('erkap_risk_identifications', 'erkap_work_programs.erkap_risk_identification_id', '=', 'erkap_risk_identifications.id')
            ->join('erkap_department_targets', 'erkap_risk_identifications.erkap_department_target_id', '=', 'erkap_department_targets.id')
            ->join('erkap_company_targets', 'erkap_department_targets.erkap_company_target_id', '=', 'erkap_company_targets.id')
            ->join('chart_of_accounts', 'erkap_routine_costs.chart_of_account_id', '=', 'chart_of_accounts.id')
            ->where('erkap_company_targets.erkap_rkap_id', $rkap->id)
            ->where('chart_of_accounts.type', $type)
            ->when($divisionId, function ($query) use ($divisionId) {
                $query->where('erkap_department_targets.division_id', $divisionId);
            })
            ->get();
    }

    protected static function queryInvestmentPlans(RKAP $rkap, ?int $divisionId, string $type): Collection
    {
        return InvestmentPlan::query()
            ->select([
                'erkap_investment_plans.chart_of_account_id',
                'erkap_investment_plans.jan_plan',
                'erkap_investment_plans.feb_plan',
                'erkap_investment_plans.mar_plan',
                'erkap_investment_plans.apr_plan',
                'erkap_investment_plans.may_plan',
                'erkap_investment_plans.jun_plan',
                'erkap_investment_plans.jul_plan',
                'erkap_investment_plans.aug_plan',
                'erkap_investment_plans.sep_plan',
                'erkap_investment_plans.oct_plan',
                'erkap_investment_plans.nov_plan',
                'erkap_investment_plans.dec_plan',
                'erkap_investment_plans.total',
            ])
            ->selectRaw('erkap_department_targets.division_id as division_id')
            ->join('erkap_work_programs', 'erkap_investment_plans.erkap_work_program_id', '=', 'erkap_work_programs.id')
            ->join('erkap_risk_identifications', 'erkap_work_programs.erkap_risk_identification_id', '=', 'erkap_risk_identifications.id')
            ->join('erkap_department_targets', 'erkap_risk_identifications.erkap_department_target_id', '=', 'erkap_department_targets.id')
            ->join('erkap_company_targets', 'erkap_department_targets.erkap_company_target_id', '=', 'erkap_company_targets.id')
            ->join('chart_of_accounts', 'erkap_investment_plans.chart_of_account_id', '=', 'chart_of_accounts.id')
            ->where('erkap_company_targets.erkap_rkap_id', $rkap->id)
            ->where('chart_of_accounts.type', $type)
            ->where('erkap_investment_plans.status', 'approved')
            ->when($divisionId, function ($query) use ($divisionId) {
                $query->where('erkap_department_targets.division_id', $divisionId);
            })
            ->get();
    }

    protected static function mergeAndAggregate(Collection $routineItems, Collection $investmentItems): Collection
    {
        $normalizedRoutine = $routineItems->map(function ($item) {
            return static::normalizeItem($item, 'cost');
        });

        $normalizedInvestment = $investmentItems->map(function ($item) {
            return static::normalizeItem($item, 'plan');
        });

        $merged = $normalizedRoutine->merge($normalizedInvestment);

        return $merged
            ->groupBy(function ($item) {
                return $item['division_id'].'|'.$item['chart_of_account_id'];
            })
            ->map(function ($group) {
                $first = $group->first();
                $item = [
                    'division_id' => $first['division_id'],
                    'chart_of_account_id' => $first['chart_of_account_id'],
                    'total' => 0.0,
                ];

                foreach (static::monthKeys() as $month) {
                    $item[$month] = 0.0;
                }

                foreach ($group as $row) {
                    $item['total'] += (float) $row['total'];
                    foreach (static::monthKeys() as $month) {
                        $item[$month] += (float) $row[$month];
                    }
                }

                $item['chartOfAccount'] = ChartOfAccount::find($first['chart_of_account_id']);

                return (object) $item;
            })
            ->values();
    }

    protected static function normalizeItem(object $item, string $suffix): array
    {
        $normalized = [
            'division_id' => (int) $item->division_id,
            'chart_of_account_id' => (int) $item->chart_of_account_id,
            'total' => (float) $item->total,
        ];

        foreach (static::monthKeys() as $month) {
            $normalized[$month] = (float) ($item->{$month.'_'.$suffix} ?? 0);
        }

        return $normalized;
    }

    public static function sumMonthly(Collection $data): array
    {
        $result = array_fill(0, 12, 0.0);

        foreach ($data as $item) {
            foreach (static::monthKeys() as $index => $month) {
                $result[$index] += (float) ($item->$month ?? 0);
            }
        }

        return $result;
    }

    public static function monthKeys(): array
    {
        return ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
    }
}
