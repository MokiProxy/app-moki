<?php

namespace Tests\Feature\Erkap;

use App\Models\ChartOfAccount;
use App\Models\Erkap\Activity;
use App\Models\Erkap\BusinessUnit;
use App\Models\Erkap\CostCenter;
use App\Models\Erkap\CostElement;
use App\Models\Erkap\Location;
use App\Models\Erkap\ManagementArea;
use App\Support\CoaCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ActsAsSuperAdmin;
use Tests\TestCase;

/**
 * F4 — API dropdown cascading struktur a..d, Pusat Biaya, dan COA.
 *
 * Endpoint ini dibaca oleh `ErkapCascade` (public/js/erkap-cascade.js) pada
 * form master maupun form transaksi, sehingga tidak boleh mengembalikan kode
 * yang tidak terbentuk dari kombinasi segmen yang sah.
 */
class CoaOptionApiTest extends TestCase
{
    use RefreshDatabase, ActsAsSuperAdmin;

    private BusinessUnit $businessUnit;

    private Location $location;

    private ManagementArea $managementArea;

    private Activity $activity;

    private CostCenter $costCenter;

    private CostElement $costElement;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSuperAdmin();

        $this->businessUnit = BusinessUnit::factory()->create(['code' => 'A', 'name' => 'Korporat']);
        $this->location = Location::factory()->create([
            'code' => '01',
            'name' => 'Jakarta',
            'erkap_business_unit_id' => $this->businessUnit->id,
        ]);
        $this->managementArea = ManagementArea::factory()->create([
            'code' => '20200',
            'name' => 'Operasional',
            'erkap_location_id' => $this->location->id,
            'erkap_business_unit_id' => $this->businessUnit->id,
        ]);
        $this->activity = Activity::factory()->create([
            'code' => '202',
            'name' => 'Operasional Harian',
            'erkap_management_area_id' => $this->managementArea->id,
            'is_swakelola' => true,
        ]);

        $this->costCenter = CostCenter::factory()->create([
            'erkap_business_unit_id' => $this->businessUnit->id,
            'erkap_location_id' => $this->location->id,
            'erkap_management_area_id' => $this->managementArea->id,
            'erkap_activity_id' => $this->activity->id,
        ]);

