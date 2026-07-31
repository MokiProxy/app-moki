<?php

namespace App\Http\Controllers\ITAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalUsers = User::count();
        $totalRoles = Role::count();
        $roles = Role::withCount('users')->orderBy('users_count', 'desc')->get();

        return view('it-admin.dashboard.index', compact(
            'totalUsers',
            'totalRoles',
            'roles'
        ));
    }
}
