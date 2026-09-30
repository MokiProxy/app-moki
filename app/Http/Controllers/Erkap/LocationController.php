<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLocationRequest;
use App\Http\Requests\UpdateLocationRequest;
use App\Models\Erkap\BusinessUnit;
use App\Models\Erkap\Location;
use App\Support\ErrorMessage;
use Exception;

class LocationController extends Controller
{
    public function index()
    {
        $pageName = 'Lokasi';
        $locations = Location::with('businessUnit')
            ->withCount('managementAreas')
            ->orderBy('erkap_business_unit_id')
            ->orderBy('sort_order')
            ->orderBy('code')
            ->paginate(10);

        return view('erkap.location.index', compact('pageName', 'locations'));
    }

    public function create()
    {
        $pageName = 'Buat Lokasi';
        $businessUnits = $this->businessUnits();

        return view('erkap.location.create', compact('pageName', 'businessUnits'));
    }

    public function store(StoreLocationRequest $request)
    {
        try {
            $data = $request->safe()->except('is_active');
            $data['is_active'] = $request->boolean('is_active');
            $data['created_by'] = auth()->id();

            Location::create($data);

            return redirect()->route('erkap.locations.index')
                ->with('success', 'Lokasi baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.locations.create')
                ->withInput()
                ->with('error', ErrorMessage::from($err));
        }
    }

    public function edit(Location $location)
    {
        $pageName = 'Edit Lokasi';
        $businessUnits = $this->businessUnits();

        return view('erkap.location.edit', compact('pageName', 'location', 'businessUnits'));
    }

    public function update(UpdateLocationRequest $request, Location $location)
    {
        try {
            $data = $request->safe()->except('is_active');
            $data['is_active'] = $request->boolean('is_active');
            $data['updated_by'] = auth()->id();

            $location->update($data);

            return redirect()->route('erkap.locations.index')
                ->with('success', 'Lokasi berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.locations.edit', $location)
                ->withInput()
                ->with('error', ErrorMessage::from($err));
        }
    }

    public function destroy(Location $location)
    {
        try {
            if ($location->managementAreas()->exists()
                || $location->activities()->exists()
                || $location->costCenters()->exists()) {
                return redirect()->route('erkap.locations.index')
                    ->with('error', 'Lokasi masih digunakan oleh Manajemen Area, Aktivitas, atau Pusat Biaya sehingga tidak dapat dihapus.');
            }

            $location->delete();

            return redirect()->route('erkap.locations.index')
                ->with('success', 'Lokasi berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.locations.index')
                ->with('error', ErrorMessage::from($err));
        }
    }

    /**
     * Induk Lokasi hanya handful, jadi dirender server-side — cascade JS
     * baru diperlukan mulai Manajemen Area ke bawah.
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
