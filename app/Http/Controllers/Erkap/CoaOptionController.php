<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Services\Erkap\CoaOptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API JSON untuk dropdown cascading struktur COA/Pusat Biaya.
 *
 * Semua endpoint dibungkus prefix `erkap.coa-options.` dan memakai
 * permission `erkap.menu` agar dapat dibaca semua pengguna modul RKAP —
 * form transaksi tidak perlu Izin CRUD master.
 */
class CoaOptionController extends Controller
{
    public function __construct(private CoaOptionService $options)
    {
    }

    /**
     * Base URL untuk `ErkapCascade`, sekaligus bundle seluruh segmen tingkat
     * pertama supaya form edit bisa prefill dalam satu request.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'endpoints' => [
                'division' => route('erkap.coa-options.divisions'),
                'business_unit' => route('erkap.coa-options.business-units'),
                'location' => route('erkap.coa-options.locations'),
                'management_area' => route('erkap.coa-options.management-areas'),
                'activity' => route('erkap.coa-options.activities'),
                'cost_center' => route('erkap.coa-options.cost-centers'),
                'cost_element' => route('erkap.coa-options.cost-elements'),
                'account' => route('erkap.coa-options.accounts'),
                'lookup' => route('erkap.coa-options.lookup'),
            ],
            'business_units' => $this->options->businessUnits(),
        ]);
    }

    /**
     * Divisi menjadi parent COA pada form Rencana Pendapatan & Beban, yang
     * tabelnya tidak menyimpan `cost_center_id`.
     */
    public function divisions(): JsonResponse
    {
        return $this->respond($this->options->divisions());
    }

    public function businessUnits(): JsonResponse
    {
        return $this->respond($this->options->businessUnits());
    }

    public function locations(Request $request): JsonResponse
    {
        return $this->respond($this->options->locations($this->id($request, 'business_unit_id')));
    }

    public function managementAreas(Request $request): JsonResponse
    {
        return $this->respond($this->options->managementAreas(
            $this->id($request, 'location_id'),
            $this->id($request, 'business_unit_id'),
            $this->id($request, 'division_id'),
        ));
    }

    public function activities(Request $request): JsonResponse
    {
        return $this->respond($this->options->activities(
            $this->id($request, 'management_area_id'),
            $this->id($request, 'location_id'),
            $this->id($request, 'business_unit_id'),
        ));
    }

    public function costElements(Request $request): JsonResponse
    {
        return $this->respond($this->options->costElements(
            $this->id($request, 'cost_center_id'),
            $this->string($request, 'type'),
        ));
    }

    public function costCenters(Request $request): JsonResponse
    {
        return $this->respond($this->options->costCenters([
            'business_unit_id' => $this->id($request, 'business_unit_id'),
            'location_id' => $this->id($request, 'location_id'),
            'management_area_id' => $this->id($request, 'management_area_id'),
            'activity_id' => $this->id($request, 'activity_id'),
            'division_id' => $this->id($request, 'division_id'),
        ]));
    }

    public function accounts(Request $request): JsonResponse
    {
        return $this->respond($this->options->accounts(
            $this->id($request, 'cost_center_id'),
            $this->id($request, 'cost_element_id'),
            $this->string($request, 'type'),
            $this->id($request, 'division_id'),
        ));
    }

    /**
     * Reverse lookup: satu kode Pusat Biaya/COA → seluruh rantai segmen.
     */
    public function lookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $chain = $this->options->lookup($validated['code']);

        // `found` bergantung pada Pusat Biaya benar-benar ada — kode yang
        // formatnya sah tetap bisa tidak merujuk entitas mana pun.
        return response()->json([
            'data' => $chain,
            'found' => ($chain['cost_center'] ?? null) !== null,
        ]);
    }

    private function respond($options): JsonResponse
    {
        return response()->json(['data' => $options->values()]);
    }

    private function id(Request $request, string $key): ?int
    {
        $value = $request->query($key);

        return filled($value) ? (int) $value : null;
    }

    private function string(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        return filled($value) ? (string) $value : null;
    }
}
