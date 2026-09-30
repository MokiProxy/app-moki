<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreManagementAreaRequest;
use App\Http\Requests\UpdateManagementAreaRequest;
use App\Models\Division;
use App\Models\Erkap\BusinessUnit;
use App\Models\Erkap\Location;
use App\Models\Erkap\ManagementArea;
use App\Services\ErkapAccess;
use App\Support\ErrorMessage;
use Exception;

class ManagementAreaController extends Controller
{
    public function index()
    {
        $pageName = 'Manajemen Area';
        $managementAreas = ManagementArea::with(['location', 'division'])
            ->withCount('activities')
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->where('division_id', ErkapAccess::divisionId());
            })
            ->orderBy('code')
            ->paginate(10);

        return view('erkap.management-area.index', compact('pageName', 'managementAreas'));
    }

    public function create()
    {
        $pageName = 'Buat Manajemen Area';
        $businessUnits = $this->businessUnits();
        $divisions = $this->divisions();

        return view('erkap.management-area.create', compact('pageName', 'businessUnits', 'divisions'));
    }

    public function store(StoreManagementAreaRequest $request)
    {
        try {
            $data = $request->safe()->except('is_active');
            $data['is_active'] = $request->boolean('is_active');
            $data['created_by'] = auth()->id();

            ManagementArea::create($data);

            return redirect()->route('erkap.management-areas.index')
                ->with('success', 'Manajemen Area baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.management-areas.create')
                ->withInput()
                ->with('error', ErrorMessage::from($err));
        }
    }

    public function edit(ManagementArea $managementArea)
    {
        $pageName = 'Edit Manajemen Area';
        $businessUnits = $this->businessUnits();
        $divisions = $this->divisions();

        // Opsi Lokasi dirender server-side agar nilai lama tidak hilang
        // sebelum cascade JS sempat berjalan.
        $locations = Location::query()
            ->where('erkap_business_unit_id', $managementArea->erkap_business_unit_id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('erkap.management-area.edit', compact(
            'pageName', 'managementArea', 'businessUnits', 'divisions', 'locations'
        ));
    }

    public function update(UpdateManagementAreaRequest $request, ManagementArea $managementArea)
    {
        try {
            $data = $request->safe()->except('is_active');
            $data['is_active'] = $request->boolean('is_active');
            $data['updated_by'] = auth()->id();

            $managementArea->update($data);

            return redirect()->route('erkap.management-areas.index')
                ->with('success', 'Manajemen Area berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.management-areas.edit', $managementArea)
                ->withInput()
                ->with('error', ErrorMessage::from($err));
        }
    }

    public function destroy(ManagementArea $managementArea)
    {
        try {
            if ($managementArea->activities()->exists() || $managementArea->costCenters()->exists()) {
                return redirect()->route('erkap.management-areas.index')
                    ->with('error', 'Manajemen Area masih digunakan oleh Aktivitas atau Pusat Biaya sehingga tidak dapat dihapus.');
            }

            $managementArea->delete();

            return redirect()->route('erkap.management-areas.index')
                ->with('success', 'Manajemen Area berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.management-areas.index')
                ->with('error', ErrorMessage::from($err));
        }
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

    /**
     * Segmen a handful, cukup dirender server-side; cascade JS mulai
     * dibutuhkan pada level Lokasi ke bawah.
     */
    private function businessUnits()
    {
        return BusinessUnit::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get(['id', 'code', 'name']);
    }
}
