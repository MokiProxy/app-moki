<?php

namespace Database\Factories\Erkap;

use App\Models\Erkap\RiskType;
use Illuminate\Database\Eloquent\Factories\Factory;

class RiskTypeFactory extends Factory
{
    protected $model = RiskType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'risk_taxonomy_id' => \App\Models\Erkap\RiskTaxonomy::factory(),
        ];
    }
}