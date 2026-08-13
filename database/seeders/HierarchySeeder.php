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
            ['position_id' => 'DIR-UTAMA', 'superior_id' => null, 'pos_title' => 'DIREKTUR UTAMA'],
            ['position_id' => 'DIR-PEMBINA', 'superior_id' => 'DIR-UTAMA', 'pos_title' => 'DIREKTUR PEMBINA'],
            ['position_id' => 'SR-MANAGER-MSI', 'superior_id' => 'DIR-PEMBINA', 'pos_title' => 'SENIOR MANAGER MSI'],
            ['position_id' => 'MANAGER-MSI', 'superior_id' => 'SR-MANAGER-MSI', 'pos_title' => 'MANAGER MSI'],
            ['position_id' => 'ASMEN-INFRA-KEAMANANAN-SISTEM', 'superior_id' => 'MANAGER-MSI', 'pos_title' => 'ASISTEN MANAGER INFRA DAN KEAMANAN SISTEM'],
            ['position_id' => 'IT-TEKNOLOGI', 'superior_id' => 'ASMEN-INFRA-KEAMANANAN-SISTEM', 'pos_title' => 'IT TEKNOLOGI'],
        ];

        foreach ($positions as $pos) {
            PegawaiMasterPosisi::firstOrCreate(
                ['position_id' => $pos['position_id']],
                $pos
            );
        }

        // 3. Seed employees
        $employees = [
            ['position_id' => 'DIR-UTAMA', 'employee_id' => '2025081', 'nik' => "0595987493811", 'nopeg' => '2025081', 'nama' => 'Agung',  'email' => 'agung@satriabahana.co.id', 'jabatan0' => 'DIREKTUR UTAMA',                 'kode_satker' => 'HO'],
            ['position_id' => 'DIR-PEMBINA', 'employee_id' => '2025082', 'nik' => "0595987493821", 'nopeg' => '2025082', 'nama' => 'Sahlul', 'email' => 'sahlul@satriabahana.co.id', 'jabatan0' => 'DIREKTUR PEMBINA',               'kode_satker' => 'HO'],
            ['position_id' => 'SR-MANAGER-MSI', 'employee_id' => '2025083', 'nik' => "0595987493831", 'nopeg' => '2025083', 'nama' => 'Fajriwan', 'email' => 'fajriwan@satriabahana.co.id', 'jabatan0' => 'SENIOR MANAGER MSI',  'kode_satker' => 'HO'],
            ['position_id' => 'MANAGER-MSI', 'employee_id' => '2025084', 'nik' => "0595987493841", 'nopeg' => '2025084', 'nama' => 'Karmono', 'email' => 'karmono@satriabahana.co.id', 'jabatan0' => 'MANAGER MSI',   'kode_satker' => 'HO'],
            ['position_id' => 'ASMEN-INFRA-KEAMANANAN-SISTEM', 'employee_id' => '2025085', 'nik' => "0595987493851", 'nopeg' => '2025085', 'nama' => 'Harmoko', 'email' => 'harmoko@satriabahana.co.id', 'jabatan0' => 'ASISTEN MANAGER INFRA DAN KEAMANAN SISTEM',   'kode_satker' => 'HO'],
            ['position_id' => 'IT-TEKNOLOGI', 'employee_id' => '2025086', 'nik' => "0595987493861", 'nopeg' => '2025086', 'nama' => 'Vita', 'email' => 'vita@satriabahana.co.id', 'jabatan0' => 'IT TEKNOLOGI',   'kode_satker' => 'HO'],
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
