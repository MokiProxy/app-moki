<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Models\Erkap\Activity;
use App\Models\Erkap\BusinessUnit;
use App\Models\Erkap\Location;
use App\Models\Erkap\ManagementArea;
use App\Services\ErkapAccess;
use App\Support\ErrorMessage;
use Exception;

class ActivityController extends Controller
{
    public function index()
    {
        $pageName = 'Aktivitas';
        $activities = Activity::with(['managementArea', 'businessUnit', 'location'])
            ->withCount('costCenters')
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->whereHas('managementArea', fn ($area) => $area->where('division_id', ErkapAccess::divisionId()));
            })
            ->orderBy('erkap_business_unit_id')
            ->orderBy('erkap_location_id')
            ->orderBy('erkap_management_area_id')
            ->orderBy('sort_order')
            ->orderBy('code')
            ->paginate(10);

        return view('erkap.activity.index', compact('pageName', 'activities'));
    }

    public function create()
    {
        $pageName = 'Buat Aktivitas';
        $businessUnits = $this->businessUnits();

        return view('erkap.activity.create', compact('pageName', 'businessUnits'));
    }

    public function store(StoreActivityRequest $request)
    {
        try {
            $data = $request->safe()->except(['is_active', 'is_swakelola']);
            $data['is_active'] = $request->boolean('is_active');
            $data['is_swakelola'] = $request->boolean('is_swakelola');
            $data['created_by'] = auth()->id();

            Activity::create($data);

            return redirect()->route('erkap.activities.index')
                ->with('success', 'Aktivitas baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.activities.create')
                ->withInput()
                ->with('error', ErrorMessage::from($err));
        }
    }

    public function edit(Activity $activity)
    {
        $pageName = 'Edit Aktivitas';
        $businessUnits = $this->businessUnits();

        // Rantai a..c dirender server-side agar nilai lama tetap tampil
        // sebelum cascade JS sempat mengambil opsi dari API.
        $locations = Location::query()
            ->where('erkap_business_unit_id', $activity->erkap_business_unit_id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $managementAreas = ManagementArea::query()
            ->where('erkap_location_id', $activity->erkap_location_id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('erkap.activity.edit', compact(
            'pageName', 'activity', 'businessUnits', 'locations', 'managementAreas'
        ));
    }

    public function update(UpdateActivityRequest $request, Activity $activity)
    {
        try {
            $data = $request->safe()->except(['is_active', 'is_swakelola']);
            $data['is_active'] = $request->boolean('is_active');
            $data['is_swakelola'] = $request->boolean('is_swakelola');
            $data['updated_by'] = auth()->id();

            $activity->update($data);

            return redirect()->route('erkap.activities.index')
                ->with('success', 'Aktivitas berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.activities.edit', $activity)
                ->withInput()
                ->with('error', ErrorMessage::from($err));
        }
    }

    public function destroy(Activity $activity)
    {
        try {
            if ($activity->costCenters()->exists()) {
                return redirect()->route('erkap.activities.index')
                    ->with('error', 'Aktivitas masih digunakan oleh Pusat Biaya sehingga tidak dapat dihapus.');
            }

            $activity->delete();

            return redirect()->route('erkap.activities.index')
                ->with('success', 'Aktivitas berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.activities.index')
                ->with('error', ErrorMessage::from($err));
        }
    }

    /**
     * Segmen a handful, cukup dirender server-side; cascade JS menangani
     * level Lokasi dan Manajemen Area ke bawah.
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
