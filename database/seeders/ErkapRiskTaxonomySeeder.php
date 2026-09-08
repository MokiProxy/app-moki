<?php

namespace Database\Seeders;

use App\Models\Erkap\RiskTaxonomy;
use Illuminate\Database\Seeder;

class ErkapRiskTaxonomySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $taxonomies = [
            ["name" => "Strategic", "risk_appetite_id" => 1],
            ["name" => "Financial", "risk_appetite_id" => 2],
            ["name" => "Legal, Compliance & Reputation", "risk_appetite_id" => 3],
            ["name" => "Market & Macroeconomic", "risk_appetite_id" => 4],
            ["name" => "Operational", "risk_appetite_id" => 4],
            ["name" => "IT & Cybersecurity", "risk_appetite_id" => 3],
            ["name" => "Social & Environment", "risk_appetite_id" => 3],
            ["name" => "Project", "risk_appetite_id" => 2],
        ];

        foreach($taxonomies as $taxonomy) {
            RiskTaxonomy::create($taxonomy);
        }
    }
}
