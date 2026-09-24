<?php

namespace Database\Factories;

use App\Models\Regional;
use Illuminate\Database\Eloquent\Factories\Factory;

class RegionalFactory extends Factory
{
    protected $model = Regional::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->state(),
            'abbreviation' => strtoupper($this->faker->unique()->lexify('??')),
        ];
    }
}