<?php

namespace App\Http\Controllers\Erkap;

use App\Exports\Erkap\Form1Export;
use App\Exports\Erkap\Form1TemplateExport;
use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\RiskIdentification;
use App\Services\ErkapAccess;
use App\Services\Form1ImportExportService;
use Exception;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class Form1Controller extends Controller
{
    public function index()
    {
        $pageName = 'Form 1 - Sasaran & Asesmen Risiko';
        $rkaps = RKAP::orderByDesc('year')->get();

        $divisionQuery = Division::query()
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->where('id', ErkapAccess::divisionId());
            });

        $divisions = $divisionQuery->get();

        return view('erkap.form1.index', compact('pageName', 'rkaps', 'divisions'));
    }

    public function template()
    {
        return Excel::download(new Form1TemplateExport(), 'template-form1.xlsx');
    }

    public function export(Request $request)
    {
        try {
            $request->validate([
                'erkap_rkap_id' => ['required', 'integer', 'exists:erkap_rkap,id'],
                'division_id' => ['nullable', 'integer', 'exists:divisions,id'],
            ]);

            $riskIdentifications = RiskIdentification::with([
                'departmentTarget.companyTarget',
                'departmentTarget.ratingCriteria',
                'riskType',
                'riskTaxonomy',
                'reasons',
                'impacts',
                'analysis.riskProbability',
                'analysis.riskImpact',
                'analysis.riskScoreValue',
                'departmentRiskStrategies',
                'workPrograms',
            ])
                ->whereHas('departmentTarget.companyTarget', function ($query) use ($request) {
                    $query->where('erkap_rkap_id', $request->integer('erkap_rkap_id'));
                })
                ->when($request->filled('division_id'), function ($query) use ($request) {
                    $query->where('erkap_department_target_id', function ($q) use ($request) {
                        $q->select('id')->from('erkap_department_targets')
                            ->where('division_id', $request->integer('division_id'));
                    });
                })
                ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                    $query->whereIn('erkap_department_target_id', ErkapAccess::departmentTargetIds());
                })
                ->orderBy('id')
                ->get();

            return Excel::download(new Form1Export($riskIdentifications), 'form1-' . date('Y-m-d-Hi') . '.xlsx');
        } catch (Exception $err) {
            return redirect()->route('erkap.form1.index')
                ->with('error', $err->getMessage());
        }
    }

    public function import(Request $request, Form1ImportExportService $service)
    {
        try {
            $request->validate([
                'erkap_rkap_id' => ['required', 'integer', 'exists:erkap_rkap,id'],
                'division_id' => ['required', 'integer', 'exists:divisions,id'],
                'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
            ]);

            if (ErkapAccess::isDivisionScoped()) {
                ErkapAccess::assertDivisionAccess($request->integer('division_id'));
            }

            $count = $service->import(
                $request->file('file'),
                $request->integer('erkap_rkap_id'),
                $request->integer('division_id')
            );

            return redirect()->route('erkap.form1.index')
                ->with('success', "Import Form 1 berhasil: {$count} risiko diproses.");
        } catch (\Illuminate\Validation\ValidationException $err) {
            $message = $err->validator->errors()->first();

            return redirect()->route('erkap.form1.index')
                ->withInput()
                ->with('error', $message ?: 'Data Form 1 tidak valid.');
        } catch (Exception $err) {
            return redirect()->route('erkap.form1.index')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                ]);
        }
    }
}