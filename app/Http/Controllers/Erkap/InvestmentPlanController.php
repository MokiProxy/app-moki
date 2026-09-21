<?php

namespace App\Http\Controllers\Erkap;

use App\Exports\Erkap\InvestmentPlanExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvestmentPlanRequest;
use App\Http\Requests\UpdateInvestmentPlanRequest;
use App\Models\Erkap\InvestationCriteria;
use App\Models\Erkap\InvestationType;
use App\Models\Erkap\InvestattionCategory;
use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\WorkProgram;
use App\Services\ApprovalService;
use App\Services\ErkapAccess;
use Exception;
use Maatwebsite\Excel\Facades\Excel;

class InvestmentPlanController extends Controller
{
    public function index()
    {
        $pageName = 'Rencana Investasi (CAPEX)';
        $investmentPlans = InvestmentPlan::with(['workProgram', 'investattionCategory', 'investationType', 'investationCriteria'])
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->whereIn('erkap_work_program_id', ErkapAccess::workProgramIds());
            })
            ->paginate(10);

        $submittableCount = InvestmentPlan::query()
            ->whereIn('erkap_work_program_id', ErkapAccess::workProgramIds())
            ->whereIn('status', ['draft', 'rejected'])
            ->count();

        return view('erkap.investment-plan.index', compact('pageName', 'investmentPlans', 'submittableCount'));
    }

    public function export()
    {
        $investmentPlans = InvestmentPlan::with(['workProgram', 'investattionCategory', 'investationType', 'investationCriteria'])
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->whereIn('erkap_work_program_id', ErkapAccess::workProgramIds());
            })
            ->orderBy('id')
            ->get();

        return Excel::download(new InvestmentPlanExport($investmentPlans), 'rencana-investasi-' . date('Y-m-d-Hi') . '.xlsx');
    }

    public function create()
    {
        $pageName = 'Buat Rencana Investasi';
        $workPrograms = WorkProgram::whereIn('id', ErkapAccess::workProgramIds())->get();
        $investattionCategories = InvestattionCategory::all();
        $investationTypes = InvestationType::all();
        $investationCriterias = InvestationCriteria::all();

        return view('erkap.investment-plan.create', compact('pageName', 'workPrograms', 'investattionCategories', 'investationTypes', 'investationCriterias'));
    }

    public function store(StoreInvestmentPlanRequest $request)
    {
        try {
            ErkapAccess::assertWorkProgramAccess($request->integer('erkap_work_program_id'));

            $data = $request->validated();
            $data['is_kumulatif'] = $request->boolean('is_kumulatif');
            $data['total'] = $this->calcTotal($data);

            InvestmentPlan::create($data);

            return redirect()->route('erkap.investment-plans.index')
                ->with('success', 'Rencana investasi baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.investment-plans.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(InvestmentPlan $investmentPlan)
    {
        ErkapAccess::assertWorkProgramAccess($investmentPlan->erkap_work_program_id);

        $pageName = 'Edit Rencana Investasi';
        $workPrograms = WorkProgram::whereIn('id', ErkapAccess::workProgramIds())->get();
        $investattionCategories = InvestattionCategory::all();
        $investationTypes = InvestationType::all();
        $investationCriterias = InvestationCriteria::all();

        return view('erkap.investment-plan.edit', compact('pageName', 'investmentPlan', 'workPrograms', 'investattionCategories', 'investationTypes', 'investationCriterias'));
    }

    public function update(UpdateInvestmentPlanRequest $request, InvestmentPlan $investmentPlan)
    {
        try {
            ErkapAccess::assertWorkProgramAccess($investmentPlan->erkap_work_program_id);
            ErkapAccess::assertWorkProgramAccess($request->integer('erkap_work_program_id'));

            $data = $request->validated();
            $data['is_kumulatif'] = $request->boolean('is_kumulatif');
            $data['total'] = $this->calcTotal($data);

            $investmentPlan->update($data);

            return redirect()->route('erkap.investment-plans.index')
                ->with('success', 'Rencana investasi berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.investment-plans.edit', $investmentPlan->id)
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(InvestmentPlan $investmentPlan)
    {
        try {
            ErkapAccess::assertWorkProgramAccess($investmentPlan->erkap_work_program_id);

            $investmentPlan->delete();

            return redirect()->route('erkap.investment-plans.index')
                ->with('success', 'Rencana investasi berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.investment-plans.index')->with('error', $err->getMessage());
        }
    }

    public function submit(InvestmentPlan $investmentPlan)
    {
        try {
            ErkapAccess::assertWorkProgramAccess($investmentPlan->erkap_work_program_id);

            ApprovalService::submit($investmentPlan);

            return redirect()->route('erkap.investment-plans.index')
                ->with('success', 'Rencana investasi berhasil diajukan untuk persetujuan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.investment-plans.index')->with('error', $err->getMessage());
        }
    }

    public function submitBatch()
    {
        try {
            $investmentPlans = InvestmentPlan::query()
                ->whereIn('erkap_work_program_id', ErkapAccess::workProgramIds())
                ->whereIn('status', ['draft', 'rejected'])
                ->get();

            if ($investmentPlans->isEmpty()) {
                return redirect()->route('erkap.investment-plans.index')
                    ->with('error', 'Tidak ada rencana investasi yang dapat diajukan untuk persetujuan.');
            }

            $results = ApprovalService::submitBatch($investmentPlans);

            $message = "{$results['submitted']} rencana investasi berhasil diajukan untuk persetujuan.";

            if ($results['skipped'] > 0) {
                $message .= " {$results['skipped']} dilewati (sudah dalam proses/disetujui).";
            }

            if ($results['failed'] > 0) {
                $message .= " {$results['failed']} gagal diajukan.";
            }

            if ($results['failed'] > 0 && $results['errors']) {
                $message .= ' ('.$results['errors'][0].')';
            }

            return redirect()->route('erkap.investment-plans.index')
                ->with($results['failed'] > 0 ? 'error' : 'success', $message);
        } catch (Exception $err) {
            return redirect()->route('erkap.investment-plans.index')->with('error', $err->getMessage());
        }
    }

    private function calcTotal(array $data): float
    {
        return (float) $data['qty'] * (float) $data['unit_price'];
    }
}
