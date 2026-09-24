<?php

namespace Tests\Feature\Erkap;

use App\Models\Division;
use App\Models\Erkap\CostCenter;
use App\Models\Erkap\CostElement;
use App\Models\Erkap\CostElementCategory;
use App\Models\Erkap\RoutineCost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ActsAsSuperAdmin;
use Tests\Concerns\BuildsErkapChain;
use Tests\TestCase;

class CentralizedCostFeatureTest extends TestCase
{
    use ActsAsSuperAdmin;
    use BuildsErkapChain;
    use RefreshDatabase;

    private Division $chainDivision;
    private Division $other;
    private array $chain;
    private CostElement $element;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSuperAdmin();

        $this->chain = $this->buildErkapChain(['code' => 'WP-CENTRAL']);
        $this->chainDivision = $this->chain['division'];
        $this->other = Division::factory()->create();

        $this->element = CostElement::create([
            'code' => '9100',
            'name' => 'Elemen Biaya Uji',
            'erkap_cost_element_category_id' => CostElementCategory::factory()->create()->id,
        ]);
    }

    public function test_centralized_cost_center_can_be_created_with_coordinator(): void
    {
        $payload = [
            'code' => 'F01202151009100',
            'name' => 'Gaji (HR)',
            'owner' => 'Pemilik',
            'division_id' => $this->chainDivision->id,
            'is_swakelola' => 0,
            'is_centralized' => 1,
            'coordinating_division_id' => $this->other->id,
        ];

        $response = $this->post(route('erkap.cost-centers.store'), $payload);

        $response->assertRedirect(route('erkap.cost-centers.index'));
        $this->assertDatabaseHas('cost_centers', [
            'name' => 'Gaji (HR)',
            'is_centralized' => true,
            'coordinating_division_id' => $this->other->id,
        ]);
    }

    public function test_centralized_cost_center_requires_coordinator(): void
    {
        $payload = [
            'code' => 'F01202151009100',
            'name' => 'Gaji (HR)',
            'owner' => 'Pemilik',
            'division_id' => $this->chainDivision->id,
            'is_centralized' => 1,
        ];

        $response = $this->post(route('erkap.cost-centers.store'), $payload);

        $response->assertSessionHasErrors('coordinating_division_id');
        $this->assertDatabaseMissing('cost_centers', ['name' => 'Gaji (HR)']);
    }

    public function test_non_centralized_cost_center_creates_without_coordinator(): void
    {
        $payload = [
            'code' => 'F01202151009100',
            'name' => 'Non Terpusat',
            'owner' => 'Pemilik',
            'division_id' => $this->chainDivision->id,
            'is_swakelola' => 1,
        ];

        $response = $this->post(route('erkap.cost-centers.store'), $payload);

        $response->assertRedirect(route('erkap.cost-centers.index'));
        $this->assertDatabaseHas('cost_centers', [
            'name' => 'Non Terpusat',
            'is_centralized' => false,
            'coordinating_division_id' => null,
        ]);
    }

    public function test_routine_cost_rejected_on_centralized_cost_center_of_other_division(): void
    {
        $centralized = CostCenter::factory()->create([
            'is_centralized' => true,
            'coordinating_division_id' => $this->other->id,
        ]);

        $response = $this->post(route('erkap.routine-costs.store'), $this->routineCostPayload($centralized->id));

        $response->assertRedirect(route('erkap.routine-costs.create'));
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('erkap_routine_costs', 0);
    }

    public function test_routine_cost_allowed_on_centralized_cost_center_of_coordinating_division(): void
    {
        $centralized = CostCenter::factory()->create([
            'is_centralized' => true,
            'coordinating_division_id' => $this->chainDivision->id,
        ]);

        $response = $this->post(route('erkap.routine-costs.store'), $this->routineCostPayload($centralized->id));

        $response->assertRedirect(route('erkap.routine-costs.index'));
        $response->assertSessionHas('success');
        $this->assertSame(1, RoutineCost::where('cost_center_id', $centralized->id)->count());
    }

    public function test_routine_cost_allowed_on_non_centralized_cost_center_for_any_division(): void
    {
        $nonCentralized = CostCenter::factory()->create();

        $response = $this->post(route('erkap.routine-costs.store'), $this->routineCostPayload($nonCentralized->id));

        $response->assertRedirect(route('erkap.routine-costs.index'));
        $response->assertSessionHas('success');
        $this->assertSame(1, RoutineCost::where('cost_center_id', $nonCentralized->id)->count());
    }

    public function test_consolidate_groups_centralized_and_non_centralized(): void
    {
        $centralized = CostCenter::factory()->create([
            'is_centralized' => true,
            'coordinating_division_id' => $this->chainDivision->id,
        ]);
        $nonCentralized = CostCenter::factory()->create();

        RoutineCost::factory()->create([
            'erkap_work_program_id' => $this->chain['workProgram']->id,
            'cost_center_id' => $centralized->id,
        ]);
        RoutineCost::factory()->create([
            'erkap_work_program_id' => $this->chain['workProgram']->id,
            'cost_center_id' => $nonCentralized->id,
        ]);

        $response = $this->get(route('erkap.routine-costs.consolidate', ['division_id' => $this->chainDivision->id]));

        $response->assertOk();
        $response->assertViewHas('centralizedGroups');

        $groups = $response->viewData('centralizedGroups');
        $this->assertCount(1, $groups['centralized']);
        $this->assertCount(1, $groups['non_centralized']);
        $this->assertSame($centralized->id, $groups['centralized']->first()->cost_center_id);
        $this->assertSame($nonCentralized->id, $groups['non_centralized']->first()->cost_center_id);
    }

    private function routineCostPayload(int $costCenterId): array
    {
        return [
            'erkap_work_program_id' => $this->chain['workProgram']->id,
            'need' => 'Kebutuhan uji biaya terpusat',
            'cost_center_id' => $costCenterId,
            'cost_center_owner' => 'Pemilik',
            'qty' => 2,
            'units' => 'unit',
            'unit_price' => 50000,
            'erkap_cost_element_id' => $this->element->id,
            'is_kumulatif' => 1,
            'total' => 100000,
        ];
    }
}