<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\Erkap\ProcessApprovalRequest;
use App\Models\Division;
use App\Models\Erkap\Approval;
use App\Services\ApprovalService;
use Illuminate\Support\Collection;

class ApprovalController extends Controller
{
    public function index()
    {
        $pageName = 'Approval Dokumen ERKAP';

        $query = Approval::query()
            ->with(['approvalable', 'approver'])
            ->where('approver_id', auth()->id());

        $pendingApprovals = (clone $query)
            ->where('status', 'pending')
            ->latest()
            ->get()
            ->filter(fn (Approval $approval) => ApprovalService::typeFor($approval->approvalable) !== null)
            ->values();

        $historyApprovals = (clone $query)
            ->where('status', '!=', 'pending')
            ->latest()
            ->get()
            ->filter(fn (Approval $approval) => ApprovalService::typeFor($approval->approvalable) !== null)
            ->values();

        $pendingGroups = $pendingApprovals
            ->groupBy(fn (Approval $approval) => ApprovalService::divisionFor($approval->approvalable)?->id ?? 'lainnya')
            ->map(function (Collection $approvals, $key) {
                $division = $key === 'lainnya' ? null : Division::find($key);

                return [
                    'key' => $key,
                    'division' => $division,
                    'division_label' => $division?->name ?? 'Tanpa Divisi',
                    'approvals' => $approvals,
                ];
            })
            ->values();

        return view('erkap.approvals.index', compact('pageName', 'pendingGroups', 'historyApprovals'));
    }

    public function history()
    {
        $pageName = 'Riwayat Approval ERKAP';

        $pendingGroups = new Collection;

        $historyApprovals = Approval::query()
            ->with(['approvalable', 'approver'])
            ->where('approver_id', auth()->id())
            ->where('status', '!=', 'pending')
            ->latest()
            ->get()
            ->filter(fn (Approval $approval) => ApprovalService::typeFor($approval->approvalable) !== null)
            ->values();

        return view('erkap.approvals.index', compact('pageName', 'pendingGroups', 'historyApprovals'));
    }

    public function division(string $divisionKey)
    {
        $pageName = 'Persetujuan per Divisi';

        $isGeneral = $divisionKey === 'lainnya';
        $division = $isGeneral ? null : Division::findOrFail((int) $divisionKey);

        $approvals = Approval::query()
            ->with(['approvalable', 'approver'])
            ->where('approver_id', auth()->id())
            ->where('status', 'pending')
            ->latest()
            ->get()
            ->filter(fn (Approval $approval) => ApprovalService::typeFor($approval->approvalable) !== null)
            ->filter(function (Approval $approval) use ($isGeneral, $division) {
                $approvalDivision = ApprovalService::divisionFor($approval->approvalable);

                return $isGeneral
                    ? $approvalDivision === null
                    : $approvalDivision?->id === $division->id;
            })
            ->values();

        $groups = $approvals
            ->groupBy(fn (Approval $approval) => ApprovalService::typeFor($approval->approvalable))
            ->map(function (Collection $approvals, $type) {
                return [
                    'type' => $type,
                    'label' => ApprovalService::documentTypes()[$type]['label'],
                    'view' => str_replace('_', '-', $type),
                    'items' => $approvals,
                ];
            })
            ->values();

        return view('erkap.approvals.division', compact('pageName', 'division', 'isGeneral', 'groups'));
    }

    public function show(string $type, $id)
    {
        $pageName = 'Detail Persetujuan';

        $model = ApprovalService::resolveModel($type, $id);

        $approvals = $model->approvals()->with('approver')->orderBy('level')->get();
        $myApproval = $model->approvals()->where('approver_id', auth()->id())->first();

        if (! $myApproval && ! auth()->user()->hasAnyRole(['erkap-admin', 'admin', 'super-admin'])) {
            abort(403);
        }

        $nextPending = $model->approvals()->where('status', 'pending')->orderBy('level')->first();
        $hasTurn = $nextPending && $myApproval && (int) $nextPending->id === (int) $myApproval->id;
        $canProcess = $model->status === 'submitted' && $hasTurn;

        $typeMeta = ApprovalService::documentTypes()[$type];

        return view('erkap.approvals.show', compact('pageName', 'model', 'approvals', 'myApproval', 'type', 'typeMeta', 'canProcess'));
    }

    public function approve(ProcessApprovalRequest $request, string $type, $id)
    {
        return $this->process($request, $type, $id, 'approve');
    }

    public function reject(ProcessApprovalRequest $request, string $type, $id)
    {
        return $this->process($request, $type, $id, 'reject');
    }

    protected function process(ProcessApprovalRequest $request, string $type, $id, string $action)
    {
        $model = ApprovalService::resolveModel($type, $id);

        try {
            if ($action === 'approve') {
                ApprovalService::approve($model, auth()->user(), $request->get('notes'));
                $message = 'Dokumen berhasil disetujui!';
            } else {
                ApprovalService::reject($model, auth()->user(), $request->get('notes'));
                $message = 'Dokumen berhasil ditolak.';
            }

            return redirect()->route('erkap.approvals.show', [$type, $id])->with('success', $message);
        } catch (\Exception $err) {
            return redirect()->route('erkap.approvals.show', [$type, $id])->with('error', $err->getMessage());
        }
    }
}