<?php

namespace Database\Factories\Erkap;

use App\Models\Erkap\Activity;
use App\Models\Erkap\CostCenter;
use App\Models\Division;
use Illuminate\Database\Eloquent\Factories\Factory;

class CostCenterFactory extends Factory
{
    protected $model = CostCenter::class;

    public function definition(): array
    {
        $activity = Activity::factory()->create();

        return [
            'name' => $this->faker->company(),
            'owner' => $this->faker->name(),
            'division_id' => Division::factory(),
            'erkap_activity_id' => $activity->id,
            'erkap_management_area_id' => $activity->erkap_management_area_id,
            'erkap_location_id' => $activity->erkap_location_id,
            'erkap_business_unit_id' => $activity->erkap_business_unit_id,
            'is_centralized' => false,
        ];
    }

    public function centralized(int $coordinatingDivisionId): static
    {
        return $this->state(fn () => [
            'is_centralized' => true,
            'coordinating_division_id' => $coordinatingDivisionId,
        ]);
    }
}
