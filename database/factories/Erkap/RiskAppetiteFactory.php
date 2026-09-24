<?php

namespace Database\Factories\Erkap;

use App\Models\Erkap\RiskAppetite;
use Illuminate\Database\Eloquent\Factories\Factory;

class RiskAppetiteFactory extends Factory
{
    protected $model = RiskAppetite::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
        ];
    }
}