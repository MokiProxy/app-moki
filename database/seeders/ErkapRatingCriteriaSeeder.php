<?php

namespace Database\Seeders;

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
        $criterias = [
            ["rating" => "AAA", "qualification" => "Sangat Kritis", "description" => "Sangat berpengaruh terhadap keberlangsungan hidup atau pertumbuhan perusahaan"],
            ["rating" => "AA", "qualification" => "Kritis", "description" => "Dapat berpengaruh terhadap keberlangsungan hidup atau pertumbuhan perusahaan secara berlajut"],
            ["rating" => "A", "qualification" => "Cukup Kritis", "description" => "Sangat mendukung operasi perusahaan secara keseluruhan"],
            ["rating" => "B", "qualification" => "Penting", "description" => "Dapat mendukung operasi perusahaan secara keseluruhan"],
            ["rating" => "BB", "qualification" => "Cukup Penting", "description" => "Dapat mendukung sebagian besar operasi perusahaan"],
        ];

        foreach($criterias as $criteria) {
            RatingCriteria::create($criteria);
        }
    }
}
