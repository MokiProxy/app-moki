<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCostCenterRequest;
use App\Http\Requests\UpdateCostCenterRequest;
use App\Models\Division;
use App\Models\Erkap\BusinessUnit;
use App\Models\Erkap\CostCenter;
use App\Models\Erkap\Location;
use App\Models\Erkap\ManagementArea;
use App\Services\ErkapAccess;
use App\Support\ErrorMessage;
use Exception;

class CostCenterController extends Controller
{
    public function index()
    {
        $pageName = 'Pusat Biaya (Cost Center)';
        $costCenters = CostCenter::with(['division', 'coordinatingDivision', 'businessUnit', 'location', 'managementArea', 'activity'])
            ->withCount('chartOfAccounts')
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->where('division_id', ErkapAccess::divisionId());
            })
            ->orderBy('code')
            ->paginate(10);

        return view('erkap.cost-center.index', compact('pageName', 'costCenters'));
    }

    public function create()
    {
        $pageName = 'Buat Pusat Biaya';

        return view('erkap.cost-center.create', compact('pageName') + [
            'businessUnits' => $this->businessUnits(),
            'divisions' => $this->divisions(),
        ]);
    }

    public function store(StoreCostCenterRequest $request)
    {
        try {
            $data = $request->safe()->except(['is_centralized']);
            $data['is_centralized'] = $request->boolean('is_centralized');

            if (! $data['is_centralized']) {
                $data['coordinating_division_id'] = null;
            }

            $data['created_by'] = auth()->id();

            CostCenter::create($data);

            return redirect()->route('erkap.cost-centers.index')
                ->with('success', 'Pusat biaya baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.cost-centers.create')
                ->withInput()
                ->with('error', ErrorMessage::from($err));
        }
    }

    public function edit(CostCenter $costCenter)
    {
        $pageName = 'Edit Pusat Biaya';

        // Rantai a..c dirender server-side agar nilai lama tetap tampil
        // sebelum cascade JS sempat mengambil opsi anak dari API.
        $locations = Location::query()
            ->where('erkap_business_unit_id', $costCenter->erkap_business_unit_id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $managementAreas = ManagementArea::query()
            ->where('erkap_location_id', $costCenter->erkap_location_id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('erkap.cost-center.edit', compact('pageName', 'costCenter') + [
            'businessUnits' => $this->businessUnits(),
            'locations' => $locations,
            'managementAreas' => $managementAreas,
            'divisions' => $this->divisions(),
        ]);
    }

    public function update(UpdateCostCenterRequest $request, CostCenter $costCenter)
    {
        try {
            $data = $request->safe()->except(['is_centralized']);
            $data['is_centralized'] = $request->boolean('is_centralized');

            if (! $data['is_centralized']) {
                $data['coordinating_division_id'] = null;
            }

            $data['updated_by'] = auth()->id();

            $costCenter->update($data);

            return redirect()->route('erkap.cost-centers.index')
                ->with('success', 'Pusat biaya berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.cost-centers.edit', $costCenter->id)
                ->withInput()
                ->with('error', ErrorMessage::from($err));
        }
    }

    public function destroy(CostCenter $costCenter)
    {
        try {
            // `chart_of_accounts.cost_center_id` memakai ON DELETE RESTRICT,
            // jadi hapus harus dicegah lebih dulu agar pesan error ramah.
            if ($costCenter->chartOfAccounts()->exists()) {
                return redirect()->route('erkap.cost-centers.index')
                    ->with('error', 'Pusat Biaya masih memiliki Chart of Account sehingga tidak dapat dihapus.');
            }

            if ($costCenter->routineCosts()->exists()) {
                return redirect()->route('erkap.cost-centers.index')
                    ->with('error', 'Pusat Biaya masih digunakan pada Biaya Rutin sehingga tidak dapat dihapus.');
            }

            $costCenter->delete();

            return redirect()->route('erkap.cost-centers.index')
                ->with('success', 'Pusat biaya berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.cost-centers.index')->with('error', ErrorMessage::from($err));
        }
    }

    /**
     * Segmen a handful, cukup dirender server-side; cascade JS mengambil
     * Lokasi, Manajemen Area, dan Aktivitas dari API.
     */
    private function businessUnits()
    {
        return BusinessUnit::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get(['id', 'code', 'name']);
    }

    private function divisions()
    {
        return Division::query()
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->where('id', ErkapAccess::divisionId());
            })
            ->orderBy('name')
            ->get();
    }
}
