<?php

namespace Database\Seeders;

use App\Models\Erkap\InvestationType;
use Illuminate\Database\Seeder;

class ErkapInvestationTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $types = [
            ['code' => '1', 'name' => 'Tanah'],
            ['code' => '2', 'name' => 'Bangunan'],
            ['code' => '3', 'name' => 'Mesin dan Peralatan'],
            ['code' => '4', 'name' => 'Kendaraan'],
            ['code' => '5', 'name' => 'Inventaris Kantor'],
            ['code' => '6', 'name' => 'Aktiva dalam Penyelesaian'],
        ];

        foreach ($types as $type) {
            InvestationType::create($type);
        }
    }
}