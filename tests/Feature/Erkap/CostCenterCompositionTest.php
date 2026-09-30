<?php

namespace Tests\Feature\Erkap;

use App\Models\ChartOfAccount;
use App\Models\Division;
use App\Models\Erkap\Activity;
use App\Models\Erkap\BusinessUnit;
use App\Models\Erkap\CostCenter;
use App\Models\Erkap\Location;
use App\Models\Erkap\ManagementArea;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RoutineCost;
use App\Support\CoaCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\ActsAsErkapRole;
use Tests\TestCase;

/**
 * F5 — CRUD Pusat Biaya dengan komposisi segmen a..d.
 *
 * `code` Pusat Biaya tidak boleh diketik manual. Test ini memverifikasi kode
 * disusun server dari master, segmen a..c diwarisi dari Aktivitas sehingga
 * kombinasi lintas segmen yang tidak sah ditolak, dan penghapusan dicegah
 * selama masih ada COA atau Biaya Rutin yang memakainya.
 */
class CostCenterCompositionTest extends TestCase
{
    use RefreshDatabase, ActsAsErkapRole;

    private function admin(): void
    {
        $this->actAsErkapRole('erkap-admin');
    }

    /**
     * Rantai master a → b → c → d yang konsisten.
     *
     * Kode unit bisnis, lokasi, area, dan aktivitas dibiarkan oleh factory agar
     * tidak bentrok dengan unit lain yang dibuat di dalam test yang sama —
     * ruang kode segmen a hanya satu karakter.
     *
     * @return array{businessUnit: BusinessUnit, location: Location, managementArea: ManagementArea, activity: Activity, division: Division}
     */
    private function chain(): array
    {
        $businessUnit = BusinessUnit::factory()->create();
        $location = Location::factory()->create([
            'erkap_business_unit_id' => $businessUnit->id,
        ]);
        $managementArea = ManagementArea::factory()->create([
            'erkap_location_id' => $location->id,
        ]);
        $activity = Activity::factory()->create([
            'erkap_management_area_id' => $managementArea->id,
        ]);
        $division = Division::factory()->create();

        return compact('businessUnit', 'location', 'managementArea', 'activity', 'division');
    }

    /**
     * Work program yang valid butuh risiko dengan strategi mitigasi, dan
     * `WorkProgram::factory()` menolak bila itu tidak ada. Untuk test guard
     * hapus, cukup satu baris work program, jadi dibuat langsung lewat query
     * builder agar model hook validasi tidak dijalankan.
     */
    private function workProgramId(): int
    {
        return DB::table('erkap_work_programs')->insertGetId([
            'erkap_risk_identification_id' => RiskIdentification::factory()->create()->id,
            'name' => 'Program Kerja Uji',
            'units' => 'unit',
        ]);
    }

    /**
     * @param  array<string, mixed>  $chain
     * @return array<string, mixed>
     */
    private function payload(array $chain, array $overrides = []): array
    {
        return array_merge([
            'erkap_business_unit_id' => $chain['businessUnit']->id,
            'erkap_location_id' => $chain['location']->id,
            'erkap_management_area_id' => $chain['managementArea']->id,
            'erkap_activity_id' => $chain['activity']->id,
            'name' => 'Pusat Biaya Uji',
            'owner' => 'Pemilik Uji',
            'division_id' => $chain['division']->id,
        ], $overrides);
    }

    /* ---------------------------------------------------------------------
     | Komposisi kode
     | ------------------------------------------------------------------ */

    public function test_it_composes_the_code_from_the_selected_segments(): void
    {
        $this->admin();
        $chain = $this->chain();

        $this->post(route('erkap.cost-centers.store'), $this->payload($chain))
            ->assertRedirect(route('erkap.cost-centers.index'));

        // a=1 + b=2 + c=5 + d=3 = 11 karakter, disusun dari master yang dipilih.
        $expected = CoaCode::composeCostCenter([
            'business_unit' => $chain['businessUnit']->code,
            'location' => $chain['location']->code,
            'management_area' => $chain['managementArea']->code,
            'activity' => $chain['activity']->code,
        ]);

        $this->assertSame(11, strlen((string) $expected));
        $this->assertDatabaseHas('cost_centers', ['code' => $expected]);
    }

