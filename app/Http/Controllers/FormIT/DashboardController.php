<?php

namespace App\Http\Controllers\FormIT;

use App\Http\Controllers\Controller;
use App\Models\FormitApproval;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index() {
        $employeeId = auth()->user()->employee_id;
        $isApprover = FormitApproval::where('approver_id', $employeeId)->exists();

        $forms = [
            [
                "name" => "Pengajuan Install Software & Aplikasi",
                "link" => "form-it.forms.software-installation",
                "icon" => "mdi-file-document-edit"
            ],
            [
                "name" => "Pengajuan Saya",
                "link" => "form-it.forms.my-submissions",
                "icon" => "mdi-file-document-multiple"
            ],
        ];

        if ($isApprover) {
            $forms[] = [
                "name" => "Approval Pengajuan IT",
                "link" => "form-it.approval.index",
                "icon" => "mdi-file-document-check"
            ];
        }

        $forms[] = [
            "name" => "Peminjaman Fixed Asset IT",
            "link" => "form-it.index",
            "icon" => "mdi-laptop"
        ];

        return view('form-it.dashboard.index', compact("forms"));
    }
}
