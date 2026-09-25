<?php

namespace Tests\Feature\Erkap;

use App\Models\Erkap\CostCenter;
use App\Models\Erkap\CostElement;
use App\Models\Erkap\CostElementCategory;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\WorkProgram;
use App\Models\User;
use App\Services\Erkap\RKAPLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\ActsAsSuperAdmin;
use Tests\Concerns\BuildsErkapChain;
use Tests\TestCase;

class RKAPLifecycleFeatureTest extends TestCase
{
    use RefreshDatabase, ActsAsSuperAdmin, BuildsErkapChain;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->setUpSuperAdmin();

        foreach (['erkap-admin', 'erkap-ppk'] as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $permissions = [
            'erkap.rkap.view',
            'erkap.rkap.edit',
            'erkap.routine-costs.view',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        Role::findByName('erkap-ppk', 'web')->givePermissionTo(['erkap.rkap.view', 'erkap.rkap.edit']);
    }

    private function roleUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_show_page_displays_lifecycle_for_viewer(): void
    {
        $rkap = RKAP::factory()->create(['year' => 2026, 'phase' => 'preparation']);

        $this->actingAs($this->roleUser('erkap-ppk'))
            ->get(route('erkap.rkap.show', $rkap->id))
            ->assertOk()
            ->assertSee($rkap->phaseLabel())
            ->assertSee('Inisiasi & Kick-off');
    }

    public function test_advance_updates_phase(): void
    {
        $rkap = RKAP::factory()->create(['phase' => 'initiation']);

        $this->actingAs($this->roleUser('erkap-ppk'))
            ->post(route('erkap.rkap.advance', $rkap->id))
            ->assertRedirect(route('erkap.rkap.show', $rkap->id));

        $this->assertSame('preparation', $rkap->refresh()->phase);
    }

    public function test_advance_requires_edit_permission(): void
    {
        $rkap = RKAP::factory()->create(['phase' => 'initiation']);
        $viewer = User::factory()->create();

        $this->actingAs($viewer)
            ->post(route('erkap.rkap.advance', $rkap->id))
            ->assertForbidden();

        $this->assertSame('initiation', $rkap->refresh()->phase);
    }

    public function test_kickoff_saves_schedule_and_attendees(): void
    {
        $rkap = RKAP::factory()->create();

        $this->actingAs($this->roleUser('erkap-ppk'))
            ->post(route('erkap.rkap.kickoff', $rkap->id), [
                'kickoff_date' => '2026-02-02',
                'kickoff_notes' => 'Kick-off dilakukan secara hybrid.',
                'attendees' => [
                    ['name' => 'Ahmad', 'division_id' => null, 'attended' => 1],
                    ['name' => 'Budi', 'division_id' => null, 'attended' => 0],
                ],
            ])
            ->assertRedirect(route('erkap.rkap.show', $rkap->id));

        $rkap->refresh()->load('kickoffAttendees');

        $this->assertSame('2026-02-02', $rkap->kickoff_date->format('Y-m-d'));
        $this->assertCount(2, $rkap->kickoffAttendees);
        $this->assertTrue($rkap->kickoffAttendees->first()->attended);
        $this->assertFalse($rkap->kickoffAttendees->get(1)->attended);
    }

    public function test_direction_upload_stores_file(): void
    {
        Storage::fake('public');

        $rkap = RKAP::factory()->create();

        $this->actingAs($this->roleUser('erkap-ppk'))
            ->post(route('erkap.rkap.direction', $rkap->id), [
                'direction_notes' => 'Arahan direksi terkait efisiensi.',
                'direction_file' => UploadedFile::fake()->create('arahan.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('erkap.rkap.show', $rkap->id));

        $rkap->refresh();

        $this->assertNotNull($rkap->direction_file_path);

        Storage::disk('public')->assertExists($rkap->direction_file_path);
    }

    public function test_distribute_marks_distributed(): void
    {
        $rkap = RKAP::factory()->create(['phase' => 'approved']);
        $admin = $this->roleUser('erkap-admin');

        $this->actingAs($this->roleUser('erkap-ppk'))
            ->post(route('erkap.rkap.distribute', $rkap->id))
            ->assertRedirect(route('erkap.rkap.show', $rkap->id));

        $this->assertSame('distributed', $rkap->refresh()->distribution_status);

        Notification::assertSentTo($admin, \App\Notifications\RkapLifecycleNotification::class);
    }

    public function test_store_routine_cost_blocked_when_lock_phase(): void
    {
        $chain = $this->buildErkapChain();
        $rkap = RKAP::find($chain['rkapId']);
        $rkap->update(['phase' => 'finalization']);

        $this->actingAs($this->user)
            ->post(route('erkap.routine-costs.store'), $this->costPayload($chain['workProgram']->id))
            ->assertRedirect(route('erkap.routine-costs.create'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('erkap_routine_costs', 0);
    }

    public function test_store_routine_cost_allowed_during_open_phase(): void
    {
        $chain = $this->buildErkapChain();
        $rkap = RKAP::find($chain['rkapId']);
        $rkap->update(['phase' => 'preparation']);

        $this->actingAs($this->user)
            ->post(route('erkap.routine-costs.store'), $this->costPayload($chain['workProgram']->id))
            ->assertRedirect(route('erkap.routine-costs.index'));

        $this->assertDatabaseHas('erkap_routine_costs', [
            'erkap_work_program_id' => $chain['workProgram']->id,
        ]);
    }

    private function costPayload(int $workProgramId): array
    {
        $costCenter = CostCenter::factory()->create(['code' => 'CC-LIFE']);
        $costElement = CostElement::factory()->create([
            'code' => 'CE-LIFE',
            'erkap_cost_element_category_id' => CostElementCategory::factory()->create()->id,
        ]);

        return [
            'erkap_work_program_id' => $workProgramId,
            'need' => 'Kebutuhan biaya uji lifecycle',
            'cost_center_id' => $costCenter->id,
            'cost_center_owner' => 'Divisi Uji',
            'qty' => 1,
            'units' => 'Paket',
            'unit_price' => 100000,
            'erkap_cost_element_id' => $costElement->id,
            'total' => 100000,
            'is_kumulatif' => 1,
        ];
    }

    public function test_rkap_index_shows_lifecycle_columns(): void
    {
        $this->actingAs($this->roleUser('erkap-ppk'))
            ->get(route('erkap.rkap.index'))
            ->assertOk()
            ->assertSee('Fase Lifecycle');
    }
}