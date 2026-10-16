<?php

namespace Database\Seeders;

use App\Models\KategoriPendapatan;
use Illuminate\Database\Seeder;

class SimkaKategoriPendapatanSeeder extends Seeder
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
                'name' => 'Pekerjaan Tanah',
            ],
            [
                'id' => 2,
                'name' => 'Pekerjaan Batubara',
            ],
        ];

        KategoriPendapatan::insert($data);
    }
}
