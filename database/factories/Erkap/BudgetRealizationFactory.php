<?php

namespace Database\Factories\Erkap;

use App\Models\Erkap\BudgetRealization;
use Illuminate\Database\Eloquent\Factories\Factory;

class BudgetRealizationFactory extends Factory
{
    protected $model = BudgetRealization::class;

    public function definition(): array
    {
        return [
            'erkap_rkap_id' => \App\Models\Erkap\RKAP::factory(),
            'month' => $this->faker->numberBetween(1, 12),
            'year' => (int) date('Y'),
            'budgeted' => $this->faker->numberBetween(100000, 10000000),
            'realized' => $this->faker->numberBetween(100000, 10000000),
            'variance' => 0,
            'variance_percent' => 0,
            'source' => 'manual',
        ];
    }

    public function opex(int $routineCostId): static
    {
        return $this->state(fn () => ['erkap_routine_cost_id' => $routineCostId]);
    }

    public function capex(int $investmentPlanId): static
    {
        return $this->state(fn () => ['erkap_investment_plan_id' => $investmentPlanId]);
    }
}