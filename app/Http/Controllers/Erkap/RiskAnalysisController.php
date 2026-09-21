<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRiskAnalysisRequest;
use App\Http\Requests\UpdateRiskAnalysisRequest;
use App\Models\Erkap\RiskAnalysis;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RiskImpact;
use App\Models\Erkap\RiskProbability;
use App\Models\Erkap\RiskScoreLevel;
use App\Services\ErkapAccess;
use Exception;
use Illuminate\Http\JsonResponse;

class RiskAnalysisController extends Controller
{
    public function index()
    {
        $pageName = 'Analisis Risiko';
        $riskAnalyses = RiskAnalysis::with(['riskIdentification', 'riskProbability', 'riskImpact', 'riskScoreValue'])
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->whereIn('erkap_risk_identification_id', ErkapAccess::riskIdentificationIds());
            })
            ->paginate(10);

        return view('erkap.risk-analysis.index', compact('pageName', 'riskAnalyses'));
    }

    public function create()
    {
        $pageName = 'Buat Analisis Risiko';
        $riskIdentifications = RiskIdentification::whereIn('id', ErkapAccess::riskIdentificationIds())->get();
        $riskProbabilities = RiskProbability::all();
        $riskImpacts = RiskImpact::all();

        return view('erkap.risk-analysis.create', compact('pageName', 'riskIdentifications', 'riskProbabilities', 'riskImpacts'));
    }

    public function store(StoreRiskAnalysisRequest $request)
    {
        try {
            ErkapAccess::assertRiskIdentificationAccess($request->integer('erkap_risk_identification_id'));

            RiskAnalysis::create($request->validated());

            return redirect()->route('erkap.risk-analysis.index')
                ->with('success', 'Analisis risiko baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-analysis.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(RiskAnalysis $riskAnalysis)
    {
        ErkapAccess::assertRiskIdentificationAccess($riskAnalysis->erkap_risk_identification_id);

        $pageName = 'Edit Analisis Risiko';
        $riskIdentifications = RiskIdentification::whereIn('id', ErkapAccess::riskIdentificationIds())->get();
        $riskProbabilities = RiskProbability::all();
        $riskImpacts = RiskImpact::all();

        return view('erkap.risk-analysis.edit', compact('pageName', 'riskAnalysis', 'riskIdentifications', 'riskProbabilities', 'riskImpacts'));
    }

    public function update(UpdateRiskAnalysisRequest $request, RiskAnalysis $riskAnalysis)
    {
        try {
            ErkapAccess::assertRiskIdentificationAccess($riskAnalysis->erkap_risk_identification_id);
            ErkapAccess::assertRiskIdentificationAccess($request->integer('erkap_risk_identification_id'));

            $riskAnalysis->update($request->validated());

            return redirect()->route('erkap.risk-analysis.index')
                ->with('success', 'Analisis risiko berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-analysis.edit', $riskAnalysis->id)
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(RiskAnalysis $riskAnalysis)
    {
        try {
            ErkapAccess::assertRiskIdentificationAccess($riskAnalysis->erkap_risk_identification_id);

            $riskAnalysis->delete();

            return redirect()->route('erkap.risk-analysis.index')
                ->with('success', 'Analisis risiko berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-analysis.index')->with('error', $err->getMessage());
        }
    }

    public function getScoreLevel($probabilityId, $impactId): JsonResponse
    {
        $scoreLevel = RiskScoreLevel::where('erkap_risk_probability_id', $probabilityId)
            ->where('erkap_risk_impact_id', $impactId)
            ->first();

        if (! $scoreLevel) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'id' => $scoreLevel->id,
            'score' => $scoreLevel->score,
            'level' => $scoreLevel->level,
        ]);
    }
}
