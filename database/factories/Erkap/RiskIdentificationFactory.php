<?php

namespace Database\Factories\Erkap;

use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\DepartmentTarget;
use App\Models\Erkap\RiskType;
use App\Models\Erkap\RiskTaxonomy;
use Illuminate\Database\Eloquent\Factories\Factory;

class RiskIdentificationFactory extends Factory
{
    protected $model = RiskIdentification::class;

    public function definition(): array
    {
        return [
            'risk' => $this->faker->sentence(),
            'risk_direction' => $this->faker->randomElement(['positive', 'negative']),
            'erkap_department_target_id' => DepartmentTarget::factory(),
            'erkap_risk_type_id' => RiskType::factory(),
            'erkap_risk_taxonomy_id' => RiskTaxonomy::factory(),
        ];
    }
}