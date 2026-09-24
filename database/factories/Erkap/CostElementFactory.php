<?php

namespace Database\Factories\Erkap;

use App\Models\Erkap\CostElement;
use App\Models\Erkap\CostElementCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class CostElementFactory extends Factory
{
    protected $model = CostElement::class;

    public function definition(): array
    {
        return [
            'code' => 'CE' . $this->faker->unique()->numerify('####'),
            'name' => $this->faker->word(),
            'erkap_cost_element_category_id' => CostElementCategory::factory(),
        ];
    }
}