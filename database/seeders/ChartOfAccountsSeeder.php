<?php

namespace Database\Seeders;

use App\Models\Erkap\CostElement;
use App\Support\CoaCode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChartOfAccountsSeeder extends Seeder
{
    private const CHUNK = 500;

    /**
     * Chart of Account adalah hasil komposisi Pusat Biaya (segmen a..d) dengan
     * Elemen Biaya (segmen e) — jadi satu akun dibuat untuk setiap pasangan
     * Pusat Biaya x Elemen Biaya, bukan satu akun per kode elemen legacy.
     */
    public function run()
    {
        $costCenters = DB::table('cost_centers')
            ->join('erkap_business_units', 'erkap_business_units.id', '=', 'cost_centers.erkap_business_unit_id')
            ->join('erkap_locations', 'erkap_locations.id', '=', 'cost_centers.erkap_location_id')
            ->join('erkap_management_areas', 'erkap_management_areas.id', '=', 'cost_centers.erkap_management_area_id')
            ->join('erkap_activities', 'erkap_activities.id', '=', 'cost_centers.erkap_activity_id')
            ->get([
                'cost_centers.id as cost_center_id',
                'cost_centers.code as cost_center_code',
                'erkap_business_units.code as business_unit_code',
                'erkap_locations.code as location_code',
                'erkap_management_areas.code as management_area_code',
                'erkap_activities.code as activity_code',
            ]);

        $costElements = CostElement::orderBy('code')->get(['id', 'code', 'name']);

        if ($costCenters->isEmpty() || $costElements->isEmpty()) {
            return;
        }

        $now = now();
        $rows = [];

        foreach ($costCenters as $costCenter) {
            $segments = [
                'business_unit' => $costCenter->business_unit_code,
                'location' => $costCenter->location_code,
                'management_area' => $costCenter->management_area_code,
                'activity' => $costCenter->activity_code,
            ];

            foreach ($costElements as $costElement) {
                if ($code = CoaCode::compose($segments + ['cost_element' => $costElement->code])) {
                    $rows[] = [
                        'cost_center_id' => $costCenter->cost_center_id,
                        'cost_element_id' => $costElement->id,
                        'code' => $code,
                        'name' => $costElement->name,
                        'type' => str_starts_with($costElement->code, '6') ? 'revenue' : 'expense',
                        'description' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        foreach (array_chunk($rows, self::CHUNK) as $chunk) {
            DB::table('chart_of_accounts')->upsert(
                $chunk,
                ['code'],
                ['cost_center_id', 'cost_element_id', 'name', 'type', 'updated_at']
            );
        }

        $this->linkCostElements();
    }

    /**
     * Tautkan elemen biaya ke Chart of Account default-nya (kode a..e pertama),
     * dipakai sebagai fallback saat transaksi tidak memilih COA secara eksplisit.
     */
    private function linkCostElements(): void
    {
        DB::table('erkap_cost_elements')->update(['chart_of_account_id' => null]);

        DB::statement(<<<'SQL'
            UPDATE erkap_cost_elements ce
               SET chart_of_account_id = sub.id
              FROM (
                    SELECT DISTINCT ON (cost_element_id) cost_element_id, id
                      FROM chart_of_accounts
                     WHERE cost_element_id IS NOT NULL
                     ORDER BY cost_element_id, code
                   ) sub
             WHERE ce.id = sub.cost_element_id
        SQL);
    }
}
