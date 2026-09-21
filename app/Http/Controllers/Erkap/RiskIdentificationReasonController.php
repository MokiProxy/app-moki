<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRiskIdentificationReasonRequest;
use App\Http\Requests\UpdateRiskIdentificationReasonRequest;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RiskIdentificationReason;
use App\Services\ErkapAccess;
use Exception;

class RiskIdentificationReasonController extends Controller
{
    public function index()
    {
        $pageName = 'Alasan Identifikasi Risiko';
        $reasons = RiskIdentificationReason::with('riskIdentification')
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->whereIn('erkap_risk_identification_id', ErkapAccess::riskIdentificationIds());
            })
            ->paginate(10);

        return view('erkap.risk-identification-reason.index', compact('pageName', 'reasons'));
    }

    public function create()
    {
        $pageName = 'Buat Alasan Identifikasi Risiko';
        $riskIdentifications = RiskIdentification::whereIn('id', ErkapAccess::riskIdentificationIds())->get();

        return view('erkap.risk-identification-reason.create', compact('pageName', 'riskIdentifications'));
    }

    public function store(StoreRiskIdentificationReasonRequest $request)
    {
        try {
            ErkapAccess::assertRiskIdentificationAccess($request->integer('erkap_risk_identification_id'));

            RiskIdentificationReason::create($request->validated());

            return redirect()->route('erkap.risk-identification-reasons.index')
                ->with('success', 'Alasan identifikasi risiko baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-identification-reasons.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(RiskIdentificationReason $riskIdentificationReason)
    {
        ErkapAccess::assertRiskIdentificationAccess($riskIdentificationReason->erkap_risk_identification_id);

        $pageName = 'Edit Alasan Identifikasi Risiko';
        $riskIdentifications = RiskIdentification::whereIn('id', ErkapAccess::riskIdentificationIds())->get();

        return view('erkap.risk-identification-reason.edit', compact('pageName', 'riskIdentificationReason', 'riskIdentifications'));
    }

    public function update(UpdateRiskIdentificationReasonRequest $request, RiskIdentificationReason $riskIdentificationReason)
    {
        try {
            ErkapAccess::assertRiskIdentificationAccess($riskIdentificationReason->erkap_risk_identification_id);
            ErkapAccess::assertRiskIdentificationAccess($request->integer('erkap_risk_identification_id'));

            $riskIdentificationReason->update($request->validated());

            return redirect()->route('erkap.risk-identification-reasons.index')
                ->with('success', 'Alasan identifikasi risiko berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-identification-reasons.edit', $riskIdentificationReason->id)
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(RiskIdentificationReason $riskIdentificationReason)
    {
        try {
            ErkapAccess::assertRiskIdentificationAccess($riskIdentificationReason->erkap_risk_identification_id);

            $riskIdentificationReason->delete();

            return redirect()->route('erkap.risk-identification-reasons.index')
                ->with('success', 'Alasan identifikasi risiko berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-identification-reasons.index')->with('error', $err->getMessage());
        }
    }
}