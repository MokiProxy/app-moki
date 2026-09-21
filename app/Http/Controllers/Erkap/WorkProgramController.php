<?php

namespace App\Http\Controllers\Erkap;

use App\Exports\Erkap\WorkProgramExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkProgramRequest;
use App\Http\Requests\UpdateWorkProgramRequest;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\WorkProgram;
use App\Services\ApprovalService;
use App\Services\ErkapAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class WorkProgramController extends Controller
{
    private const ALLOWED_RATINGS = ['AAA', 'AA', 'A'];

    public function index()
    {
        $pageName = 'Program Kerja';
        $workPrograms = WorkProgram::with(['riskIdentification.departmentTarget.ratingCriteria'])
            ->withCount(['routineCosts', 'investmentPlans'])
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->whereIn('erkap_risk_identification_id', ErkapAccess::riskIdentificationIds());
            })
            ->paginate(10);

        $submittableCount = WorkProgram::query()
            ->whereIn('erkap_risk_identification_id', ErkapAccess::riskIdentificationIds())
            ->whereIn('status', ['draft', 'rejected'])
            ->count();

        return view('erkap.work-program.index', compact('pageName', 'workPrograms', 'submittableCount'));
    }

    public function export()
    {
        $workPrograms = WorkProgram::with(['riskIdentification.departmentTarget.division', 'riskIdentification.departmentTarget.ratingCriteria'])
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->whereIn('erkap_risk_identification_id', ErkapAccess::riskIdentificationIds());
            })
            ->orderBy('id')
            ->get();

        return Excel::download(new WorkProgramExport($workPrograms), 'program-kerja-' . date('Y-m-d-Hi') . '.xlsx');
    }

    public function exportPdf()
    {
        $workPrograms = WorkProgram::with(['riskIdentification.departmentTarget.division', 'riskIdentification.departmentTarget.ratingCriteria'])
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->whereIn('erkap_risk_identification_id', ErkapAccess::riskIdentificationIds());
            })
            ->orderBy('id')
            ->get();

        $pdf = Pdf::loadView('erkap.exports.work-program-pdf', compact('workPrograms'));
        $pdf->setOption('isRemoteEnabled', true);

        return $pdf->download('program-kerja-' . date('Y-m-d-Hi') . '.pdf');
    }

    public function create(Request $request)
    {
        $pageName = 'Buat Program Kerja';

        $selectedRiskId = $request->filled('risk_identification_id')
            ? $request->integer('risk_identification_id')
            : ($request->filled('erkap_risk_identification_id')
                ? $request->integer('erkap_risk_identification_id')
                : null);

        $riskIdentification = null;
        if ($selectedRiskId) {
            $riskIdentification = RiskIdentification::with('departmentTarget.ratingCriteria')
                ->whereIn('id', ErkapAccess::riskIdentificationIds())
                ->find($selectedRiskId);

            if (! $riskIdentification) {
                abort(404);
            }

            if (! $this->checkRating($riskIdentification)) {
                return redirect()->route('erkap.work-programs.create')
                    ->with('error', 'Program kerja hanya bisa dibuat untuk sasaran dengan rating A ke atas.');
            }
        }

        $riskIdentifications = RiskIdentification::with('departmentTarget.ratingCriteria')
            ->whereIn('id', ErkapAccess::riskIdentificationIds())
            ->get();

        return view('erkap.work-program.create', compact('pageName', 'riskIdentifications', 'riskIdentification'));
    }

    public function store(StoreWorkProgramRequest $request)
    {
        try {
            ErkapAccess::assertRiskIdentificationAccess($request->integer('erkap_risk_identification_id'));

            $riskIdentification = RiskIdentification::with('departmentTarget.ratingCriteria')
                ->findOrFail($request->integer('erkap_risk_identification_id'));

            if (! $this->checkRating($riskIdentification)) {
                return redirect()->route('erkap.work-programs.create')
                    ->withInput()
                    ->with('error', 'Program kerja hanya bisa dibuat untuk sasaran dengan rating A ke atas.');
            }

            $data = $request->validated();

            if (empty($data['name'])) {
                $data['name'] = 'Program Kerja: '.$riskIdentification->risk;
            }

            WorkProgram::create($data);

            return redirect()->route('erkap.work-programs.index')
                ->with('success', 'Program kerja baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.work-programs.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(WorkProgram $workProgram)
    {
        ErkapAccess::assertRiskIdentificationAccess($workProgram->erkap_risk_identification_id);

        $pageName = 'Edit Program Kerja';
        $riskIdentifications = RiskIdentification::with('departmentTarget.ratingCriteria')
            ->whereIn('id', ErkapAccess::riskIdentificationIds())
            ->get();

        return view('erkap.work-program.edit', compact('pageName', 'workProgram', 'riskIdentifications'));
    }

    public function update(UpdateWorkProgramRequest $request, WorkProgram $workProgram)
    {
        try {
            ErkapAccess::assertRiskIdentificationAccess($workProgram->erkap_risk_identification_id);
            ErkapAccess::assertRiskIdentificationAccess($request->integer('erkap_risk_identification_id'));

            $riskIdentification = RiskIdentification::with('departmentTarget.ratingCriteria')
                ->findOrFail($request->integer('erkap_risk_identification_id'));

            if (! $this->checkRating($riskIdentification)) {
                return redirect()->route('erkap.work-programs.edit', $workProgram->id)
                    ->withInput()
                    ->with('error', 'Program kerja hanya bisa dibuat untuk sasaran dengan rating A ke atas.');
            }

            $workProgram->update($request->validated());

            return redirect()->route('erkap.work-programs.index')
                ->with('success', 'Program kerja berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.work-programs.edit', $workProgram->id)
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(WorkProgram $workProgram)
    {
        try {
            ErkapAccess::assertRiskIdentificationAccess($workProgram->erkap_risk_identification_id);

            $workProgram->delete();

            return redirect()->route('erkap.work-programs.index')
                ->with('success', 'Program kerja berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.work-programs.index')->with('error', $err->getMessage());
        }
    }

    public function submit(WorkProgram $workProgram)
    {
        try {
            ErkapAccess::assertRiskIdentificationAccess($workProgram->erkap_risk_identification_id);

            ApprovalService::submit($workProgram);

            return redirect()->route('erkap.work-programs.index')
                ->with('success', 'Program kerja berhasil diajukan untuk persetujuan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.work-programs.index')->with('error', $err->getMessage());
        }
    }

    public function submitBatch()
    {
        try {
            $workPrograms = WorkProgram::query()
                ->whereIn('erkap_risk_identification_id', ErkapAccess::riskIdentificationIds())
                ->whereIn('status', ['draft', 'rejected'])
                ->get();

            if ($workPrograms->isEmpty()) {
                return redirect()->route('erkap.work-programs.index')
                    ->with('error', 'Tidak ada program kerja yang dapat diajukan untuk persetujuan.');
            }

            $results = ApprovalService::submitBatch($workPrograms);

            $message = "{$results['submitted']} program kerja berhasil diajukan untuk persetujuan.";

            if ($results['skipped'] > 0) {
                $message .= " {$results['skipped']} dilewati (sudah dalam proses/disetujui).";
            }

            if ($results['failed'] > 0) {
                $message .= " {$results['failed']} gagal diajukan.";
            }

            if ($results['failed'] > 0 && $results['errors']) {
                $message .= ' ('.$results['errors'][0].')';
            }

            return redirect()->route('erkap.work-programs.index')
                ->with($results['failed'] > 0 ? 'error' : 'success', $message);
        } catch (Exception $err) {
            return redirect()->route('erkap.work-programs.index')->with('error', $err->getMessage());
        }
    }

    protected function checkRating(RiskIdentification $riskIdentification): bool
    {
        $rating = optional(optional($riskIdentification->departmentTarget)->ratingCriteria)->rating;

        return in_array($rating, self::ALLOWED_RATINGS, true);
    }
}
