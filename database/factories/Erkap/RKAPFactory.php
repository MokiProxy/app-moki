<?php

namespace Database\Factories\Erkap;

use App\Models\Erkap\RKAP;
use Illuminate\Database\Eloquent\Factories\Factory;

class RKAPFactory extends Factory
{
    protected $model = RKAP::class;

    public function definition(): array
    {
        return [
            'year' => now()->year,
            'status' => 'draft',
        ];
    }
}