<?php

namespace Database\Seeders;

use App\Models\Erkap\RiskProbability;
use Illuminate\Database\Seeder;

class ErkapRiskProbabilitiy extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $probabilities = [
            ["name" => "Sangat Jarang Terjadi", "point" => 1],
            ["name" => "Jarang Terjadi", "point" => 2],
            ["name" => "Bisa Terjadi", "point" => 3],
            ["name" => "Sangat Mungkin Terjadi", "point" => 4],
            ["name" => "Hampir Pasti Terjadi", "point" => 5],
        ];

        foreach($probabilities as $probability) {
            RiskProbability::create($probability);
        }
    }
}
