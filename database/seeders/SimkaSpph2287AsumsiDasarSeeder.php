<?php

namespace Database\Seeders;

use App\Models\Spph2287AsumsiDasar;
use Illuminate\Database\Seeder;

class SimkaSpph2287AsumsiDasarSeeder extends Seeder
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
                'name' => 'Harga BBM',
                'harga_acuan' => 13800,
                'harga_aktual' => 18195,
            ],
            [
                'id' => 2,
                'name' => 'UMSK',
                'harga_acuan' => 3364511,
                'harga_aktual' => 4240899,
            ],
        ];

        Spph2287AsumsiDasar::insert($data);
    }
}
