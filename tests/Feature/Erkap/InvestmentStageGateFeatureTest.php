<?php

namespace Tests\Feature\Erkap;

use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\InvestmentStageGate;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\Erkap\InvestmentGateReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\ActsAsSuperAdmin;
use Tests\Concerns\BuildsErkapChain;
use Tests\TestCase;

class InvestmentStageGateFeatureTest extends TestCase
{
    use RefreshDatabase, ActsAsSuperAdmin, BuildsErkapChain;

    private array $reviewers;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->setUpSuperAdmin();

        $roles = [
            'erkap-ppk',
            'erkap-manajemen-aset',
            'erkap-direksi-keuangan',
            'erkap-accounting',
        ];

        foreach ($roles as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $permissions = [
            'erkap.menu',
            'erkap.approvals.view',
            'erkap.investment-plans.view',
            'erkap.investment-plans.approve',
            'erkap.investment-plans.reject',
            'erkap.investment-plans.download',
            'erkap.investment-gates.view',
            'erkap.investment-gates.review',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $gatePermissions = [
            'erkap.menu',
            'erkap.approvals.view',
            'erkap.investment-plans.view',
            'erkap.investment-plans.approve',
            'erkap.investment-plans.reject',
            'erkap.investment-plans.download',
            'erkap.investment-gates.view',
            'erkap.investment-gates.review',
        ];

        foreach (['erkap-ppk', 'erkap-manajemen-aset', 'erkap-direksi-keuangan'] as $name) {
            Role::findByName($name, 'web')->givePermissionTo($gatePermissions);
        }

        $this->user->givePermissionTo([
            'erkap.investment-gates.view',
            'erkap.investment-gates.review',
            'erkap.investment-plans.view',
            'erkap.investment-plans.download',
        ]);

        $this->reviewers['erkap-ppk'] = $this->roleUser('erkap-ppk');
        $this->reviewers['erkap-manajemen-aset'] = $this->roleUser('erkap-manajemen-aset');
        $this->reviewers['erkap-direksi-keuangan'] = $this->roleUser('erkap-direksi-keuangan');
        $this->reviewers['erkap-accounting'] = $this->roleUser('erkap-accounting');
    }

    private function roleUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function submittedPlan(): InvestmentPlan
    {
        $chain = $this->buildErkapChain(['code' => 'WP-INV-' . uniqid(), 'program_name' => 'Program Investasi Uji']);
        $plan = InvestmentPlan::factory()->withProposal()->create([
            'erkap_work_program_id' => $chain['workProgram']->id,
        ]);
        ApprovalService::submit($plan);

        return $plan->fresh();
    }

    public function test_gate_index_is_accessible_by_reviewer(): void
    {
        $plan = $this->submittedPlan();

        $this->actingAs($this->reviewers['erkap-ppk'])
            ->get(route('erkap.investment-gates.index'))
            ->assertOk()
            ->assertViewHas('gates');

        $this->assertSame(
            2,
            $plan->stageGates()->where('reviewer_role', 'erkap-ppk')->where('status', 'pending')->count()
        );
    }

    public function test_unprivileged_user_gets_403_on_gate_detail(): void
    {
        $plan = $this->submittedPlan();
        $gate = $plan->stageGates()->where('stage', 'proposal')->first();

        $this->actingAs($this->reviewers['erkap-accounting'])
            ->get(route('erkap.investment-gates.show', $gate->id))
            ->assertForbidden();
    }

    public function test_reviewer_can_review_gate_via_http(): void
    {
        $plan = $this->submittedPlan();
        $gate = $plan->stageGates()->where('stage', 'proposal')->first();

        $this->actingAs($this->reviewers['erkap-ppk'])
            ->post(route('erkap.investment-gates.store', $gate->id), [
                'status' => 'approved',
                'result' => 'layak',
                'notes' => 'Proposal lengkap.',
            ])
            ->assertRedirect(route('erkap.investment-gates.index'));

        $this->assertSame('approved', $gate->refresh()->status);
        $this->assertSame('layak', $gate->result);
        $this->assertSame('proposal', $gate->stage);
        $this->assertSame($this->reviewers['erkap-ppk']->id, $gate->reviewed_by);
    }

    public function test_rejecting_gate_via_http_rejects_plan(): void
    {
        $plan = $this->submittedPlan();
        $gate = $plan->stageGates()->where('stage', 'proposal')->first();

        $this->actingAs($this->reviewers['erkap-ppk'])
            ->post(route('erkap.investment-gates.store', $gate->id), [
                'status' => 'rejected',
                'result' => 'tidak_layak',
                'notes' => 'Tidak layak.',
            ])
            ->assertRedirect(route('erkap.investment-gates.index'));

        $this->assertSame('rejected', $gate->refresh()->status);
        $this->assertSame('rejected', $plan->refresh()->status);
    }

    public function test_stream_review_permissions_guard_role_listing(): void
    {
        $plan = $this->submittedPlan();

        InvestmentGateReviewService::review($plan, 'proposal', $this->reviewers['erkap-ppk'], ['status' => 'approved']);
        InvestmentGateReviewService::review($plan, 'cba', $this->reviewers['erkap-ppk'], ['status' => 'approved']);

        $this->actingAs($this->reviewers['erkap-manajemen-aset'])
            ->get(route('erkap.investment-gates.index'))
            ->assertOk();

        $this->assertSame(
            ['aset'],
            $this->actingAs($this->reviewers['erkap-manajemen-aset'])
                ->get(route('erkap.investment-gates.index'))
                ->viewData('gates')
                ->getCollection()
                ->pluck('stage')
                ->all()
        );
    }

    public function test_approval_must_wait_for_related_gate_group(): void
    {
        $plan = $this->submittedPlan();

        $this->expectException(\RuntimeException::class);
        ApprovalService::approve($plan, $this->reviewers['erkap-manajemen-aset']);
    }

    public function test_full_flow_to_approved_plan(): void
    {
        $plan = $this->submittedPlan();

        foreach (['proposal' => 'erkap-ppk', 'cba' => 'erkap-ppk', 'aset' => 'erkap-manajemen-aset', 'direksi_keuangan' => 'erkap-direksi-keuangan'] as $stage => $role) {
            $gate = $plan->stageGates()->where('stage', $stage)->first();

            $this->actingAs($this->reviewers[$role])
                ->post(route('erkap.investment-gates.store', $gate->id), [
                    'status' => 'approved',
                    'result' => 'layak',
                ])
                ->assertRedirect(route('erkap.investment-gates.index'));
        }

        $plan->refresh();

        $this->assertSame('approved', $plan->status);
        $this->assertSame('approved', $plan->gate_review_status);
        $this->assertSame(0, $plan->approvals()->where('status', '!=', 'approved')->count());
    }

    public function test_plan_index_shows_gate_progress(): void
    {
        $plan = $this->submittedPlan();

        $this->actingAs($this->user)
            ->get(route('erkap.investment-plans.index'))
            ->assertOk();
    }

    public function test_download_proposal_without_file_redirects(): void
    {
        $chain = $this->buildErkapChain(['code' => 'WP-INV-' . uniqid(), 'program_name' => 'Program Investasi Uji']);
        $plan = InvestmentPlan::factory()->create([
            'erkap_work_program_id' => $chain['workProgram']->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('erkap.investment-plans.proposal-download', $plan->id))
            ->assertRedirect(route('erkap.investment-plans.index'));
    }
}