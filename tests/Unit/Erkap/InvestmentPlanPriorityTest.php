<?php

namespace Tests\Unit\Erkap;

use App\Models\Erkap\CostCenter;
use App\Models\Erkap\InvestmentPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsErkapChain;
use Tests\TestCase;

class InvestmentPlanPriorityTest extends TestCase
{
    use RefreshDatabase, BuildsErkapChain;

    public function test_priority_order_and_cost_center_are_persisted_via_mass_assignment(): void
    {
        $chain = $this->buildErkapChain(['code' => 'WP-PRI-' . uniqid(), 'program_name' => 'Program Prioritas Uji']);
        $costCenter = CostCenter::factory()->create();

        $plan = InvestmentPlan::factory()->create([
            'erkap_work_program_id' => $chain['workProgram']->id,
            'cost_center_id' => $costCenter->id,
            'priority_order' => '2',
        ]);

        $plan->refresh();

        $this->assertSame($costCenter->id, $plan->cost_center_id);
        $this->assertSame(2, $plan->priority_order);
        $this->assertIsInt($plan->priority_order);
    }

    public function test_proposal_url_returns_public_storage_url(): void
    {
        $chain = $this->buildErkapChain(['code' => 'WP-PRI-' . uniqid(), 'program_name' => 'Program Prioritas Uji']);
        $plan = InvestmentPlan::factory()->withProposal()->create([
            'erkap_work_program_id' => $chain['workProgram']->id,
        ]);

        $this->assertStringContainsString('/storage/investment-plans/proposals/fake-proposal.pdf', $plan->proposalUrl());
        $this->assertTrue($plan->hasProposal());
    }

    public function test_plan_without_proposal_has_no_url(): void
    {
        $chain = $this->buildErkapChain(['code' => 'WP-PRI-' . uniqid(), 'program_name' => 'Program Prioritas Uji']);
        $plan = InvestmentPlan::factory()->create([
            'erkap_work_program_id' => $chain['workProgram']->id,
        ]);

        $this->assertNull($plan->proposalUrl());
        $this->assertFalse($plan->hasProposal());
    }
}
