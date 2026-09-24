<?php

namespace Database\Factories\Erkap;

use App\Models\Erkap\InvestationCriteria;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvestationCriteriaFactory extends Factory
{
    protected $model = InvestationCriteria::class;

    public function definition(): array
    {
        return [
            'code' => 'ICR' . $this->faker->unique()->numerify('####'),
            'name' => $this->faker->word(),
        ];
    }
}