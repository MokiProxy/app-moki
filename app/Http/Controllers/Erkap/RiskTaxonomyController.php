<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRiskTaxonomyRequest;
use App\Http\Requests\UpdateRiskTaxonomyRequest;
use App\Models\Erkap\RiskAppetite;
use App\Models\Erkap\RiskTaxonomy;
use Exception;

class RiskTaxonomyController extends Controller
{
    public function index()
    {
        $pageName = 'Risk Taxonomies';
        $taxonomies = RiskTaxonomy::with('riskAppetite')->withCount('riskTypes')->paginate(10);

        return view('erkap.risk-taxonomy.index', compact('pageName', 'taxonomies'));
    }

    public function create()
    {
        $pageName = 'Buat Risk Taxonomy';
        $appetites = RiskAppetite::all();

        return view('erkap.risk-taxonomy.create', compact('pageName', 'appetites'));
    }

    public function store(StoreRiskTaxonomyRequest $request)
    {
        try {
            RiskTaxonomy::create($request->validated());

            return redirect()->route('erkap.risk-taxonomies.index')
                ->with('success', 'Risk taxonomy baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-taxonomies.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(RiskTaxonomy $riskTaxonomy)
    {
        $pageName = 'Edit Risk Taxonomy';
        $appetites = RiskAppetite::all();

        return view('erkap.risk-taxonomy.edit', compact('pageName', 'riskTaxonomy', 'appetites'));
    }

    public function update(UpdateRiskTaxonomyRequest $request, RiskTaxonomy $riskTaxonomy)
    {
        try {
            $riskTaxonomy->update($request->validated());

            return redirect()->route('erkap.risk-taxonomies.index')
                ->with('success', 'Risk taxonomy berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-taxonomies.edit', $riskTaxonomy->id)
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(RiskTaxonomy $riskTaxonomy)
    {
        try {
            if ($riskTaxonomy->riskTypes()->exists()) {
                return redirect()->route('erkap.risk-taxonomies.index')
                    ->with('error', 'Risk taxonomy tidak dapat dihapus karena masih memiliki risk type!');
            }

            $riskTaxonomy->delete();

            return redirect()->route('erkap.risk-taxonomies.index')
                ->with('success', 'Risk taxonomy berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-taxonomies.index')->with('error', $err->getMessage());
        }
    }
}