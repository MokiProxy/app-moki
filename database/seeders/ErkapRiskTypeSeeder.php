<?php

namespace Database\Seeders;

use App\Models\Erkap\RiskType;
use Illuminate\Database\Seeder;

class ErkapRiskTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $riskTypes = [
            ["name" => "A1 Macro economy", "risk_taxonomy_id" => 4],
            ["name" => "A2 Industry", "risk_taxonomy_id" => 4],
            ["name" => "A3 Investment   Konteks Project", "risk_taxonomy_id" => 8],
            ["name" => "A3 Investment   Konteks Operasional", "risk_taxonomy_id" => 5],
            ["name" => "A4 Regulation Changes   Konteks Bisnis", "risk_taxonomy_id" => 1],
            ["name" => "A4 Regulation Changes   Konteks Operasional", "risk_taxonomy_id" => 5],
            ["name" => "A4 Regulation Changes   Konteks Lingkungan", "risk_taxonomy_id" => 7],
            ["name" => "A5 Community Relation   Konteks Sosial", "risk_taxonomy_id" => 7],
            ["name" => "A5 Community Relation   Konteks Reputasi", "risk_taxonomy_id" => 3],
            ["name" => "A6  Security Threat", "risk_taxonomy_id" => 5],
            ["name" => "A7 Weather", "risk_taxonomy_id" => 7],
            ["name" => "B1 Production Cost", "risk_taxonomy_id" => 5],
            ["name" => "B2 Sourcing", "risk_taxonomy_id" => 5],
            ["name" => "B3 Health & Safety", "risk_taxonomy_id" => 5],
            ["name" => "B3 Environment", "risk_taxonomy_id" => 7],
            ["name" => "B4 Production Disruption", "risk_taxonomy_id" => 5],
            ["name" => "B5 Product Quality", "risk_taxonomy_id" => 5],
            ["name" => "B6 Capacity", "risk_taxonomy_id" => 5],
            ["name" => "B7 Facility  Infrastructure", "risk_taxonomy_id" => 5],
            ["name" => "B8 Operating Planning", "risk_taxonomy_id" => 5],
            ["name" => "B9 Marketing", "risk_taxonomy_id" => 4],
            ["name" => "B9 Sales", "risk_taxonomy_id" => 5],
            ["name" => "B10 Business Interuption   Konteks Bisnis", "risk_taxonomy_id" => 1],
            ["name" => "B10 Business Interuption   Konteks Bencana Alam", "risk_taxonomy_id" => 7],
            ["name" => "B11 Project", "risk_taxonomy_id" => 8],
            ["name" => "B12 Contractor/Third Party", "risk_taxonomy_id" => 5],
            ["name" => "B13 Financial Reporting", "risk_taxonomy_id" => 2],
            ["name" => "B14 Land Availability", "risk_taxonomy_id" => 5],
            ["name" => "B15 Reserve of Mineral Resources", "risk_taxonomy_id" => 5],
            ["name" => "C1 People", "risk_taxonomy_id" => 5],
            ["name" => "C2 Governance", "risk_taxonomy_id" => 3],
            ["name" => "C3 Business Process", "risk_taxonomy_id" => 5],
            ["name" => "C4 Financial", "risk_taxonomy_id" => 2],
            ["name" => "C5 Information Technology", "risk_taxonomy_id" => 6],
            ["name" => "C6 Legal & Regulatory Compliance", "risk_taxonomy_id" => 3],
        ];

        foreach($riskTypes as $riskType) {
            RiskType::create($riskType);
        }
    }
}
