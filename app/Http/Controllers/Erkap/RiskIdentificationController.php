<?php

namespace App\Http\Controllers\Erkap;

use App\Exports\Erkap\RiskIdentificationExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRiskIdentificationRequest;
use App\Http\Requests\UpdateRiskIdentificationRequest;
use App\Models\Erkap\DepartmentTarget;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RiskTaxonomy;
use App\Models\Erkap\RiskType;
use App\Services\ApprovalService;
use App\Services\ErkapAccess;
use App\Services\ErkapEvaluationLock;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Maatwebsite\Excel\Facades\Excel;

class RiskIdentificationController extends Controller
{
    public function index()
    {
        $pageName = 'Identifikasi Risiko';
        $riskIdentifications = RiskIdentification::with(['departmentTarget.ratingCriteria', 'riskType', 'riskTaxonomy'])
            ->withCount('workPrograms')
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->whereIn('erkap_department_target_id', ErkapAccess::departmentTargetIds());
            })
            ->paginate(10);

        $submittableCount = RiskIdentification::query()
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->whereIn('erkap_department_target_id', ErkapAccess::departmentTargetIds());
            })
            ->whereIn('status', ['draft', 'rejected'])
            ->count();

        return view('erkap.risk-identification.index', compact('pageName', 'riskIdentifications', 'submittableCount'));
    }

    public function export()
    {
        $riskIdentifications = RiskIdentification::with(['departmentTarget.division', 'departmentTarget.ratingCriteria', 'riskType', 'riskTaxonomy'])
            ->withCount('workPrograms')
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->whereIn('erkap_department_target_id', ErkapAccess::departmentTargetIds());
            })
            ->orderBy('id')
            ->get();

        return Excel::download(new RiskIdentificationExport($riskIdentifications), 'identifikasi-risiko-'.date('Y-m-d-Hi').'.xlsx');
    }

    public function exportPdf()
    {
        $riskIdentifications = RiskIdentification::with(['departmentTarget.division', 'departmentTarget.ratingCriteria', 'riskType', 'riskTaxonomy'])
            ->withCount('workPrograms')
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->whereIn('erkap_department_target_id', ErkapAccess::departmentTargetIds());
            })
            ->orderBy('id')
            ->get();

        $pdf = Pdf::loadView('erkap.exports.risk-identification-pdf', compact('riskIdentifications'));
        $pdf->setOption('isRemoteEnabled', true);

        return $pdf->download('identifikasi-risiko-'.date('Y-m-d-Hi').'.pdf');
    }

    public function create()
    {
        $pageName = 'Buat Identifikasi Risiko';
        $departmentTargets = DepartmentTarget::query()
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->where('division_id', ErkapAccess::divisionId());
            })
            ->get();
        $riskTypes = RiskType::all();
        $riskTaxonomies = RiskTaxonomy::all();

        return view('erkap.risk-identification.create', compact('pageName', 'departmentTargets', 'riskTypes', 'riskTaxonomies'));
    }

    public function store(StoreRiskIdentificationRequest $request)
    {
        try {
            ErkapAccess::assertDepartmentTargetAccess($request->integer('erkap_department_target_id'));

            RiskIdentification::create($request->validated());

            return redirect()->route('erkap.risk-identifications.index')
                ->with('success', 'Identifikasi risiko baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-identifications.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(RiskIdentification $riskIdentification)
    {
        ErkapAccess::assertDepartmentTargetAccess($riskIdentification->erkap_department_target_id);

        try {
            ErkapEvaluationLock::assertRiskEditable($riskIdentification);
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-identifications.index')
                ->with('error', $err->getMessage());
        }

        $pageName = 'Edit Identifikasi Risiko';
        $departmentTargets = DepartmentTarget::query()
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->where('division_id', ErkapAccess::divisionId());
            })
            ->get();
        $riskTypes = RiskType::all();
        $riskTaxonomies = RiskTaxonomy::all();

        return view('erkap.risk-identification.edit', compact('pageName', 'riskIdentification', 'departmentTargets', 'riskTypes', 'riskTaxonomies'));
    }

    public function update(UpdateRiskIdentificationRequest $request, RiskIdentification $riskIdentification)
    {
        try {
            ErkapAccess::assertDepartmentTargetAccess($riskIdentification->erkap_department_target_id);
            ErkapAccess::assertDepartmentTargetAccess($request->integer('erkap_department_target_id'));
            ErkapEvaluationLock::assertRiskEditable($riskIdentification);

            $riskIdentification->update($request->validated());

            return redirect()->route('erkap.risk-identifications.index')
                ->with('success', 'Identifikasi risiko berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-identifications.edit', $riskIdentification->id)
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(RiskIdentification $riskIdentification)
    {
        try {
            ErkapAccess::assertDepartmentTargetAccess($riskIdentification->erkap_department_target_id);
            ErkapEvaluationLock::assertRiskEditable($riskIdentification);

            if ($riskIdentification->hasWorkProgram()) {
                return redirect()->route('erkap.risk-identifications.index')
                    ->with('error', 'Risiko yang sudah memiliki program kerja tidak bisa dihapus.');
            }

            $riskIdentification->delete();

            return redirect()->route('erkap.risk-identifications.index')
                ->with('success', 'Identifikasi risiko berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-identifications.index')->with('error', $err->getMessage());
        }
    }

    public function submit(RiskIdentification $riskIdentification)
    {
        try {
            ErkapAccess::assertDepartmentTargetAccess($riskIdentification->erkap_department_target_id);
            $riskIdentification->validateHasStrategyAndWorkProgram();

            ApprovalService::submit($riskIdentification);

            return redirect()->route('erkap.risk-identifications.index')
                ->with('success', 'Form 1 (identifikasi risiko) berhasil diajukan ke Dept. Manajemen Risiko untuk evaluasi!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-identifications.index')->with('error', $err->getMessage());
        }
    }

    public function submitBatch()
    {
        try {
            $riskIdentifications = RiskIdentification::query()
                ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                    $query->whereIn('erkap_department_target_id', ErkapAccess::departmentTargetIds());
                })
                ->whereIn('status', ['draft', 'rejected'])
                ->get();

            if ($riskIdentifications->isEmpty()) {
                return redirect()->route('erkap.risk-identifications.index')
                    ->with('error', 'Tidak ada Form 1 yang dapat diajukan untuk evaluasi.');
            }

            $results = ApprovalService::submitBatch($riskIdentifications);

            $message = "{$results['submitted']} Form 1 berhasil diajukan untuk evaluasi Manajemen Risiko.";

            if ($results['skipped'] > 0) {
                $message .= " {$results['skipped']} dilewati (sudah dalam proses/disetujui).";
            }

            if ($results['failed'] > 0) {
                $message .= " {$results['failed']} gagal diajukan.";
            }

            if ($results['failed'] > 0 && $results['errors']) {
                $message .= ' ('.$results['errors'][0].')';
            }

            return redirect()->route('erkap.risk-identifications.index')
                ->with($results['failed'] > 0 ? 'error' : 'success', $message);
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-identifications.index')->with('error', $err->getMessage());
        }
    }
}
