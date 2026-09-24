<?php

namespace Database\Factories\Erkap;

use App\Models\Erkap\InvestationType;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvestationTypeFactory extends Factory
{
    protected $model = InvestationType::class;

    public function definition(): array
    {
        return [
            'code' => 'IT' . $this->faker->unique()->numerify('####'),
            'name' => $this->faker->word(),
        ];
    }
}