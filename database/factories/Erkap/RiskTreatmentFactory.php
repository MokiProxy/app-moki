<?php

namespace Database\Factories\Erkap;

use App\Enums\ErkapRiskTreatmentType;
use App\Models\Erkap\RiskTreatment;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\DepartmentRiskStrategy;
use Illuminate\Database\Eloquent\Factories\Factory;

class RiskTreatmentFactory extends Factory
{
    protected $model = RiskTreatment::class;

    public function definition(): array
    {
        return [
            'erkap_risk_identification_id' => RiskIdentification::factory(),
            'erkap_department_risk_strategy_id' => DepartmentRiskStrategy::factory(),
            'treatment_type' => $this->faker->randomElement(ErkapRiskTreatmentType::values()),
            'description' => $this->faker->paragraph(),
            'responsible_party' => $this->faker->name(),
            'target_date' => $this->faker->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
            'status' => $this->faker->randomElement(['planned', 'in_progress', 'completed', 'cancelled']),
            'result' => $this->faker->optional()->paragraph(),
        ];
    }
}