<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\Erkap\ExpensePlan;
use App\Models\Erkap\ProfitLossStatement;
use App\Models\Erkap\RevenuePlan;
use App\Models\Erkap\RKAP;
use Illuminate\Database\Seeder;

class FinancialProjectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $rkap = RKAP::firstOrCreate(['year' => '2026']);

        $revenueAccounts = [
            ChartOfAccount::where('code', '6300')->first(),
            ChartOfAccount::where('code', '6900')->first(),
        ];

        $expenseAccounts = [
            ChartOfAccount::where('code', '8008')->first(),
            ChartOfAccount::where('code', '8101')->first(),
            ChartOfAccount::where('code', '8200')->first(),
        ];

        $monthlyValues = [
            'jan_plan' => 250000000,
            'feb_plan' => 250000000,
            'mar_plan' => 300000000,
            'apr_plan' => 300000000,
            'may_plan' => 300000000,
            'jun_plan' => 350000000,
            'jul_plan' => 350000000,
            'aug_plan' => 350000000,
            'sep_plan' => 400000000,
            'oct_plan' => 400000000,
            'nov_plan' => 400000000,
            'dec_plan' => 450000000,
        ];

        foreach ($revenueAccounts as $account) {
            if (! $account) {
                continue;
            }

            RevenuePlan::updateOrCreate(
                [
                    'erkap_rkap_id' => $rkap->id,
                    'division_id' => 2,
                    'chart_of_account_id' => $account->id,
                ],
                array_merge($monthlyValues, ['total' => array_sum($monthlyValues)])
            );
        }

        $expenseMonthly = [
            'jan_plan' => 180000000,
            'feb_plan' => 180000000,
            'mar_plan' => 200000000,
            'apr_plan' => 200000000,
            'may_plan' => 200000000,
            'jun_plan' => 220000000,
            'jul_plan' => 220000000,
            'aug_plan' => 220000000,
            'sep_plan' => 250000000,
            'oct_plan' => 250000000,
            'nov_plan' => 250000000,
            'dec_plan' => 270000000,
        ];

        foreach ($expenseAccounts as $account) {
            if (! $account) {
                continue;
            }

            ExpensePlan::updateOrCreate(
                [
                    'erkap_rkap_id' => $rkap->id,
                    'division_id' => 2,
                    'chart_of_account_id' => $account->id,
                ],
                array_merge($expenseMonthly, ['total' => array_sum($expenseMonthly)])
            );
        }

        foreach ([null, 2] as $divisionId) {
            $totalRevenue = RevenuePlan::where('erkap_rkap_id', $rkap->id)
                ->when($divisionId, fn ($query) => $query->where('division_id', $divisionId))
                ->sum('total');

            $totalExpense = ExpensePlan::where('erkap_rkap_id', $rkap->id)
                ->when($divisionId, fn ($query) => $query->where('division_id', $divisionId))
                ->sum('total');

            $grossProfit = $totalRevenue - $totalExpense;
            $margin = $totalRevenue > 0 ? round(($grossProfit / $totalRevenue) * 100, 2) : 0;

            ProfitLossStatement::updateOrCreate(
                [
                    'erkap_rkap_id' => $rkap->id,
                    'division_id' => $divisionId,
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
        }
    }
}
