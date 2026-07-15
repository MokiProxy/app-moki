<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{

    public function index() {
        return view('helpdesk.dashboard.index');
    }
}
