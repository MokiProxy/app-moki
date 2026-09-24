<?php

namespace Tests\Unit\Erkap;

use App\Models\Erkap\RKAP;
use App\Models\User;
use App\Notifications\RkapLifecycleNotification;
use App\Services\Erkap\RKAPLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\Concerns\BuildsErkapChain;
use Tests\TestCase;

class RKAPLifecycleTest extends TestCase
{
    use RefreshDatabase, BuildsErkapChain;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        foreach (['erkap-admin', 'erkap-gate-review', 'erkap-bmi-admin', 'erkap-ppk'] as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    private function rkap(array $attributes = []): RKAP
    {
        return RKAP::factory()->create($attributes);
    }

    private function roleUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_next_phase_follows_sequential_order(): void
    {
        $rkap = $this->rkap(['phase' => 'initiation']);

        $this->assertSame('preparation', $rkap->nextPhase());
        $this->assertTrue($rkap->canTransitionTo('preparation'));
        $this->assertFalse($rkap->canTransitionTo('finalization'));

        $rkap->update(['phase' => 'archived']);

        $this->assertNull($rkap->nextPhase());
    }

    public function test_advance_moves_phase_and_starts_timer(): void
    {
        $rkap = $this->rkap(['phase' => 'initiation', 'phase_started_at' => null]);

        RKAPLifecycleService::advance($rkap);

        $this->assertSame('preparation', $rkap->phase);
        $this->assertNotNull($rkap->phase_started_at);
    }

public function test_advance_is_strictly_sequential(): void
    {
        $rkap = $this->rkap(['phase' => 'consolidation']);

        RKAPLifecycleService::advance($rkap);
        $this->assertSame('finalization', $rkap->phase);

        RKAPLifecycleService::advance($rkap);
        $this->assertSame('approved', $rkap->phase);

        RKAPLifecycleService::advance($rkap);
        $this->assertSame('archived', $rkap->phase);

        $this->expectException(\RuntimeException::class);
        RKAPLifecycleService::advance($rkap);
    }

    public function test_advance_to_approved_sets_resolution_date(): void
    {
        $rkap = $this->rkap(['phase' => 'finalization', 'resolution_date' => null]);

        RKAPLifecycleService::advance($rkap);

        $this->assertSame('approved', $rkap->phase);
        $this->assertNotNull($rkap->resolution_date);
    }

    public function test_advance_applies_meta_fields(): void
    {
        $rkap = $this->rkap(['phase' => 'initiation']);

        RKAPLifecycleService::advance($rkap, [
            'kickoff_date' => '2026-02-01',
            'kickoff_notes' => 'Sosialisasi dilakukan.',
            'direction_notes' => 'Arahan dari direksi.',
        ]);

        $this->assertNotNull($rkap->kickoff_date);
        $this->assertSame('Sosialisasi dilakukan.', $rkap->kickoff_notes);
        $this->assertSame('Arahan dari direksi.', $rkap->direction_notes);
    }

    public function test_reset_phase_resets_lifecycle_when_status_allows(): void
    {
        $rkap = $this->rkap([
            'phase' => 'finalization',
            'status' => 'draft',
            'bmi_alignment_status' => 'aligned',
            'bmi_notes' => 'note',
            'distribution_status' => 'distributed',
        ]);

        RKAPLifecycleService::resetPhase($rkap);

        $this->assertSame('initiation', $rkap->phase);
        $this->assertSame('none', $rkap->bmi_alignment_status);
        $this->assertNull($rkap->bmi_notes);
        $this->assertSame('not_distributed', $rkap->distribution_status);
    }

    public function test_reset_phase_blocked_on_approved_status(): void
    {
        $rkap = $this->rkap(['phase' => 'finalization', 'status' => 'approved']);

        $this->expectException(\RuntimeException::class);
        RKAPLifecycleService::resetPhase($rkap);
    }

    public function test_is_locked_for_input_for_final_phases(): void
    {
        $this->assertFalse($this->rkap(['phase' => 'initiation'])->isLockedForInput());
        $this->assertFalse($this->rkap(['phase' => 'preparation'])->isLockedForInput());
        $this->assertFalse($this->rkap(['phase' => 'consolidation'])->isLockedForInput());
        $this->assertTrue($this->rkap(['phase' => 'finalization'])->isLockedForInput());
        $this->assertTrue($this->rkap(['phase' => 'approved'])->isLockedForInput());
        $this->assertTrue($this->rkap(['phase' => 'archived'])->isLockedForInput());
    }

    public function test_mark_bmi_aligned_requires_gate_review_role(): void
    {
        $rkap = $this->rkap();

        $regularUser = $this->roleUser('erkap-ppk');

        $this->expectException(ValidationException::class);
        RKAPLifecycleService::markBmiAligned($rkap, $regularUser, [
            'bmi_alignment_status' => 'aligned',
            'bmi_notes' => 'Selaras dengan target holding.',
        ]);
    }

    public function test_mark_bmi_aligned_updates_status_for_gate_review(): void
    {
        $rkap = $this->rkap(['bmi_alignment_status' => 'none']);

        RKAPLifecycleService::markBmiAligned($rkap, $this->roleUser('erkap-gate-review'), [
            'bmi_alignment_status' => 'aligned',
            'bmi_notes' => 'Selaras dengan target holding.',
        ]);

        $this->assertSame('aligned', $rkap->bmi_alignment_status);
        $this->assertSame('Selaras dengan target holding.', $rkap->bmi_notes);
    }

    public function test_mark_bmi_aligned_accepts_bmi_admin_role(): void
    {
        $rkap = $this->rkap();

        RKAPLifecycleService::markBmiAligned($rkap, $this->roleUser('erkap-bmi-admin'), [
            'bmi_alignment_status' => 'in_review',
        ]);

        $this->assertSame('in_review', $rkap->bmi_alignment_status);
    }

    public function test_distribute_marks_distributed_and_notifies_admin(): void
    {
        $rkap = $this->rkap(['phase' => 'approved', 'resolution_date' => null]);
        $admin = $this->roleUser('erkap-admin');

        RKAPLifecycleService::distribute($rkap, $this->roleUser('erkap-gate-review'));

        $this->assertSame('distributed', $rkap->distribution_status);
        $this->assertNotNull($rkap->resolution_date);

        Notification::assertSentTo($admin, RkapLifecycleNotification::class);
    }

    public function test_distribute_rejects_already_distributed(): void
    {
        $rkap = $this->rkap(['distribution_status' => 'distributed']);

        $this->expectException(\RuntimeException::class);
        RKAPLifecycleService::distribute($rkap, $this->roleUser('erkap-gate-review'));
    }

    public function test_resolve_for_work_program_returns_rkap(): void
    {
        $chain = $this->buildErkapChain();

        $rkap = RKAPLifecycleService::resolveForWorkProgram($chain['workProgram']->id);

        $this->assertSame($chain['rkapId'], $rkap->id);
    }

    public function test_resolve_for_unknown_work_program_returns_null(): void
    {
        $this->assertNull(RKAPLifecycleService::resolveForWorkProgram(999999));
    }

    public function test_assert_not_locked_passes_for_open_phase(): void
    {
        $rkap = $this->rkap(['phase' => 'preparation']);

        $this->expectNotToPerformAssertions();
        RKAPLifecycleService::assertNotLocked($rkap, 'Biaya rutin');
    }

    public function test_assert_not_locked_throws_for_locked_phase(): void
    {
        $rkap = $this->rkap(['phase' => 'approved', 'year' => 2026]);

        $this->expectException(ValidationException::class);
        RKAPLifecycleService::assertNotLocked($rkap, 'Biaya rutin');
    }
}