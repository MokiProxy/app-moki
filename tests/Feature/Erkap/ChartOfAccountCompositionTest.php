<?php

namespace Tests\Feature\Erkap;

use App\Models\ChartOfAccount;
use App\Models\Division;
use App\Models\Erkap\Activity;
use App\Models\Erkap\BusinessUnit;
use App\Models\Erkap\CostCenter;
use App\Models\Erkap\CostElement;
use App\Models\Erkap\Location;
use App\Models\Erkap\ManagementArea;
use App\Support\CoaCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\ActsAsErkapRole;
use Tests\TestCase;

/**
 * F6 — Chart of Account sebagai hasil komposisi Pusat Biaya (a..d) + Elemen
 * Biaya (e).
 *
 * `code` COA tidak boleh diketik manual. Test ini memverifikasi kode disusun
 * server dari master, kombinasi Pusat Biaya × Elemen Biaya bersifat unik,
 * `sync()` melengkapi kombinasi yang kosong secara idempoten, dan tautan COA
 * default pada Elemen Biaya selalu terisi.
 */
class ChartOfAccountCompositionTest extends TestCase
{
    use ActsAsErkapRole, RefreshDatabase;

    private function admin(): void
    {
        $this->actAsErkapRole('erkap-admin');
    }

    /**
     * Rantai master a → b → c → d yang konsisten, dipakai untuk membentuk Pusat
     * Biaya. Semua kode dibiarkan oleh factory: ruang kode segmen a hanya satu
     * karakter, sehingga hard-code huruf di sini mudah bentrok dengan unit lain
     * yang dibuat factory di test yang sama.
     *
     * @return array{businessUnit: BusinessUnit, location: Location, managementArea: ManagementArea, activity: Activity}
     */
    private function chain(int $variant = 0): array
    {
        $businessUnit = BusinessUnit::factory()->create(['name' => 'Zebra '.$variant]);
        $location = Location::factory()->create([
            'erkap_business_unit_id' => $businessUnit->id,
        ]);
        $managementArea = ManagementArea::factory()->create([
            'erkap_location_id' => $location->id,
        ]);
        $activity = Activity::factory()->create([
            'erkap_management_area_id' => $managementArea->id,
        ]);

        return compact('businessUnit', 'location', 'managementArea', 'activity');
    }

    private function costCenter(array $chain, string $name = 'Pusat Biaya Uji'): CostCenter
    {
        return CostCenter::create([
            'erkap_business_unit_id' => $chain['businessUnit']->id,
            'erkap_location_id' => $chain['location']->id,
            'erkap_management_area_id' => $chain['managementArea']->id,
            'erkap_activity_id' => $chain['activity']->id,
            'name' => $name,
            'owner' => 'Pemilik Uji',
            'division_id' => Division::factory()->create()->id,
        ]);
    }

    /**
     * Pusat Biaya kedua dengan rantai master sendiri.
     */
    private function anotherCostCenter(int $variant, string $name): CostCenter
    {
        return $this->costCenter($this->chain($variant), $name);
    }

