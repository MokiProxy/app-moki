<?php

namespace Database\Factories\Erkap;

use App\Models\Erkap\BusinessUnit;
use App\Models\Erkap\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->numerify('##'),
            'name' => $this->faker->unique()->city(),
            'erkap_business_unit_id' => BusinessUnit::factory(),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
