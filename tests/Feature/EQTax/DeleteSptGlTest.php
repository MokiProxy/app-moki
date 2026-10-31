<?php

namespace Tests\Feature\EQTax;

use App\Models\EQTAXCoretaxSPT;
use App\Models\EQTAXGL;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\ActsAsSuperAdmin;
use Tests\TestCase;

class DeleteSptGlTest extends TestCase
{
    use RefreshDatabase, ActsAsSuperAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSuperAdmin();
    }

    private function makeSpt(array $attributes = []): EQTAXCoretaxSPT
    {
        return EQTAXCoretaxSPT::create(array_merge([
            'entity'          => 'PK',
            'no_faktur_pajak' => '010.000-00.' . uniqid(),
            'nama_penjual'    => 'Penjual Test',
            'masa_pajak'      => '01',
            'tahun'           => '2026',
            'dpp'             => 1000,
            'ppn'             => 110,
        ], $attributes));
    }

    private function makeGl(array $attributes = []): EQTAXGL
    {
        return EQTAXGL::create(array_merge([
            'sheet'           => 'Sheet A',
            'nama_supplier'   => 'Supplier Test',
            'jurnal_date'     => '20260115',
            'jurnal_no'       => 'JRN-' . uniqid(),
            'no_faktur_pajak' => '010.000-00.' . uniqid(),
            'dpp'             => 2000,
            'ppn'             => 220,
        ], $attributes));
    }

    public function test_single_delete_spt(): void
    {
        $spt = $this->makeSpt();

        $response = $this->postJson(route('eqtax.spt.coretax.delete'), [
            'tab' => 'PK',
            'ids' => [$spt->id],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('deleted_count', 1);

        $this->assertDatabaseMissing('eqtax_coretax_spt', ['id' => $spt->id]);
    }

    public function test_bulk_delete_spt_by_ids(): void
    {
        $first = $this->makeSpt();
        $second = $this->makeSpt();

        $response = $this->postJson(route('eqtax.spt.coretax.delete'), [
            'tab' => 'PK',
            'ids' => [$first->id, $second->id],
        ]);

        $response->assertOk()->assertJsonPath('deleted_count', 2);

        $this->assertDatabaseMissing('eqtax_coretax_spt', ['id' => $first->id]);
        $this->assertDatabaseMissing('eqtax_coretax_spt', ['id' => $second->id]);
    }

    public function test_select_all_spt_only_deletes_active_tab(): void
    {
        $this->makeSpt(['entity' => 'PK']);
        $this->makeSpt(['entity' => 'PK']);
        $pm = $this->makeSpt(['entity' => 'PM']);

        $response = $this->postJson(route('eqtax.spt.coretax.delete'), [
            'tab'        => 'PK',
            'select_all' => 1,
        ]);

        $response->assertOk()->assertJsonPath('deleted_count', 2);

        $this->assertDatabaseHas('eqtax_coretax_spt', ['id' => $pm->id]);
        $this->assertSame(0, EQTAXCoretaxSPT::where('entity', 'like', 'PK%')->count());
    }

    public function test_delete_spt_rejects_ids_from_other_tab(): void
    {
        $pm = $this->makeSpt(['entity' => 'PM']);

        $response = $this->postJson(route('eqtax.spt.coretax.delete'), [
            'tab' => 'PK',
            'ids' => [$pm->id],
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
        $this->assertDatabaseHas('eqtax_coretax_spt', ['id' => $pm->id]);
    }

    public function test_single_delete_gl(): void
    {
        $gl = $this->makeGl();

        $response = $this->postJson(route('eqtax.gl.delete'), [
            'ids' => [$gl->id],
        ]);

        $response->assertOk()->assertJsonPath('deleted_count', 1);
        $this->assertDatabaseMissing('eqtax_gl', ['id' => $gl->id]);
    }

    public function test_select_all_gl_respects_sheet_filter(): void
    {
        $this->makeGl(['sheet' => 'Sheet A']);
        $this->makeGl(['sheet' => 'Sheet A']);
        $other = $this->makeGl(['sheet' => 'Sheet B']);

        $response = $this->postJson(route('eqtax.gl.delete'), [
            'select_all' => 1,
            'sheet'      => 'Sheet A',
        ]);

        $response->assertOk()->assertJsonPath('deleted_count', 2);

        $this->assertDatabaseHas('eqtax_gl', ['id' => $other->id]);
        $this->assertSame(0, EQTAXGL::where('sheet', 'Sheet A')->count());
    }

    public function test_delete_requires_ids_when_select_all_is_not_set(): void
    {
        $response = $this->postJson(route('eqtax.spt.coretax.delete'), [
            'tab' => 'PK',
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_delete_returns_422_when_no_matching_rows(): void
    {
        $response = $this->postJson(route('eqtax.gl.delete'), [
            'ids' => [999999],
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_user_without_delete_permission_gets_forbidden(): void
    {
        foreach (['eqtax.menu', 'eqtax.spt.coretax.view', 'eqtax.gl.view'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $role = Role::firstOrCreate(['name' => 'eqtax-viewer', 'guard_name' => 'web']);
        $role->syncPermissions(['eqtax.menu', 'eqtax.spt.coretax.view', 'eqtax.gl.view']);

        $user = User::factory()->create();
        $user->assignRole($role);

        $spt = $this->makeSpt();

        $this->actingAs($user)
            ->postJson(route('eqtax.spt.coretax.delete'), ['tab' => 'PK', 'ids' => [$spt->id]])
            ->assertForbidden();

        $this->assertDatabaseHas('eqtax_coretax_spt', ['id' => $spt->id]);
    }
}
