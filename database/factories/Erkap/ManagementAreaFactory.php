<?php

namespace Database\Factories\Erkap;

use App\Models\Erkap\Location;
use App\Models\Erkap\ManagementArea;
use Illuminate\Database\Eloquent\Factories\Factory;

class ManagementAreaFactory extends Factory
{
    protected $model = ManagementArea::class;

    public function definition(): array
    {
        $location = Location::factory()->create();

        return [
            'code' => $this->faker->unique()->numerify('#####'),
            'name' => $this->faker->unique()->jobTitle(),
            'erkap_location_id' => $location->id,
            'erkap_business_unit_id' => $location->erkap_business_unit_id,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
