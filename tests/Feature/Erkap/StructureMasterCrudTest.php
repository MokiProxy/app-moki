<?php

namespace Tests\Feature\Erkap;

use App\Models\Division;
use App\Models\Erkap\Activity;
use App\Models\Erkap\BusinessUnit;
use App\Models\Erkap\CostCenter;
use App\Models\Erkap\Location;
use App\Models\Erkap\ManagementArea;
use App\Models\User;
use App\Support\CoaCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ActsAsErkapRole;
use Tests\Concerns\ActsAsSuperAdmin;
use Tests\TestCase;

/**
 * F9 — CRUD master struktur a..d (Bisnis Unit, Lokasi, Manajemen Area, Aktivitas).
 *
 * Master ini adalah sumber segmen kode COA, jadi selain CRUD dasar test ini
 * juga memverifikasi komposisi kode 11 karakter dan penolakan kode di luar
 * panjang segmen.
 */
class StructureMasterCrudTest extends TestCase
{
    use RefreshDatabase, ActsAsSuperAdmin, ActsAsErkapRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSuperAdmin();
    }

    /* ---------------------------------------------------------------------
     | Bisnis Unit (a)
     | ------------------------------------------------------------------ */

    public function test_it_creates_a_business_unit(): void
    {
        $this->post(route('erkap.business-units.store'), [
            'code' => 'A',
            'name' => 'Korporat',
            'description' => 'Bisnis unit pusat',
            'sort_order' => 1,
            'is_active' => '1',
        ])->assertRedirect(route('erkap.business-units.index'));

        $this->assertDatabaseHas('erkap_business_units', [
            'code' => 'A',
            'name' => 'Korporat',
            'is_active' => true,
        ]);
    }

    public function test_it_rejects_a_business_unit_code_longer_than_one_character(): void
    {
        $this->post(route('erkap.business-units.store'), ['code' => 'AB', 'name' => 'Korporat'])
            ->assertSessionHasErrors('code');

        $this->assertDatabaseMissing('erkap_business_units', ['code' => 'AB']);
    }

    public function test_it_rejects_a_duplicate_business_unit_code(): void
    {
        BusinessUnit::factory()->create(['code' => 'A']);

        $this->post(route('erkap.business-units.store'), ['code' => 'A', 'name' => 'Duplikat'])
            ->assertSessionHasErrors('code');
    }

    public function test_it_updates_a_business_unit(): void
    {
        $businessUnit = BusinessUnit::factory()->create(['code' => 'A', 'name' => 'Lama']);

        $this->put(route('erkap.business-units.update', $businessUnit), [
            'code' => 'A',
            'name' => 'Baru',
            'is_active' => '0',
        ])->assertRedirect(route('erkap.business-units.index'));

        $this->assertDatabaseHas('erkap_business_units', [
            'id' => $businessUnit->id,
            'name' => 'Baru',
            'is_active' => false,
        ]);
    }

    public function test_it_allows_keeping_the_same_code_when_updating(): void
    {
        $businessUnit = BusinessUnit::factory()->create(['code' => 'A']);

        $this->put(route('erkap.business-units.update', $businessUnit), [
            'code' => 'A',
            'name' => 'Nama Baru',
        ])->assertSessionHasNoErrors();
    }

    public function test_it_blocks_deleting_a_business_unit_that_is_still_used(): void
    {
        $businessUnit = BusinessUnit::factory()->create();
        Location::factory()->create(['erkap_business_unit_id' => $businessUnit->id]);

        $this->delete(route('erkap.business-units.destroy', $businessUnit))
            ->assertRedirect(route('erkap.business-units.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('erkap_business_units', ['id' => $businessUnit->id]);
    }

    public function test_it_deletes_an_unused_business_unit(): void
    {
        $businessUnit = BusinessUnit::factory()->create();

        $this->delete(route('erkap.business-units.destroy', $businessUnit))
            ->assertRedirect(route('erkap.business-units.index'));

        $this->assertDatabaseMissing('erkap_business_units', ['id' => $businessUnit->id]);
    }

    /* ---------------------------------------------------------------------
     | Lokasi (b)
     | ------------------------------------------------------------------ */

    public function test_it_creates_a_location_under_a_business_unit(): void
    {
        $businessUnit = BusinessUnit::factory()->create();

        $this->post(route('erkap.locations.store'), [
            'code' => '01',
            'name' => 'Jakarta',
            'erkap_business_unit_id' => $businessUnit->id,
            'is_active' => '1',
        ])->assertRedirect(route('erkap.locations.index'));

        $this->assertDatabaseHas('erkap_locations', [
            'code' => '01',
            'erkap_business_unit_id' => $businessUnit->id,
        ]);
    }

    public function test_it_rejects_a_location_code_that_is_not_two_digits(): void
    {
        $businessUnit = BusinessUnit::factory()->create();

        $this->post(route('erkap.locations.store'), [
            'code' => '1',
            'name' => 'Jakarta',
            'erkap_business_unit_id' => $businessUnit->id,
        ])->assertSessionHasErrors('code');
    }

    public function test_the_same_location_code_is_allowed_under_a_different_business_unit(): void
    {
        $first = BusinessUnit::factory()->create();
        $second = BusinessUnit::factory()->create();

        Location::factory()->create(['code' => '01', 'erkap_business_unit_id' => $first->id]);

        $this->post(route('erkap.locations.store'), [
            'code' => '01',
            'name' => 'Bandung',
            'erkap_business_unit_id' => $second->id,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('erkap_locations', [
            'code' => '01',
            'erkap_business_unit_id' => $second->id,
        ]);
    }

    public function test_it_rejects_a_duplicate_location_code_within_the_same_business_unit(): void
    {
        $businessUnit = BusinessUnit::factory()->create();
        Location::factory()->create(['code' => '01', 'erkap_business_unit_id' => $businessUnit->id]);

        $this->post(route('erkap.locations.store'), [
            'code' => '01',
            'name' => 'Duplikat',
            'erkap_business_unit_id' => $businessUnit->id,
        ])->assertSessionHasErrors('code');
    }

    public function test_it_requires_a_business_unit_for_a_location(): void
    {
        $this->post(route('erkap.locations.store'), ['code' => '02', 'name' => 'Tanpa induk'])
            ->assertSessionHasErrors('erkap_business_unit_id');
    }

    public function test_it_blocks_deleting_a_location_that_is_still_used(): void
    {
        $location = Location::factory()->create();
        ManagementArea::factory()->create(['erkap_location_id' => $location->id]);

        $this->delete(route('erkap.locations.destroy', $location))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('erkap_locations', ['id' => $location->id]);
    }

    /* ---------------------------------------------------------------------
     | Manajemen Area (c)
     | ------------------------------------------------------------------ */

    public function test_it_creates_a_management_area_and_inherits_the_business_unit(): void
    {
        $location = Location::factory()->create();

        $this->post(route('erkap.management-areas.store'), [
            'code' => '20200',
            'name' => 'Operasional',
            'erkap_location_id' => $location->id,
            'division_id' => Division::factory()->create()->id,
            'is_active' => '1',
        ])->assertRedirect(route('erkap.management-areas.index'));

        $this->assertDatabaseHas('erkap_management_areas', [
            'code' => '20200',
            'erkap_location_id' => $location->id,
            // Diwarisi dari Lokasi oleh ManagementArea::booted()
            'erkap_business_unit_id' => $location->erkap_business_unit_id,
        ]);
    }

    public function test_it_rejects_a_management_area_code_that_is_not_five_digits(): void
    {
        $location = Location::factory()->create();

        $this->post(route('erkap.management-areas.store'), [
            'code' => '2020',
            'name' => 'Terlalu Pendek',
            'erkap_location_id' => $location->id,
        ])->assertSessionHasErrors('code');
    }

    public function test_the_same_management_area_code_is_allowed_under_a_different_location(): void
    {
        $first = Location::factory()->create();
        $second = Location::factory()->create();
        ManagementArea::factory()->create(['code' => '20200', 'erkap_location_id' => $first->id]);

        $this->post(route('erkap.management-areas.store'), [
            'code' => '20200',
            'name' => 'Area Sama',
            'erkap_location_id' => $second->id,
        ])->assertSessionHasNoErrors();
    }

    public function test_it_blocks_deleting_a_management_area_that_is_still_used(): void
    {
        $managementArea = ManagementArea::factory()->create();
        Activity::factory()->create(['erkap_management_area_id' => $managementArea->id]);

        $this->delete(route('erkap.management-areas.destroy', $managementArea))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('erkap_management_areas', ['id' => $managementArea->id]);
    }

    /* ---------------------------------------------------------------------
     | Aktivitas (d)
     | ------------------------------------------------------------------ */

    public function test_it_creates_an_activity_and_inherits_the_upper_segments(): void
    {
        $location = Location::factory()->create();
        $managementArea = ManagementArea::factory()->create([
            'erkap_location_id' => $location->id,
            'erkap_business_unit_id' => $location->erkap_business_unit_id,
        ]);

        $this->post(route('erkap.activities.store'), [
            'code' => '202',
            'name' => 'Operasional Harian',
            'erkap_management_area_id' => $managementArea->id,
            'is_swakelola' => '1',
            'is_active' => '1',
        ])->assertRedirect(route('erkap.activities.index'));

        $this->assertDatabaseHas('erkap_activities', [
            'code' => '202',
            'erkap_management_area_id' => $managementArea->id,
            'erkap_location_id' => $location->id,
            'erkap_business_unit_id' => $location->erkap_business_unit_id,
            'is_swakelola' => true,
        ]);
    }

    public function test_it_ignores_a_client_supplied_business_unit_and_derives_it_from_the_area(): void
    {
        $location = Location::factory()->create();
        $managementArea = ManagementArea::factory()->create([
            'erkap_location_id' => $location->id,
            'erkap_business_unit_id' => $location->erkap_business_unit_id,
        ]);
        $attackerUnit = BusinessUnit::factory()->create();

        $this->post(route('erkap.activities.store'), [
            'code' => '203',
            'name' => 'Aktivitas',
            'erkap_management_area_id' => $managementArea->id,
            // Tidak tervalidasi, jadi harus diabaikan dan diwarisi.
            'erkap_business_unit_id' => $attackerUnit->id,
            'erkap_location_id' => $attackerUnit->id,
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('erkap_activities', [
            'code' => '203',
            'erkap_business_unit_id' => $location->erkap_business_unit_id,
            'erkap_location_id' => $location->id,
        ]);
    }

    public function test_it_rejects_an_activity_code_that_is_not_three_digits(): void
    {
        $managementArea = ManagementArea::factory()->create();

        $this->post(route('erkap.activities.store'), [
            'code' => '20',
            'name' => 'Terlalu Pendek',
            'erkap_management_area_id' => $managementArea->id,
        ])->assertSessionHasErrors('code');
    }

    public function test_it_blocks_deleting_an_activity_that_is_still_used(): void
    {
        $activity = Activity::factory()->create();
        CostCenter::factory()->create(['erkap_activity_id' => $activity->id]);

        $this->delete(route('erkap.activities.destroy', $activity))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('erkap_activities', ['id' => $activity->id]);
    }

    /* ---------------------------------------------------------------------
     | Komposisi kode 11 karakter
     | ------------------------------------------------------------------ */

    public function test_the_four_segments_compose_an_eleven_character_code(): void
    {
        $businessUnit = BusinessUnit::factory()->create(['code' => 'A']);
        $location = Location::factory()->create([
            'code' => '01',
            'erkap_business_unit_id' => $businessUnit->id,
        ]);
        $managementArea = ManagementArea::factory()->create([
            'code' => '20200',
            'erkap_location_id' => $location->id,
            'erkap_business_unit_id' => $businessUnit->id,
        ]);
        $activity = Activity::factory()->create([
            'code' => '202',
            'erkap_management_area_id' => $managementArea->id,
        ]);

        $code = $activity->composeCode();

        $this->assertSame('A0120200202', $code);
        $this->assertSame(11, strlen($code));
        $this->assertTrue(CoaCode::validCostCenter($code));
    }

    /* ---------------------------------------------------------------------
     | Matriks permission
     | ------------------------------------------------------------------ */

    public function test_erkap_admin_may_manage_the_structure(): void
    {
        $this->actAsErkapRole('erkap-admin');

        $this->get(route('erkap.business-units.index'))->assertOk();
        $this->get(route('erkap.business-units.create'))->assertOk();
        $this->get(route('erkap.locations.create'))->assertOk();
        $this->get(route('erkap.management-areas.create'))->assertOk();
        $this->get(route('erkap.activities.create'))->assertOk();
    }

    public function test_erkap_admin_may_persist_structure_changes(): void
    {
        $this->actAsErkapRole('erkap-admin');

        $this->post(route('erkap.business-units.store'), ['code' => 'Q', 'name' => 'Korporat'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('erkap_business_units', ['code' => 'Q']);
    }

    public function test_erkap_cost_owner_may_read_the_cascading_api_but_not_manage_structure(): void
    {
        $this->actAsErkapRole('erkap-cost-owner');

        // `erkap.menu` cukup untuk mengisi form transaksi.
        $this->getJson(route('erkap.coa-options.business-units'))->assertOk();

        $this->get(route('erkap.business-units.index'))->assertForbidden();
        $this->get(route('erkap.activities.create'))->assertForbidden();

        $this->post(route('erkap.business-units.store'), ['code' => 'Q', 'name' => 'Ditolak'])
            ->assertForbidden();

        $this->assertDatabaseMissing('erkap_business_units', ['code' => 'Q']);
    }

    public function test_erkap_auditor_may_read_structure_but_not_write_it(): void
    {
        $this->actAsErkapRole('erkap-auditor');

        $this->get(route('erkap.business-units.index'))->assertOk();
        $this->get(route('erkap.activities.index'))->assertOk();
        $this->get(route('erkap.activities.create'))->assertForbidden();

        $this->post(route('erkap.activities.store'), [
            'code' => '555',
            'name' => 'Ditolak',
            'erkap_management_area_id' => ManagementArea::factory()->create()->id,
        ])->assertForbidden();
    }

    public function test_users_without_erkap_menu_cannot_read_the_cascading_api(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->getJson(route('erkap.coa-options.business-units'))->assertForbidden();
    }

    public function test_guests_are_redirected_from_the_cascading_api(): void
    {
        // setUp() mendaftarkan super-admin, jadi sesi harus dibuang.
        auth()->logout();

        $this->get(route('erkap.coa-options.business-units'))->assertRedirect(route('login'));
    }
}
