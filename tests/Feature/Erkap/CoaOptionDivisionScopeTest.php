<?php

namespace Tests\Feature\Erkap;

use App\Models\ChartOfAccount;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Erkap\Activity;
use App\Models\Erkap\CostCenter;
use App\Models\Erkap\CostElement;
use App\Models\Erkap\ManagementArea;
use App\Models\Regional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\ActsAsErkapRole;
use Tests\TestCase;

/**
 * F10 — audit keamanan scoping divisi pada endpoint opsi F4.
 *
 * `ErkapAccess` membatasi `erkap-cost-owner` ke divisi(employee)-nya sendiri,
 * tetapi pembatasan itu hanya berguna bila diterapkan pada setiap query. Slash
 * pemeriksa F10 menemukan tiga endpoint yang menerima filter dari client
 * (`management-areas?division_id=`, `activities`, `lookup?code=`) tanpa melewati
 * `ErkapAccess`, sehingga pemilik biaya bisa membaca struktur milik divisi lain
 * hanya dengan mengetik parameter atau kode.
 */
class CoaOptionDivisionScopeTest extends TestCase
{
    use ActsAsErkapRole, RefreshDatabase;

    private Division $ownDivision;

    private Division $otherDivision;

    private ManagementArea $ownArea;

    private ManagementArea $otherArea;

    private CostCenter $ownCostCenter;

    private CostCenter $otherCostCenter;

    private CostElement $ownElement;

    private CostElement $otherElement;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ownDivision = Division::factory()->create(['name' => 'Divisi Sendiri']);
        $this->otherDivision = Division::factory()->create(['name' => 'Divisi Lain']);

        $this->ownArea = $this->areaFor($this->ownDivision, '20200');
        $this->otherArea = $this->areaFor($this->otherDivision, '30300');

        [$this->ownCostCenter, $this->otherCostCenter] = $this->costCentersFor($this->ownArea, $this->otherArea);
        [$this->ownElement, $this->otherElement] = [
            CostElement::factory()->create(),
            CostElement::factory()->create(),
        ];

        ChartOfAccount::factory()->composed($this->ownCostCenter, $this->ownElement)->create();
        ChartOfAccount::factory()->composed($this->otherCostCenter, $this->otherElement)->create();

