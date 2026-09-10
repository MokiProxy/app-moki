<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRiskIdentificationImpactRequest;
use App\Http\Requests\UpdateRiskIdentificationImpactRequest;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RiskIdentificationImpact;
use Exception;

class RiskIdentificationImpactController extends Controller
{
    public function index()
    {
        $pageName = 'Dampak Identifikasi Risiko';
        $impacts = RiskIdentificationImpact::with('riskIdentification')->paginate(10);

        return view('erkap.risk-identification-impact.index', compact('pageName', 'impacts'));
    }

    public function create()
    {
        $pageName = 'Buat Dampak Identifikasi Risiko';
        $riskIdentifications = RiskIdentification::all();

        return view('erkap.risk-identification-impact.create', compact('pageName', 'riskIdentifications'));
    }

    public function store(StoreRiskIdentificationImpactRequest $request)
    {
        try {
            RiskIdentificationImpact::create($request->validated());

            return redirect()->route('erkap.risk-identification-impacts.index')
                ->with('success', 'Dampak identifikasi risiko baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-identification-impacts.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(RiskIdentificationImpact $riskIdentificationImpact)
    {
        $pageName = 'Edit Dampak Identifikasi Risiko';
        $riskIdentifications = RiskIdentification::all();

        return view('erkap.risk-identification-impact.edit', compact('pageName', 'riskIdentificationImpact', 'riskIdentifications'));
    }

    public function update(UpdateRiskIdentificationImpactRequest $request, RiskIdentificationImpact $riskIdentificationImpact)
    {
        try {
            $riskIdentificationImpact->update($request->validated());

            return redirect()->route('erkap.risk-identification-impacts.index')
                ->with('success', 'Dampak identifikasi risiko berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-identification-impacts.edit', $riskIdentificationImpact->id)
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(RiskIdentificationImpact $riskIdentificationImpact)
    {
        try {
            $riskIdentificationImpact->delete();

            return redirect()->route('erkap.risk-identification-impacts.index')
                ->with('success', 'Dampak identifikasi risiko berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-identification-impacts.index')->with('error', $err->getMessage());
        }
    }
}