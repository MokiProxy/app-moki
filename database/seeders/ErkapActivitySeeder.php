<?php

namespace Database\Seeders;

use App\Models\Erkap\Activity;
use App\Models\Erkap\BusinessUnit;
use App\Models\Erkap\ManagementArea;
use Illuminate\Database\Seeder;

class ErkapActivitySeeder extends Seeder
{
    /**
     * Segmen (d) — Aktivitas, 3 digit, anak dari Manajemen Area.
     * Digit pertama mengikuti Bisnis Unit: 1 = Penambangan, 2 = Rental.
     */
    private const ACTIVITIES_BY_BUSINESS_UNIT = [
        'F' => [
            ['code' => '110', 'name' => 'Penambangan Swakelola', 'is_swakelola' => true],
            ['code' => '120', 'name' => 'Penambangan Non-Swakelola', 'is_swakelola' => false],
            ['code' => '130', 'name' => 'Penambangan Terpusat', 'is_swakelola' => false],
        ],
        'G' => [
            ['code' => '210', 'name' => 'Rental Swakelola', 'is_swakelola' => true],
            ['code' => '220', 'name' => 'Rental Non-Swakelola', 'is_swakelola' => false],
            ['code' => '230', 'name' => 'Rental Terpusat', 'is_swakelola' => false],
        ],
    ];

    public function run()
    {
        $businessUnits = BusinessUnit::pluck('code', 'id');

        ManagementArea::orderBy('id')->get()->each(function (ManagementArea $area) use ($businessUnits) {
            $activities = self::ACTIVITIES_BY_BUSINESS_UNIT[$businessUnits[$area->erkap_business_unit_id] ?? 'F']
                ?? self::ACTIVITIES_BY_BUSINESS_UNIT['F'];

            foreach ($activities as $order => $activity) {
                Activity::updateOrCreate(
                    [
                        'code' => $activity['code'],
                        'erkap_management_area_id' => $area->id,
                    ],
                    [
                        'name' => $area->name.' - '.$activity['name'],
                        'description' => 'Segmen (d) Pusat Biaya / Chart of Account.',
                        'is_swakelola' => $activity['is_swakelola'],
                        'is_active' => true,
                        'sort_order' => $order + 1,
                    ]
                );
            }
        });
    }
}
