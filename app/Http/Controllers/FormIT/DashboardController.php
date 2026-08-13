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

        return view('form-it.dashboard.index');
    }
}
