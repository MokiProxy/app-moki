<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvestationCriteriaRequest;
use App\Http\Requests\UpdateInvestationCriteriaRequest;
use App\Models\Erkap\InvestationCriteria;
use Exception;

class InvestationCriteriaController extends Controller
{
    public function index()
    {
        $pageName = 'Investation Criterias';
        $criterias = InvestationCriteria::orderBy('code')->paginate(10);

        return view('erkap.investation-criteria.index', compact('pageName', 'criterias'));
    }

    public function create()
    {
        $pageName = 'Buat Investation Criteria';

        return view('erkap.investation-criteria.create', compact('pageName'));
    }

    public function store(StoreInvestationCriteriaRequest $request)
    {
        try {
            InvestationCriteria::create($request->validated());

            return redirect()->route('erkap.investation-criterias.index')
                ->with('success', 'Investation criteria baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.investation-criterias.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(InvestationCriteria $investationCriteria)
    {
        $pageName = 'Edit Investation Criteria';

        return view('erkap.investation-criteria.edit', compact('pageName', 'investationCriteria'));
    }

    public function update(UpdateInvestationCriteriaRequest $request, InvestationCriteria $investationCriteria)
    {
        try {
            $investationCriteria->update($request->validated());

            return redirect()->route('erkap.investation-criterias.index')
                ->with('success', 'Investation criteria berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.investation-criterias.edit', $investationCriteria->id)
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(InvestationCriteria $investationCriteria)
    {
        try {
            $investationCriteria->delete();

            return redirect()->route('erkap.investation-criterias.index')
                ->with('success', 'Investation criteria berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.investation-criterias.index')->with('error', $err->getMessage());
        }
    }
}