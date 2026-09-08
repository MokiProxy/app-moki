<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRiskImpactRequest;
use App\Http\Requests\UpdateRiskImpactRequest;
use App\Models\Erkap\RiskImpact;
use Exception;

class RiskImpactController extends Controller
{
    public function index()
    {
        $pageName = 'Risk Impacts';
        $impacts = RiskImpact::orderBy('point')->paginate(10);

        return view('erkap.risk-impact.index', compact('pageName', 'impacts'));
    }

    public function create()
    {
        $pageName = 'Buat Risk Impact';

        return view('erkap.risk-impact.create', compact('pageName'));
    }

    public function store(StoreRiskImpactRequest $request)
    {
        try {
            RiskImpact::create($request->validated());

            return redirect()->route('erkap.risk-impacts.index')
                ->with('success', 'Risk impact baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-impacts.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(RiskImpact $riskImpact)
    {
        $pageName = 'Edit Risk Impact';

        return view('erkap.risk-impact.edit', compact('pageName', 'riskImpact'));
    }

    public function update(UpdateRiskImpactRequest $request, RiskImpact $riskImpact)
    {
        try {
            $riskImpact->update($request->validated());

            return redirect()->route('erkap.risk-impacts.index')
                ->with('success', 'Risk impact berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-impacts.edit', $riskImpact->id)
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(RiskImpact $riskImpact)
    {
        try {
            $riskImpact->delete();

            return redirect()->route('erkap.risk-impacts.index')
                ->with('success', 'Risk impact berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-impacts.index')->with('error', $err->getMessage());
        }
    }
}