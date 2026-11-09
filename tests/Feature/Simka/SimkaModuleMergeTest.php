<?php

namespace Tests\Feature\Simka;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Fase A — konsolidasi modul E-RKAP ke dalam SIMKA.
 *
 * Fokus test ini adalah perubahan yang terlihat oleh pengguna dan gerbang
 * otorisasi baru, bukan logika bisnis SPPH/E-RKAP.
 */
class SimkaModuleMergeTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPermissions(array $permissions, ?string $roleName = null): User
    {
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $user = User::factory()->create();

        if ($roleName !== null) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->givePermissionTo($permissions);
            $user->assignRole($role);
        } else {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }

    public function test_portal_only_shows_the_simka_card(): void
    {
        $response = $this->get(route('portal.index'))->assertOk();

        $response->assertSee('SIMKA');
        $response->assertSee('Pendapatan &amp; Pengeluaran', false);
        $response->assertDontSee('E-RKAP');
    }

    public function test_simka_cannot_be_opened_without_the_menu_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('simka.index'))
            ->assertForbidden();
    }

    public function test_simka_menu_permission_allows_read_access(): void
    {
        $this->actingAs($this->userWithPermissions(['simka.menu']))
            ->get(route('simka.index'))
            ->assertOk()
            ->assertSee('Pendapatan: SIMKA', false)
            ->assertSee('Pengeluaran: E-RKAP', false);
    }

    public function test_spph_write_requires_the_manage_permission(): void
    {
        $user = $this->userWithPermissions(['simka.menu']);

        $this->actingAs($user)
            ->get(route('simka.spph2287.konfigurasi'))
            ->assertOk();

        $this->actingAs($user)
            ->post(route('simka.spph2287.konfigurasi.simpan'), [])
            ->assertForbidden();
    }

    public function test_erkap_role_sees_both_domains_in_the_merged_sidebar(): void
    {
        $user = $this->userWithPermissions(
            ['simka.menu', 'simka.spph.manage', 'erkap.menu'],
            'erkap-admin'
        );

        $this->actingAs($user)
            ->get(route('simka.index'))
            ->assertOk()
            ->assertSee('Pendapatan: SIMKA', false)
            ->assertSee('Pengeluaran: E-RKAP', false)
            ->assertSee('SPPH 773')
            ->assertSee('Dashboard E-RKAP');
    }

    public function test_erkap_route_names_now_resolve_below_simka(): void
    {
        $this->assertSame(url('/simka/erkap'), route('erkap.index'));
        $this->assertSame(url('/simka/erkap/rkap'), route('erkap.rkap.index'));
    }

    public function test_legacy_erkap_url_redirects_to_the_new_location(): void
    {
        $this->get('/erkap/rkap')->assertRedirect('/simka/erkap/rkap');
    }

    public function test_all_erkap_views_extend_the_simka_layout(): void
    {
        foreach (File::allFiles(resource_path('views/erkap')) as $file) {
            $contents = File::get($file->getPathname());

            $this->assertStringNotContainsString(
                "layouts.Erkap",
                $contents,
                "{$file->getRelativePathname()} masih memakai layout E-RKAP."
            );
        }
    }

    public function test_all_simka_views_use_the_canonical_layout_name(): void
    {
        foreach (File::allFiles(resource_path('views/simka')) as $file) {
            $contents = File::get($file->getPathname());

            $this->assertStringNotContainsString(
                'layouts.SIMKA',
                $contents,
                "{$file->getRelativePathname()} masih memakai layouts.SIMKA (case-sensitive)."
            );
        }
    }
}
