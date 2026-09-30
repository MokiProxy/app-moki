<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBusinessUnitRequest;
use App\Http\Requests\UpdateBusinessUnitRequest;
use App\Models\Erkap\BusinessUnit;
use App\Support\ErrorMessage;
use Exception;

class BusinessUnitController extends Controller
{
    public function index()
    {
        $pageName = 'Bisnis Unit';
        $businessUnits = BusinessUnit::withCount('locations')
            ->orderBy('sort_order')
            ->orderBy('code')
            ->paginate(10);

        return view('erkap.business-unit.index', compact('pageName', 'businessUnits'));
    }

    public function create()
    {
        $pageName = 'Buat Bisnis Unit';

        return view('erkap.business-unit.create', compact('pageName'));
    }

    public function store(StoreBusinessUnitRequest $request)
    {
        try {
            $data = $request->safe()->except('is_active');
            $data['is_active'] = $request->boolean('is_active');
            $data['created_by'] = auth()->id();

            BusinessUnit::create($data);

            return redirect()->route('erkap.business-units.index')
                ->with('success', 'Bisnis Unit baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.business-units.create')
                ->withInput()
                ->with('error', ErrorMessage::from($err));
        }
    }

    public function edit(BusinessUnit $businessUnit)
    {
        $pageName = 'Edit Bisnis Unit';

        return view('erkap.business-unit.edit', compact('pageName', 'businessUnit'));
    }

    public function update(UpdateBusinessUnitRequest $request, BusinessUnit $businessUnit)
    {
        try {
            $data = $request->safe()->except('is_active');
            $data['is_active'] = $request->boolean('is_active');
            $data['updated_by'] = auth()->id();

            $businessUnit->update($data);

            return redirect()->route('erkap.business-units.index')
                ->with('success', 'Bisnis Unit berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.business-units.edit', $businessUnit)
                ->withInput()
                ->with('error', ErrorMessage::from($err));
        }
    }

    public function destroy(BusinessUnit $businessUnit)
    {
        try {
            // Tidak boleh dihapus bila sudah dipakai anak, karena FK saat ini
            // nullOnDelete — menghapus induk akan memutus rantai kode a..d.
            if ($businessUnit->locations()->exists()
                || $businessUnit->managementAreas()->exists()
                || $businessUnit->activities()->exists()
                || $businessUnit->costCenters()->exists()) {
                return redirect()->route('erkap.business-units.index')
                    ->with('error', 'Bisnis Unit masih digunakan oleh Lokasi, Manajemen Area, Aktivitas, atau Pusat Biaya sehingga tidak dapat dihapus.');
            }

            $businessUnit->delete();

            return redirect()->route('erkap.business-units.index')
                ->with('success', 'Bisnis Unit berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.business-units.index')
                ->with('error', ErrorMessage::from($err));
        }
    }
}
