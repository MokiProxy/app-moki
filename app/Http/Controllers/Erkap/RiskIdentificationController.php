<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRiskIdentificationRequest;
use App\Http\Requests\UpdateRiskIdentificationRequest;
use App\Models\Erkap\DepartmentTarget;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RiskTaxonomy;
use App\Models\Erkap\RiskType;
use Exception;

class RiskIdentificationController extends Controller
{
    public function index()
    {
        $pageName = 'Identifikasi Risiko';
        $riskIdentifications = RiskIdentification::with(['departmentTarget', 'riskType', 'riskTaxonomy'])->paginate(10);

        return view('erkap.risk-identification.index', compact('pageName', 'riskIdentifications'));
    }

    public function create()
    {
        $pageName = 'Buat Identifikasi Risiko';
        $departmentTargets = DepartmentTarget::all();
        $riskTypes = RiskType::all();
        $riskTaxonomies = RiskTaxonomy::all();

        return view('erkap.risk-identification.create', compact('pageName', 'departmentTargets', 'riskTypes', 'riskTaxonomies'));
    }

    public function store(StoreRiskIdentificationRequest $request)
    {
        try {
            RiskIdentification::create($request->validated());

            return redirect()->route('erkap.risk-identifications.index')
                ->with('success', 'Identifikasi risiko baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-identifications.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(RiskIdentification $riskIdentification)
    {
        $pageName = 'Edit Identifikasi Risiko';
        $departmentTargets = DepartmentTarget::all();
        $riskTypes = RiskType::all();
        $riskTaxonomies = RiskTaxonomy::all();

        return view('erkap.risk-identification.edit', compact('pageName', 'riskIdentification', 'departmentTargets', 'riskTypes', 'riskTaxonomies'));
    }

    public function update(UpdateRiskIdentificationRequest $request, RiskIdentification $riskIdentification)
    {
        try {
            $riskIdentification->update($request->validated());

            return redirect()->route('erkap.risk-identifications.index')
                ->with('success', 'Identifikasi risiko berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-identifications.edit', $riskIdentification->id)
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(RiskIdentification $riskIdentification)
    {
        try {
            $riskIdentification->delete();

            return redirect()->route('erkap.risk-identifications.index')
                ->with('success', 'Identifikasi risiko berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-identifications.index')->with('error', $err->getMessage());
        }
    }
}
