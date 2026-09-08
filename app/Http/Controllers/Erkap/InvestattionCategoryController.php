<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvestattionCategoryRequest;
use App\Http\Requests\UpdateInvestattionCategoryRequest;
use App\Models\Erkap\InvestattionCategory;
use Exception;

class InvestattionCategoryController extends Controller
{
    public function index()
    {
        $pageName = 'Investattion Categories';
        $categories = InvestattionCategory::orderBy('code')->paginate(10);

        return view('erkap.investattion-category.index', compact('pageName', 'categories'));
    }

    public function create()
    {
        $pageName = 'Buat Investattion Category';

        return view('erkap.investattion-category.create', compact('pageName'));
    }

    public function store(StoreInvestattionCategoryRequest $request)
    {
        try {
            InvestattionCategory::create($request->validated());

            return redirect()->route('erkap.investattion-categories.index')
                ->with('success', 'Investattion category baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.investattion-categories.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(InvestattionCategory $investattionCategory)
    {
        $pageName = 'Edit Investattion Category';

        return view('erkap.investattion-category.edit', compact('pageName', 'investattionCategory'));
    }

    public function update(UpdateInvestattionCategoryRequest $request, InvestattionCategory $investattionCategory)
    {
        try {
            $investattionCategory->update($request->validated());

            return redirect()->route('erkap.investattion-categories.index')
                ->with('success', 'Investattion category berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.investattion-categories.edit', $investattionCategory->id)
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(InvestattionCategory $investattionCategory)
    {
        try {
            $investattionCategory->delete();

            return redirect()->route('erkap.investattion-categories.index')
                ->with('success', 'Investattion category berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.investattion-categories.index')->with('error', $err->getMessage());
        }
    }
}