<?php

namespace Database\Seeders;

use App\Models\Erkap\BusinessUnit;
use App\Models\Erkap\Location;
use Illuminate\Database\Seeder;

class ErkapLocationSeeder extends Seeder
{
    /**
     * Segmen (b) — Lokasi, 2 digit, anak dari Bisnis Unit.
     */
    public function run()
    {
        $locations = [
            ['code' => '01', 'name' => 'Umum'],
            ['code' => '02', 'name' => 'Banko'],
            ['code' => '03', 'name' => 'TAL'],
            ['code' => '04', 'name' => 'PELTAR'],
            ['code' => '05', 'name' => 'Peranap'],
            ['code' => '06', 'name' => 'BTU'],
        ];

        foreach (BusinessUnit::orderBy('code')->get() as $businessUnit) {
            foreach ($locations as $order => $location) {
                Location::updateOrCreate(
                    [
                        'code' => $location['code'],
                        'erkap_business_unit_id' => $businessUnit->id,
                    ],
                    [
                        'name' => $location['name'],
                        'description' => 'Segmen (b) Pusat Biaya / Chart of Account.',
                        'is_active' => true,
                        'sort_order' => $order + 1,
                    ]
                );
            }
        }
    }
}
