<?php

namespace Tests\Feature\Erkap;

use App\Models\Employee;
use App\Models\Erkap\DepartmentRiskStrategy;
use App\Models\Erkap\RiskIdentification;
use App\Models\Regional;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\ActsAsSuperAdmin;
use Tests\Concerns\BuildsErkapChain;
use Tests\TestCase;

class RiskIdentificationApprovalFeatureTest extends TestCase
{
    use ActsAsSuperAdmin, BuildsErkapChain, RefreshDatabase;

    private array $chain;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->setUpSuperAdmin();

        Role::firstOrCreate(['name' => 'erkap-risk-manager', 'guard_name' => 'web']);

        foreach ([
            'erkap.menu',
            'erkap.approvals.view',
            'erkap.risk-identifications.view',
            'erkap.risk-identifications.submit',
            'erkap.risk-identifications.create',
            'erkap.risk-identifications.edit',
            'erkap.risk-identifications.delete',
            'erkap.risk-identification-reasons.create',
            'erkap.risk-identifications.approve',
            'erkap.risk-identifications.reject',
        ] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        Role::findByName('erkap-risk-manager', 'web')->givePermissionTo([
            'erkap.menu',
            'erkap.approvals.view',
            'erkap.risk-identifications.view',
            'erkap.risk-identifications.approve',
            'erkap.risk-identifications.reject',
        ]);

        $this->user->givePermissionTo([
            'erkap.approvals.view',
            'erkap.risk-identifications.view',
            'erkap.risk-identifications.submit',
            'erkap.risk-identifications.create',
            'erkap.risk-identifications.edit',
            'erkap.risk-identifications.delete',
            'erkap.risk-identification-reasons.create',
        ]);

        $this->chain = $this->buildErkapChain();
    }

    private function riskManager(): User
    {
        $regional = Regional::factory()->create();

        $employee = Employee::create([
            'employee_id' => 'EMP-RM-'.uniqid(),
            'name' => 'Risk Manager',
            'division_id' => $this->chain['division']->id,
            'regional_id' => $regional->id,
        ]);

        $user = User::factory()->create(['employee_id' => $employee->employee_id]);
        $user->assignRole('erkap-risk-manager');

        return $user;
    }

    private function costOwner(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo([
            'erkap.risk-identifications.view',
            'erkap.risk-identifications.edit',
            'erkap.risk-identification-reasons.create',
        ]);

        return $user;
    }

    public function test_cost_owner_submits_risk_register_for_evaluation(): void
    {
        $riskManager = $this->riskManager();
        $risk = $this->chain['risk'];

        $this->actingAs($this->user)
            ->post(route('erkap.risk-identifications.submit', $risk->id))
            ->assertRedirect(route('erkap.risk-identifications.index'))
            ->assertSessionHas('success');

        $risk->refresh();

        $this->assertSame('submitted', $risk->status);
        $this->assertSame('submitted', $risk->approval_status);

        $approval = $risk->approvals()->first();

        $this->assertNotNull($approval);
        $this->assertSame(1, $approval->level);
        $this->assertSame('erkap-risk-manager', $approval->role);
        $this->assertSame('pending', $approval->status);
        $this->assertSame($riskManager->id, $approval->approver_id);
    }

