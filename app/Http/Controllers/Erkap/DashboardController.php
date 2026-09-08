<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return view('erkap.dashboard.index');
    }
}
