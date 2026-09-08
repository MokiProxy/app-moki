<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvestationTypeRequest;
use App\Http\Requests\UpdateInvestationTypeRequest;
use App\Models\Erkap\InvestationType;
use Exception;

class InvestationTypeController extends Controller
{
    public function index()
    {
        $pageName = 'Investation Types';
        $types = InvestationType::orderBy('code')->paginate(10);

        return view('erkap.investation-type.index', compact('pageName', 'types'));
    }

    public function create()
    {
        $pageName = 'Buat Investation Type';

        return view('erkap.investation-type.create', compact('pageName'));
    }

    public function store(StoreInvestationTypeRequest $request)
    {
        try {
            InvestationType::create($request->validated());

            return redirect()->route('erkap.investation-types.index')
                ->with('success', 'Investation type baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.investation-types.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(InvestationType $investationType)
    {
        $pageName = 'Edit Investation Type';

        return view('erkap.investation-type.edit', compact('pageName', 'investationType'));
    }

    public function update(UpdateInvestationTypeRequest $request, InvestationType $investationType)
    {
        try {
            $investationType->update($request->validated());

            return redirect()->route('erkap.investation-types.index')
                ->with('success', 'Investation type berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.investation-types.edit', $investationType->id)
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(InvestationType $investationType)
    {
        try {
            $investationType->delete();

            return redirect()->route('erkap.investation-types.index')
                ->with('success', 'Investation type berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.investation-types.index')->with('error', $err->getMessage());
        }
    }
}