<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\Erkap\Activity;
use App\Models\Erkap\CostCenter;
use App\Models\Erkap\ManagementArea;
use Illuminate\Database\Seeder;

class CostCentersSeeder extends Seeder
{
    private const OWNERS = [
        'General Manager',
        'Kepala Departemen',
        'Supervisor',
        'Kepala Bagian',
        'Manajer',
    ];

    /**
     * Pusat Biaya dibangun dari kombinasi segmen a..d — bukan dari kode hardcode.
     * Setiap Manajemen Area mendapat satu Pusat Biaya per aktivitas (Swakelola &
     * Non-Swakelola); aktivitas "Terpusat" hanya dipakai untuk biaya yang
     * dikoordinir satu departemen (gaji -> HR/SDM, TI -> IT).
     */
    public function run()
    {
        $ownerIndex = 0;
        $costCenters = 0;

        $areas = ManagementArea::with('activities.location', 'activities.businessUnit')
            ->whereNotNull('division_id')
            ->orderBy('id')
            ->get();

        foreach ($areas as $area) {
            $activities = $area->activities->sortBy('code');

            foreach ($activities as $activity) {
                if ($activity->isCentralizedActivity()) {
                    continue;
                }

                $ownerIndex++;
                $costCenters += $this->store($area, $activity, $this->owner($ownerIndex), [
                    'name' => $area->name.($activity->is_swakelola ? ' (Swakelola)' : ' (Non-Swakelola)'),
                ]);
            }
        }

        $this->seedCentralizedCostCenters($areas, $ownerIndex);
    }

    /**
     * Biaya terpusat: gaji (HR/SDM) & TI hanya boleh diinput koordinatornya.
     */
    private function seedCentralizedCostCenters($areas, int $ownerIndex): void
    {
        $divisions = Division::orderBy('id')->get(['id', 'name']);

        if ($divisions->isEmpty()) {
            return;
        }

        $centralized = [
            ['name' => 'Gaji (HR)', 'keywords' => ['hr', 'sdm', 'sumber daya manusia', 'kepegawaian', 'personalia']],
            ['name' => 'TI (IT)', 'keywords' => ['teknologi informasi', 'informatika', 'tik', 'kominfo']],
        ];

        foreach ($centralized as $index => $entry) {
            $division = $divisions->first(fn (Division $division) => $this->matches($division->name, $entry['keywords']));

            $area = $division ? $areas->firstWhere('division_id', $division->id) : null;
            $activity = $area?->activities->first(fn (Activity $activity) => $activity->isCentralizedActivity());

            if (! $area || ! $activity) {
                continue;
            }

            $this->store($area, $activity, 'Departemen Koordinator', [
                'name' => $entry['name'],
                'is_centralized' => true,
                'coordinating_division_id' => $division->id,
            ]);
        }
    }

    /**
     * Kode Pusat Biaya dihitung otomatis oleh model dari segmen a..d.
     */
    private function store(ManagementArea $area, Activity $activity, string $owner, array $extra = []): int
    {
        $costCenter = CostCenter::updateOrCreate(
            [
                'erkap_business_unit_id' => $area->erkap_business_unit_id,
                'erkap_location_id' => $area->erkap_location_id,
                'erkap_management_area_id' => $area->id,
                'erkap_activity_id' => $activity->id,
            ],
            $extra + [
                'owner' => $owner,
                'division_id' => $area->division_id,
                'is_swakelola' => (bool) $activity->is_swakelola,
                'is_centralized' => false,
                'coordinating_division_id' => null,
            ]
        );

        return $costCenter->wasRecentlyCreated ? 1 : 0;
    }

    private function owner(int $index): string
    {
        return self::OWNERS[$index % count(self::OWNERS)];
    }

    private function matches(string $name, array $keywords): bool
    {
        $name = strtolower($name);

        foreach ($keywords as $keyword) {
            if (str_contains($name, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
