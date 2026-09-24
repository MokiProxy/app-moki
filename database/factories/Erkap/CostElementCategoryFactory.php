<?php

namespace Database\Factories\Erkap;

use App\Models\Erkap\CostElementCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class CostElementCategoryFactory extends Factory
{
    protected $model = CostElementCategory::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
        ];
    }
}