<?php

namespace Database\Seeders;

use App\Enums\ErkapRatingLevel;
use App\Models\Erkap\RatingCriteria;
use Illuminate\Database\Seeder;

class ErkapRatingCriteriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (ErkapRatingLevel::cases() as $level) {
            RatingCriteria::updateOrCreate(
                ['rating' => $level->value],
                [
                    'qualification' => $level->qualification(),
                    'description' => $level->description(),
                ]
            );
        }
    }
}