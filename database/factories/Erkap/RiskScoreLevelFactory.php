<?php

namespace Database\Factories\Erkap;

use App\Models\Erkap\RiskScoreLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

class RiskScoreLevelFactory extends Factory
{
    protected $model = RiskScoreLevel::class;

    public function definition(): array
    {
        return [
            'score' => $this->faker->numberBetween(1, 25),
            'level' => $this->faker->randomElement(['Low', 'Moderate', 'High', 'Extreme']),
        ];
    }
}
