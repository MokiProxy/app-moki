<?php

namespace App\Http\Controllers\FormIT;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index() {
        $forms = [
            [
                "name" => "Pengajuan Install Software & Aplikasi",
                "link" => "form-it.forms.software-installation"
            ],
            [
                "name" => "Peminjaman Fixed Asset IT",
                "link" => "form-it.index"
            ]
        ];
        return view('form-it.dashboard.index', compact("forms"));
    }
}
