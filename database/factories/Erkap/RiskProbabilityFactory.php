<?php

namespace Database\Factories\Erkap;

use App\Models\Erkap\RiskProbability;
use Illuminate\Database\Eloquent\Factories\Factory;

class RiskProbabilityFactory extends Factory
{
    protected $model = RiskProbability::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Rendah', 'Sedang', 'Tinggi']),
            'point' => $this->faker->numberBetween(1, 5),
        ];
    }
}
