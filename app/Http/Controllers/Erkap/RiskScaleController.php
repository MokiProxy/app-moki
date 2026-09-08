<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRiskScaleRequest;
use App\Http\Requests\UpdateRiskScaleRequest;
use App\Models\Erkap\RiskScale;
use Exception;

class RiskScaleController extends Controller
{
    public function index()
    {
        $pageName = 'Risk Scales';
        $scales = RiskScale::orderBy('scale')->paginate(10);

        return view('erkap.risk-scale.index', compact('pageName', 'scales'));
    }

    public function create()
    {
        $pageName = 'Buat Risk Scale';

        return view('erkap.risk-scale.create', compact('pageName'));
    }

    public function store(StoreRiskScaleRequest $request)
    {
        try {
            RiskScale::create($request->validated());

            return redirect()->route('erkap.risk-scales.index')
                ->with('success', 'Risk scale baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-scales.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(RiskScale $riskScale)
    {
        $pageName = 'Edit Risk Scale';

        return view('erkap.risk-scale.edit', compact('pageName', 'riskScale'));
    }

    public function update(UpdateRiskScaleRequest $request, RiskScale $riskScale)
    {
        try {
            $riskScale->update($request->validated());

            return redirect()->route('erkap.risk-scales.index')
                ->with('success', 'Risk scale berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-scales.edit', $riskScale->id)
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(RiskScale $riskScale)
    {
        try {
            $riskScale->delete();

            return redirect()->route('erkap.risk-scales.index')
                ->with('success', 'Risk scale berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-scales.index')->with('error', $err->getMessage());
        }
    }
}