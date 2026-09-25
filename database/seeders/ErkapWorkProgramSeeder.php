<?php

namespace Database\Seeders;

use App\Models\Erkap\WorkProgram;
use Illuminate\Database\Seeder;

class ErkapWorkProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $programs = [
            [
                "erkap_risk_identification_id" => 1,
                "name" => "Pemenuhan perangkat jaringan di setiap unit kerja perusahaan",
                "units" => "%",
                "year_plan" => 100,
                "jan_plan" => 8,
                "feb_plan" => 8,
                "mar_plan" => 8,
                "apr_plan" => 8,
                "may_plan" => 8,
                "jun_plan" => 8,
                "jul_plan" => 8,
                "aug_plan" => 8,
                "sep_plan" => 8,
                "oct_plan" => 8,
                "nov_plan" => 8,
                "dec_plan" => 12
            ],
            [
                "erkap_risk_identification_id" => 1,
                "name" => "Pemenuhan material dan suku cadang IT",
                "units" => "%",
                "year_plan" => 100,
                "jan_plan" => 8,
                "feb_plan" => 8,
                "mar_plan" => 8,
                "apr_plan" => 8,
                "may_plan" => 8,
                "jun_plan" => 8,
                "jul_plan" => 8,
                "aug_plan" => 8,
                "sep_plan" => 8,
                "oct_plan" => 8,
                "nov_plan" => 8,
                "dec_plan" => 12
            ],
            [
                "erkap_risk_identification_id" => 2,
                "name" => "Pemenuhan perangkat PC, Notebook, Printer dan Scanner sesuai dengan kajian kebutuhan pengguna",
                "units" => "%",
                "year_plan" => 100,
                "jan_plan" => 8,
                "feb_plan" => 8,
                "mar_plan" => 8,
                "apr_plan" => 8,
                "may_plan" => 8,
                "jun_plan" => 8,
                "jul_plan" => 8,
                "aug_plan" => 8,
                "sep_plan" => 8,
                "oct_plan" => 8,
                "nov_plan" => 8,
                "dec_plan" => 12
            ],
            [
                "erkap_risk_identification_id" => 3,
                "name" => "Pemenuhan Software original berlisensi",
                "units" => "%",
                "year_plan" => 100,
                "jan_plan" => 8,
                "feb_plan" => 8,
                "mar_plan" => 8,
                "apr_plan" => 8,
                "may_plan" => 8,
                "jun_plan" => 8,
                "jul_plan" => 8,
                "aug_plan" => 8,
                "sep_plan" => 8,
                "oct_plan" => 8,
                "nov_plan" => 8,
                "dec_plan" => 12
            ],
        ];

        foreach($programs as $program) {
            WorkProgram::create($program);
        }
    }
}
