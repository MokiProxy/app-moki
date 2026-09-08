<?php

namespace Database\Seeders;

use App\Models\Erkap\RiskScoreLevel;
use Illuminate\Database\Seeder;

class ErkapRiskScoreLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $datas = [
            ["erkap_risk_probability_id" => 1, "erkap_risk_impact_id" => 1, "score" => 1, "level" => "Low"],
            ["erkap_risk_probability_id" => 1, "erkap_risk_impact_id" => 2, "score" => 5, "level" => "Low"],
            ["erkap_risk_probability_id" => 1, "erkap_risk_impact_id" => 3, "score" => 10, "level" => "Low To Moderate"],
            ["erkap_risk_probability_id" => 1, "erkap_risk_impact_id" => 4, "score" => 15, "level" => "Moderate"],
            ["erkap_risk_probability_id" => 1, "erkap_risk_impact_id" => 5, "score" => 20, "level" => "High"],
            ["erkap_risk_probability_id" => 2, "erkap_risk_impact_id" => 1, "score" => 2, "level" => "Low"],
            ["erkap_risk_probability_id" => 2, "erkap_risk_impact_id" => 2, "score" => 6, "level" => "Low To Moderate"],
            ["erkap_risk_probability_id" => 2, "erkap_risk_impact_id" => 3, "score" => 11, "level" => "Low To Moderate"],
            ["erkap_risk_probability_id" => 2, "erkap_risk_impact_id" => 4, "score" => 16, "level" => "Moderate To High"],
            ["erkap_risk_probability_id" => 2, "erkap_risk_impact_id" => 5, "score" => 21, "level" => "High"],
            ["erkap_risk_probability_id" => 3, "erkap_risk_impact_id" => 1, "score" => 3, "level" => "Low"],
            ["erkap_risk_probability_id" => 3, "erkap_risk_impact_id" => 2, "score" => 8, "level" => "Low To Moderate"],
            ["erkap_risk_probability_id" => 3, "erkap_risk_impact_id" => 3, "score" => 13, "level" => "Moderate"],
            ["erkap_risk_probability_id" => 3, "erkap_risk_impact_id" => 4, "score" => 16, "level" => "Moderate To High"],
            ["erkap_risk_probability_id" => 3, "erkap_risk_impact_id" => 5, "score" => 23, "level" => "High"],
            ["erkap_risk_probability_id" => 4, "erkap_risk_impact_id" => 1, "score" => 4, "level" => "Low"],
            ["erkap_risk_probability_id" => 4, "erkap_risk_impact_id" => 2, "score" => 9, "level" => "Low To Moderate"],
            ["erkap_risk_probability_id" => 4, "erkap_risk_impact_id" => 3, "score" => 14, "level" => "Moderate"],
            ["erkap_risk_probability_id" => 4, "erkap_risk_impact_id" => 4, "score" => 19, "level" => "Moderate To High"],
            ["erkap_risk_probability_id" => 4, "erkap_risk_impact_id" => 5, "score" => 24, "level" => "High"],
            ["erkap_risk_probability_id" => 5, "erkap_risk_impact_id" => 1, "score" => 7, "level" => "Low To Moderate"],
            ["erkap_risk_probability_id" => 5, "erkap_risk_impact_id" => 2, "score" => 12, "level" => "Moderate"],
            ["erkap_risk_probability_id" => 5, "erkap_risk_impact_id" => 3, "score" => 17, "level" => "Moderate To High"],
            ["erkap_risk_probability_id" => 5, "erkap_risk_impact_id" => 4, "score" => 22, "level" => "High"],
            ["erkap_risk_probability_id" => 5, "erkap_risk_impact_id" => 5, "score" => 25, "level" => "High"],
        ];

        foreach($datas as $data) {
            RiskScoreLevel::create($data);
        }
    }
}
