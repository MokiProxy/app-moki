<?php

namespace Database\Seeders;

use App\Models\Erkap\BusinessUnit;
use Illuminate\Database\Seeder;

class ErkapBusinessUnitSeeder extends Seeder
{
    /**
     * Segmen (a) — Bisnis Unit, 1 karakter.
     */
    public function run()
    {
        $units = [
            ['code' => 'F', 'name' => 'Penambangan'],
            ['code' => 'G', 'name' => 'Rental'],
        ];

        foreach ($units as $index => $unit) {
            BusinessUnit::updateOrCreate(
                ['code' => $unit['code']],
                [
                    'name' => $unit['name'],
                    'description' => 'Segmen (a) Chart of Account / Pusat Biaya.',
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }
}
