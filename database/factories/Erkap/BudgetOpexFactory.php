<?php

namespace Database\Factories\Erkap;

use App\Models\Erkap\BudgetOpex;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\CostCenter;
use App\Models\ChartOfAccount;
use App\Models\Division;
use Illuminate\Database\Eloquent\Factories\Factory;

class BudgetOpexFactory extends Factory
{
    protected $model = BudgetOpex::class;

    public function definition(): array
    {
        return [
            'erkap_rkap_id' => RKAP::factory(),
            'division_id' => Division::factory(),
            'cost_center_id' => CostCenter::factory(),
            'chart_of_account_id' => ChartOfAccount::factory(),
            'budget_amount' => $this->faker->randomFloat(2, 1000000, 100000000),
            'realization_amount' => $this->faker->randomFloat(2, 0, 100000000),
            'variance' => $this->faker->randomFloat(2, -10000000, 10000000),
            'status' => $this->faker->randomElement(['draft', 'submitted', 'approved', 'rejected']),
        ];
    }
}