<?php

namespace Database\Seeders;

use App\Models\Erkap\RiskScale;
use Illuminate\Database\Seeder;

class ErkapRiskScales extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $scales = [
            ["scale" => 1, "level" => "Low"],
            ["scale" => 2, "level" => "Low"],
            ["scale" => 3, "level" => "Low"],
            ["scale" => 4, "level" => "Low"],
            ["scale" => 5, "level" => "Low"],
            ["scale" => 6, "level" => "Low To Moderate"],
            ["scale" => 7, "level" => "Low To Moderate"],
            ["scale" => 8, "level" => "Low To Moderate"],
            ["scale" => 9, "level" => "Low To Moderate"],
            ["scale" => 10, "level" => "Low To Moderate"],
            ["scale" => 11, "level" => "Low To Moderate"],
            ["scale" => 12, "level" => "Moderate"],
            ["scale" => 13, "level" => "Moderate"],
            ["scale" => 14, "level" => "Moderate"],
            ["scale" => 15, "level" => "Moderate"],
            ["scale" => 16, "level" => "Moderate To High"],
            ["scale" => 17, "level" => "Moderate To High"],
            ["scale" => 18, "level" => "Moderate To High"],
            ["scale" => 19, "level" => "Moderate To High"],
            ["scale" => 20, "level" => "High"],
            ["scale" => 21, "level" => "High"],
            ["scale" => 22, "level" => "High"],
            ["scale" => 23, "level" => "High"],
            ["scale" => 24, "level" => "High"],
            ["scale" => 25, "level" => "High"],
        ];

        foreach($scales as $scale) {
            RiskScale::create($scale);
        }
    }
}