        $this->loginAsCostOwnerOf($this->ownDivision);
    }

    /* ---------------------------------------------------------------------
     | management-areas?division_id=
     | ------------------------------------------------------------------ */

    public function test_management_areas_only_returns_the_users_own_division(): void
    {
        $codes = $this->codesOf(
            $this->getJson(route('erkap.coa-options.management-areas'))->assertOk()
        );

        $this->assertSame([$this->ownArea->code], $codes);
    }

    public function test_a_cost_owner_cannot_request_another_divisions_management_areas(): void
    {
        // Parameter `division_id` berasal dari client, jadi tidak boleh
        // menimpa divisi milik pengguna meski nilainya ID divisi yang valid.
        $response = $this->getJson(route('erkap.coa-options.management-areas', [
            'division_id' => $this->otherDivision->id,
        ]))->assertOk();

        $this->assertSame([$this->ownArea->code], $this->codesOf($response));
        $this->assertStringNotContainsString($this->otherArea->name, $response->getContent());
    }

    /* ---------------------------------------------------------------------
     | activities
     | ------------------------------------------------------------------ */

    public function test_activities_of_another_division_are_hidden(): void
    {
        $own = $this->getJson(route('erkap.coa-options.activities', [
            'management_area_id' => $this->ownArea->id,
        ]))->assertOk();

        $other = $this->getJson(route('erkap.coa-options.activities', [
            'management_area_id' => $this->otherArea->id,
        ]))->assertOk();

        $this->assertCount(1, $own->json('data'));
        $this->assertCount(0, $other->json('data'));
    }

    public function test_unfiltered_activities_never_include_another_division(): void
    {
        $codes = $this->codesOf($this->getJson(route('erkap.coa-options.activities'))->assertOk());

        $this->assertSame([$this->ownArea->activity->code], $codes);
    }

    /* ---------------------------------------------------------------------
     | lookup?code=
     | ------------------------------------------------------------------ */

    public function test_lookup_resolves_a_cost_center_inside_the_users_division(): void
    {
        $this->getJson(route('erkap.coa-options.lookup', ['code' => $this->ownCostCenter->code]))
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('data.cost_center.id', $this->ownCostCenter->id);
    }

    public function test_lookup_hides_another_divisions_cost_center(): void
    {
        $this->getJson(route('erkap.coa-options.lookup', ['code' => $this->otherCostCenter->code]))
            ->assertOk()
            ->assertJsonPath('found', false)
            ->assertJsonPath('data.cost_center', null)
            ->assertJsonPath('data.code', null);
    }

    public function test_lookup_hides_another_divisions_coa(): void
    {
        $account = ChartOfAccount::query()
            ->where('cost_center_id', $this->otherCostCenter->id)
            ->firstOrFail();

        // Bentuk respons harus identik dengan kode yang sama sekali tidak
        // terdaftar; kalau 403 atau `found: true`, keberadaannya bocor.
        $this->getJson(route('erkap.coa-options.lookup', ['code' => $account->code]))
            ->assertOk()
            ->assertJsonPath('found', false)
            ->assertJsonPath('data.chart_of_account', null)
            ->assertJsonPath('data.cost_element', null)
            ->assertJsonPath('data.code', null);
    }

    /* ---------------------------------------------------------------------
     | cost-elements?cost_center_id=
     | ------------------------------------------------------------------ */

    public function test_cost_elements_reject_another_divisions_cost_center(): void
    {
        $this->getJson(route('erkap.coa-options.cost-elements', [
            'cost_center_id' => $this->otherCostCenter->id,
        ]))->assertForbidden();

        $this->getJson(route('erkap.coa-options.cost-elements', [
            'cost_center_id' => $this->ownCostCenter->id,
        ]))->assertOk()->assertJsonCount(1, 'data');
    }

    /* ---------------------------------------------------------------------
     | accounts & cost-centers
     | ------------------------------------------------------------------ */

    public function test_accounts_never_expose_another_divisions_coa(): void
    {
        $response = $this->getJson(route('erkap.coa-options.accounts'))->assertOk();

        $this->assertSame(
            [$this->ownCostCenter->id],
            collect($response->json('data'))->pluck('cost_center_id')->unique()->values()->all()
        );
    }

    public function test_accounts_ignore_a_requested_foreign_division(): void
    {
        $response = $this->getJson(route('erkap.coa-options.accounts', [
            'division_id' => $this->otherDivision->id,
        ]))->assertOk();

        // Divisi asing tidak boleh mengganti cakupan: hasilnya tetap milik
        // divisi pengguna, bukan kosong dan bukan milik divisi yang diminta.
        $this->assertSame(
            [$this->ownCostCenter->id],
            collect($response->json('data'))->pluck('cost_center_id')->unique()->values()->all()
        );
    }

    public function test_cost_center_list_never_exposes_another_division(): void
    {
        $response = $this->getJson(route('erkap.coa-options.cost-centers'))->assertOk();

        $this->assertSame([$this->ownCostCenter->id], collect($response->json('data'))->pluck('id')->all());
    }

    /* ---------------------------------------------------------------------
     | Helper
     | ------------------------------------------------------------------ */

    /**
     * @return array{0: ManagementArea, 1: ManagementArea}
     */
    private function structureFor(Division $division): array
    {
        return [$this->areaFor($division, '20200'), $this->areaFor($division, '30300')];
    }

    private function areaFor(Division $division, string $code): ManagementArea
    {
        $area = ManagementArea::factory()->create([
            'code' => $code,
            'division_id' => $division->id,
        ]);

        return $area->setRelation('activity', Activity::factory()->create([
            'erkap_management_area_id' => $area->id,
        ]));
    }

    /**
     * @return array{0: CostCenter, 1: CostCenter}
     */
    private function costCentersFor(ManagementArea $a, ManagementArea $b): array
    {
        return [
            CostCenter::factory()->create([
                'division_id' => $a->division_id,
                'erkap_business_unit_id' => $a->erkap_business_unit_id,
                'erkap_location_id' => $a->erkap_location_id,
                'erkap_management_area_id' => $a->id,
                'erkap_activity_id' => $a->activity->id,
            ]),
            CostCenter::factory()->create([
                'division_id' => $b->division_id,
                'erkap_business_unit_id' => $b->erkap_business_unit_id,
                'erkap_location_id' => $b->erkap_location_id,
                'erkap_management_area_id' => $b->id,
                'erkap_activity_id' => $b->activity->id,
            ]),
        ];
    }

    private function loginAsCostOwnerOf(Division $division): void
    {
        $regional = Regional::factory()->create();

        $employee = Employee::create([
            'employee_id' => 'EMP-SCOPE-'.uniqid(),
            'name' => 'Pemilik Biaya',
            'division_id' => $division->id,
            'regional_id' => $regional->id,
        ]);

        $this->actAsErkapRole('erkap-cost-owner');

        $user = User::factory()->create(['employee_id' => $employee->employee_id]);
        $user->assignRole('erkap-cost-owner');
        $this->actingAs($user);
    }

    /**
     * @param  TestResponse  $response
     * @return list<string>
     */
    private function codesOf($response): array
    {
        return collect($response->json('data'))->pluck('code')->sort()->values()->all();
    }
}
