<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartmentRiskStrategyRequest;
use App\Http\Requests\UpdateDepartmentRiskStrategyRequest;
use App\Models\Erkap\DepartmentRiskStrategy;
use App\Models\Erkap\RiskIdentification;
use Exception;

class DepartmentRiskStrategyController extends Controller
{
    public function index()
    {
        $pageName = 'Strategi Risiko Departemen';
        $departmentRiskStrategies = DepartmentRiskStrategy::with('riskIdentification')->paginate(10);

        return view('erkap.department-risk-strategy.index', compact('pageName', 'departmentRiskStrategies'));
    }

    public function create()
    {
        $pageName = 'Buat Strategi Risiko Departemen';
        $riskIdentifications = RiskIdentification::all();

        return view('erkap.department-risk-strategy.create', compact('pageName', 'riskIdentifications'));
    }

    public function store(StoreDepartmentRiskStrategyRequest $request)
    {
        try {
            DepartmentRiskStrategy::create($request->validated());

            return redirect()->route('erkap.department-risk-strategies.index')
                ->with('success', 'Strategi risiko departemen baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.department-risk-strategies.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(DepartmentRiskStrategy $departmentRiskStrategy)
    {
        $pageName = 'Edit Strategi Risiko Departemen';
        $riskIdentifications = RiskIdentification::all();

        return view('erkap.department-risk-strategy.edit', compact('pageName', 'departmentRiskStrategy', 'riskIdentifications'));
    }

    public function update(UpdateDepartmentRiskStrategyRequest $request, DepartmentRiskStrategy $departmentRiskStrategy)
    {
        try {
            $departmentRiskStrategy->update($request->validated());

            return redirect()->route('erkap.department-risk-strategies.index')
                ->with('success', 'Strategi risiko departemen berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.department-risk-strategies.edit', $departmentRiskStrategy->id)
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(DepartmentRiskStrategy $departmentRiskStrategy)
    {
        try {
            $departmentRiskStrategy->delete();

            return redirect()->route('erkap.department-risk-strategies.index')
                ->with('success', 'Strategi risiko departemen berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.department-risk-strategies.index')->with('error', $err->getMessage());
        }
    }
}