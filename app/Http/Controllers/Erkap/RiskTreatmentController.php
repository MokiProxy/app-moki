<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRiskTreatmentRequest;
use App\Http\Requests\UpdateRiskTreatmentRequest;
use App\Models\Erkap\DepartmentRiskStrategy;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RiskTreatment;
use App\Services\ErkapAccess;
use App\Services\ErkapEvaluationLock;
use Exception;
use Illuminate\Http\Request;

class RiskTreatmentController extends Controller
{
    public function index(Request $request)
    {
        $pageName = 'Perlakuan Risiko';
        $riskTreatments = RiskTreatment::with(['riskIdentification', 'departmentRiskStrategy'])
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->whereIn('erkap_risk_identification_id', ErkapAccess::riskIdentificationIds());
            })
            ->when($request->filled('erkap_risk_identification_id'), function ($query) use ($request) {
                $query->where('erkap_risk_identification_id', $request->integer('erkap_risk_identification_id'));
            })
            ->paginate(10)
            ->withQueryString();

        return view('erkap.risk-treatment.index', compact('pageName', 'riskTreatments'));
    }

    public function create()
    {
        $pageName = 'Buat Perlakuan Risiko';
        $riskIdentifications = RiskIdentification::whereIn('id', ErkapAccess::riskIdentificationIds())->get();
        $strategies = DepartmentRiskStrategy::whereIn('erkap_risk_identification_id', ErkapAccess::riskIdentificationIds())->get();

        return view('erkap.risk-treatment.create', compact('pageName', 'riskIdentifications', 'strategies'));
    }

    public function store(StoreRiskTreatmentRequest $request)
    {
        try {
            ErkapAccess::assertRiskIdentificationAccess($request->integer('erkap_risk_identification_id'));
            ErkapEvaluationLock::assertRiskEditable(RiskIdentification::findOrFail($request->integer('erkap_risk_identification_id')));

            RiskTreatment::create($request->validated());

            return redirect()->route('erkap.risk-treatments.index')
                ->with('success', 'Rencana perlakuan risiko baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-treatments.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(RiskTreatment $riskTreatment)
    {
        try {
            ErkapAccess::assertRiskIdentificationAccess($riskTreatment->erkap_risk_identification_id);
            ErkapEvaluationLock::assertRiskEditable(RiskIdentification::find($riskTreatment->erkap_risk_identification_id));
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-treatments.index')
                ->with('error', $err->getMessage());
        }

        $pageName = 'Edit Perlakuan Risiko';
        $riskIdentifications = RiskIdentification::whereIn('id', ErkapAccess::riskIdentificationIds())->get();
        $strategies = DepartmentRiskStrategy::whereIn('erkap_risk_identification_id', ErkapAccess::riskIdentificationIds())->get();

        return view('erkap.risk-treatment.edit', compact('pageName', 'riskTreatment', 'riskIdentifications', 'strategies'));
    }

    public function update(UpdateRiskTreatmentRequest $request, RiskTreatment $riskTreatment)
    {
        try {
            ErkapAccess::assertRiskIdentificationAccess($riskTreatment->erkap_risk_identification_id);
            ErkapAccess::assertRiskIdentificationAccess($request->integer('erkap_risk_identification_id'));
            ErkapEvaluationLock::assertRiskEditable(RiskIdentification::find($riskTreatment->erkap_risk_identification_id));
            ErkapEvaluationLock::assertRiskEditable(RiskIdentification::find($request->integer('erkap_risk_identification_id')));

            $riskTreatment->update($request->validated());

            return redirect()->route('erkap.risk-treatments.index')
                ->with('success', 'Rencana perlakuan risiko berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-treatments.edit', $riskTreatment->id)
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(RiskTreatment $riskTreatment)
    {
        try {
            ErkapAccess::assertRiskIdentificationAccess($riskTreatment->erkap_risk_identification_id);
            ErkapEvaluationLock::assertRiskEditable(RiskIdentification::find($riskTreatment->erkap_risk_identification_id));

            $riskTreatment->delete();

            return redirect()->route('erkap.risk-treatments.index')
                ->with('success', 'Rencana perlakuan risiko berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-treatments.index')->with('error', $err->getMessage());
        }
    }
}