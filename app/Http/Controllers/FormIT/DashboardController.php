<?php

namespace App\Http\Controllers\FormIT;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index() {
        $isApprover = auth()->user()->hasPermissionTo('form-it.approval.view');
        $canCreateFixedAsset = auth()->user()->hasPermissionTo('form-it.fixed-asset.create');
        $canApproveFixedAsset = auth()->user()->hasPermissionTo('form-it.fixed-asset.approve');
        $canViewFixedAsset = auth()->user()->hasPermissionTo('form-it.fixed-asset.view');

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

        if ($canCreateFixedAsset) {
            $forms[] = [
                "name" => "Peminjaman Fixed Asset IT",
                "link" => "form-it.forms.fixed-asset.create",
                "icon" => "mdi-laptop"
            ];
        }

        if ($canViewFixedAsset) {
            $forms[] = [
                "name" => "Pengajuan Fixed Asset Saya",
                "link" => "form-it.forms.fixed-asset.my-submissions",
                "icon" => "mdi-laptop"
            ];
        }

        if ($isApprover) {
            $forms[] = [
                "name" => "Approval Pengajuan IT",
                "link" => "form-it.approval.index",
                "icon" => "mdi-file-document-check"
            ];
        }

        if ($canApproveFixedAsset) {
            $forms[] = [
                "name" => "Approval Fixed Asset",
                "link" => "form-it.approval.fixed-asset.index",
                "icon" => "mdi-check-square"
            ];
        }

        return view('form-it.dashboard.index', compact("forms"));
    }
}