    public function test_it_ignores_a_client_supplied_code(): void
    {
        $this->admin();
        $chain = $this->chain();

        $this->post(route('erkap.cost-centers.store'), $this->payload($chain, [
            'code' => 'HACKED99999',
        ]))->assertRedirect(route('erkap.cost-centers.index'));

        $this->assertDatabaseMissing('cost_centers', ['code' => 'HACKED99999']);

        $costCenter = CostCenter::firstOrFail();
        $this->assertSame(11, strlen($costCenter->code));
        $this->assertSame(
            $costCenter->code,
            CoaCode::composeCostCenter($costCenter->segments()),
            'Kode tersimpan harus sama dengan komposisi segmen a..d.'
        );
    }

    public function test_it_derives_swakelola_from_the_activity(): void
    {
        $this->admin();
        $chain = $this->chain();
        $chain['activity']->update(['is_swakelola' => true]);

        $this->post(route('erkap.cost-centers.store'), $this->payload($chain, [
            'is_swakelola' => '0',
        ]))->assertRedirect(route('erkap.cost-centers.index'));

        $this->assertTrue(CostCenter::firstOrFail()->is_swakelola);
    }

    /* ---------------------------------------------------------------------
     | Integritas rantai
     | ------------------------------------------------------------------ */

    public function test_it_inherits_segments_a_to_c_from_the_activity(): void
    {
        $this->admin();
        $chain = $this->chain();

        // Sengaja kirim segmen atas yang tidak terkait dengan Aktivitas. Kode
        // bisnis unit tidak dikunci karena yang diuji adalah penolakan kombinasi
        // lintas segmen, bukan kode spesifik.
        $otherUnit = BusinessUnit::factory()->create();
        $otherLocation = Location::factory()->create([
            'erkap_business_unit_id' => $otherUnit->id,
            'code' => '66',
        ]);
        $otherArea = ManagementArea::factory()->create([
            'erkap_location_id' => $otherLocation->id,
            'code' => '44444',
        ]);

        $this->post(route('erkap.cost-centers.store'), $this->payload($chain, [
            'erkap_business_unit_id' => $otherUnit->id,
            'erkap_location_id' => $otherLocation->id,
            'erkap_management_area_id' => $otherArea->id,
        ]))->assertRedirect(route('erkap.cost-centers.index'));

        $costCenter = CostCenter::firstOrFail();

        $this->assertSame($chain['businessUnit']->id, $costCenter->erkap_business_unit_id);
        $this->assertSame($chain['location']->id, $costCenter->erkap_location_id);
        $this->assertSame($chain['managementArea']->id, $costCenter->erkap_management_area_id);
    }

    public function test_it_rejects_a_duplicate_segment_combination(): void
    {
        $this->admin();
        $chain = $this->chain();

        $this->post(route('erkap.cost-centers.store'), $this->payload($chain))
            ->assertRedirect(route('erkap.cost-centers.index'));

        $this->from(route('erkap.cost-centers.create'));
        $this->post(route('erkap.cost-centers.store'), $this->payload($chain, ['name' => 'Duplikat']))
            ->assertSessionHasErrors('code');

        $this->assertSame(1, CostCenter::count());
    }

    /* ---------------------------------------------------------------------
     | Validasi
     | ------------------------------------------------------------------ */

    public function test_it_requires_every_segment(): void
    {
        $this->admin();

        $this->from(route('erkap.cost-centers.create'));
        $this->post(route('erkap.cost-centers.store'), ['name' => 'Tanpa segmen'])
            ->assertSessionHasErrors([
                'erkap_business_unit_id',
                'erkap_location_id',
                'erkap_management_area_id',
                'erkap_activity_id',
            ]);
    }

    public function test_it_requires_a_coordinating_division_when_centralized(): void
    {
        $this->admin();
        $chain = $this->chain();

        $this->from(route('erkap.cost-centers.create'));
        $this->post(route('erkap.cost-centers.store'), $this->payload($chain, [
            'is_centralized' => '1',
            'coordinating_division_id' => null,
        ]))->assertSessionHasErrors('coordinating_division_id');
    }

