<?php

namespace App\Http\Controllers\ITAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index() {
        return view("it-admin.dashboard.index");
    }
}
