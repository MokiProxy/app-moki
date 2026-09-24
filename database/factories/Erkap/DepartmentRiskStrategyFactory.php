<?php

namespace Database\Factories\Erkap;

use App\Enums\ErkapRiskTreatmentType;
use App\Models\Erkap\DepartmentRiskStrategy;
use App\Models\Erkap\RiskIdentification;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentRiskStrategyFactory extends Factory
{
    protected $model = DepartmentRiskStrategy::class;

    public function definition(): array
    {
        return [
            'erkap_risk_identification_id' => RiskIdentification::factory(),
            'strategy' => $this->faker->randomElement(ErkapRiskTreatmentType::values()),
        ];
    }
}