    public function test_it_stores_the_coordinating_division(): void
    {
        $this->admin();
        $chain = $this->chain();
        $coordinator = Division::factory()->create();

        $this->post(route('erkap.cost-centers.store'), $this->payload($chain, [
            'is_centralized' => '1',
            'coordinating_division_id' => $coordinator->id,
        ]))->assertRedirect(route('erkap.cost-centers.index'));

        $this->assertDatabaseHas('cost_centers', [
            'is_centralized' => true,
            'coordinating_division_id' => $coordinator->id,
        ]);
    }

    public function test_it_clears_the_coordinating_division_when_uncentralized(): void
    {
        $this->admin();
        $chain = $this->chain();
        $coordinator = Division::factory()->create();

        $this->post(route('erkap.cost-centers.store'), $this->payload($chain, [
            'is_centralized' => '1',
            'coordinating_division_id' => $coordinator->id,
        ]));

        $costCenter = CostCenter::firstOrFail();

        $this->put(route('erkap.cost-centers.update', $costCenter), $this->payload($chain, [
            'is_centralized' => '0',
            'coordinating_division_id' => null,
        ]))->assertRedirect(route('erkap.cost-centers.index'));

        $this->assertNull($costCenter->fresh()->coordinating_division_id);
    }

    /* ---------------------------------------------------------------------
     | Update
     | ------------------------------------------------------------------ */

    public function test_it_recomposes_the_code_on_update(): void
    {
        $this->admin();
        $chain = $this->chain();

        $this->post(route('erkap.cost-centers.store'), $this->payload($chain));
        $costCenter = CostCenter::firstOrFail();
        $original = $costCenter->code;

        $newArea = ManagementArea::factory()->create([
            'erkap_location_id' => $chain['location']->id,
            'code' => '33333',
        ]);
        $newActivity = Activity::factory()->create([
            'erkap_management_area_id' => $newArea->id,
            'code' => '222',
        ]);

        $this->put(route('erkap.cost-centers.update', $costCenter), $this->payload($chain, [
            'erkap_management_area_id' => $newArea->id,
            'erkap_activity_id' => $newActivity->id,
        ]))->assertRedirect(route('erkap.cost-centers.index'));

        $costCenter->refresh();

        $this->assertNotSame($original, $costCenter->code);
        $this->assertSame(11, strlen($costCenter->code));
        $this->assertStringEndsWith('33333222', $costCenter->code);
        $this->assertSame($newArea->id, $costCenter->erkap_management_area_id);
    }

    public function test_updating_to_an_existing_combination_is_rejected(): void
    {
        $this->admin();
        $chain = $this->chain();

        $this->post(route('erkap.cost-centers.store'), $this->payload($chain));
        $firstCenter = CostCenter::firstOrFail();

        $secondActivity = Activity::factory()->create([
            'erkap_management_area_id' => $chain['managementArea']->id,
            'code' => '999',
        ]);

        $this->post(route('erkap.cost-centers.store'), $this->payload($chain, [
            'erkap_activity_id' => $secondActivity->id,
        ]));

        $secondCenter = CostCenter::whereKeyNot($firstCenter->id)->firstOrFail();

        // Arahkan Pusat Biaya kedua ke kombinasi yang sudah dipakai pertama.
        $this->from(route('erkap.cost-centers.edit', $secondCenter));
        $this->put(route('erkap.cost-centers.update', $secondCenter), $this->payload($chain, [
            'erkap_activity_id' => $chain['activity']->id,
        ]))->assertSessionHasErrors('code');

        $this->assertSame(2, CostCenter::count());
        $this->assertSame(
            $secondActivity->id,
            $secondCenter->fresh()->erkap_activity_id,
            'Kombinasi yang ditolak tidak boleh sempat tersimpan.'
        );
    }

