<?php

namespace Database\Seeders;

use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RiskTreatment;
use Illuminate\Database\Seeder;

class RiskTreatmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $risk = RiskIdentification::first();

        if (! $risk) {
            return;
        }

        $strategy = $risk->departmentRiskStrategies()->first();

        $treatments = [
            [
                'treatment_type' => 'reduction',
                'description' => 'Menyusun SOP mitigasi dan melakukan pengawasan berkala',
                'responsible_party' => 'Manajer Risiko',
                'status' => 'in_progress',
            ],
            [
                'treatment_type' => 'sharing',
                'description' => 'Mengalihkan sebagian risiko melalui asuransi',
                'responsible_party' => 'Head of Finance',
                'status' => 'planned',
            ],
            [
                'treatment_type' => 'acceptance',
                'description' => 'Menerima risiko dengan pemantauan rutin',
                'responsible_party' => 'Direksi',
                'status' => 'planned',
            ],
        ];

        foreach ($treatments as $treatment) {
            RiskTreatment::create(array_merge($treatment, [
                'erkap_risk_identification_id' => $risk->id,
                'erkap_department_risk_strategy_id' => $strategy?->id,
                'target_date' => now()->addMonths(6)->format('Y-m-d'),
                'result' => null,
            ]));
        }
    }
}