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
            ['kode_satker' => 'SAT-001'],
            ['nama_satker' => 'Kantor Pusat']
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
            ['position_id' => 'POS-CEO', 'nik' => 'NIK-001', 'nopeg' => 'NP-001', 'nama' => 'Dedi',  'email' => 'dedi@company.com', 'jabatan0' => 'CEO',                 'kode_satker' => 'SAT-001'],
            ['position_id' => 'POS-GMIT', 'nik' => 'NIK-002', 'nopeg' => 'NP-002', 'nama' => 'Agus', 'email' => 'agus@company.com', 'jabatan0' => 'GM IT',               'kode_satker' => 'SAT-001'],
            ['position_id' => 'POS-MGRDEV', 'nik' => 'NIK-003', 'nopeg' => 'NP-003', 'nama' => 'Toni', 'email' => 'toni@company.com', 'jabatan0' => 'Manager Development',  'kode_satker' => 'SAT-001'],
            ['position_id' => 'POS-SPVBE', 'nik' => 'NIK-004', 'nopeg' => 'NP-004', 'nama' => 'Andi', 'email' => 'andi@company.com', 'jabatan0' => 'Supervisor Backend',   'kode_satker' => 'SAT-001'],
            ['position_id' => 'POS-PRGBE', 'nik' => 'NIK-005', 'nopeg' => 'NP-005', 'nama' => 'Budi', 'email' => 'budi@company.com', 'jabatan0' => 'Programmer Backend',   'kode_satker' => 'SAT-001'],
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
