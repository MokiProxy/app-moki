<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartmentTargetRequest;
use App\Http\Requests\UpdateDepartmentTargetRequest;
use App\Models\Division;
use App\Models\Erkap\CompanyTarget;
use App\Models\Erkap\DepartmentTarget;
use App\Models\Erkap\RatingCriteria;
use App\Services\ErkapAccess;
use Exception;

class DepartmentTargetController extends Controller
{
    public function index()
    {
        $pageName = 'Sasaran Departemen';
        $departmentTargets = DepartmentTarget::with(['division', 'ratingCriteria', 'companyTarget'])
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->where('division_id', ErkapAccess::divisionId());
            })
            ->orderBy('priority')
            ->paginate(10);

        return view('erkap.department-target.index', compact('pageName', 'departmentTargets'));
    }

    public function create()
    {
        $pageName = 'Buat Sasaran Departemen';
        $ratingCriterias = RatingCriteria::all();
        $companyTargets = CompanyTarget::all();

        if (ErkapAccess::isDivisionScoped()) {
            $userDivision = Division::find(ErkapAccess::divisionId());

            if (! $userDivision) {
                return redirect()->route('erkap.department-targets.index')
                    ->with('error', 'Data divisi Anda tidak ditemukan. Silakan hubungi administrator.');
            }

            return view('erkap.department-target.create', compact('pageName', 'userDivision', 'ratingCriterias', 'companyTargets'));
        }

        $divisions = Division::all();

        return view('erkap.department-target.create', compact('pageName', 'divisions', 'ratingCriterias', 'companyTargets'));
    }

    public function store(StoreDepartmentTargetRequest $request)
    {
        try {
            $data = $request->validated();

            if (ErkapAccess::isDivisionScoped()) {
                $data['division_id'] = ErkapAccess::divisionId();
            }

            DepartmentTarget::create($data);

            return redirect()->route('erkap.department-targets.index')
                ->with('success', 'Sasaran departemen baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.department-targets.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(DepartmentTarget $departmentTarget)
    {
        ErkapAccess::assertDepartmentTargetAccess($departmentTarget->id);

        $pageName = 'Edit Sasaran Departemen';
        $ratingCriterias = RatingCriteria::all();
        $companyTargets = CompanyTarget::all();

        if (ErkapAccess::isDivisionScoped()) {
            $userDivision = Division::find(ErkapAccess::divisionId());

            if (! $userDivision) {
                return redirect()->route('erkap.department-targets.index')
                    ->with('error', 'Data divisi Anda tidak ditemukan. Silakan hubungi administrator.');
            }

            return view('erkap.department-target.edit', compact('pageName', 'departmentTarget', 'userDivision', 'ratingCriterias', 'companyTargets'));
        }

        $divisions = Division::all();

        return view('erkap.department-target.edit', compact('pageName', 'departmentTarget', 'divisions', 'ratingCriterias', 'companyTargets'));
    }

    public function update(UpdateDepartmentTargetRequest $request, DepartmentTarget $departmentTarget)
    {
        try {
            ErkapAccess::assertDepartmentTargetAccess($departmentTarget->id);

            $data = $request->validated();

            if (ErkapAccess::isDivisionScoped()) {
                $data['division_id'] = ErkapAccess::divisionId();
            }

            $departmentTarget->update($data);

            return redirect()->route('erkap.department-targets.index')
                ->with('success', 'Sasaran departemen berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.department-targets.edit', $departmentTarget->id)
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(DepartmentTarget $departmentTarget)
    {
        try {
            ErkapAccess::assertDepartmentTargetAccess($departmentTarget->id);

            if ($departmentTarget->riskIdentifications()->exists()) {
                return redirect()->route('erkap.department-targets.index')
                    ->with('error', 'Sasaran departemen tidak dapat dihapus karena masih memiliki identifikasi risiko!');
            }

            $departmentTarget->delete();

            return redirect()->route('erkap.department-targets.index')
                ->with('success', 'Sasaran departemen berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.department-targets.index')->with('error', $err->getMessage());
        }
    }
}