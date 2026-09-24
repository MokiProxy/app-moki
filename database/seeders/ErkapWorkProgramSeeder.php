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
                "jan_plan" => null,
                "feb_plan" => null,
                "mar_plan" => null,
                "apr_plan" => null,
                "may_plan" => null,
                "jun_plan" => null,
                "jul_plan" => null,
                "aug_plan" => null,
                "sep_plan" => null,
                "oct_plan" => null,
                "nov_plan" => null,
                "dec_plan" => null
            ],
            [
                "erkap_risk_identification_id" => 1,
                "name" => "Pemenuhan material dan suku cadang IT",
                "units" => "%",
                "year_plan" => 100,
                "jan_plan" => null,
                "feb_plan" => null,
                "mar_plan" => null,
                "apr_plan" => null,
                "may_plan" => null,
                "jun_plan" => null,
                "jul_plan" => null,
                "aug_plan" => null,
                "sep_plan" => null,
                "oct_plan" => null,
                "nov_plan" => null,
                "dec_plan" => null
            ],
            [
                "erkap_risk_identification_id" => 2,
                "name" => "Pemenuhan perangkat PC, Notebook, Printer dan Scanner sesuai dengan kajian kebutuhan pengguna",
                "units" => "%",
                "year_plan" => 100,
                "jan_plan" => null,
                "feb_plan" => null,
                "mar_plan" => null,
                "apr_plan" => null,
                "may_plan" => null,
                "jun_plan" => null,
                "jul_plan" => null,
                "aug_plan" => null,
                "sep_plan" => null,
                "oct_plan" => null,
                "nov_plan" => null,
                "dec_plan" => null
            ],
            [
                "erkap_risk_identification_id" => 3,
                "name" => "Pemenuhan Software original berlisensi",
                "units" => "%",
                "year_plan" => 100,
                "jan_plan" => null,
                "feb_plan" => null,
                "mar_plan" => null,
                "apr_plan" => null,
                "may_plan" => null,
                "jun_plan" => null,
                "jul_plan" => null,
                "aug_plan" => null,
                "sep_plan" => null,
                "oct_plan" => null,
                "nov_plan" => null,
                "dec_plan" => null
            ],
        ];

        foreach($programs as $program) {
            WorkProgram::create($program);
        }
    }
}
