<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\Erkap\BusinessUnit;
use App\Models\Erkap\Location;
use App\Models\Erkap\ManagementArea;
use Illuminate\Database\Seeder;

class ErkapManagementAreaSeeder extends Seeder
{
    /**
     * Grup fungsional Manajemen Area (3 digit pertama). Dua digit terakhir
     * adalah nomor urut area di dalam grup tersebut, sehingga kode `20200`
     * adalah area functionally pertama — sesuai Pedoman RKAP.
     *
     * `exact` dicek lebih dulu agar nama divisi pendek seperti "IT" tidak
     * tertangkap kata kunci "it" di dalam "LOGISTIK".
     */
    private const FUNCTIONAL_GROUPS = [
        [
            'prefix' => '202',
            'name' => 'Senior Manajer Keuangan, Umum dan SDM',
            'exact' => ['direksi', 'ho', 'finance', 'hrd', 'sdm', 'sekretaris', 'office support'],
            'keywords' => ['finance', 'keuangan', 'sekretaris', 'direksi', 'office support', 'legal', 'sdm', 'hrd', 'ho'],
            'business_unit' => 'F',
        ],
        [
            'prefix' => '301',
            'name' => 'Senior Manajer Komersial',
            'exact' => ['business development', 'komersial', 'marketing'],
            'keywords' => ['business development', 'komersial', 'marketing', 'penjualan'],
            'business_unit' => 'F',
        ],
        [
            'prefix' => '401',
            'name' => 'Senior Manajer Operasi dan Produksi',
            'exact' => ['penambangan', 'msi', 'implementasi', 'operasi', 'produksi'],
            'keywords' => ['penambangan', 'msi', 'implementasi', 'produksi', 'operasi'],
            'business_unit' => 'F',
        ],
        [
            'prefix' => '501',
            'name' => 'Senior Manajer Teknik dan Pemeliharaan',
            'exact' => ['teknik', 'maintenance', 'engineering'],
            'keywords' => ['teknik', 'maintenance', 'pemeliharaan', 'engineering'],
            'business_unit' => 'F',
        ],
        [
            'prefix' => '601',
            'name' => 'Senior Manajer HSE dan Keamanan',
            'exact' => ['security', 'hse', 'k3', 'cleaning service', 'lingkungan'],
            'keywords' => ['security', 'hse', 'k3', 'lingkungan', 'cleaning'],
            'business_unit' => 'G',
        ],
        [
            'prefix' => '701',
            'name' => 'Senior Manajer TI dan Sistem Informasi',
            'exact' => ['it', 'kominfo', 'tik', 'informatika'],
            'keywords' => ['teknologi informasi', 'sistem informasi', 'informatika', 'kominfo', 'tik'],
            'business_unit' => 'G',
        ],
        [
            'prefix' => '801',
            'name' => 'Senior Manajer Logistik dan Transportasi',
            'exact' => ['logistik', 'transportasi', 'gudang', 'expeditur'],
            'keywords' => ['logistik', 'transportasi', 'gudang', 'expeditur'],
            'business_unit' => 'F',
        ],
    ];

    private const DEFAULT_GROUP = '202';

    /**
     * Segmen (c) — Manajemen Area, 5 digit, anak dari Lokasi.
     */
    public function run()
    {
        $divisions = Division::orderBy('id')->get();

        if ($divisions->isEmpty()) {
            return;
        }

        $businessUnits = BusinessUnit::pluck('id', 'code');
        $locations = Location::orderBy('code')->get()->groupBy('erkap_business_unit_id');

        $sequences = [];

        foreach ($divisions as $division) {
            $group = $this->resolveGroup($division);

            $sequence = $sequences[$group['prefix']] = ($sequences[$group['prefix']] ?? -1) + 1;

            $businessUnitId = $businessUnits[$group['business_unit']] ?? null;
            $location = $locations->get($businessUnitId)->first() ?? $locations->flatten()->first();

            ManagementArea::updateOrCreate(
                [
                    'code' => $group['prefix'].str_pad((string) $sequence, 2, '0', STR_PAD_LEFT),
                    'erkap_location_id' => $location?->id,
                ],
                [
                    'name' => $division->name.' ('.$group['name'].')',
                    'description' => 'Segmen (c) Pusat Biaya / Chart of Account.',
                    'erkap_business_unit_id' => $businessUnitId,
                    'division_id' => $division->id,
                    'is_active' => true,
                    'sort_order' => $sequence + 1,
                ]
            );
        }
    }

    private function resolveGroup(Division $division): array
    {
        $name = strtolower(trim((string) $division->name));

        foreach (['exact', 'keywords'] as $matcher) {
            foreach (self::FUNCTIONAL_GROUPS as $group) {
                foreach ($group[$matcher] as $keyword) {
                    if ($matcher === 'exact' ? $name === $keyword : str_contains($name, $keyword)) {
                        return $group;
                    }
                }
            }
        }

        return collect(self::FUNCTIONAL_GROUPS)->firstWhere('prefix', self::DEFAULT_GROUP);
    }
}
