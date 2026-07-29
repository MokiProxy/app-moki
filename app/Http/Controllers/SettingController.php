<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function resetPasswordIndex()
    {
        return view('components.reset-password');
    }

    public function updatePassword(Request $request)
    {
        // TODO: implementasi reset password
        return back()->with('success', 'Password berhasil diubah.');
    }
}
