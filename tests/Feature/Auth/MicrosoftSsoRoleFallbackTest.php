<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\MicrosoftAuthController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * F10 — audit keamanan fallback role pada Microsoft SSO.
 *
 * Fallback lama selalu memberi role `staff` kepada user Microsoft baru tanpa
 * syarat. `staff` sendiri memegang `ams.settings.reset-password` (reset
 * password seluruh pengguna), hapus tiket helpdesk, dan akses berkas dokter —
 * seluruhnya di luar lingkup modul yang wajar diberikan otomatis oleh SSO.
 *
 * `laravel/socialite` belum terpasang, jadi `callback()` tidak bisa dijalankan
 * di test. Keputusan role diuji lewat `ensureRole()` yang dipanggil callback,
 * sehingga yang diuji adalah kebijakan yang sama, bukan hanya bentuk kode.
 */
class MicrosoftSsoRoleFallbackTest extends TestCase
{
    use RefreshDatabase;

    private MicrosoftAuthController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.microsoft.default_role' => null]);

        $this->controller = new MicrosoftAuthController;
    }

    public function test_a_new_user_gets_no_role_by_default(): void
    {
        $user = User::factory()->create(['email' => 'baru@perusahaan.local']);

        $this->assign($user);

        $this->assertFalse($user->roles()->exists());
    }

    public function test_no_role_is_assigned_even_when_a_fallback_role_exists_in_the_database(): void
    {
        // `staff` ada di database, tetapi tanpa konfigurasi eksplisit role
        // default tidak boleh dipilih otomatis.
        Role::create(['name' => 'staff', 'guard_name' => 'web']);

        $user = User::factory()->create(['email' => 'baru@perusahaan.local']);

        $this->assign($user);

        $this->assertFalse($user->roles()->exists());
    }

    public function test_an_explicitly_configured_default_role_is_assigned(): void
    {
        Role::create(['name' => 'form-it-user', 'guard_name' => 'web']);
        config(['services.microsoft.default_role' => 'form-it-user']);

        $user = User::factory()->create(['email' => 'baru@perusahaan.local']);

        $this->assign($user);

        $this->assertTrue($user->hasRole('form-it-user'));
    }

    public function test_an_unknown_configured_role_does_not_grant_access(): void
    {
        config(['services.microsoft.default_role' => 'role-yang-tidak-ada']);

        $user = User::factory()->create(['email' => 'baru@perusahaan.local']);

        $this->assign($user);

        $this->assertFalse($user->roles()->exists());
    }

    public function test_an_existing_role_is_never_overwritten(): void
    {
        Role::create(['name' => 'erkap-cost-owner', 'guard_name' => 'web']);
        config(['services.microsoft.default_role' => 'form-it-user']);
        Role::create(['name' => 'form-it-user', 'guard_name' => 'web']);

        $user = User::factory()->create(['email' => 'pamit@perusahaan.local']);
        $user->assignRole('erkap-cost-owner');

        $this->assign($user);

        $this->assertTrue($user->hasRole('erkap-cost-owner'));
        $this->assertFalse($user->hasRole('form-it-user'));
    }

    /**
     * Memanggil `ensureRole()` secara langsung: `laravel/socialite` belum
     * terpasang sehingga callback penuh tidak bisa diuji di sini.
     */
    private function assign(User $user): void
    {
        $method = new \ReflectionMethod(MicrosoftAuthController::class, 'ensureRole');
        $method->setAccessible(true);
        $method->invoke($this->controller, $user);
    }
}
