<?php

namespace Tests\Feature\Erkap;

use App\Models\Erkap\InvestattionCategory;
use App\Models\Erkap\InvestationCriteria;
use App\Models\Erkap\InvestationType;
use App\Models\Erkap\InvestmentPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ActsAsSuperAdmin;
use Tests\Concerns\BuildsErkapChain;
use Tests\TestCase;

class InvestmentPlanPriorityFeatureTest extends TestCase
{
    use RefreshDatabase, ActsAsSuperAdmin, BuildsErkapChain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSuperAdmin();
    }

    private function planPayload(int $workProgramId, array $overrides = []): array
    {
        return array_merge([
            'erkap_work_program_id' => $workProgramId,
            'erkap_investattion_category_id' => InvestattionCategory::factory()->create()->id,
            'erkap_investation_type_id' => InvestationType::factory()->create()->id,
            'erkap_investation_criteria_id' => InvestationCriteria::factory()->create()->id,
            'name' => 'Investasi Prioritas',
            'description' => 'Investasi untuk pengujian urutan prioritas.',
            'unit' => 'unit',
            'qty' => 1,
            'unit_price' => 100000,
            'total' => 100000,
            'is_kumulatif' => 1,
            'priority_order' => 1,
        ], $overrides);
    }

    public function test_store_plan_persists_priority_order(): void
    {
        $chain = $this->buildErkapChain(['code' => 'WP-PRI-' . uniqid(), 'program_name' => 'Program Prioritas Uji']);

        $this->post(route('erkap.investment-plans.store'), $this->planPayload($chain['workProgram']->id, [
            'priority_order' => 3,
        ]))
            ->assertRedirect(route('erkap.investment-plans.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('erkap_investment_plans', [
            'name' => 'Investasi Prioritas',
            'priority_order' => 3,
        ]);
    }

    public function test_duplicate_priority_within_division_is_rejected(): void
    {
        $chain = $this->buildErkapChain(['code' => 'WP-PRI-' . uniqid(), 'program_name' => 'Program Prioritas Uji']);

        InvestmentPlan::factory()->create([
            'erkap_work_program_id' => $chain['workProgram']->id,
            'name' => 'Investasi Eksisting',
            'priority_order' => 1,
        ]);

        $this->post(route('erkap.investment-plans.store'), $this->planPayload($chain['workProgram']->id, [
            'name' => 'Investasi Duplikat',
            'priority_order' => 1,
        ]))
            ->assertSessionHasErrors('priority_order');

        $this->assertSame(0, InvestmentPlan::query()->where('name', 'Investasi Duplikat')->count());
    }

    public function test_same_priority_in_different_division_is_allowed(): void
    {
        $chainA = $this->buildErkapChain(['code' => 'WP-PRI-A-' . uniqid(), 'program_name' => 'Program Prioritas A']);
        $chainB = $this->buildErkapChain(['code' => 'WP-PRI-B-' . uniqid(), 'program_name' => 'Program Prioritas B']);

        InvestmentPlan::factory()->create([
            'erkap_work_program_id' => $chainA['workProgram']->id,
            'name' => 'Investasi Divisi A',
            'priority_order' => 1,
        ]);

        $this->post(route('erkap.investment-plans.store'), $this->planPayload($chainB['workProgram']->id, [
            'name' => 'Investasi Divisi B',
            'priority_order' => 1,
        ]))
            ->assertRedirect(route('erkap.investment-plans.index'))
            ->assertSessionHas('success');

        $this->assertSame(2, InvestmentPlan::query()->where('priority_order', 1)->count());
    }

    public function test_invalid_priority_order_is_rejected(): void
    {
        $chain = $this->buildErkapChain(['code' => 'WP-PRI-' . uniqid(), 'program_name' => 'Program Prioritas Uji']);

        $this->post(route('erkap.investment-plans.store'), $this->planPayload($chain['workProgram']->id, [
            'priority_order' => 0,
        ]))
            ->assertSessionHasErrors('priority_order');
    }
}
