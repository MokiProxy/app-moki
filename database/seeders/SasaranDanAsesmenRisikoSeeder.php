<?php

namespace Database\Seeders;

use App\Models\Erkap\CompanyTarget;
use App\Models\Erkap\DepartmentRiskStrategy;
use App\Models\Erkap\DepartmentTarget;
use App\Models\Erkap\RiskAnalysis;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RiskIdentificationImpact;
use App\Models\Erkap\RiskIdentificationReason;
use App\Models\Erkap\RKAP;
use Illuminate\Database\Seeder;

class SasaranDanAsesmenRisikoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $periodeRKAP = ["year" => "2026"];
        $rkap = RKAP::create($periodeRKAP);

        $companyTargets = [
            [
                "target" => "Tercapainya produksi batubara sebesar 49 juta bcp dengan melakukan optimalisasi seluruh tambang yang saat ini sudah beroperasi maupun yang direncanakan akan beroperasi dalam Tahun 2027",
                "erkap_rkap_id" => $rkap->id
            ]
        ];

        foreach($companyTargets as $target) {
            $companyTarget = CompanyTarget::create($target);

            $departmentTargets = [
                [
                    "target" => "Tercapainya produksi BB dilokasi Banl Tenga sebesar 26 juta bcm",
                    "division_id" => 49,
                    "erkap_rating_criteria_id" => 1,
                    "erkap_company_target_id" => $companyTarget->id
                ]
            ];

            foreach($departmentTargets as $departmentTarget) {
                $deptTarget = DepartmentTarget::create($departmentTarget);

                $risks = [
                    [
                        "risk" => "Terdapat deviasi antara volume asil joint survey",
                        "risk_direction" => "negative",
                        "erkap_department_target_id" => $deptTarget->id,
                        "erkap_risk_type_id" => 29,
                        "erkap_risk_taxonomy_id" => 5,
                    ]
                ];

                foreach($risks as $risk) {
                    $riskIdentification = RiskIdentification::create($risk);

                    $reasons = [
                        ["reason" => "Perangkat rusak", "erkap_risk_identification_id" => 1],
                        ["reason" => "Server tidak bisa diakses", "erkap_risk_identification_id" => 1],
                        ["reason" => "Jaringan putus", "erkap_risk_identification_id" => 1],
                        ["reason" => "Tidak tersedianya backup suku cadang", "erkap_risk_identification_id" => 1],
                        ["reason" => "Performa perangkat sudah menurun dikarenakan usia pemakaian dan teknologi yang sudah tertinggal", "erkap_risk_identification_id" => 2],
                        ["reason" => "Menggunakan software yang tidak berlisensi / bajakan", "erkap_risk_identification_id" => 3],
                    ];
                    foreach($reasons as $reason) {
                        if($reason['erkap_risk_identification_id'] == $riskIdentification->id) {
                            $riskIdentificationReason = RiskIdentificationReason::create($reason);
                        }
                    }

                    $impacts = [
                        ["impact" => "Pekerjaan operasional yang menggunakan perangkat IT menjadi terhambat", "erkap_risk_identification_id" => 1],
                        ["impact" => "Pekerjaan operasional yang menggunakan perangkat IT menjadi terhambat", "erkap_risk_identification_id" => 2],
                        ["impact" => "Penurunan reputasi perusahaan", "erkap_risk_identification_id" => 3],
                    ];
                    foreach($impacts as $impact) {
                        if($impact['erkap_risk_identification_id'] == $riskIdentification->id) {
                            $riskIdentificationImpact = RiskIdentificationImpact::create($impact);
                        }
                    }

                    $risksAnalysis = [
                        [
                            "erkap_risk_identification_id" => 1,
                            "erkap_risk_probability_id" => 4,
                            "erkap_risk_impact_id" => 3,
                            "erkap_risk_score_value_id" => 18,
                        ],
                        [
                            "erkap_risk_identification_id" => 2,
                            "erkap_risk_probability_id" => 4,
                            "erkap_risk_impact_id" => 3,
                            "erkap_risk_score_value_id" => 18,
                        ],
                        [
                            "erkap_risk_identification_id" => 3,
                            "erkap_risk_probability_id" => 3,
                            "erkap_risk_impact_id" => 4,
                            "erkap_risk_score_value_id" => 14,
                        ],
                    ];
                    foreach ($risksAnalysis as $riskAnalysis) {
                        if($riskAnalysis['erkap_risk_identification_id'] == $riskIdentification->id) {
                            $createdRiskAnalysis = RiskAnalysis::create($riskAnalysis);
                        }
                    }

                    $strategies = [
                        ["erkap_risk_identification_id" => 1, "strategy" => "Melakukan pemenuhan kebutuhan perangkat jaringan di setiap unit kerja perusahaan"],
                        ["erkap_risk_identification_id" => 1, "strategy" => "Menyediakan suku cadang sebagai backup jika terjadi kerusakan"],
                        ["erkap_risk_identification_id" => 2, "strategy" => "Melakukan perawatan secara berkala"],
                        ["erkap_risk_identification_id" => 3, "strategy" => "Memenuhi kebutuhan lisensi semua perangkat lunak yang terinstall di dalam perangkat yang digunakan user"],
                        ["erkap_risk_identification_id" => 3, "strategy" => "Memastikan perpanjangan lisensi dibayarkan tepat waktu"],
                    ];
                    foreach ($strategies as $strategy) {
                        if($strategy['erkap_risk_identification_id'] == $riskIdentification->id) {
                            $createdStrategy = DepartmentRiskStrategy::create($strategy);
                        }
                    }

                }
            }
        }
    }
}
