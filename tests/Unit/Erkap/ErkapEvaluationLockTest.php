<?php

namespace Tests\Unit\Erkap;

use App\Models\Erkap\RiskIdentification;
use App\Models\User;
use App\Services\ErkapEvaluationLock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ErkapEvaluationLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_is_editable(): void
    {
        $risk = RiskIdentification::factory()->make(['status' => 'draft']);

        ErkapEvaluationLock::assertRiskEditable($risk, User::factory()->make());
        $this->assertFalse(ErkapEvaluationLock::isEvaluated($risk));
    }

    public function test_submitted_and_approved_are_evaluated(): void
    {
        $submitted = RiskIdentification::factory()->make(['status' => 'submitted']);
        $approved = RiskIdentification::factory()->make(['status' => 'approved']);

        $this->assertTrue(ErkapEvaluationLock::isEvaluated($submitted));
        $this->assertTrue(ErkapEvaluationLock::isEvaluated($approved));
    }

    public function test_regular_user_cannot_edit_locked_register(): void
    {
        $risk = RiskIdentification::factory()->make(['status' => 'approved']);
        $user = User::factory()->make();

        $this->expectException(ValidationException::class);

        ErkapEvaluationLock::assertRiskEditable($risk, $user);
    }

    public function test_admin_can_override_lock(): void
    {
        Role::firstOrCreate(['name' => 'erkap-admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('erkap-admin');

        $risk = RiskIdentification::factory()->make(['status' => 'approved']);

        ErkapEvaluationLock::assertRiskEditable($risk, $admin);
        $this->assertTrue(true);
    }
}
