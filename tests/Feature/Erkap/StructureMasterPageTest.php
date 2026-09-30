<?php

namespace Tests\Feature\Erkap;

use App\Models\Division;
use App\Models\Erkap\Activity;
use App\Models\Erkap\BusinessUnit;
use App\Models\Erkap\Location;
use App\Models\Erkap\ManagementArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ActsAsErkapRole;
use Tests\TestCase;

/**
 * Smoke test halaman master struktur a..d.
 *
 * Tujuannya menangkap error render Blade (include salah, variabel yang tidak
 * diteruskan, route yang tidak ada) yang tidak akan terlihat dari test CRUD
 * murni karena test itu tidak merender view.
 */
class StructureMasterPageTest extends TestCase
{
    use RefreshDatabase, ActsAsErkapRole;

    private BusinessUnit $businessUnit;

    private Location $location;

    private ManagementArea $managementArea;

    private Activity $activity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actAsErkapRole('erkap-admin');

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
            'division_id' => Division::factory()->create()->id,
        ]);
        $this->activity = Activity::factory()->create([
            'code' => '202',
            'name' => 'Operasional Harian',
            'erkap_management_area_id' => $this->managementArea->id,
        ]);
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function pageProvider(): array
    {
        return [
            'business unit index' => ['erkap.business-units.index', 'Bisnis Unit'],
            'business unit create' => ['erkap.business-units.create', 'Buat Bisnis Unit'],
            'location index' => ['erkap.locations.index', 'Lokasi'],
            'location create' => ['erkap.locations.create', 'Buat Lokasi'],
            'management area index' => ['erkap.management-areas.index', 'Manajemen Area'],
            'management area create' => ['erkap.management-areas.create', 'Buat Manajemen Area'],
            'activity index' => ['erkap.activities.index', 'Aktivitas'],
            'activity create' => ['erkap.activities.create', 'Buat Aktivitas'],
        ];
    }

    /**
     * @dataProvider pageProvider
     */
    public function test_it_renders_the_index_and_create_pages(string $routeName, string $expectedText): void
    {
        $this->get(route($routeName))
            ->assertOk()
            ->assertSee($expectedText, false);
    }

    public function test_it_renders_the_business_unit_edit_page(): void
    {
        $this->get(route('erkap.business-units.edit', $this->businessUnit))
            ->assertOk()
            ->assertSee('Korporat')
            ->assertSee('Edit Bisnis Unit');
    }

    public function test_it_renders_the_location_edit_page_with_its_parent(): void
    {
        $this->get(route('erkap.locations.edit', $this->location))
            ->assertOk()
            ->assertSee('Jakarta')
            ->assertSee('Edit Lokasi');
    }

    public function test_it_renders_the_management_area_edit_page_with_its_division(): void
    {
        $this->get(route('erkap.management-areas.edit', $this->managementArea))
            ->assertOk()
            ->assertSee('Operasional')
            ->assertSee('Edit Manajemen Area');
    }

    public function test_it_renders_the_activity_edit_page_with_the_whole_chain(): void
    {
        $this->get(route('erkap.activities.edit', $this->activity))
            ->assertOk()
            ->assertSee('Operasional Harian')
            ->assertSee('Edit Aktivitas');
    }

    public function test_the_activity_form_embeds_the_cascade_endpoint(): void
    {
        $response = $this->get(route('erkap.activities.create'))->assertOk();

        // Helper cascading hanya berfungsi bila base URL diteruskan ke view.
        $response->assertSee('ERKAP_COA_OPTIONS_URL', false);
        $response->assertSee('erkap-cascade.js', false);
        // `@json()` meloloskan garis miring, jadi yang diharapkan adalah
        // hasil json_encode dari URL tersebut.
        $response->assertSee(json_encode(route('erkap.coa-options.index')), false);
    }

    public function test_the_activity_form_shows_the_composed_code_preview(): void
    {
        $this->get(route('erkap.activities.create'))
            ->assertOk()
            ->assertSee('erkap_code_preview', false);
    }

    public function test_the_sidebar_links_to_the_structure_section(): void
    {
        $this->get(route('erkap.index'))
            ->assertOk()
            ->assertSee(route('erkap.business-units.index'), false)
            ->assertSee(route('erkap.activities.index'), false);
    }
}
