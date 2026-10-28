<?php

namespace Database\Seeders;

use App\Models\Spph773KomponenTarif;
use Illuminate\Database\Seeder;

class SimkaSpph773KomponenTarifSeeder extends Seeder
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
                'kategori_pendapatan_id' => 1,
                'usd' => 24.00,
                'rupiah' => 76.00,
            ],
            [
                'id' => 2,
                'kategori_pendapatan_id' => 2,
                'usd' => 24.00,
                'rupiah' => 76.00,
            ],
        ];

        Spph773KomponenTarif::insert($data);
    }
}
