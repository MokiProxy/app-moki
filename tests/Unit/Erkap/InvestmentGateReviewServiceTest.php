<?php

namespace Tests\Unit\Erkap;

use App\Models\Erkap\InvestmentPlan;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\Erkap\InvestmentGateReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\Concerns\BuildsErkapChain;
use Tests\TestCase;

class InvestmentGateReviewServiceTest extends TestCase
{
    use RefreshDatabase, BuildsErkapChain;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        foreach (['erkap-ppk', 'erkap-manajemen-aset', 'erkap-direksi-keuangan'] as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    private function roleUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function reviewers(): array
    {
        return [
            'erkap-ppk' => $this->roleUser('erkap-ppk'),
            'erkap-manajemen-aset' => $this->roleUser('erkap-manajemen-aset'),
            'erkap-direksi-keuangan' => $this->roleUser('erkap-direksi-keuangan'),
        ];
    }

    private function makePlan(array $attributes = []): InvestmentPlan
    {
        $chain = $this->buildErkapChain(['code' => 'WP-INV-' . uniqid(), 'program_name' => 'Program Investasi Uji']);

        return InvestmentPlan::factory()->withProposal()->create($attributes + [
            'erkap_work_program_id' => $chain['workProgram']->id,
        ]);
    }

    public function test_default_stages_have_expected_order_and_roles(): void
    {
        $stages = InvestmentGateReviewService::defaultStages();

        $this->assertSame(['proposal', 'cba', 'aset', 'direksi_keuangan'], array_keys($stages));
        $this->assertSame('erkap-ppk', $stages['proposal']['reviewer_role']);
        $this->assertSame('erkap-ppk', $stages['cba']['reviewer_role']);
        $this->assertSame('erkap-manajemen-aset', $stages['aset']['reviewer_role']);
        $this->assertSame('erkap-direksi-keuangan', $stages['direksi_keuangan']['reviewer_role']);
    }

    public function test_initialize_creates_four_gates_and_marks_plan_in_review(): void
    {
        $plan = $this->makePlan();

        InvestmentGateReviewService::initialize($plan);

        $gates = $plan->stageGates()->orderBy('stage_order')->get();
        $this->assertCount(4, $gates);
        $this->assertSame(['proposal', 'cba', 'aset', 'direksi_keuangan'], $gates->pluck('stage')->all());
        $this->assertSame([1, 2, 3, 4], $gates->pluck('stage_order')->all());
        $this->assertTrue($gates->every(fn ($gate) => $gate->status === 'pending'));
        $this->assertSame('in_review', $plan->refresh()->gate_review_status);

        InvestmentGateReviewService::initialize($plan);
        $this->assertSame(4, $plan->stageGates()->count());
    }

    public function test_initialize_resets_gates_when_plan_was_rejected(): void
    {
        $plan = $this->makePlan();
        InvestmentGateReviewService::initialize($plan);
        $plan->stageGates()->first()->update([
            'status' => 'rejected',
            'result' => 'tidak_layak',
            'reviewed_at' => now(),
            'reviewed_by' => User::factory()->create()->id,
        ]);
        $plan->update(['status' => 'rejected']);

        InvestmentGateReviewService::initialize($plan, true);

        $this->assertSame('pending', $plan->stageGates()->first()->status);
        $this->assertNull($plan->stageGates()->first()->result);
        $this->assertSame(4, $plan->stageGates()->count());
    }

    public function test_submit_without_proposal_is_rejected(): void
    {
        $plan = InvestmentPlan::factory()->create([
            'erkap_work_program_id' => $this->buildErkapChain(['code' => 'WP-INV-' . uniqid(), 'program_name' => 'Program Investasi Uji'])['workProgram']->id,
        ]);

        $this->expectException(\RuntimeException::class);
        ApprovalService::submit($plan);
    }

    public function test_submit_with_proposal_creates_gates_and_three_approvals(): void
    {
        $this->reviewers();
        $plan = $this->makePlan();

        ApprovalService::submit($plan);

        $this->assertSame('submitted', $plan->refresh()->status);
        $this->assertCount(4, $plan->stageGates);
        $levels = $plan->approvals()->orderBy('level')->pluck('role')->all();
        $this->assertSame(
            ['erkap-ppk', 'erkap-manajemen-aset', 'erkap-direksi-keuangan'],
            $levels
        );
    }

    public function test_review_rejects_wrong_role_and_prior_pending_stages(): void
    {
        $reviewers = $this->reviewers();
        $plan = $this->makePlan();
        ApprovalService::submit($plan);

        $this->expectException(ValidationException::class);
        InvestmentGateReviewService::review($plan, 'proposal', $reviewers['erkap-manajemen-aset'], ['status' => 'approved']);
    }

    public function test_review_cba_before_proposal_fails(): void
    {
        $reviewers = $this->reviewers();
        $plan = $this->makePlan();
        ApprovalService::submit($plan);

        try {
            InvestmentGateReviewService::review($plan, 'cba', $reviewers['erkap-ppk'], ['status' => 'approved']);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('belum disetujui', $e->validator->errors()->first('stage'));
        }
    }

    public function test_approving_all_gates_approves_plan_and_approvals(): void
    {
        $reviewers = $this->reviewers();
        $plan = $this->makePlan();
        ApprovalService::submit($plan);

        $gate = InvestmentGateReviewService::review($plan, 'proposal', $reviewers['erkap-ppk'], ['status' => 'approved', 'result' => 'layak']);
        $this->assertSame('approved', $gate->status);
        $this->assertSame('partial', $plan->refresh()->gate_review_status);

        InvestmentGateReviewService::review($plan, 'cba', $reviewers['erkap-ppk'], ['status' => 'approved', 'result' => 'layak']);
        $this->assertSame('approved', $plan->approvals()->where('role', 'erkap-ppk')->first()->status);

        InvestmentGateReviewService::review($plan, 'aset', $reviewers['erkap-manajemen-aset'], ['status' => 'approved']);
        InvestmentGateReviewService::review($plan, 'direksi_keuangan', $reviewers['erkap-direksi-keuangan'], ['status' => 'approved']);

        $plan->refresh();

        $this->assertSame('approved', $plan->status);
        $this->assertSame('approved', $plan->gate_review_status);
        $this->assertTrue($plan->stageGates->every(fn ($g) => $g->status === 'approved'));
        $this->assertSame(0, $plan->approvals()->where('status', 'pending')->count());
        $this->assertSame(3, $plan->approvals()->where('status', 'approved')->count());
    }

    public function test_rejecting_stage_rejects_plan_and_pending_approvals(): void
    {
        $reviewers = $this->reviewers();
        $plan = $this->makePlan();
        ApprovalService::submit($plan);

        InvestmentGateReviewService::review($plan, 'proposal', $reviewers['erkap-ppk'], [
            'status' => 'rejected',
            'result' => 'tidak_layak',
            'notes' => 'Tidak memenuhi kriteria.',
        ]);

        $plan->refresh();

        $this->assertSame('rejected', $plan->status);
        $this->assertSame('rejected', $plan->gate_review_status);
        $this->assertSame(0, $plan->approvals()->where('status', 'pending')->count());
        $this->assertSame(3, $plan->approvals()->where('status', 'rejected')->count());
    }

    public function test_approval_route_is_blocked_when_gate_group_not_approved(): void
    {
        $reviewers = $this->reviewers();
        $plan = $this->makePlan();
        ApprovalService::submit($plan);

        InvestmentGateReviewService::review($plan, 'proposal', $reviewers['erkap-ppk'], ['status' => 'approved']);
        InvestmentGateReviewService::review($plan, 'cba', $reviewers['erkap-ppk'], ['status' => 'approved']);

        $this->expectException(\RuntimeException::class);
        ApprovalService::approve($plan, $reviewers['erkap-manajemen-aset']);
    }
}