    public function test_submit_without_strategy_and_work_program_fails(): void
    {
        $risk = RiskIdentification::factory()->create([
            'erkap_department_target_id' => $this->chain['deptTarget']->id,
            'erkap_risk_type_id' => $this->chain['riskType']->id,
            'erkap_risk_taxonomy_id' => $this->chain['taxonomy']->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('erkap.risk-identifications.submit', $risk->id))
            ->assertRedirect(route('erkap.risk-identifications.index'))
            ->assertSessionHas('error');

        $this->assertSame('draft', $risk->refresh()->status);
        $this->assertSame(0, $risk->approvals()->count());
    }

    public function test_batch_submit_submits_only_eligible_risks(): void
    {
        $this->riskManager();

        $eligible = $this->chain['risk'];

        $ineligible = RiskIdentification::factory()->create([
            'erkap_department_target_id' => $this->chain['deptTarget']->id,
            'erkap_risk_type_id' => $this->chain['riskType']->id,
            'erkap_risk_taxonomy_id' => $this->chain['taxonomy']->id,
        ]);

        $alreadyApproved = RiskIdentification::factory()->create([
            'erkap_department_target_id' => $this->chain['deptTarget']->id,
            'erkap_risk_type_id' => $this->chain['riskType']->id,
            'erkap_risk_taxonomy_id' => $this->chain['taxonomy']->id,
        ]);
        DepartmentRiskStrategy::create([
            'erkap_risk_identification_id' => $alreadyApproved->id,
            'strategy' => 'acceptance',
        ]);
        $alreadyApproved->update(['status' => 'approved']);

        $this->actingAs($this->user)
            ->post(route('erkap.risk-identifications.submit-batch'))
            ->assertRedirect(route('erkap.risk-identifications.index'))
            ->assertSessionHas('error');

        $this->assertSame('submitted', $eligible->refresh()->status);
        $this->assertSame('draft', $ineligible->refresh()->status);
        $this->assertSame('approved', $alreadyApproved->refresh()->status);
    }

    public function test_risk_manager_approve_with_notes_via_http(): void
    {
        $riskManager = $this->riskManager();
        $risk = $this->chain['risk'];
        ApprovalService::submit($risk);

        $this->actingAs($riskManager)
            ->post(route('erkap.approvals.approve', ['risk_register', $risk->id]), [
                'notes' => 'Register risiko diterima.',
            ])
            ->assertRedirect(route('erkap.approvals.show', ['risk_register', $risk->id]));

        $risk->refresh();

        $this->assertSame('approved', $risk->status);
        $this->assertSame('approved', $risk->approvals()->first()->status);
        $this->assertSame('Register risiko diterima.', $risk->approvals()->first()->notes);
    }

    public function test_risk_manager_reject_with_notes_via_http(): void
    {
        $riskManager = $this->riskManager();
        $risk = $this->chain['risk'];
        ApprovalService::submit($risk);

        $this->actingAs($riskManager)
            ->post(route('erkap.approvals.reject', ['risk_register', $risk->id]), [
                'notes' => 'Program kerja belum memadai.',
            ])
            ->assertRedirect(route('erkap.approvals.show', ['risk_register', $risk->id]));

        $this->assertSame('rejected', $risk->refresh()->status);
        $this->assertSame('rejected', $risk->approvals()->first()->status);
    }

    public function test_rejected_risk_register_can_be_resubmitted(): void
    {
        $riskManager = $this->riskManager();
        $risk = $this->chain['risk'];
        ApprovalService::submit($risk);
        ApprovalService::reject($risk, $riskManager, 'Perbaiki program kerja.');

        $this->actingAs($this->user)
            ->post(route('erkap.risk-identifications.submit', $risk->id))
            ->assertRedirect(route('erkap.risk-identifications.index'))
            ->assertSessionHas('success');

        $risk->refresh();

        $this->assertSame('submitted', $risk->status);
        $this->assertSame(1, $risk->approvals()->where('status', 'pending')->count());
    }

    public function test_approved_risk_register_is_locked_for_regular_user(): void
    {
        $riskManager = $this->riskManager();
        $risk = $this->chain['risk'];
        ApprovalService::submit($risk);
        ApprovalService::approve($risk, $riskManager, 'Disetujui.');

        $this->assertTrue($risk->refresh()->isApproved());

        $this->actingAs($this->costOwner())
            ->put(route('erkap.risk-identifications.update', $risk->id), [
                'risk' => 'Risiko Baru',
                'risk_direction' => $risk->risk_direction,
                'erkap_department_target_id' => $risk->erkap_department_target_id,
                'erkap_risk_type_id' => $risk->erkap_risk_type_id,
                'erkap_risk_taxonomy_id' => $risk->erkap_risk_taxonomy_id,
            ])
            ->assertRedirect(route('erkap.risk-identifications.edit', $risk->id))
            ->assertSessionHas('error');

        $this->assertNotSame('Risiko Baru', $risk->refresh()->risk);
    }

    public function test_child_entries_are_locked_after_evaluation(): void
    {
        $riskManager = $this->riskManager();
        $risk = $this->chain['risk'];
        ApprovalService::submit($risk);
        ApprovalService::approve($risk, $riskManager);

        $this->actingAs($this->costOwner())
            ->post(route('erkap.risk-identification-reasons.store'), [
                'reason' => 'Alasan tambahan',
                'erkap_risk_identification_id' => $risk->id,
            ])
            ->assertRedirect(route('erkap.risk-identification-reasons.create'))
            ->assertSessionHas('error');

        $this->assertSame(0, $risk->reasons()->count());
    }

    public function test_submitted_but_not_approved_risk_register_is_also_locked(): void
    {
        $this->riskManager();

        $risk = $this->chain['risk'];
        ApprovalService::submit($risk);

        $this->actingAs($this->costOwner())
            ->post(route('erkap.risk-identification-reasons.store'), [
                'reason' => 'Alasan tambahan',
                'erkap_risk_identification_id' => $risk->id,
            ])
            ->assertRedirect(route('erkap.risk-identification-reasons.create'))
            ->assertSessionHas('error');

        $this->assertSame(0, $risk->fresh()->reasons()->count());
    }

    public function test_risk_register_shows_in_approval_show_page(): void
    {
        $riskManager = $this->riskManager();
        $risk = $this->chain['risk'];
        ApprovalService::submit($risk);

        $this->actingAs($riskManager)
            ->get(route('erkap.approvals.show', ['risk_register', $risk->id]))
            ->assertOk();
    }

    public function test_index_shows_submittable_count(): void
    {
        $risk = RiskIdentification::factory()->create([
            'erkap_department_target_id' => $this->chain['deptTarget']->id,
            'erkap_risk_type_id' => $this->chain['riskType']->id,
            'erkap_risk_taxonomy_id' => $this->chain['taxonomy']->id,
        ]);
        DepartmentRiskStrategy::create([
            'erkap_risk_identification_id' => $risk->id,
            'strategy' => 'reduction',
        ]);

        $this->actingAs($this->user)
            ->get(route('erkap.risk-identifications.index'))
            ->assertOk()
            ->assertViewHas('submittableCount');
    }
}
