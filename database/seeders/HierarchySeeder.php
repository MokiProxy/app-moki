<?php

namespace Database\Seeders;

use App\Models\MasterPegawaiHirarki;
use App\Models\PegawaiHirarki;
use App\Models\PegawaiMasterPosisi;
use App\Models\PegawaiSatker;
use App\Services\HierarchyService;
use Illuminate\Database\Seeder;

class HierarchySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed satker
        $satker = PegawaiSatker::firstOrCreate(
            ['kode_satker' => 'HO'],
            ['nama_satker' => 'Head Office']
        );

        // 2. Seed positions (adjacency list)
        $positions = [
            ['position_id' => 'POS-CEO', 'superior_id' => null, 'pos_title' => 'CEO'],
            ['position_id' => 'POS-GMIT', 'superior_id' => 'POS-CEO', 'pos_title' => 'GM IT'],
            ['position_id' => 'POS-MGRDEV', 'superior_id' => 'POS-GMIT', 'pos_title' => 'Manager Development'],
            ['position_id' => 'POS-SPVBE', 'superior_id' => 'POS-MGRDEV', 'pos_title' => 'Supervisor Backend'],
            ['position_id' => 'POS-PRGBE', 'superior_id' => 'POS-SPVBE', 'pos_title' => 'Programmer Backend'],
        ];

        foreach ($positions as $pos) {
            PegawaiMasterPosisi::firstOrCreate(
                ['position_id' => $pos['position_id']],
                $pos
            );
        }

        // 3. Seed employees
        $employees = [
            ['position_id' => 'POS-CEO', 'employee_id' => 'EMP0001', 'nopeg' => '20260001', 'nama' => 'Abimana CEO',  'email' => 'abimana.ceo@tpm-facility.com', 'jabatan0' => 'CEO',                 'kode_satker' => 'HO'],
            ['position_id' => 'POS-GMIT', 'employee_id' => 'EMP0002', 'nopeg' => '20260002', 'nama' => 'Bayu GMIT', 'email' => 'bayu.gmit@tpm-facility.com', 'jabatan0' => 'GM IT',               'kode_satker' => 'HO'],
            ['position_id' => 'POS-MGRDEV', 'employee_id' => 'EMP0003', 'nopeg' => '20260003', 'nama' => 'Irsyad MGRDEV', 'email' => 'irsyad.mgrdev@tpm-facility.com', 'jabatan0' => 'Manager Development',  'kode_satker' => 'HO'],
            ['position_id' => 'POS-SPVBE', 'employee_id' => 'EMP0004', 'nopeg' => '20260004', 'nama' => 'Humam SPVBE', 'email' => 'humam.spvbe@tpm-facility.com', 'jabatan0' => 'Supervisor Backend',   'kode_satker' => 'HO'],
            ['position_id' => 'POS-PRGBE', 'employee_id' => 'EMP0005', 'nopeg' => '20260005', 'nama' => 'Wahid PGRBE', 'email' => 'wahid.pgrbe@tpm-facility.com', 'jabatan0' => 'Programmer Backend',   'kode_satker' => 'HO'],
        ];

        foreach ($employees as $emp) {
            PegawaiHirarki::firstOrCreate(
                ['position_id' => $emp['position_id']],
                $emp
            );
        }

        // 4. Build hierarchy cache via service
        $service = app(HierarchyService::class);

        foreach ($positions as $pos) {
            $position = PegawaiMasterPosisi::find($pos['position_id']);
            if ($position !== null) {
                $service->buildPositionHierarchy($position);
            }
        }

        foreach ($employees as $emp) {
            $employee = PegawaiHirarki::where('position_id', $emp['position_id'])->first();
            if ($employee !== null) {
                $service->buildEmployeeHierarchy($employee);
            }
        }
    }
}
