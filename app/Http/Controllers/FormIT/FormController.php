<?php

namespace App\Http\Controllers\FormIT;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\FixedAssetBorrowing;
use App\Models\FormitApproval;
use App\Models\SoftwareInstallation;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FormController extends Controller
{
    public $softwareOptions = [
        [
            'title' => 'Microsoft Office',
            'slug' => 'microsoft-office',
        ],
        [
            'title' => 'Visio',
            'slug' => 'visio',
        ],
        [
            'title' => 'AutoCAD',
            'slug' => 'autocad',
        ],
    ];

    public function forms()
    {
        $isApprover = auth()->user()->hasPermissionTo('form-it.approval.view');
        $canCreateFixedAsset = auth()->user()->hasPermissionTo('form-it.fixed-asset.create');
        $canApproveFixedAsset = auth()->user()->hasPermissionTo('form-it.fixed-asset.approve');
        $canViewFixedAsset = auth()->user()->hasPermissionTo('form-it.fixed-asset.view');

        $forms = [
            [
                "name" => "Pengajuan Install Software & Aplikasi",
                "link" => "form-it.forms.my-submissions",
                "icon" => "mdi-file-document-edit"
            ],
        ];

        if ($canCreateFixedAsset) {
            $forms[] = [
                "name" => "Peminjaman Fixed Asset IT",
                "link" => "form-it.forms.fixed-asset.my-submissions",
                "icon" => "mdi-laptop"
            ];
        }

        $pageName = "Form IT";
        return view('form-it.forms.index', compact("forms", "pageName"));
    }

    public function mySubmissions()
    {
        abort_unless(auth()->user()->hasPermissionTo('form-it.forms.view'), 403);

        $employeeId = auth()->user()->employee_id;

        $submissions = SoftwareInstallation::with(['pemohon', 'pemohon.division', 'approvals'])
            ->where('pemohon_id', $employeeId)
            ->latest()
            ->get();

        $pageName = 'Pengajuan Saya';

        return view('form-it.forms.my-submissions', compact('pageName', 'submissions'));
    }

    public function softwareInstallation()
    {
        abort_unless(auth()->user()->hasPermissionTo('form-it.forms.create'), 403);

        $pageName = 'Form Pengajuan Install Software & Aplikasi';
        $pemohon = Employee::with(['division', 'regional'])->where('employee_id', auth()->user()->employee_id)->first();
        $softwareOptions = $this->softwareOptions;

        return view('form-it.forms.software-installation', compact('pageName', 'pemohon', 'softwareOptions'));
    }

    public function softwareInstallationCreate(Request $request)
    {
        abort_unless(auth()->user()->hasPermissionTo('form-it.forms.create'), 403);

        $validated = $request->validate([
            'softwares' => 'required|array|min:1',
            'softwares.*' => 'string',
            'keterangan' => 'nullable|string|max:1000',
        ]);

        $pemohon = Employee::with(['division', 'regional', 'hirarki'])
            ->where('employee_id', auth()->user()->employee_id)
            ->first();

        $superior1 = $pemohon->superior1();

        $managerIT = Employee::whereHas('hirarki', function ($query) {
            $query->where('jabatan0', 'GM IT');
        })->first();

        DB::beginTransaction();

        try {
            $softwareInstallation = SoftwareInstallation::create([
                'pemohon_id' => $pemohon->employee_id,
                'superior1_id' => $superior1?->employee_id,
                'manager_it_id' => $managerIT?->employee_id,
                'softwares' => $validated['softwares'],
                'keterangan' => $validated['keterangan'] ?? null,
                'status' => 'pending',
            ]);

            if ($superior1) {
                FormitApproval::create([
                    'formit_software_installation_id' => $softwareInstallation->id,
                    'approver_id' => $superior1->employee_id,
                    'level' => 1,
                    'status' => 'pending',
                ]);
            }

            if ($managerIT) {
                FormitApproval::create([
                    'formit_software_installation_id' => $softwareInstallation->id,
                    'approver_id' => $managerIT->employee_id,
                    'level' => 2,
                    'status' => 'pending',
                ]);
            }

            DB::commit();

            return redirect()
                ->route('form-it.forms.software-installation.show', $softwareInstallation->id)
                ->with('success', 'Pengajuan berhasil dibuat! Menunggu approval dari Superior dan Manager IT.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal membuat pengajuan: ' . $e->getMessage());
        }
    }

    public function softwareInstallationShow($id)
    {
        abort_unless(auth()->user()->hasPermissionTo('form-it.forms.view'), 403);

        $softwareInstallation = SoftwareInstallation::with([
            'pemohon',
            'pemohon.division',
            'pemohon.regional',
            'superior1',
            'managerIt',
            'approvals',
            'approvals.approver',
        ])->findOrFail($id);

        $pageName = 'Detail Pengajuan Install Software';
        $softwareOptions = $this->softwareOptions;

        return view('form-it.forms.software-installation-show', compact('pageName', 'softwareInstallation', 'softwareOptions'));
    }

    public function showPdf($id)
    {
        abort_unless(auth()->user()->hasPermissionTo('form-it.forms.view'), 403);

        $softwareInstallation = SoftwareInstallation::with([
            'pemohon',
            'pemohon.division',
            'pemohon.regional',
            'superior1',
            'managerIt',
            'approvals',
        ])->findOrFail($id);

        if ($softwareInstallation->status === 'rejected') {
            abort(404, 'Pengajuan ini telah ditolak.');
        }

        $pemohon = $softwareInstallation->pemohon;
        $date = $softwareInstallation->created_at->format('d F Y');
        $softwareOptions = $this->softwareOptions;
        $selectedSoftware = $softwareInstallation->softwares;
        $keterangan = $softwareInstallation->keterangan;

        $superior1Approval = $softwareInstallation->approvals->where('level', 1)->first();
        $managerITApproval = $softwareInstallation->approvals->where('level', 2)->first();

        $sign = [
            'diajukan' => $softwareInstallation->pemohon->name,
            'diketahui' => $softwareInstallation->superior1,
            'disetujui' => $softwareInstallation->managerIt,
            'diajukan_approved' => true,
            'diketahui_approved' => $superior1Approval?->status === 'approved',
            'disetujui_approved' => $managerITApproval?->status === 'approved',
            'diajukan_date' => $softwareInstallation->created_at->format('d M Y'),
            'diketahui_date' => $superior1Approval?->approved_at?->format('d M Y'),
            'disetujui_date' => $managerITApproval?->approved_at?->format('d M Y'),
        ];

        $pdf = Pdf::loadView('form-it.templates.software-installation', compact(
            'pemohon',
            'date',
            'softwareOptions',
            'selectedSoftware',
            'keterangan',
            'sign'
        ))->setPaper('a4', 'portrait');

        $pdf->getDomPDF()->getOptions()->set('isImagickEnabled', false);

        return $pdf->stream("install-software-{$id}.pdf");
    }

    public function fixedAssetCreate()
    {
        abort_unless(auth()->user()->hasPermissionTo('form-it.fixed-asset.create'), 403);

        $pageName = 'Form Peminjaman Fixed Asset IT';
        $pemohon = Employee::with(['division', 'regional'])
            ->where('employee_id', auth()->user()->employee_id)
            ->first();

        return view('form-it.forms.fixed-asset-create', compact('pageName', 'pemohon'));
    }

    public function fixedAssetStore(Request $request)
    {
        abort_unless(auth()->user()->hasPermissionTo('form-it.fixed-asset.create'), 403);

        $validated = $request->validate([
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
            'tujuan_lokasi' => 'required|string|max:255',
            'keperluan' => 'required|string|max:1000',
            'tipe_perangkat' => 'required|string|max:255',
        ]);

        $pemohon = Employee::with(['division', 'regional'])
            ->where('employee_id', auth()->user()->employee_id)
            ->first();

        DB::beginTransaction();
        try {
            $borrowing = FixedAssetBorrowing::create([
                'pemohon_id' => $pemohon->employee_id,
                'pemohon_name' => $pemohon->name,
                'pemohon_jabatan' => $pemohon->jabatan,
                'pemohon_departemen' => $pemohon->division->name ?? null,
                'pemohon_area' => $pemohon->regional->name ?? null,
                'date_start' => $validated['date_start'],
                'date_end' => $validated['date_end'],
                'tujuan_lokasi' => $validated['tujuan_lokasi'],
                'keperluan' => $validated['keperluan'],
                'tipe_perangkat' => $validated['tipe_perangkat'],
                'status' => 'pending',
            ]);

            DB::commit();

            return redirect()
                ->route('form-it.forms.fixed-asset.my-submissions')
                ->with('success', 'Pengajuan peminjaman fixed asset berhasil dibuat! Menunggu approval.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal membuat pengajuan: ' . $e->getMessage());
        }
    }

    public function fixedAssetMySubmissions()
    {
        abort_unless(auth()->user()->hasPermissionTo('form-it.fixed-asset.view'), 403);

        $employeeId = auth()->user()->employee_id;
        $submissions = FixedAssetBorrowing::with(['pemohon', 'approver'])
            ->where('pemohon_id', $employeeId)
            ->latest()
            ->get();

        $pageName = 'Pengajuan Peminjaman Fixed Asset Saya';
        return view('form-it.forms.fixed-asset-my-submissions', compact('pageName', 'submissions'));
    }

    public function fixedAssetShow($id)
    {
        abort_unless(auth()->user()->hasPermissionTo('form-it.fixed-asset.view'), 403);

        $borrowing = FixedAssetBorrowing::with(['pemohon', 'pemohon.division', 'pemohon.regional', 'approver', 'deviceCompletions'])
            ->findOrFail($id);

        $pageName = 'Detail Pengajuan Peminjaman Fixed Asset';
        return view('form-it.forms.fixed-asset-show', compact('pageName', 'borrowing'));
    }

    public function fixedAssetShowPdf($id)
    {
        abort_unless(auth()->user()->hasPermissionTo('form-it.forms.view'), 403);

        $borrowing = FixedAssetBorrowing::with([
            'pemohon',
            "deviceCompletions",
            "approver",
        ])->findOrFail($id);

        if ($borrowing->status === 'rejected') {
            abort(404, 'Pengajuan ini telah ditolak.');
        }

        $pdf = Pdf::loadView('form-it.templates.fixed-asset', compact('borrowing'))->setPaper('a4', 'portrait');
        $pdf->getDomPDF()->getOptions()->set('isImagickEnabled', false);

        return $pdf->stream("fixed-asset-{$id}.pdf");
    }
}
