<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartmentTargetRequest;
use App\Http\Requests\UpdateDepartmentTargetRequest;
use App\Models\Division;
use App\Models\Erkap\CompanyTarget;
use App\Models\Erkap\DepartmentTarget;
use App\Models\Erkap\RatingCriteria;
use Exception;

class DepartmentTargetController extends Controller
{
    public function index()
    {
        $pageName = 'Sasaran Departemen';
        $departmentTargets = DepartmentTarget::with(['division', 'ratingCriteria', 'companyTarget'])->paginate(10);

        return view('erkap.department-target.index', compact('pageName', 'departmentTargets'));
    }

    public function create()
    {
        $pageName = 'Buat Sasaran Departemen';
        $divisions = Division::all();
        $ratingCriterias = RatingCriteria::all();
        $companyTargets = CompanyTarget::all();

        return view('erkap.department-target.create', compact('pageName', 'divisions', 'ratingCriterias', 'companyTargets'));
    }

    public function store(StoreDepartmentTargetRequest $request)
    {
        try {
            DepartmentTarget::create($request->validated());

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
        $pageName = 'Edit Sasaran Departemen';
        $divisions = Division::all();
        $ratingCriterias = RatingCriteria::all();
        $companyTargets = CompanyTarget::all();

        return view('erkap.department-target.edit', compact('pageName', 'departmentTarget', 'divisions', 'ratingCriterias', 'companyTargets'));
    }

    public function update(UpdateDepartmentTargetRequest $request, DepartmentTarget $departmentTarget)
    {
        try {
            $departmentTarget->update($request->validated());

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
