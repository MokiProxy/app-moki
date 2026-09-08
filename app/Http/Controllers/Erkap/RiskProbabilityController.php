<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRiskProbabilityRequest;
use App\Http\Requests\UpdateRiskProbabilityRequest;
use App\Models\Erkap\RiskProbability;
use Exception;

class RiskProbabilityController extends Controller
{
    public function index()
    {
        $pageName = 'Risk Probabilities';
        $probabilities = RiskProbability::orderBy('point')->paginate(10);

        return view('erkap.risk-probability.index', compact('pageName', 'probabilities'));
    }

    public function create()
    {
        $pageName = 'Buat Risk Probability';

        return view('erkap.risk-probability.create', compact('pageName'));
    }

    public function store(StoreRiskProbabilityRequest $request)
    {
        try {
            RiskProbability::create($request->validated());

            return redirect()->route('erkap.risk-probabilities.index')
                ->with('success', 'Risk probability baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-probabilities.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(RiskProbability $riskProbability)
    {
        $pageName = 'Edit Risk Probability';

        return view('erkap.risk-probability.edit', compact('pageName', 'riskProbability'));
    }

    public function update(UpdateRiskProbabilityRequest $request, RiskProbability $riskProbability)
    {
        try {
            $riskProbability->update($request->validated());

            return redirect()->route('erkap.risk-probabilities.index')
                ->with('success', 'Risk probability berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-probabilities.edit', $riskProbability->id)
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(RiskProbability $riskProbability)
    {
        try {
            $riskProbability->delete();

            return redirect()->route('erkap.risk-probabilities.index')
                ->with('success', 'Risk probability berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-probabilities.index')->with('error', $err->getMessage());
        }
    }
}