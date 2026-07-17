<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login()
    {
        return view('components.login');
    }

    public function authenticated(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required'],
        ]);

        // Field form namanya "username" (biar UI-nya tetap "Username"),
        // tapi kolom di database aslinya "employee_id" (isinya NIP, mis. EMP0001).
        $credentials = [
            'employee_id' => $request->username,
            'password'    => $request->password,
        ];

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // LOGIKA ROLE: users.employee_id sekarang berisi NIP (string, mis. EMP0001),
            // sedangkan user_roles.employee_id berisi PK employees.id (angka).
            // Jadi perlu translate dulu dari NIP -> PK employees.id sebelum cari role-nya.
            $employee = DB::table('employees')->where('employee_id', $user->employee_id)->first();

            $userRole = $employee
                ? DB::table('user_roles')->where('employee_id', $employee->id)->first()
                : null;

            $request->session()->regenerate();

            // SIMPAN ROLE ID KE SESSION (TETAP DIPERTAHANKAN)
            session(['user_role' => $userRole ? $userRole->role_id : null]);

            $request->session()->flash('success', 'Login berhasil, Welcome to portal');

            // --- PERUBAHAN DISINI: Direct ke halaman Portal ---
            return redirect()->route('portal.index');
        }

        $request->session()->flash('error', 'Login gagal, silahkan coba kembali');
        return back();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        session()->forget('user_role');

        return redirect('/');
    }
}