    public function test_updating_a_cost_center_with_its_own_combination_is_allowed(): void
    {
        $this->admin();
        $chain = $this->chain();

        $this->post(route('erkap.cost-centers.store'), $this->payload($chain));
        $costCenter = CostCenter::firstOrFail();

        // `unique` harus mengabaikan baris yang sedang diedit.
        $this->put(route('erkap.cost-centers.update', $costCenter), $this->payload($chain, [
            'name' => 'Nama Baru',
        ]))->assertRedirect(route('erkap.cost-centers.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Nama Baru', $costCenter->fresh()->name);
    }

    /* ---------------------------------------------------------------------
     | Hapus
     | ------------------------------------------------------------------ */

    public function test_it_refuses_to_delete_a_cost_center_with_coa(): void
    {
        $this->admin();
        $chain = $this->chain();

        $this->post(route('erkap.cost-centers.store'), $this->payload($chain));
        $costCenter = CostCenter::firstOrFail();

        $account = ChartOfAccount::factory()->create([
            'cost_center_id' => $costCenter->id,
        ]);

        $this->delete(route('erkap.cost-centers.destroy', $costCenter))
            ->assertRedirect(route('erkap.cost-centers.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('chart_of_accounts', ['id' => $account->id]);
        $this->assertDatabaseHas('cost_centers', ['id' => $costCenter->id]);
    }

    public function test_it_refuses_to_delete_a_cost_center_with_routine_cost(): void
    {
        $this->admin();
        $chain = $this->chain();

        $this->post(route('erkap.cost-centers.store'), $this->payload($chain));
        $costCenter = CostCenter::firstOrFail();

        $routineCost = RoutineCost::factory()->create([
            'erkap_work_program_id' => $this->workProgramId(),
            'cost_center_id' => $costCenter->id,
        ]);

        $this->delete(route('erkap.cost-centers.destroy', $costCenter))
            ->assertRedirect(route('erkap.cost-centers.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('erkap_routine_costs', ['id' => $routineCost->id]);
    }

    public function test_it_deletes_an_unused_cost_center(): void
    {
        $this->admin();
        $chain = $this->chain();

        $this->post(route('erkap.cost-centers.store'), $this->payload($chain));
        $costCenter = CostCenter::firstOrFail();

        $this->delete(route('erkap.cost-centers.destroy', $costCenter))
            ->assertRedirect(route('erkap.cost-centers.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('cost_centers', ['id' => $costCenter->id]);
    }

    /* ---------------------------------------------------------------------
     | Halaman
     | ------------------------------------------------------------------ */

    public function test_the_create_page_embeds_the_cascade(): void
    {
        $this->admin();

        $response = $this->get(route('erkap.cost-centers.create'))->assertOk();

        $response->assertSee('erkap-cascade.js', false);
        $response->assertSee(json_encode(route('erkap.coa-options.index')), false);
        $response->assertSee('erkap_code_preview', false);
        // Input kode manual harus dihapus.
        $response->assertDontSee('name="code"', false);
    }

    public function test_the_edit_page_preselects_the_chain(): void
    {
        $this->admin();
        $chain = $this->chain();

        $this->post(route('erkap.cost-centers.store'), $this->payload($chain));
        $costCenter = CostCenter::firstOrFail();

        $response = $this->get(route('erkap.cost-centers.edit', $costCenter))->assertOk();

        $response->assertSee('value="'.$chain['businessUnit']->id.'"', false);
        $response->assertSee('value="'.$chain['location']->id.'"', false);
        $response->assertSee('value="'.$chain['managementArea']->id.'"', false);
    }

    public function test_the_index_shows_the_composed_code(): void
    {
        $this->admin();
        $chain = $this->chain();

        $this->post(route('erkap.cost-centers.store'), $this->payload($chain));
        $costCenter = CostCenter::firstOrFail();

        $this->get(route('erkap.cost-centers.index'))
            ->assertOk()
            ->assertSee(CoaCode::format($costCenter->code), false);
    }

    /* ---------------------------------------------------------------------
     | Permission
     | ------------------------------------------------------------------ */

    public function test_a_user_without_permission_cannot_open_the_create_page(): void
    {
        $this->actAsErkapRole('erkap-cost-owner');

        $this->get(route('erkap.cost-centers.create'))->assertForbidden();
    }

    public function test_a_user_without_permission_cannot_store(): void
    {
        $this->actAsErkapRole('erkap-cost-owner');
        $chain = $this->chain();

        $this->post(route('erkap.cost-centers.store'), $this->payload($chain))
            ->assertForbidden();

        $this->assertSame(0, CostCenter::count());
    }
}
