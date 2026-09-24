<?php

namespace Database\Factories\Erkap;

use App\Models\Erkap\CostCenter;
use App\Models\Division;
use Illuminate\Database\Eloquent\Factories\Factory;

class CostCenterFactory extends Factory
{
    protected $model = CostCenter::class;

    public function definition(): array
    {
        return [
            'code' => 'CC' . $this->faker->unique()->numerify('####'),
            'name' => $this->faker->company(),
            'owner' => $this->faker->name(),
            'division_id' => Division::factory(),
            'is_swakelola' => false,
        ];
    }
}