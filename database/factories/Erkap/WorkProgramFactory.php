<?php

namespace Database\Factories\Erkap;

use App\Models\Erkap\WorkProgram;
use App\Models\Erkap\RiskIdentification;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkProgramFactory extends Factory
{
    protected $model = WorkProgram::class;

    public function definition(): array
    {
        return [
            'erkap_risk_identification_id' => RiskIdentification::factory(),
            'code' => 'WP-' . strtoupper($this->faker->unique()->lexify('????')),
            'name' => $this->faker->sentence(),
            'units' => $this->faker->word(),
            'year_plan' => 100,
            'jan_plan' => 8,
            'feb_plan' => 8,
            'mar_plan' => 8,
            'apr_plan' => 8,
            'may_plan' => 8,
            'jun_plan' => 8,
            'jul_plan' => 8,
            'aug_plan' => 8,
            'sep_plan' => 8,
            'oct_plan' => 8,
            'nov_plan' => 8,
            'dec_plan' => 12,
            'status' => 'draft',
        ];
    }
}