<?php

namespace Database\Factories;

use App\Models\ChartOfAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChartOfAccountFactory extends Factory
{
    protected $model = ChartOfAccount::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->numerify('################'),
            'name' => $this->faker->sentence(3),
            'type' => 'expense',
            'description' => $this->faker->sentence(),
        ];
    }

    public function revenue(): static
    {
        return $this->state(fn () => ['type' => 'revenue']);
    }

    public function expense(): static
    {
        return $this->state(fn () => ['type' => 'expense']);
    }
}