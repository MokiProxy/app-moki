<?php

namespace Database\Seeders;

use App\Models\Spph773AsumsiDasar;
use Illuminate\Database\Seeder;

class SimkaSpph773AsumsiDasarSeeder extends Seeder
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
                'name' => 'BBM Dasar',
                'harga_acuan' => 14000,
                'harga_aktual' => 18195,
            ],
            [
                'id' => 2,
                'name' => 'UMK Dasar',
                'harga_acuan' => 3394000,
                'harga_aktual' => 4240899,
            ],
            [
                'id' => 3,
                'name' => 'Kurs Dasar',
                'harga_acuan' => 14550,
                'harga_aktual' => 16673,
            ],
        ];

        Spph773AsumsiDasar::insert($data);
    }
}
