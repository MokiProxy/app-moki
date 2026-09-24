<?php

namespace Database\Factories\Erkap;

use App\Models\Erkap\RiskTaxonomy;
use Illuminate\Database\Eloquent\Factories\Factory;

class RiskTaxonomyFactory extends Factory
{
    protected $model = RiskTaxonomy::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'risk_appetite_id' => \App\Models\Erkap\RiskAppetite::factory(),
        ];
    }
}