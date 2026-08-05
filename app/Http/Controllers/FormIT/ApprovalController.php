<?php

namespace App\Http\Controllers\FormIT;

use App\Http\Controllers\Controller;
use App\Models\FormitApproval;
use App\Models\SoftwareInstallation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalController extends Controller
{
    public function index()
    {
        $employeeId = auth()->user()->employee_id;

        $pendingApprovals = SoftwareInstallation::with(['pemohon', 'pemohon.division'])
            ->whereHas('approvals', function ($query) use ($employeeId) {
                $query->where('approver_id', $employeeId)
                      ->where('status', 'pending');
            })
            ->where('status', '!=', 'rejected')
            ->latest()
            ->get();

        $historyApprovals = SoftwareInstallation::with(['pemohon', 'pemohon.division', 'approvals'])
            ->whereHas('approvals', function ($query) use ($employeeId) {
                $query->where('approver_id', $employeeId)
                      ->where('status', '!=', 'pending');
            })
            ->latest()
            ->get();

        $pageName = 'Approval Pengajuan IT';

        return view('form-it.approval.index', compact('pageName', 'pendingApprovals', 'historyApprovals'));
    }

    public function show($id)
    {
        $employeeId = auth()->user()->employee_id;

        $softwareInstallation = SoftwareInstallation::with([
            'pemohon', 'pemohon.division', 'pemohon.regional',
            'superior1', 'managerIt',
            'approvals', 'approvals.approver',
        ])->findOrFail($id);

        $myApproval = $softwareInstallation->approvals()
            ->where('approver_id', $employeeId)
            ->first();

        $pageName = 'Detail Pengajuan IT';

        return view('form-it.approval.show', compact('pageName', 'softwareInstallation', 'myApproval'));
    }

    public function process(Request $request, $id)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string|max:500',
        ]);

        $employeeId = auth()->user()->employee_id;

        $softwareInstallation = SoftwareInstallation::findOrFail($id);

        $approval = $softwareInstallation->approvals()
            ->where('approver_id', $employeeId)
            ->where('status', 'pending')
            ->first();

        if (!$approval) {
            return back()->with('error', 'Anda tidak memiliki akses untuk melakukan approval ini.');
        }

        if ($validated['action'] === 'approve') {
            if ($approval->level === 2 && !$softwareInstallation->isApprovedByLevel(1)) {
                return back()->with('error', 'Superior1 harus approve terlebih dahulu sebelum Manager IT.');
            }
        }

        DB::beginTransaction();

        try {
            if ($validated['action'] === 'approve') {
                $approval->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                    'notes' => $validated['notes'] ?? null,
                ]);

                if ($approval->level === 1) {
                    $softwareInstallation->update(['status' => 'process']);
                } elseif ($approval->level === 2) {
                    $softwareInstallation->update([
                        'status' => 'approved',
                        'approved_at' => now(),
                    ]);
                }
            } else {
                $approval->update([
                    'status' => 'rejected',
                    'notes' => $validated['notes'] ?? null,
                ]);

                $softwareInstallation->update([
                    'status' => 'rejected',
                    'rejected_by' => $employeeId,
                    'rejection_reason' => $validated['notes'] ?? null,
                ]);
            }

            DB::commit();

            $message = $validated['action'] === 'approve'
                ? 'Pengajuan berhasil disetujui!'
                : 'Pengajuan berhasil ditolak.';

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses approval: ' . $e->getMessage());
        }
    }
}
