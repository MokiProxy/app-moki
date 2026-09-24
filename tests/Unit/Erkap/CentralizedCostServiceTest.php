<?php

namespace Tests\Unit\Erkap;

use App\Models\Division;
use App\Models\Erkap\CostCenter;
use App\Models\Erkap\RoutineCost;
use App\Services\Erkap\CentralizedCostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\BuildsErkapChain;
use Tests\TestCase;

class CentralizedCostServiceTest extends TestCase
{
    use BuildsErkapChain;
    use RefreshDatabase;

    private Division $coordinator;
    private Division $other;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coordinator = Division::factory()->create();
        $this->other = Division::factory()->create();
    }

    public function test_non_centralized_cost_center_allowed_for_any_division(): void
    {
        $costCenter = CostCenter::factory()->create();

        $this->assertTrue(CentralizedCostService::allowedToUse($costCenter, $this->coordinator->id));
        $this->assertTrue(CentralizedCostService::allowedToUse($costCenter, $this->other->id));
        $this->assertTrue(CentralizedCostService::allowedToUse($costCenter, null));
    }

    public function test_centralized_cost_center_allowed_only_for_coordinating_division(): void
    {
        $costCenter = CostCenter::factory()->create([
            'is_centralized' => true,
            'coordinating_division_id' => $this->coordinator->id,
        ]);

        $this->assertTrue(CentralizedCostService::allowedToUse($costCenter, $this->coordinator->id));
        $this->assertFalse(CentralizedCostService::allowedToUse($costCenter, $this->other->id));
        $this->assertFalse(CentralizedCostService::allowedToUse($costCenter, null));
    }

    public function test_assert_can_input_rejects_non_coordinator_division(): void
    {
        $costCenter = CostCenter::factory()->create([
            'is_centralized' => true,
            'coordinating_division_id' => $this->coordinator->id,
        ]);

        $this->expectException(ValidationException::class);

        CentralizedCostService::assertCanInput($costCenter, $this->other->id);
    }

    public function test_assert_can_input_allows_coordinator_and_non_centralized(): void
    {
        $centralized = CostCenter::factory()->create([
            'is_centralized' => true,
            'coordinating_division_id' => $this->coordinator->id,
        ]);
        $nonCentralized = CostCenter::factory()->create();

        CentralizedCostService::assertCanInput($centralized, $this->coordinator->id);
        CentralizedCostService::assertCanInput($nonCentralized, $this->other->id);

        $this->addToAssertionCount(1);
    }

    public function test_map_default_coordinator_resolves_hr_and_it_divisions(): void
    {
        Division::query()->delete();

        $hr = Division::factory()->create([
            'name' => 'Sumber Daya Manusia',
            'code' => 'HRD',
            'abbreviation' => 'HRD',
        ]);
        $it = Division::factory()->create([
            'name' => 'Biro Teknologi Informasi',
            'code' => 'TID',
            'abbreviation' => 'TID',
        ]);

        $map = CentralizedCostService::mapDefaultCoordinator();

        $this->assertArrayHasKey('gaji', $map);
        $this->assertArrayHasKey('it', $map);
        $this->assertSame($hr->id, $map['gaji']);
        $this->assertSame($it->id, $map['it']);
    }

    public function test_map_default_coordinator_returns_nulls_when_divisions_absent(): void
    {
        Division::query()->delete();

        $map = CentralizedCostService::mapDefaultCoordinator();

        $this->assertNull($map['gaji']);
        $this->assertNull($map['it']);
    }

    public function test_group_centralized_partitions_rows(): void
    {
        $chain = $this->buildErkapChain(['code' => 'WP-GROUP']);

        $centralized = CostCenter::factory()->create([
            'is_centralized' => true,
            'coordinating_division_id' => $this->coordinator->id,
        ]);
        $nonCentralized = CostCenter::factory()->create();

        $centralizedCost = RoutineCost::factory()->create([
            'erkap_work_program_id' => $chain['workProgram']->id,
            'cost_center_id' => $centralized->id,
        ]);
        $nonCentralizedCost = RoutineCost::factory()->create([
            'erkap_work_program_id' => $chain['workProgram']->id,
            'cost_center_id' => $nonCentralized->id,
        ]);

        $groups = CentralizedCostService::groupCentralized(new Collection([$centralizedCost, $nonCentralizedCost]));

        $this->assertArrayHasKey('centralized', $groups);
        $this->assertArrayHasKey('non_centralized', $groups);
        $this->assertTrue($groups['centralized']->contains('id', $centralizedCost->id));
        $this->assertTrue($groups['centralized']->doesntContain('id', $nonCentralizedCost->id));
        $this->assertTrue($groups['non_centralized']->contains('id', $nonCentralizedCost->id));
    }
}