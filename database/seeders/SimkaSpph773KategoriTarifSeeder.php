<?php

namespace Database\Seeders;

use App\Models\KategoriTarif;
use Illuminate\Database\Seeder;

class SimkaSpph773KategoriTarifSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'id' => 1,
                'name' => 'Tarif Gali',
                'kategori_pendapatan_id' => 1,
            ],
            [
                'id' => 2,
                'name' => 'Tarif Angkut',
                'kategori_pendapatan_id' => 1,
            ],
            [
                'id' => 3,
                'name' => 'Tarif Sebar',
                'kategori_pendapatan_id' => 1,
            ],
            [
                'id' => 4,
                'name' => 'Tarif Gali',
                'kategori_pendapatan_id' => 2,
            ],
            [
                'id' => 5,
                'name' => 'Tarif Angkut',
                'kategori_pendapatan_id' => 2,
            ],
        ];

        KategoriTarif::insert($data);
    }
}
