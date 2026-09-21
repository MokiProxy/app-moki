<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\Erkap\CostCenter;
use App\Models\Erkap\CostElement;
use Illuminate\Database\Seeder;

class CostCentersSeeder extends Seeder
{
    public function run()
    {
        $divisions = Division::orderBy('id')->get(['id', 'name']);

        if ($divisions->isEmpty()) {
            return;
        }

        $costElementCodes = CostElement::orderBy('id')->pluck('code')->all();

        if (empty($costElementCodes)) {
            return;
        }

        $owners = ['General Manager', 'Kepala Departemen', 'Supervisor', 'Kepala Bagian', 'Manajer'];

        $swakelolaSegments = ['510', '110'];

        $costElementIndex = 0;

        foreach ($divisions as $division) {
            for ($i = 1; $i <= 2; $i++) {
                $elementCode = $costElementCodes[$costElementIndex % count($costElementCodes)];
                $costElementIndex++;

                $swakelolaSegment = $swakelolaSegments[($division->id + $i) % count($swakelolaSegments)];

                $code = 'F'
                    . '01'
                    . str_pad($division->id, 5, '0', STR_PAD_LEFT)
                    . $swakelolaSegment
                    . $elementCode;

                CostCenter::firstOrCreate(
                    ['code' => $code],
                    [
                        'name' => 'Cost Center ' . $division->name . ' ' . $i,
                        'owner' => $owners[($division->id + $i) % count($owners)],
                        'division_id' => $division->id,
                        'is_swakelola' => $swakelolaSegment === '510',
                    ]
                );
            }
        }
    }
}