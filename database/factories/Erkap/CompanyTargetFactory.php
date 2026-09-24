<?php

namespace Database\Factories\Erkap;

use App\Models\Erkap\CompanyTarget;
use App\Models\Erkap\DepartmentTarget;
use App\Models\Erkap\Division;
use App\Models\Erkap\RatingCriteria;
use App\Models\Erkap\RKAP;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyTargetFactory extends Factory
{
    protected $model = CompanyTarget::class;

    public function definition(): array
    {
        return [
            'erkap_rkap_id' => RKAP::factory(),
            'target' => $this->faker->sentence(),
        ];
    }
}