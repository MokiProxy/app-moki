<?php

namespace Database\Factories\Erkap;

use App\Enums\ErkapRatingLevel;
use App\Models\Erkap\RatingCriteria;
use Illuminate\Database\Eloquent\Factories\Factory;

class RatingCriteriaFactory extends Factory
{
    protected $model = RatingCriteria::class;

    public function definition(): array
    {
        return [
            'rating' => ErkapRatingLevel::A->value,
            'qualification' => ErkapRatingLevel::A->qualification(),
            'description' => $this->faker->sentence(),
        ];
    }
}