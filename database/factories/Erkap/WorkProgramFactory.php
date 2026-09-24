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
            'year_plan' => $this->faker->numberBetween(100, 1000),
            'jan_plan' => $this->faker->numberBetween(5, 20),
            'feb_plan' => $this->faker->numberBetween(5, 20),
            'mar_plan' => $this->faker->numberBetween(5, 20),
            'apr_plan' => $this->faker->numberBetween(5, 20),
            'may_plan' => $this->faker->numberBetween(5, 20),
            'jun_plan' => $this->faker->numberBetween(5, 20),
            'jul_plan' => $this->faker->numberBetween(5, 20),
            'aug_plan' => $this->faker->numberBetween(5, 20),
            'sep_plan' => $this->faker->numberBetween(5, 20),
            'oct_plan' => $this->faker->numberBetween(5, 20),
            'nov_plan' => $this->faker->numberBetween(5, 20),
            'dec_plan' => $this->faker->numberBetween(5, 20),
            'status' => 'draft',
        ];
    }
}