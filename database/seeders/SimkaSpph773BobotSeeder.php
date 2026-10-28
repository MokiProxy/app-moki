<?php

namespace Database\Seeders;

use App\Models\Spph773Bobot;
use Illuminate\Database\Seeder;

class SimkaSpph773BobotSeeder extends Seeder
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
                'name' => 'Gali',
                'kategori_pendapatan_id' => 1,
                'bobot_bbm' => 37.00,
                'bobot_umsp' => 9.00,
            ],
            [
                'id' => 2,
                'name' => 'Angkutan',
                'kategori_pendapatan_id' => 1,
                'bobot_bbm' => 37.00,
                'bobot_umsp' => 9.00,
            ],
            [
                'id' => 3,
                'name' => 'Sebar',
                'kategori_pendapatan_id' => 1,
                'bobot_bbm' => 37.00,
                'bobot_umsp' => 9.00,
            ],
            [
                'id' => 4,
                'name' => 'Gali',
                'kategori_pendapatan_id' => 2,
                'bobot_bbm' => 37.00,
                'bobot_umsp' => 9.00,
            ],
            [
                'id' => 5,
                'name' => 'Angkutan',
                'kategori_pendapatan_id' => 2,
                'bobot_bbm' => 37.00,
                'bobot_umsp' => 9.00,
            ],
        ];

        Spph773Bobot::insert($data);
    }
}
