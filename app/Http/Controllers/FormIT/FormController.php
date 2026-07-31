<?php

namespace App\Http\Controllers\FormIT;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class FormController extends Controller
{
    public $softwareOptions = [
        [
            "title" => "Microsoft Office",
            "slug" => "microsoft-office"
        ],
        [
            "title" => "Visio",
            "slug" => "visio"
        ],
        [
            "title" => "AutoCAD",
            "slug" => "autocad"
        ]
    ];
    public function softwareInstallation()
    {
        $pageName = "Form Pengajuan Install Software & Aplikasi";
        $pemohon = Employee::with(["division", "regional"])->where("employee_id", auth()->user()->employee_id)->first();
        $softwareOptions = $this->softwareOptions;

        return view("form-it.forms.software-installation", compact("pageName", "pemohon", "softwareOptions"));
    }

    public function softwareInstallationCreate(Request $request)
    {
        $pemohon = Employee::with(["division", "regional"])->where("employee_id", auth()->user()->employee_id)->first();
        $date = date('d F Y');
        $softwareOptions = $this->softwareOptions;
        $selectedSoftware = $request["softwares"];
        $keterangan = $request["keterangan"];
        $userManager = 
        // $sign = [
        //     "diajukan" => $request["name"],
        //     "diketahui" =>
        // ];

        $pdf = Pdf::loadView('laporan', compact("pemohon", "date", "softwareOptions", "selectedSoftware", "keterangan"))->setPaper('a4', 'portrait');
        $pdf->getDomPDF()->getOptions()->set('isImagickEnabled', false);
        return $pdf->stream('install-software.pdf');
    }
}