    private function costElement(?string $code = null, string $name = 'Beban Uji'): CostElement
    {
        return CostElement::factory()->create(array_filter([
            'code' => $code,
            'name' => $name,
        ], fn ($value) => $value !== null));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(CostCenter $costCenter, CostElement $element, array $overrides = []): array
    {
        return array_merge([
            'cost_center_id' => $costCenter->id,
            'cost_element_id' => $element->id,
        ], $overrides);
    }

    /* ---------------------------------------------------------------------
     | Komposisi kode
     | ------------------------------------------------------------------ */

    public function test_it_composes_the_code_from_cost_center_and_cost_element(): void
    {
        $this->admin();
        $costCenter = $this->costCenter($this->chain());
        $element = $this->costElement();

        $this->post(route('erkap.chart-of-accounts.store'), $this->payload($costCenter, $element))
            ->assertRedirect(route('erkap.chart-of-accounts.index'));

        // a=1 + b=2 + c=5 + d=3 + e=4 = 15 karakter.
        $expected = CoaCode::compose(array_merge($costCenter->segments(), ['cost_element' => $element->code]));

        $this->assertSame(15, strlen((string) $expected));
        $this->assertDatabaseHas('chart_of_accounts', [
            'code' => $expected,
            'cost_center_id' => $costCenter->id,
            'cost_element_id' => $element->id,
        ]);
    }

    public function test_it_ignores_a_client_supplied_code(): void
    {
        $this->admin();
        $costCenter = $this->costCenter($this->chain());
        $element = $this->costElement();

        $this->post(route('erkap.chart-of-accounts.store'), $this->payload($costCenter, $element, [
            'code' => 'HACKED99999999',
        ]))->assertRedirect(route('erkap.chart-of-accounts.index'));

        $this->assertDatabaseMissing('chart_of_accounts', ['code' => 'HACKED99999999']);

        $account = ChartOfAccount::firstOrFail();
        $this->assertSame(15, strlen($account->code));
        $this->assertSame(
            $account->code,
            CoaCode::compose(array_merge($costCenter->segments(), ['cost_element' => $element->code]))
        );
    }

    public function test_it_rejects_a_duplicate_center_element_combination(): void
    {
        $this->admin();
        $costCenter = $this->costCenter($this->chain());
        $element = $this->costElement();

        $this->post(route('erkap.chart-of-accounts.store'), $this->payload($costCenter, $element))
            ->assertRedirect(route('erkap.chart-of-accounts.index'));

        $this->from(route('erkap.chart-of-accounts.create'))
            ->post(route('erkap.chart-of-accounts.store'), $this->payload($costCenter, $element))
            ->assertRedirect(route('erkap.chart-of-accounts.create'))
            ->assertSessionHasErrors('code');

        $this->assertSame(1, ChartOfAccount::count());
    }

    public function test_it_allows_the_same_element_on_a_different_cost_center(): void
    {
        $this->admin();
        $element = $this->costElement();

        $first = $this->costCenter($this->chain());
        $second = $this->anotherCostCenter(1, 'Pusat Biaya Kedua');

        $this->post(route('erkap.chart-of-accounts.store'), $this->payload($first, $element))
            ->assertRedirect(route('erkap.chart-of-accounts.index'));
        $this->post(route('erkap.chart-of-accounts.store'), $this->payload($second, $element))
            ->assertRedirect(route('erkap.chart-of-accounts.index'));

        $this->assertSame(2, ChartOfAccount::count());
    }

    public function test_it_requires_both_a_cost_center_and_a_cost_element(): void
    {
        $this->admin();
        $costCenter = $this->costCenter($this->chain());
        $element = $this->costElement();

        $this->from(route('erkap.chart-of-accounts.create'))
            ->post(route('erkap.chart-of-accounts.store'), ['cost_center_id' => $costCenter->id])
            ->assertSessionHasErrors('cost_element_id');

        $this->from(route('erkap.chart-of-accounts.create'))
            ->post(route('erkap.chart-of-accounts.store'), ['cost_element_id' => $element->id])
            ->assertSessionHasErrors('cost_center_id');

        $this->assertSame(0, ChartOfAccount::count());
    }

    /* ---------------------------------------------------------------------
     | Default name & type
     | ------------------------------------------------------------------ */

    public function test_it_defaults_the_name_from_the_cost_element_and_cost_center(): void
    {
        $this->admin();
        $costCenter = $this->costCenter($this->chain(), 'Penambangan Swakelola');
        $element = $this->costElement(null, 'Pendapatan Jasa Kupas Tanah');

        $this->post(route('erkap.chart-of-accounts.store'), $this->payload($costCenter, $element))
            ->assertRedirect(route('erkap.chart-of-accounts.index'));

        $account = ChartOfAccount::firstOrFail();
        $this->assertStringContainsString('Pendapatan Jasa Kupas Tanah', $account->name);
        $this->assertStringContainsString('Penambangan Swakelola', $account->name);
    }

    public function test_it_keeps_an_explicit_name_and_type(): void
    {
        $this->admin();
        $costCenter = $this->costCenter($this->chain());
        $element = $this->costElement();

        $this->post(route('erkap.chart-of-accounts.store'), $this->payload($costCenter, $element, [
            'name' => 'Akun PengHvatan',
            'type' => 'revenue',
        ]))->assertRedirect(route('erkap.chart-of-accounts.index'));

        $this->assertDatabaseHas('chart_of_accounts', [
            'name' => 'Akun PengHvatan',
            'type' => 'revenue',
        ]);
    }

    public function test_it_defaults_the_type_from_an_existing_account_of_the_element(): void
    {
        $this->admin();
        $element = $this->costElement();
        $seed = $this->costCenter($this->chain());

        // COA pertama menentukan tipe elemen, dibuat langsung lewat model agar
        // tipe `revenue` tidak ditimpa default.
        ChartOfAccount::create([
            'cost_center_id' => $seed->id,
            'cost_element_id' => $element->id,
            'name' => 'Awal',
            'type' => 'revenue',
        ]);

        $target = $this->anotherCostCenter(1, 'Pusat Biaya Kedua');
        $this->post(route('erkap.chart-of-accounts.store'), $this->payload($target, $element))
            ->assertRedirect(route('erkap.chart-of-accounts.index'));

        $this->assertDatabaseHas('chart_of_accounts', [
            'cost_center_id' => $target->id,
            'cost_element_id' => $element->id,
            'type' => 'revenue',
        ]);
    }

    /* ---------------------------------------------------------------------
     | Sinkronisasi kombinasi
     | ------------------------------------------------------------------ */

    public function test_sync_creates_every_missing_combination(): void
    {
        $this->admin();
        $first = $this->costCenter($this->chain(), 'Pusat Biaya Satu');
        $second = $this->anotherCostCenter(1, 'Pusat Biaya Dua');
        $elementA = $this->costElement(null, 'Beban A');
        $elementB = $this->costElement(null, 'Beban B');

        $this->post(route('erkap.chart-of-accounts.sync'))
            ->assertRedirect(route('erkap.chart-of-accounts.index'));

        // 2 Pusat Biaya × 2 Elemen Biaya = 4 kombinasi, tanpa duplikat.
        $this->assertSame(4, ChartOfAccount::count());
        $this->assertSame(0, ChartOfAccount::query()
            ->groupBy('cost_center_id', 'cost_element_id')
            ->havingRaw('COUNT(*) > 1')
            ->count(DB::raw('1')), 'Tidak boleh ada kombinasi Pusat Biaya × Elemen Biaya yang ganda.');

        foreach ([[$first, $elementA], [$first, $elementB], [$second, $elementA], [$second, $elementB]] as [$cc, $el]) {
            $this->assertDatabaseHas('chart_of_accounts', [
                'cost_center_id' => $cc->id,
                'cost_element_id' => $el->id,
                'code' => CoaCode::compose(array_merge($cc->segments(), ['cost_element' => $el->code])),
            ]);
        }
    }

    public function test_sync_is_idempotent(): void
    {
        $this->admin();
        $this->costCenter($this->chain());
        $this->costElement();

        $this->post(route('erkap.chart-of-accounts.sync'))->assertRedirect();
        $after = ChartOfAccount::count();

        $this->post(route('erkap.chart-of-accounts.sync'))->assertRedirect();
        $this->assertSame($after, ChartOfAccount::count(), 'Sinkronisasi kedua tidak boleh menambah baris.');
    }

    public function test_sync_does_not_duplicate_an_existing_combination(): void
    {
        $this->admin();
        $costCenter = $this->costCenter($this->chain());
        $element = $this->costElement();

        $this->post(route('erkap.chart-of-accounts.store'), $this->payload($costCenter, $element, [
            'name' => 'Sudah Ada',
            'type' => 'revenue',
        ]))->assertRedirect();

        $this->post(route('erkap.chart-of-accounts.sync'))->assertRedirect();

        $this->assertSame(1, ChartOfAccount::count());
        $this->assertDatabaseHas('chart_of_accounts', [
            'cost_center_id' => $costCenter->id,
            'cost_element_id' => $element->id,
            'name' => 'Sudah Ada',
            'type' => 'revenue',
        ]);
    }

    public function test_sync_reports_the_totals_in_the_flash_message(): void
    {
        $this->admin();
        $this->costCenter($this->chain());
        $this->costElement();

        $response = $this->post(route('erkap.chart-of-accounts.sync'));
        $response->assertRedirect(route('erkap.chart-of-accounts.index'));

        $this->assertStringContainsString(
            'Kombinasi terisi 1 dari 1',
            (string) session('success')
        );
    }

    public function test_sync_reports_remaining_gaps(): void
    {
        $this->admin();
        $costCenter = $this->costCenter($this->chain());
        $elementA = $this->costElement(null, 'A');
        $elementB = $this->costElement(null, 'B');

        // Satu kombinasi sudah diisi manual, sisanya dilengkapi oleh sync.
        $this->post(route('erkap.chart-of-accounts.store'), $this->payload($costCenter, $elementA))
            ->assertRedirect();

        $this->post(route('erkap.chart-of-accounts.sync'))->assertRedirect();

        $this->assertSame(2, ChartOfAccount::count());
        $this->assertStringContainsString('Kombinasi terisi 2 dari 2', (string) session('success'));
        $this->assertStringNotContainsString('Masih ada', (string) session('success'));
    }

    public function test_sync_links_a_default_account_to_every_cost_element(): void
    {
        $this->admin();
        $this->costCenter($this->chain());
        $element = $this->costElement();

        $this->assertNull($element->fresh()->chart_of_account_id);

        $this->post(route('erkap.chart-of-accounts.sync'))->assertRedirect();

        $account = ChartOfAccount::where('cost_element_id', $element->id)->firstOrFail();
        $this->assertSame($account->id, $element->fresh()->chart_of_account_id);
    }

    public function test_sync_does_not_overwrite_an_existing_element_link(): void
    {
        $this->admin();
        $element = $this->costElement();
        $preferred = $this->costCenter($this->chain(), 'Pusat Biaya Pilihan');
        $element->update([
            'chart_of_account_id' => ChartOfAccount::create([
                'cost_center_id' => $preferred->id,
                'cost_element_id' => $element->id,
                'name' => 'Pilihan',
                'type' => 'expense',
            ])->id,
        ]);

        $this->anotherCostCenter(1, 'Pusat Biaya Lain');
        $this->post(route('erkap.chart-of-accounts.sync'))->assertRedirect();

        $this->assertSame(
            ChartOfAccount::where('cost_center_id', $preferred->id)->firstOrFail()->id,
            $element->fresh()->chart_of_account_id
        );
    }

    /* ---------------------------------------------------------------------
     | Halaman
     | ------------------------------------------------------------------ */

    public function test_the_create_page_embeds_the_cascade_without_a_manual_code_field(): void
    {
        $this->admin();
        $this->costCenter($this->chain());

        $this->get(route('erkap.chart-of-accounts.create'))
            ->assertOk()
            ->assertSee('cost_center_id', false)
            ->assertSee('cost_element_id', false)
            ->assertSee('erkap-cascade.js', false)
            ->assertSee('parentQuery', false)
            // Form tidak boleh punya input kode yang bisa diketik user.
            ->assertDontSee('name="code"', false);
    }

    public function test_the_index_shows_the_composed_code_and_coverage(): void
    {
        $this->admin();
        $costCenter = $this->costCenter($this->chain());
        $element = $this->costElement();

        $this->post(route('erkap.chart-of-accounts.store'), $this->payload($costCenter, $element))
            ->assertRedirect();

        $this->get(route('erkap.chart-of-accounts.index'))
            ->assertOk()
            ->assertSee(CoaCode::format(CoaCode::compose(array_merge(
                $costCenter->segments(),
                ['cost_element' => $element->code]
            ))))
            ->assertSee('Cakupan Kombinasi Pusat Biaya', false);
    }

    public function test_the_show_page_decomposes_the_code(): void
    {
        $this->admin();
        $costCenter = $this->costCenter($this->chain(), 'Penambangan Swakelola');
        $element = $this->costElement(null, 'Pendapatan Jasa');

        $this->post(route('erkap.chart-of-accounts.store'), $this->payload($costCenter, $element))
            ->assertRedirect();

        $account = ChartOfAccount::firstOrFail();

        $this->get(route('erkap.chart-of-accounts.show', $account))
            ->assertOk()
            ->assertSee('Komposisi Kode', false)
            ->assertSee('Pendapatan Jasa')
            ->assertSee('Penambangan Swakelola');
    }

    /* ---------------------------------------------------------------------
     | Permission
     | ------------------------------------------------------------------ */

    public function test_a_user_without_permission_cannot_open_the_create_page(): void
    {
        $this->actAsErkapRole('erkap-auditor');

        $this->get(route('erkap.chart-of-accounts.create'))->assertForbidden();
    }

    public function test_a_user_without_permission_cannot_store(): void
    {
        $this->actAsErkapRole('erkap-auditor');
        $costCenter = $this->costCenter($this->chain());
        $element = $this->costElement();

        $this->post(route('erkap.chart-of-accounts.store'), $this->payload($costCenter, $element))
            ->assertForbidden();

        $this->assertSame(0, ChartOfAccount::count());
    }

    public function test_a_user_without_permission_cannot_sync(): void
    {
        $this->actAsErkapRole('erkap-auditor');

        $this->post(route('erkap.chart-of-accounts.sync'))->assertForbidden();
    }
}
