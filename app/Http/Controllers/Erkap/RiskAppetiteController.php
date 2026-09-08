<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRiskAppetiteRequest;
use App\Http\Requests\UpdateRiskAppetiteRequest;
use App\Models\Erkap\RiskAppetite;
use Exception;

class RiskAppetiteController extends Controller
{
    public function index()
    {
        $pageName = 'Risk Appetites';
        $appetites = RiskAppetite::withCount('riskTaxonomies')->paginate(10);

        return view('erkap.risk-appetite.index', compact('pageName', 'appetites'));
    }

    public function create()
    {
        $pageName = 'Buat Risk Appetite';

        return view('erkap.risk-appetite.create', compact('pageName'));
    }

    public function store(StoreRiskAppetiteRequest $request)
    {
        try {
            RiskAppetite::create($request->validated());

            return redirect()->route('erkap.risk-appetites.index')
                ->with('success', 'Risk appetite baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-appetites.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(RiskAppetite $riskAppetite)
    {
        $pageName = 'Edit Risk Appetite';

        return view('erkap.risk-appetite.edit', compact('pageName', 'riskAppetite'));
    }

    public function update(UpdateRiskAppetiteRequest $request, RiskAppetite $riskAppetite)
    {
        try {
            $riskAppetite->update($request->validated());

            return redirect()->route('erkap.risk-appetites.index')
                ->with('success', 'Risk appetite berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-appetites.edit', $riskAppetite->id)
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(RiskAppetite $riskAppetite)
    {
        try {
            if ($riskAppetite->riskTaxonomies()->exists()) {
                return redirect()->route('erkap.risk-appetites.index')
                    ->with('error', 'Risk appetite tidak dapat dihapus karena masih memiliki risk taxonomy!');
            }

            $riskAppetite->delete();

            return redirect()->route('erkap.risk-appetites.index')
                ->with('success', 'Risk appetite berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-appetites.index')->with('error', $err->getMessage());
        }
    }
}