        $this->costElement = CostElement::factory()->create();
    }

    public function test_it_returns_the_business_unit_chain_root(): void
    {
        $response = $this->getJson(route('erkap.coa-options.business-units'))->assertOk();

        $byCode = collect($response->json('data'))->keyBy('code');

        $this->assertTrue($byCode->has('A'));
        $this->assertSame('Korporat', $byCode['A']['name']);
        $this->assertSame('A — Korporat', $byCode['A']['label']);
    }

    public function test_business_units_are_ordered_by_sort_order_then_code(): void
    {
        $this->businessUnit->update(['sort_order' => 5]);

        // Kode dibaca dari model, bukan diketik manual: segmen (a) hanya satu
        // karakter sehingga hard-code huruf rawan bentrok dengan unit yang
        // dibuat factory lain di dalam test. Dibuat satu per satu supaya
        // `unusedCode()` selalu melihat hasil insert sebelumnya.
        $tie = collect([
            BusinessUnit::factory()->create(['sort_order' => 5]),
            BusinessUnit::factory()->create(['sort_order' => 5]),
        ]);

        $codes = collect($this->getJson(route('erkap.coa-options.business-units'))->json('data'))
            ->pluck('code')
            ->values()
            ->all();

        $contested = $tie->push($this->businessUnit)->pluck('code')->all();

        $this->assertSame(
            collect($contested)->sort()->values()->all(),
            collect($codes)->filter(fn (string $code) => in_array($code, $contested, true))->values()->all(),
            'Pada sort_order yang sama, urutan harus mengikuti kode.'
        );
    }

    public function test_locations_are_scoped_to_the_given_business_unit(): void
    {
        $other = BusinessUnit::factory()->create();
        Location::factory()->create(['code' => '07', 'erkap_business_unit_id' => $other->id]);

        $this->getJson(route('erkap.coa-options.locations', ['business_unit_id' => $this->businessUnit->id]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', '01');
    }

    public function test_management_areas_are_scoped_to_the_given_location(): void
    {
        $otherLocation = Location::factory()->create([
            'code' => '08',
            'erkap_business_unit_id' => $this->businessUnit->id,
        ]);
        ManagementArea::factory()->create([
            'code' => '30300',
            'erkap_location_id' => $otherLocation->id,
            'erkap_business_unit_id' => $this->businessUnit->id,
        ]);

        $this->getJson(route('erkap.coa-options.management-areas', ['location_id' => $this->location->id]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', '20200');
    }

    public function test_activities_are_scoped_to_the_given_management_area(): void
    {
        Activity::factory()->create(['code' => '999', 'erkap_management_area_id' => $this->managementArea->id]);

        $this->getJson(route('erkap.coa-options.activities', ['management_area_id' => $this->managementArea->id]))
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_activity_option_exposes_swakelola_flag(): void
    {
        $response = $this->getJson(route('erkap.coa-options.activities', [
            'management_area_id' => $this->managementArea->id,
        ]));

        $codes = collect($response->json('data'))->keyBy('code');

        $this->assertTrue($codes['202']['is_swakelola']);
    }

    public function test_cost_centers_are_composed_from_the_four_segments(): void
    {
        $expected = CoaCode::composeCostCenter([
            'business_unit' => 'A',
            'location' => '01',
            'management_area' => '20200',
            'activity' => '202',
        ]);

        $this->assertSame('A0120200202', $expected);

        $this->getJson(route('erkap.coa-options.cost-centers', [
            'management_area_id' => $this->managementArea->id,
        ]))
            ->assertOk()
            ->assertJsonPath('data.0.code', $expected)
            ->assertJsonCount(1, 'data');
    }

    public function test_cost_elements_without_a_cost_center_return_the_full_list(): void
    {
        CostElement::factory()->count(2)->create();

        $this->getJson(route('erkap.coa-options.cost-elements'))
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_cost_elements_are_limited_to_those_available_on_the_cost_center(): void
    {
        ChartOfAccount::factory()->composed($this->costCenter, $this->costElement)->create();
        CostElement::factory()->create();

        $this->getJson(route('erkap.coa-options.cost-elements', ['cost_center_id' => $this->costCenter->id]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', $this->costElement->code);
    }

    public function test_accounts_are_filtered_by_cost_center_and_element(): void
    {
        $account = ChartOfAccount::factory()->composed($this->costCenter, $this->costElement)->create();

        // Pusat Biaya lain harus punya segmen berbeda, kalau tidak kodenya
        // bentrok dengan kombinasi di atas. Kode Lokasi '02' dipakai karena
        // unik per (kode, Bisnis Unit) dan '01' sudah dipakai di atas.
        $otherLocation = Location::factory()->create([
            'code' => '02',
            'erkap_business_unit_id' => $this->businessUnit->id,
        ]);
        $otherArea = ManagementArea::factory()->create([
            'code' => '30300',
            'erkap_location_id' => $otherLocation->id,
            'erkap_business_unit_id' => $this->businessUnit->id,
        ]);
        $otherActivity = Activity::factory()->create(['erkap_management_area_id' => $otherArea->id]);
        $otherCenter = CostCenter::factory()->create([
            'erkap_business_unit_id' => $this->businessUnit->id,
            'erkap_location_id' => $otherLocation->id,
            'erkap_management_area_id' => $otherArea->id,
            'erkap_activity_id' => $otherActivity->id,
        ]);
        $otherElement = CostElement::factory()->create();
        ChartOfAccount::factory()->composed($otherCenter, $otherElement)->create();

        $this->getJson(route('erkap.coa-options.accounts', [
            'cost_center_id' => $this->costCenter->id,
            'cost_element_id' => $this->costElement->id,
        ]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', $account->code);
    }

    public function test_account_option_exposes_the_fifteen_character_code(): void
    {
        $account = ChartOfAccount::factory()->composed($this->costCenter, $this->costElement)->create();

        $this->assertSame(15, strlen($account->code));

        $this->getJson(route('erkap.coa-options.accounts', ['cost_center_id' => $this->costCenter->id]))
            ->assertOk()
            ->assertJsonPath('data.0.code', $account->code)
            ->assertJsonPath('data.0.cost_center_id', $this->costCenter->id)
            ->assertJsonPath('data.0.cost_element_id', $this->costElement->id);
    }

    public function test_lookup_resolves_a_full_chain_from_a_cost_center_code(): void
    {
        $this->getJson(route('erkap.coa-options.lookup', ['code' => $this->costCenter->code]))
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('data.segments.business_unit', 'A')
            ->assertJsonPath('data.segments.location', '01')
            ->assertJsonPath('data.segments.management_area', '20200')
            ->assertJsonPath('data.segments.activity', '202')
            ->assertJsonPath('data.cost_center.id', $this->costCenter->id)
            ->assertJsonPath('data.business_unit.id', $this->businessUnit->id)
            ->assertJsonPath('data.location.id', $this->location->id)
            ->assertJsonPath('data.management_area.id', $this->managementArea->id)
            ->assertJsonPath('data.activity.id', $this->activity->id);
    }

    public function test_lookup_resolves_the_account_from_a_fifteen_character_code(): void
    {
        $account = ChartOfAccount::factory()->composed($this->costCenter, $this->costElement)->create();

        $this->getJson(route('erkap.coa-options.lookup', ['code' => $account->code]))
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('data.cost_element.id', $this->costElement->id)
            ->assertJsonPath('data.chart_of_account.id', $account->id)
            ->assertJsonPath('data.code', $account->code);
    }

    public function test_lookup_reports_not_found_for_a_well_formed_but_unknown_code(): void
    {
        // Format sah (11 karakter) tapi tidak merujuk Pusat Biaya mana pun.
        $this->getJson(route('erkap.coa-options.lookup', ['code' => 'Z9909999999']))
            ->assertOk()
            ->assertJsonPath('found', false)
            ->assertJsonPath('data.cost_center', null)
            ->assertJsonPath('data.code', null);
    }

    public function test_lookup_returns_an_empty_chain_for_a_malformed_code(): void
    {
        $this->getJson(route('erkap.coa-options.lookup', ['code' => 'bukan-kode']))
            ->assertOk()
            ->assertJsonPath('found', false)
            ->assertJsonPath('data', []);
    }

    public function test_lookup_requires_a_code_parameter(): void
    {
        $this->getJson(route('erkap.coa-options.lookup'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    public function test_option_endpoints_require_authentication(): void
    {
        auth()->logout();

        $this->getJson(route('erkap.coa-options.business-units'))->assertUnauthorized();
        $this->getJson(route('erkap.coa-options.accounts'))->assertUnauthorized();
        $this->getJson(route('erkap.coa-options.lookup', ['code' => $this->costCenter->code]))->assertUnauthorized();
    }
}
