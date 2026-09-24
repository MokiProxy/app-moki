<?php

namespace Database\Factories;

use App\Models\Division;
use App\Models\Regional;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class DivisionFactory extends Factory
{
    protected $model = Division::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'abbreviation' => strtoupper($this->faker->unique()->lexify('???')),
            'regional_id' => Regional::factory(),
            'company_id' => Company::factory(),
        ];
    }
}