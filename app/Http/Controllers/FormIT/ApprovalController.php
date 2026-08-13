<?php

namespace App\Http\Controllers\FormIT;

use App\Http\Controllers\Controller;
use App\Models\FixedAssetBorrowing;
use App\Models\FormitApproval;
use App\Models\SoftwareInstallation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalController extends Controller
{

    public function index()
    {
        abort_unless(auth()->user()->hasPermissionTo('form-it.approval.view'), 403);

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
        abort_unless(auth()->user()->hasPermissionTo('form-it.approval.view'), 403);

        $employeeId = auth()->user()->employee_id;

        $softwareInstallation = SoftwareInstallation::with([
            'pemohon',
            'pemohon.division',
            'pemohon.regional',
            'superior1',
            'managerIt',
            'approvals',
            'approvals.approver',
        ])->findOrFail($id);

        $myApproval = $softwareInstallation->approvals()
            ->where('approver_id', $employeeId)
            ->first();

        $pageName = 'Detail Pengajuan IT';

        return view('form-it.approval.show', compact('pageName', 'softwareInstallation', 'myApproval'));
    }

    public function process(Request $request, $id)
    {
        abort_unless(auth()->user()->hasPermissionTo('form-it.approval.process'), 403);

        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string|max:500',
        ]);

        $employeeId = auth()->user()->employee_id;

        $softwareInstallation = SoftwareInstallation::findOrFail($id);

        if (in_array($softwareInstallation->status, ['rejected', 'approved'])) {
            return back()->with('error', 'Pengajuan ini sudah tidak dapat diproses.');
        }

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

                $softwareInstallation->approvals()
                    ->where('status', 'pending')
                    ->update(['status' => 'rejected']);

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

    public function fixedAssetIndex()
    {
        abort_unless(auth()->user()->hasPermissionTo('form-it.fixed-asset.approve'), 403);

        $employeeId = auth()->user()->employee_id;

        $pendingApprovals = FixedAssetBorrowing::with(['pemohon', 'pemohon.division'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        $historyApprovals = FixedAssetBorrowing::with(['pemohon', 'pemohon.division'])
            ->where('status', '!=', 'pending')
            ->latest()
            ->get();

        $pageName = 'Approval Peminjaman Fixed Asset';
        return view('form-it.approval.fixed-asset-index', compact('pageName', 'pendingApprovals', 'historyApprovals'));
    }

    public function fixedAssetShow($id)
    {
        abort_unless(auth()->user()->hasPermissionTo('form-it.fixed-asset.approve'), 403);

        $borrowing = FixedAssetBorrowing::with(['pemohon', 'pemohon.division', 'pemohon.regional', 'approver', 'deviceCompletions'])
            ->findOrFail($id);

        $pageName = 'Review Pengajuan Peminjaman Fixed Asset';
        return view('form-it.approval.fixed-asset-show', compact('pageName', 'borrowing'));
    }

    public function fixedAssetProcess(Request $request, $id)
    {
        abort_unless(auth()->user()->hasPermissionTo('form-it.fixed-asset.approve'), 403);

        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string|max:500',
            'penyerahkan_name' => 'required_if:action,approve|string|max:255',
            'penyerahkan_jabatan' => 'required_if:action,approve|string|max:255',
            'penyerahkan_departemen' => 'required_if:action,approve|string|max:255',
            'penyerahkan_area' => 'required_if:action,approve|string|max:255',
            'device_completions' => 'required_if:action,approve|array|min:1',
            'device_completions.*.uraian' => 'required|string|max:255',
            'device_completions.*.ada' => 'nullable|boolean',
            'device_completions.*.tidak_ada' => 'nullable|boolean',
            'device_completions.*.keterangan' => 'nullable|string|max:255',
        ]);

        $employeeId = auth()->user()->employee_id;
        $borrowing = FixedAssetBorrowing::findOrFail($id);

        if ($borrowing->status !== 'pending') {
            return back()->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        DB::beginTransaction();
        try {
            if ($validated['action'] === 'approve') {
                $borrowing->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                    'approver_id' => $employeeId,
                    'penyerahkan_name' => $validated['penyerahkan_name'],
                    'penyerahkan_jabatan' => $validated['penyerahkan_jabatan'],
                    'penyerahkan_departemen' => $validated['penyerahkan_departemen'],
                    'penyerahkan_area' => $validated['penyerahkan_area'],
                ]);

                foreach ($validated['device_completions'] as $device) {
                    $borrowing->deviceCompletions()->create([
                        'uraian' => $device['uraian'],
                        'ada' => $device['ada'] ?? false,
                        'tidak_ada' => $device['tidak_ada'] ?? false,
                        'keterangan' => $device['keterangan'] ?? null,
                    ]);
                }

                $message = 'Pengajuan peminjaman fixed asset berhasil disetujui!';
            } else {
                $borrowing->update([
                    'status' => 'rejected',
                    'rejected_by' => $employeeId,
                    'rejection_reason' => $validated['notes'] ?? null,
                    'rejected_at' => now(),
                ]);

                $message = 'Pengajuan peminjaman fixed asset berhasil ditolak.';
            }

            DB::commit();
            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses approval: ' . $e->getMessage());
        }
    }
}
