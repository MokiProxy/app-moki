<?php

namespace Database\Seeders;

use App\Models\FamilyData;
use Illuminate\Database\Seeder;

class FamilyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $datas = [
            [
                "employee_id" => "EMP0001",
                "status_keluarga" => "Anak Ke-3",
                "nama_keluarga" => "ARJUNA NUGRAHA SANJAYA",
                "tanggal_lahir" => "2022-08-01",
                "jenis_kelamin" => "M",
                "status_list" => "Baru",
                "lampiran" => null,
                "status_approval" => "ON_PROGRESS",
                "catatan" => null,
                "flag" => 1,
                "lokasi_kerja" => "HO",
                "tempat_lahir" => "Muara Enim",
                "alamat" => "JL. H. PANGERAN DANAL LK III NO.13 KEL. MUARA ENIM KEC. MUARA ENIM KAB.MUARA ENIM",
            ],
            [
                "employee_id" => "EMP0001",
                "status_keluarga" => "Anak Ke-4",
                "nama_keluarga" => "JOHN DOE",
                "tanggal_lahir" => "2025-08-01",
                "jenis_kelamin" => "M",
                "status_list" => "Baru",
                "lampiran" => null,
                "status_approval" => "ON_PROGRESS",
                "catatan" => null,
                "flag" => 1,
                "lokasi_kerja" => "HO",
                "tempat_lahir" => "Muara Enim",
                "alamat" => "JL. H. PANGERAN DANAL LK III NO.13 KEL. MUARA ENIM KEC. MUARA ENIM KAB.MUARA ENIM",
            ],
            [
                "employee_id" => "EMP0001",
                "status_keluarga" => "Anak Ke-5",
                "nama_keluarga" => "JOHN DOE TEST",
                "tanggal_lahir" => "2026-08-01",
                "jenis_kelamin" => "M",
                "status_list" => "Baru",
                "lampiran" => null,
                "status_approval" => "ON_PROGRESS",
                "catatan" => null,
                "flag" => 1,
                "lokasi_kerja" => "HO",
                "tempat_lahir" => "Muara Enim",
                "alamat" => "JL. H. PANGERAN DANAL LK III NO.13 KEL. MUARA ENIM KEC. MUARA ENIM KAB.MUARA ENIM",
            ],
        ];

        foreach ($datas as $data) {
            FamilyData::create($data);
        }
    }
}
