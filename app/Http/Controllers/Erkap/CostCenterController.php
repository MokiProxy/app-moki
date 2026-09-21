<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCostCenterRequest;
use App\Http\Requests\UpdateCostCenterRequest;
use App\Models\Division;
use App\Models\Erkap\CostCenter;
use App\Services\ErkapAccess;
use Exception;

class CostCenterController extends Controller
{
    public function index()
    {
        $pageName = 'Pusat Biaya (Cost Center)';
        $costCenters = CostCenter::with('division')
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->where('division_id', ErkapAccess::divisionId());
            })
            ->paginate(10);

        return view('erkap.cost-center.index', compact('pageName', 'costCenters'));
    }

    public function create()
    {
        $pageName = 'Buat Pusat Biaya';
        $divisions = Division::query()
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->where('id', ErkapAccess::divisionId());
            })
            ->get();

        return view('erkap.cost-center.create', compact('pageName', 'divisions'));
    }

    public function store(StoreCostCenterRequest $request)
    {
        try {
            $data = $request->validated();
            $data['is_swakelola'] = $request->boolean('is_swakelola');
            $data['created_by'] = auth()->id();

            CostCenter::create($data);

            return redirect()->route('erkap.cost-centers.index')
                ->with('success', 'Pusat biaya baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.cost-centers.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(CostCenter $costCenter)
    {
        $pageName = 'Edit Pusat Biaya';
        $divisions = Division::query()
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->where('id', ErkapAccess::divisionId());
            })
            ->get();

        return view('erkap.cost-center.edit', compact('pageName', 'costCenter', 'divisions'));
    }

    public function update(UpdateCostCenterRequest $request, CostCenter $costCenter)
    {
        try {
            $data = $request->validated();
            $data['is_swakelola'] = $request->boolean('is_swakelola');
            $data['updated_by'] = auth()->id();

            $costCenter->update($data);

            return redirect()->route('erkap.cost-centers.index')
                ->with('success', 'Pusat biaya berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.cost-centers.edit', $costCenter->id)
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(CostCenter $costCenter)
    {
        try {
            $costCenter->delete();

            return redirect()->route('erkap.cost-centers.index')
                ->with('success', 'Pusat biaya berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.cost-centers.index')->with('error', $err->getMessage());
        }
    }
}