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

        $this->seedCentralizedCostCenters($costElementCodes);
    }

    private function seedCentralizedCostCenters(array $costElementCodes): void
    {
        $divisions = Division::orderBy('id')->get(['id', 'name']);

        if ($divisions->isEmpty() || empty($costElementCodes)) {
            return;
        }

        $elementCode = $costElementCodes[0];

        $centralized = [
            [
                'name' => 'Gaji (HR)',
                'division' => $this->firstDivisionByKeywords($divisions, ['hr', 'sdm', 'sumber daya manusia', 'kepegawaian', 'personalia']),
            ],
            [
                'name' => 'TI (IT)',
                'division' => $this->firstDivisionByKeywords($divisions, ['teknologi informasi', 'informatika', 'tik']),
            ],
        ];

        foreach ($centralized as $entry) {
            if (! $entry['division']) {
                continue;
            }

            $code = 'F'
                . '01'
                . str_pad($entry['division']->id, 5, '0', STR_PAD_LEFT)
                . '110'
                . $elementCode;

            CostCenter::firstOrCreate(
                ['code' => $code],
                [
                    'name' => $entry['name'],
                    'owner' => 'Departemen Koordinator',
                    'division_id' => $entry['division']->id,
                    'is_swakelola' => false,
                    'is_centralized' => true,
                    'coordinating_division_id' => $entry['division']->id,
                ]
            );
        }
    }

    private function firstDivisionByKeywords($divisions, array $keywords)
    {
        return $divisions->first(function ($division) use ($keywords) {
            $name = strtolower((string) $division->name);

            foreach ($keywords as $keyword) {
                if (str_contains($name, $keyword)) {
                    return true;
                }
            }

            return false;
        });
    }
}