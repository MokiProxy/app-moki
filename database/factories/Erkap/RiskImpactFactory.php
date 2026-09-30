<?php

namespace Database\Factories\Erkap;

use App\Models\Erkap\RiskImpact;
use Illuminate\Database\Eloquent\Factories\Factory;

class RiskImpactFactory extends Factory
{
    protected $model = RiskImpact::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Minor', 'Moderate', 'Major']),
            'point' => $this->faker->numberBetween(1, 5),
        ];
    }
}
