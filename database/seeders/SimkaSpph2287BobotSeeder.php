<?php

namespace Database\Seeders;

use App\Models\Spph2287Bobot;
use Illuminate\Database\Seeder;

class SimkaSpph2287BobotSeeder extends Seeder
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
                'name' => 'BBM',
                'value' => 33.00,
            ],
            [
                'id' => 2,
                'name' => 'UMK',
                'value' => 9.00,
            ],
        ];

        Spph2287Bobot::insert($data);
    }
}
