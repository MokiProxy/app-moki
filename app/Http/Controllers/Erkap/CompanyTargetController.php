<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyTargetRequest;
use App\Http\Requests\UpdateCompanyTargetRequest;
use App\Models\Erkap\CompanyTarget;
use App\Models\Erkap\RKAP;
use Exception;

class CompanyTargetController extends Controller
{
    public function index()
    {
        $pageName = 'Sasaran Perusahaan';
        $companyTargets = CompanyTarget::with('rkap')->paginate(10);

        return view('erkap.company-target.index', compact('pageName', 'companyTargets'));
    }

    public function create()
    {
        $pageName = 'Buat Sasaran Perusahaan';
        $rkaps = RKAP::all();

        return view('erkap.company-target.create', compact('pageName', 'rkaps'));
    }

    public function store(StoreCompanyTargetRequest $request)
    {
        try {
            CompanyTarget::create($request->validated());

            return redirect()->route('erkap.company-targets.index')
                ->with('success', 'Sasaran perusahaan baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.company-targets.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(CompanyTarget $companyTarget)
    {
        $pageName = 'Edit Sasaran Perusahaan';
        $rkaps = RKAP::all();

        return view('erkap.company-target.edit', compact('pageName', 'companyTarget', 'rkaps'));
    }

    public function update(UpdateCompanyTargetRequest $request, CompanyTarget $companyTarget)
    {
        try {
            $companyTarget->update($request->validated());

            return redirect()->route('erkap.company-targets.index')
                ->with('success', 'Sasaran perusahaan berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.company-targets.edit', $companyTarget->id)
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(CompanyTarget $companyTarget)
    {
        try {
            if ($companyTarget->departmentTargets()->exists()) {
                return redirect()->route('erkap.company-targets.index')
                    ->with('error', 'Sasaran perusahaan tidak dapat dihapus karena masih memiliki sasaran departemen!');
            }

            $companyTarget->delete();

            return redirect()->route('erkap.company-targets.index')
                ->with('success', 'Sasaran perusahaan berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.company-targets.index')->with('error', $err->getMessage());
        }
    }
}
