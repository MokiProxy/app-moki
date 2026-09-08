<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRiskTypeRequest;
use App\Http\Requests\UpdateRiskTypeRequest;
use App\Models\Erkap\RiskTaxonomy;
use App\Models\Erkap\RiskType;
use Exception;

class RiskTypeController extends Controller
{
    public function index()
    {
        $pageName = 'Risk Types';
        $riskTypes = RiskType::with('riskTaxonomy')->paginate(10);

        return view('erkap.risk-type.index', compact('pageName', 'riskTypes'));
    }

    public function create()
    {
        $pageName = 'Buat Risk Type';
        $taxonomies = RiskTaxonomy::all();

        return view('erkap.risk-type.create', compact('pageName', 'taxonomies'));
    }

    public function store(StoreRiskTypeRequest $request)
    {
        try {
            RiskType::create($request->validated());

            return redirect()->route('erkap.risk-types.index')
                ->with('success', 'Risk type baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-types.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(RiskType $riskType)
    {
        $pageName = 'Edit Risk Type';
        $taxonomies = RiskTaxonomy::all();

        return view('erkap.risk-type.edit', compact('pageName', 'riskType', 'taxonomies'));
    }

    public function update(UpdateRiskTypeRequest $request, RiskType $riskType)
    {
        try {
            $riskType->update($request->validated());

            return redirect()->route('erkap.risk-types.index')
                ->with('success', 'Risk type berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-types.edit', $riskType->id)
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(RiskType $riskType)
    {
        try {
            $riskType->delete();

            return redirect()->route('erkap.risk-types.index')
                ->with('success', 'Risk type berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-types.index')->with('error', $err->getMessage());
        }
    }
}