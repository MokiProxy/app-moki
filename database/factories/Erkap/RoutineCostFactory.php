<?php

namespace Database\Factories\Erkap;

use App\Models\Erkap\RoutineCost;
use App\Models\Erkap\WorkProgram;
use App\Models\Erkap\CostCenter;
use App\Models\Erkap\CostElement;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoutineCostFactory extends Factory
{
    protected $model = RoutineCost::class;

    public function definition(): array
    {
        return [
            'erkap_work_program_id' => WorkProgram::factory(),
            'need' => $this->faker->sentence(),
            'cost_center_id' => CostCenter::factory(),
            'cost_center_owner' => $this->faker->name(),
            'qty' => $this->faker->numberBetween(1, 100),
            'units' => $this->faker->word(),
            'unit_price' => $this->faker->numberBetween(1000, 100000),
            'erkap_cost_element_id' => CostElement::factory(),
            'jan_cost' => $this->faker->numberBetween(1000, 100000),
            'feb_cost' => $this->faker->numberBetween(1000, 100000),
            'mar_cost' => $this->faker->numberBetween(1000, 100000),
            'apr_cost' => $this->faker->numberBetween(1000, 100000),
            'may_cost' => $this->faker->numberBetween(1000, 100000),
            'jun_cost' => $this->faker->numberBetween(1000, 100000),
            'jul_cost' => $this->faker->numberBetween(1000, 100000),
            'aug_cost' => $this->faker->numberBetween(1000, 100000),
            'sep_cost' => $this->faker->numberBetween(1000, 100000),
            'oct_cost' => $this->faker->numberBetween(1000, 100000),
            'nov_cost' => $this->faker->numberBetween(1000, 100000),
            'des_cost' => $this->faker->numberBetween(1000, 100000),
            'total' => $this->faker->numberBetween(10000, 1000000),
            'is_kumulatif' => false,
            'status' => 'draft',
        ];
    }
}