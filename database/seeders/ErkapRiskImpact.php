<?php

namespace Database\Seeders;

use App\Models\Erkap\RiskImpact;
use Illuminate\Database\Seeder;

class ErkapRiskImpact extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $impacts = [
            [
                "name" => "Sangat Rendah",
                "point" => 1
            ],
            [
                "name" => "Rendah",
                "point" => 2
            ],
            [
                "name" => "Moderat",
                "point" => 3
            ],
            [
                "name" => "Tinggi",
                "point" => 4
            ],
            [
                "name" => "Sangat Tinggi",
                "point" => 5
            ]
        ];

        foreach ($impacts as $impact) {
            RiskImpact::create($impact);
        }
    }
}
