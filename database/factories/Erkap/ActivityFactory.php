<?php

namespace Database\Factories\Erkap;

use App\Models\Erkap\Activity;
use App\Models\Erkap\ManagementArea;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        $area = ManagementArea::factory()->create();

        return [
            'code' => $this->faker->unique()->numerify('###'),
            'name' => $this->faker->unique()->jobTitle(),
            'erkap_management_area_id' => $area->id,
            'erkap_location_id' => $area->erkap_location_id,
            'erkap_business_unit_id' => $area->erkap_business_unit_id,
            'is_swakelola' => $this->faker->boolean(),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function swakelola(): static
    {
        return $this->state(fn () => ['is_swakelola' => true]);
    }

    public function nonSwakelola(): static
    {
        return $this->state(fn () => ['is_swakelola' => false]);
    }
}
