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
        $credentials = $request->validate([
            'email' => ['required', 'email:dns'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // LOGIKA LAMA: AMBIL ROLE DARI TABEL user_roles (TETAP DIPERTAHANKAN)
            $userRole = DB::table('user_roles')
                        ->where('employee_id', $user->employee_id)
                        ->first();

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