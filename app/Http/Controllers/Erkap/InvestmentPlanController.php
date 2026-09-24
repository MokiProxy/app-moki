<?php

namespace App\Http\Controllers\Erkap;

use App\Exports\Erkap\InvestmentPlanExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvestmentPlanRequest;
use App\Http\Requests\UpdateInvestmentPlanRequest;
use App\Models\ChartOfAccount;
use App\Models\Erkap\CostCenter;
use App\Models\Erkap\InvestationCriteria;
use App\Models\Erkap\InvestationType;
use App\Models\Erkap\InvestattionCategory;
use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\WorkProgram;
use App\Services\ApprovalService;
use App\Services\ErkapAccess;
use App\Services\Erkap\RKAPLifecycleService;
use App\Services\Erkap\ZBBReviewService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class InvestmentPlanController extends Controller
{
    public function index(Request $request)
    {
        $pageName = 'Rencana Investasi (CAPEX)';
        $lockedRkaps = RKAP::lockedForInput()->orderByDesc('year')->get();

        $sort = $request->string('sort');
        $order = $request->string('order') === 'desc' ? 'desc' : 'asc';

        $investmentPlans = InvestmentPlan::with(['workProgram', 'investattionCategory', 'investationType', 'investationCriteria', 'chartOfAccount', 'stageGates'])
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->whereIn('erkap_work_program_id', ErkapAccess::workProgramIds());
            })
            ->when($sort === 'priority', function ($query) use ($order) {
                $query->orderByRaw("priority_order IS NULL, priority_order {$order}, id asc");
            }, function ($query) {
                $query->orderByRaw('priority_order IS NULL, priority_order asc, id asc');
            })
            ->paginate(10)
            ->withQueryString();

        $submittableCount = InvestmentPlan::query()
            ->whereIn('erkap_work_program_id', ErkapAccess::workProgramIds())
            ->whereIn('status', ['draft', 'rejected'])
            ->count();

        return view('erkap.investment-plan.index', compact('pageName', 'investmentPlans', 'submittableCount', 'lockedRkaps'));
    }

    public function export()
    {
        $investmentPlans = InvestmentPlan::with(['workProgram', 'investattionCategory', 'investationType', 'investationCriteria', 'chartOfAccount'])
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
        $costCenters = CostCenter::all();
        $chartOfAccounts = ChartOfAccount::expense()->orderBy('code')->get();

        return view('erkap.investment-plan.create', compact('pageName', 'workPrograms', 'investattionCategories', 'investationTypes', 'investationCriterias', 'costCenters', 'chartOfAccounts'));
    }

    public function store(StoreInvestmentPlanRequest $request)
    {
        try {
            ErkapAccess::assertWorkProgramAccess($request->integer('erkap_work_program_id'));
            RKAPLifecycleService::assertNotLocked(
                RKAPLifecycleService::resolveForWorkProgram($request->integer('erkap_work_program_id')),
                'Rencana investasi'
            );

            $data = $request->validated();
            $data['chart_of_account_id'] = $this->resolveChartOfAccountId($data);
            $data['is_kumulatif'] = $request->boolean('is_kumulatif');
            $data['total'] = $this->calcTotal($data);
            $data = $this->resolveAttachments($data, $request);

            $investmentPlan = InvestmentPlan::create($data);
            $investmentPlan->validatePaymentSchedule();

            $this->rebuildZbb($request->integer('erkap_work_program_id'));

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
        $costCenters = CostCenter::all();
        $chartOfAccounts = ChartOfAccount::expense()->orderBy('code')->get();

        return view('erkap.investment-plan.edit', compact('pageName', 'investmentPlan', 'workPrograms', 'investattionCategories', 'investationTypes', 'investationCriterias', 'costCenters', 'chartOfAccounts'));
    }

    public function update(UpdateInvestmentPlanRequest $request, InvestmentPlan $investmentPlan)
    {
        try {
            ErkapAccess::assertWorkProgramAccess($investmentPlan->erkap_work_program_id);
            ErkapAccess::assertWorkProgramAccess($request->integer('erkap_work_program_id'));

            $targetWorkProgramId = $request->filled('erkap_work_program_id')
                ? $request->integer('erkap_work_program_id')
                : $investmentPlan->erkap_work_program_id;

            RKAPLifecycleService::assertNotLocked(
                RKAPLifecycleService::resolveForWorkProgram($targetWorkProgramId),
                'Rencana investasi'
            );

            $data = $request->validated();
            $data['chart_of_account_id'] = $this->resolveChartOfAccountId($data, $investmentPlan);
            $data['is_kumulatif'] = $request->boolean('is_kumulatif');
            $data['total'] = $this->calcTotal($data);
            $data = $this->resolveAttachments($data, $request, $investmentPlan);

            $investmentPlan->update($data);

            $investmentPlan->refresh();
            $investmentPlan->validatePaymentSchedule();

            $this->rebuildZbb($targetWorkProgramId);

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

            $investmentPlan->validatePaymentSchedule();
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

    public function downloadProposal(InvestmentPlan $investmentPlan)
    {
        ErkapAccess::assertWorkProgramAccess($investmentPlan->erkap_work_program_id);

        if (! $investmentPlan->proposal_file_path) {
            return redirect()->route('erkap.investment-plans.index')
                ->with('error', 'Proposal tidak tersedia untuk rencana investasi ini.');
        }

        return Storage::disk('public')->download(
            $investmentPlan->proposal_file_path,
            $investmentPlan->proposal_original_name ?: basename($investmentPlan->proposal_file_path)
        );
    }

    private function resolveChartOfAccountId(array $data, ?InvestmentPlan $investmentPlan = null): ?int
    {
        if (filled($data['chart_of_account_id'] ?? null)) {
            return (int) $data['chart_of_account_id'];
        }

        return $investmentPlan?->chart_of_account_id ? (int) $investmentPlan->chart_of_account_id : null;
    }

    private function resolveAttachments(array $data, $request, ?InvestmentPlan $investmentPlan = null): array
    {
        $data = $data + [
            'proposal_file_path' => $investmentPlan?->proposal_file_path,
            'proposal_original_name' => $investmentPlan?->proposal_original_name,
            'cba_json' => $investmentPlan?->cba_json,
            'cba_attachment_path' => $investmentPlan?->cba_attachment_path,
        ];

        if ($request->hasFile('proposal')) {
            $file = $request->file('proposal');
            $data['proposal_file_path'] = $file->store('investment-plans/proposals', 'public');
            $data['proposal_original_name'] = $file->getClientOriginalName();
        }

        if ($request->hasFile('cba_attachment')) {
            $data['cba_attachment_path'] = $request->file('cba_attachment')->store('investment-plans/cba', 'public');
        }

        $cba = [
            'npv' => $request->filled('cba_npv') ? (float) $request->input('cba_npv') : null,
            'irr' => $request->filled('cba_irr') ? (float) $request->input('cba_irr') : null,
            'payback' => $request->filled('cba_payback') ? (float) $request->input('cba_payback') : null,
            'justification' => $request->input('cba_justification'),
        ];

        if (collect($cba)->filter(fn ($value) => filled($value))->isNotEmpty()) {
            $data['cba_json'] = $cba;
        }

        return $data;
    }

    private function calcTotal(array $data): float
    {
        return (float) $data['qty'] * (float) $data['unit_price'];
    }

    private function rebuildZbb(?int $workProgramId): void
    {
        $rkap = RKAPLifecycleService::resolveForWorkProgram($workProgramId);

        if ($rkap) {
            ZBBReviewService::buildReviews($rkap);
        }
    }
}
