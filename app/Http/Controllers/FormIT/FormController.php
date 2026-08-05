<?php

namespace App\Http\Controllers\FormIT;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\FormitApproval;
use App\Models\SoftwareInstallation;
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

    public function mySubmissions()
    {
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
        $pageName = 'Form Pengajuan Install Software & Aplikasi';
        $pemohon = Employee::with(['division', 'regional'])->where('employee_id', auth()->user()->employee_id)->first();
        $softwareOptions = $this->softwareOptions;

        return view('form-it.forms.software-installation', compact('pageName', 'pemohon', 'softwareOptions'));
    }

    public function softwareInstallationCreate(Request $request)
    {
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
        $softwareInstallation = SoftwareInstallation::with([
            'pemohon', 'pemohon.division', 'pemohon.regional',
            'superior1', 'managerIt',
            'approvals', 'approvals.approver',
        ])->findOrFail($id);

        $pageName = 'Detail Pengajuan Install Software';
        $softwareOptions = $this->softwareOptions;

        return view('form-it.forms.software-installation-show', compact('pageName', 'softwareInstallation', 'softwareOptions'));
    }

    public function showPdf($id)
    {
        $softwareInstallation = SoftwareInstallation::with([
            'pemohon', 'pemohon.division', 'pemohon.regional',
            'superior1', 'managerIt', 'approvals',
        ])->findOrFail($id);

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

        $pdf = Pdf::loadView('laporan', compact(
            'pemohon', 'date', 'softwareOptions', 'selectedSoftware', 'keterangan', 'sign'
        ))->setPaper('a4', 'portrait');

        $pdf->getDomPDF()->getOptions()->set('isImagickEnabled', false);

        return $pdf->stream("install-software-{$id}.pdf");
    }
}
