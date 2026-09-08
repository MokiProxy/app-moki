<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCostElementCategoryRequest;
use App\Http\Requests\UpdateCostElementCategoryRequest;
use App\Models\Erkap\CostElementCategory;
use Exception;

class CostElementCategoryController extends Controller
{
    public function index()
    {
        $pageName = 'Kategori Elemen Biaya';
        $categories = CostElementCategory::paginate(10);

        return view('erkap.cost-element-category.index', compact('pageName', 'categories'));
    }

    public function create()
    {
        $pageName = 'Buat Kategori Elemen Biaya';

        return view('erkap.cost-element-category.create', compact('pageName'));
    }

    public function store(StoreCostElementCategoryRequest $request)
    {
        try {
            CostElementCategory::create($request->validated());

            return redirect()->route('erkap.cost-element-categories.index')
                ->with('success', 'Kategori elemen biaya baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.cost-element-categories.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(CostElementCategory $costElementCategory)
    {
        $pageName = 'Edit Kategori Elemen Biaya';

        return view('erkap.cost-element-category.edit', compact('pageName', 'costElementCategory'));
    }

    public function update(UpdateCostElementCategoryRequest $request, CostElementCategory $costElementCategory)
    {
        try {
            $costElementCategory->update($request->validated());

            return redirect()->route('erkap.cost-element-categories.index')
                ->with('success', 'Kategori elemen biaya berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.cost-element-categories.edit', $costElementCategory->id)
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(CostElementCategory $costElementCategory)
    {
        try {
            if ($costElementCategory->costElements()->exists()) {
                return redirect()->route('erkap.cost-element-categories.index')
                    ->with('error', 'Kategori tidak dapat dihapus karena masih memiliki elemen biaya!');
            }

            $costElementCategory->delete();

            return redirect()->route('erkap.cost-element-categories.index')
                ->with('success', 'Kategori elemen biaya berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.cost-element-categories.index')->with('error', $err->getMessage());
        }
    }
}
