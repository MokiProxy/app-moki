<?php

namespace Database\Factories;

use App\Models\PegawaiMasterPosisi;
use Illuminate\Database\Eloquent\Factories\Factory;

class PegawaiMasterPosisiFactory extends Factory
{
    protected $model = PegawaiMasterPosisi::class;

    public function definition(): array
    {
        return [
            'position_id' => $this->faker->unique()->bothify('POS-####'),
            'superior_id' => null,
            'pos_title' => $this->faker->jobTitle,
            'last_mode_date' => $this->faker->date(),
            'last_mode_time' => $this->faker->time(),
        ];
    }

    public function childOf(string $superiorId): static
    {
        return $this->state(fn(array $attrs) => [
            'superior_id' => $superiorId,
        ]);
    }
}
