<?php

namespace App\Services\Erkap;

use App\Models\Division;
use App\Models\Erkap\CostCenter;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CentralizedCostService
{
    private const COORDINATOR_KEYWORDS = [
        'gaji' => ['hr', 'sdm', 'sumber daya manusia', 'kepegawaian', 'personalia'],
        'it' => ['teknologi informasi', 'informatika', 'tik', 'information technology'],
    ];

    /**
     * Cost center non-terpusat boleh dipakai semua divisi; cost center terpusat
     * hanya boleh dipakai divisi koordinatornya.
     */
    public static function allowedToUse(CostCenter $costCenter, ?int $divisionId): bool
    {
        if (! $costCenter->isCentralized()) {
            return true;
        }

        $coordinatorId = $costCenter->coordinating_division_id;

        return $coordinatorId !== null
            && $divisionId !== null
            && (int) $divisionId === (int) $coordinatorId;
    }

    /**
     * Throw ValidationException bila divisi tidak diizinkan memakai cost center terpusat.
     */
    public static function assertCanInput(CostCenter $costCenter, ?int $divisionId): void
    {
        if (static::allowedToUse($costCenter, $divisionId)) {
            return;
        }

        $coordinator = $costCenter->coordinatingDivision?->name ?? '-';

        throw ValidationException::withMessages([
            'cost_center_id' => "Cost center terpusat \"{$costCenter->name}\" hanya dapat diinput oleh departemen koordinator ({$coordinator}).",
        ]);
    }

    /**
     * Pemetaan default koordinator biaya terpusat berdasarkan nama divisi:
     * gaji -> divisi HR/SDM, it -> divisi TI/Teknologi Informasi.
     *
     * @return array{gaji: ?int, it: ?int}
     */
    public static function mapDefaultCoordinator(): array
    {
        return [
            'gaji' => static::resolveCoordinatorDivision(static::COORDINATOR_KEYWORDS['gaji']),
            'it' => static::resolveCoordinatorDivision(static::COORDINATOR_KEYWORDS['it']),
        ];
    }

    /**
     * Pisahkan baris rincian biaya menjadi grup terpusat vs non-terpusat.
     * Baris terpusat dikelompokkan agar tidak dobel antardivisi.
     */
    public static function groupCentralized(Collection $rows): array
    {
        $groups = collect($rows)->groupBy(fn ($row) => ($row->costCenter ?? null) && $row->costCenter->isCentralized()
            ? 'centralized'
            : 'non_centralized');

        return [
            'centralized' => $groups->get('centralized', collect()),
            'non_centralized' => $groups->get('non_centralized', collect()),
        ];
    }

    private static function resolveCoordinatorDivision(array $keywords): ?int
    {
        $division = Division::query()->get()->first(function (Division $division) use ($keywords) {
            $name = strtolower((string) $division->name);
            $code = strtolower((string) $division->code);
            $abbreviation = strtolower((string) $division->abbreviation);

            foreach ($keywords as $keyword) {
                if (str_contains($name, $keyword)
                    || str_contains($code, $keyword)
                    || str_contains($abbreviation, $keyword)) {
                    return true;
                }
            }

            return false;
        });

        return $division?->id;
    }
}