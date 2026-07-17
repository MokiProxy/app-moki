<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class MicrosoftAuthController extends Controller
{
    /**
     * Redirect user ke halaman login Microsoft.
     */
    public function redirect()
    {
        return Socialite::driver('azure')->redirect();
    }

    /**
     * Handle callback setelah user login di Microsoft.
     */
    public function callback()
    {
        try {
            $microsoftUser = Socialite::driver('azure')->user();
        } catch (\Throwable $e) {
            Log::error('Microsoft login gagal: ' . $e->getMessage());

            return redirect()
                ->route('login')
                ->with('error', 'Login dengan Microsoft gagal, silakan coba lagi.');
        }

 
        $user = User::where('email', $microsoftUser->getEmail())->first();

        if (! $user) {
            
            $user = User::create([
                'name'              => $microsoftUser->getName() ?? $microsoftUser->getNickname(),
                'email'             => $microsoftUser->getEmail(),
                'password'          => bcrypt(Str::random(24)),
                'microsoft_id'      => $microsoftUser->getId(),
                'email_verified_at' => now(),
            ]);
        } else {
            // Simpan microsoft_id kalau user sudah ada tapi belum pernah login via Microsoft
            if (empty($user->microsoft_id)) {
                $user->update(['microsoft_id' => $microsoftUser->getId()]);
            }
        }

        Auth::login($user, true);

        return redirect()->intended(route('dashboard'));
    }
}
