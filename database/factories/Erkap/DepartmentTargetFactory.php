<?php

namespace Database\Factories\Erkap;

use App\Models\Erkap\DepartmentTarget;
use App\Models\Division;
use App\Models\Erkap\RatingCriteria;
use App\Models\Erkap\CompanyTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentTargetFactory extends Factory
{
    protected $model = DepartmentTarget::class;

    public function definition(): array
    {
        return [
            'target' => $this->faker->sentence(),
            'division_id' => Division::factory(),
            'erkap_rating_criteria_id' => RatingCriteria::factory(),
            'erkap_company_target_id' => CompanyTarget::factory(),
        ];
    }
}