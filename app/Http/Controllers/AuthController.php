<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            $request->session()->regenerate();

            $request->session()->flash('success', 'Login berhasil, Welcome to portal');

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

        return redirect('/');
    }
}
