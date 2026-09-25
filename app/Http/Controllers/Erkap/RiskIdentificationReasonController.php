<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRiskIdentificationReasonRequest;
use App\Http\Requests\UpdateRiskIdentificationReasonRequest;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RiskIdentificationReason;
use App\Services\ErkapAccess;
use App\Services\ErkapEvaluationLock;
use Exception;
use Illuminate\Support\Facades\DB;

class RiskIdentificationReasonController extends Controller
{
    public function index()
    {
        $pageName = 'Penyebab Identifikasi Risiko';
        $reasons = RiskIdentificationReason::with('riskIdentification')
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->whereIn('erkap_risk_identification_id', ErkapAccess::riskIdentificationIds());
            })
            ->paginate(10);

        return view('erkap.risk-identification-reason.index', compact('pageName', 'reasons'));
    }

    public function create()
    {
        $pageName = 'Buat Penyebab Identifikasi Risiko';
        $riskIdentifications = RiskIdentification::whereIn('id', ErkapAccess::riskIdentificationIds())->get();

        return view('erkap.risk-identification-reason.create', compact('pageName', 'riskIdentifications'));
    }

    public function store(StoreRiskIdentificationReasonRequest $request)
    {
        try {
            $riskIdentification = RiskIdentification::findOrFail($request->integer('erkap_risk_identification_id'));
            ErkapAccess::assertRiskIdentificationAccess($riskIdentification->id);
            ErkapEvaluationLock::assertRiskEditable($riskIdentification);

            $reasonTexts = collect($request->input('reasons', []))
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->push(trim((string) $request->input('reason', '')))
                ->filter()
                ->values();

            if ($reasonTexts->isEmpty()) {
                return redirect()->route('erkap.risk-identification-reasons.create')
                    ->withInput()
                    ->with('error', 'Minimal satu penyebab identifikasi risiko wajib diisi.');
            }

            DB::transaction(function () use ($riskIdentification, $reasonTexts) {
                foreach ($reasonTexts as $text) {
                    $riskIdentification->reasons()->create(['reason' => $text]);
                }
            });

            $count = $reasonTexts->count();

            return redirect()->route('erkap.risk-identification-reasons.index')
                ->with('success', "{$count} penyebab identifikasi risiko berhasil disimpan!");
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

        try {
            ErkapEvaluationLock::assertRiskEditable(RiskIdentification::find($riskIdentificationReason->erkap_risk_identification_id));
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-identification-reasons.index')
                ->with('error', $err->getMessage());
        }

        $pageName = 'Edit Penyebab Identifikasi Risiko';
        $riskIdentifications = RiskIdentification::whereIn('id', ErkapAccess::riskIdentificationIds())->get();

        return view('erkap.risk-identification-reason.edit', compact('pageName', 'riskIdentificationReason', 'riskIdentifications'));
    }

    public function update(UpdateRiskIdentificationReasonRequest $request, RiskIdentificationReason $riskIdentificationReason)
    {
        try {
            ErkapAccess::assertRiskIdentificationAccess($riskIdentificationReason->erkap_risk_identification_id);
            ErkapAccess::assertRiskIdentificationAccess($request->integer('erkap_risk_identification_id'));
            ErkapEvaluationLock::assertRiskEditable(RiskIdentification::find($riskIdentificationReason->erkap_risk_identification_id));
            ErkapEvaluationLock::assertRiskEditable(RiskIdentification::find($request->integer('erkap_risk_identification_id')));

            $riskIdentificationReason->update($request->validated());

            return redirect()->route('erkap.risk-identification-reasons.index')
                ->with('success', 'Penyebab identifikasi risiko berhasil diperbarui!');
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
            ErkapEvaluationLock::assertRiskEditable(RiskIdentification::find($riskIdentificationReason->erkap_risk_identification_id));

            $riskIdentificationReason->delete();

            return redirect()->route('erkap.risk-identification-reasons.index')
                ->with('success', 'Penyebab identifikasi risiko berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-identification-reasons.index')->with('error', $err->getMessage());
        }
    }
}
