<?php

namespace Database\Factories\Erkap;

use App\Models\Erkap\InvestattionCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvestattionCategoryFactory extends Factory
{
    protected $model = InvestattionCategory::class;

    public function definition(): array
    {
        return [
            'code' => 'IC' . $this->faker->unique()->numerify('####'),
            'name' => $this->faker->word(),
        ];
    }
}