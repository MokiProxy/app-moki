<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;

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
            if (! config('services.microsoft.auto_provision', true)) {
                Log::info('Provisioning Microsoft SSO dimatikan, login ditolak: '.$microsoftUser->getEmail());

                return redirect()
                    ->route('login')
                    ->with('error', 'Akun Anda belum terdaftar. Hubungi administrator.');
            }

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

        $this->ensureRole($user);

        Auth::login($user, true);

        return redirect()->intended(route('portal.index'));
    }

    /**
     * Beri role default hanya bila operator menetapkannya lewat konfigurasi.
     *
     * Sebelumnya user baru dari Microsoft selalu mendapat `staff` tanpa syarat,
     * padahal `staff` memberi akses modul lain (reset password AMS, hapus
     * tiket helpdesk, berkas dokter). Auto-role yang tidak dibatasi membuat
     * siapa pun yang lolos autentikasi tenant mendapat akses tersebut, termasuk
     * role ERKAP yang sama sekali tidak terkait. Sekarang role default kosong
     * secara default: akun tetap dibuat, tetapi admin yang menetapkan aksesnya.
     */
    private function ensureRole(User $user): void
    {
        if ($user->roles()->exists()) {
            return;
        }

        $defaultRole = config('services.microsoft.default_role');

        if (! $defaultRole) {
            Log::warning('User Microsoft tanpa role, akses ditahan sampai admin memberikan role: '.$user->email);

            return;
        }

        if (! Role::where('name', $defaultRole)->exists()) {
            Log::error('Role default Microsoft tidak ada: '.$defaultRole);

            return;
        }

        $user->assignRole($defaultRole);
    }
}
