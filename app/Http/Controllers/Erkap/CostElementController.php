<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCostElementRequest;
use App\Http\Requests\UpdateCostElementRequest;
use App\Models\Erkap\CostElement;
use App\Models\Erkap\CostElementCategory;
use Exception;

class CostElementController extends Controller
{
    public function index()
    {
        $pageName = 'Elemen Biaya';
        $costElements = CostElement::with('costElementCategory')->paginate(10);

        return view('erkap.cost-element.index', compact('pageName', 'costElements'));
    }

    public function create()
    {
        $pageName = 'Buat Elemen Biaya';
        $categories = CostElementCategory::all();

        return view('erkap.cost-element.create', compact('pageName', 'categories'));
    }

    public function store(StoreCostElementRequest $request)
    {
        try {
            CostElement::create($request->validated());

            return redirect()->route('erkap.cost-elements.index')
                ->with('success', 'Elemen biaya baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.cost-elements.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(CostElement $costElement)
    {
        $pageName = 'Edit Elemen Biaya';
        $categories = CostElementCategory::all();

        return view('erkap.cost-element.edit', compact('pageName', 'costElement', 'categories'));
    }

    public function update(UpdateCostElementRequest $request, CostElement $costElement)
    {
        try {
            $costElement->update($request->validated());

            return redirect()->route('erkap.cost-elements.index')
                ->with('success', 'Elemen biaya berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.cost-elements.edit', $costElement->id)
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(CostElement $costElement)
    {
        try {
            $costElement->delete();

            return redirect()->route('erkap.cost-elements.index')
                ->with('success', 'Elemen biaya berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.cost-elements.index')->with('error', $err->getMessage());
        }
    }
}
