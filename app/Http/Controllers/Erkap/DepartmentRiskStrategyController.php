<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartmentRiskStrategyRequest;
use App\Http\Requests\UpdateDepartmentRiskStrategyRequest;
use App\Models\Erkap\DepartmentRiskStrategy;
use App\Models\Erkap\RiskIdentification;
use App\Services\ErkapAccess;
use App\Services\ErkapEvaluationLock;
use Exception;

class DepartmentRiskStrategyController extends Controller
{
    public function index()
    {
        $pageName = 'Strategi Risiko Departemen';
        $departmentRiskStrategies = DepartmentRiskStrategy::with('riskIdentification')
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->whereIn('erkap_risk_identification_id', ErkapAccess::riskIdentificationIds());
            })
            ->paginate(10);

        return view('erkap.department-risk-strategy.index', compact('pageName', 'departmentRiskStrategies'));
    }

    public function create()
    {
        $pageName = 'Buat Strategi Risiko Departemen';
        $riskIdentifications = RiskIdentification::whereIn('id', ErkapAccess::riskIdentificationIds())->get();

        return view('erkap.department-risk-strategy.create', compact('pageName', 'riskIdentifications'));
    }

    public function store(StoreDepartmentRiskStrategyRequest $request)
    {
        try {
            ErkapAccess::assertRiskIdentificationAccess($request->integer('erkap_risk_identification_id'));
            ErkapEvaluationLock::assertRiskEditable(RiskIdentification::findOrFail($request->integer('erkap_risk_identification_id')));

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
        ErkapAccess::assertRiskIdentificationAccess($departmentRiskStrategy->erkap_risk_identification_id);

        try {
            ErkapEvaluationLock::assertRiskEditable(RiskIdentification::find($departmentRiskStrategy->erkap_risk_identification_id));
        } catch (Exception $err) {
            return redirect()->route('erkap.department-risk-strategies.index')
                ->with('error', $err->getMessage());
        }

        $pageName = 'Edit Strategi Risiko Departemen';
        $riskIdentifications = RiskIdentification::whereIn('id', ErkapAccess::riskIdentificationIds())->get();

        return view('erkap.department-risk-strategy.edit', compact('pageName', 'departmentRiskStrategy', 'riskIdentifications'));
    }

    public function update(UpdateDepartmentRiskStrategyRequest $request, DepartmentRiskStrategy $departmentRiskStrategy)
    {
        try {
            ErkapAccess::assertRiskIdentificationAccess($departmentRiskStrategy->erkap_risk_identification_id);
            ErkapAccess::assertRiskIdentificationAccess($request->integer('erkap_risk_identification_id'));
            ErkapEvaluationLock::assertRiskEditable(RiskIdentification::find($departmentRiskStrategy->erkap_risk_identification_id));
            ErkapEvaluationLock::assertRiskEditable(RiskIdentification::find($request->integer('erkap_risk_identification_id')));

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
            ErkapAccess::assertRiskIdentificationAccess($departmentRiskStrategy->erkap_risk_identification_id);
            ErkapEvaluationLock::assertRiskEditable(RiskIdentification::find($departmentRiskStrategy->erkap_risk_identification_id));

            $departmentRiskStrategy->delete();

            return redirect()->route('erkap.department-risk-strategies.index')
                ->with('success', 'Strategi risiko departemen berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.department-risk-strategies.index')->with('error', $err->getMessage());
        }
    }
}
