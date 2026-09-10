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
use Exception;

class RiskAnalysisController extends Controller
{
    public function index()
    {
        $pageName = 'Analisis Risiko';
        $riskAnalyses = RiskAnalysis::with(['riskIdentification', 'riskProbability', 'riskImpact', 'riskScoreValue'])->paginate(10);

        return view('erkap.risk-analysis.index', compact('pageName', 'riskAnalyses'));
    }

    public function create()
    {
        $pageName = 'Buat Analisis Risiko';
        $riskIdentifications = RiskIdentification::all();
        $riskProbabilities = RiskProbability::all();
        $riskImpacts = RiskImpact::all();
        $riskScoreValues = RiskScoreLevel::all();

        return view('erkap.risk-analysis.create', compact('pageName', 'riskIdentifications', 'riskProbabilities', 'riskImpacts', 'riskScoreValues'));
    }

    public function store(StoreRiskAnalysisRequest $request)
    {
        try {
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
        $pageName = 'Edit Analisis Risiko';
        $riskIdentifications = RiskIdentification::all();
        $riskProbabilities = RiskProbability::all();
        $riskImpacts = RiskImpact::all();
        $riskScoreValues = RiskScoreLevel::all();

        return view('erkap.risk-analysis.edit', compact('pageName', 'riskAnalysis', 'riskIdentifications', 'riskProbabilities', 'riskImpacts', 'riskScoreValues'));
    }

    public function update(UpdateRiskAnalysisRequest $request, RiskAnalysis $riskAnalysis)
    {
        try {
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
            $riskAnalysis->delete();

            return redirect()->route('erkap.risk-analysis.index')
                ->with('success', 'Analisis risiko berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-analysis.index')->with('error', $err->getMessage());
        }
    }
}