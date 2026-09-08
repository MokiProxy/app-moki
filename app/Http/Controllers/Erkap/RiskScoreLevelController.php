<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRiskScoreLevelRequest;
use App\Http\Requests\UpdateRiskScoreLevelRequest;
use App\Models\Erkap\RiskImpact;
use App\Models\Erkap\RiskProbability;
use App\Models\Erkap\RiskScoreLevel;
use Exception;

class RiskScoreLevelController extends Controller
{
    public function index()
    {
        $pageName = 'Risk Score & Levels';
        $riskScoreLevels = RiskScoreLevel::with(['riskProbability', 'riskImpact'])->paginate(10);

        return view('erkap.risk-score-level.index', compact('pageName', 'riskScoreLevels'));
    }

    public function create()
    {
        $pageName = 'Buat Risk Score Level';
        $probabilities = RiskProbability::all();
        $impacts = RiskImpact::all();

        return view('erkap.risk-score-level.create', compact('pageName', 'probabilities', 'impacts'));
    }

    public function store(StoreRiskScoreLevelRequest $request)
    {
        try {
            RiskScoreLevel::create($request->validated());

            return redirect()->route('erkap.risk-score-levels.index')
                ->with('success', 'Risk score level baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-score-levels.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(RiskScoreLevel $riskScoreLevel)
    {
        $pageName = 'Edit Risk Score Level';
        $probabilities = RiskProbability::all();
        $impacts = RiskImpact::all();

        return view('erkap.risk-score-level.edit', compact('pageName', 'riskScoreLevel', 'probabilities', 'impacts'));
    }

    public function update(UpdateRiskScoreLevelRequest $request, RiskScoreLevel $riskScoreLevel)
    {
        try {
            $riskScoreLevel->update($request->validated());

            return redirect()->route('erkap.risk-score-levels.index')
                ->with('success', 'Risk score level berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-score-levels.edit', $riskScoreLevel->id)
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(RiskScoreLevel $riskScoreLevel)
    {
        try {
            $riskScoreLevel->delete();

            return redirect()->route('erkap.risk-score-levels.index')
                ->with('success', 'Risk score level berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-score-levels.index')->with('error', $err->getMessage());
        }
    }
}