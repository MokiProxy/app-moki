<?php

namespace Tests\Feature\Erkap;

use App\Models\Employee;
use App\Models\Erkap\RiskTreatment;
use App\Models\Regional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\ActsAsSuperAdmin;
use Tests\Concerns\BuildsErkapChain;
use Tests\TestCase;

class RiskTreatmentFeatureTest extends TestCase
{
    use ActsAsSuperAdmin, BuildsErkapChain, RefreshDatabase;

    private array $chain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSuperAdmin();
        $this->chain = $this->buildErkapChain();
    }

    protected function createRiskManager(): User
    {
        Role::firstOrCreate(['name' => 'erkap-risk-manager', 'guard_name' => 'web']);

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

    public function test_index_page_lists_risk_treatments(): void
    {
        RiskTreatment::create([
            'erkap_risk_identification_id' => $this->chain['risk']->id,
            'erkap_department_risk_strategy_id' => $this->chain['risk']->departmentRiskStrategies()->first()?->id,
            'treatment_type' => 'sharing',
            'description' => 'Alihkan ke asuransi',
            'responsible_party' => 'PIC',
            'target_date' => now()->addMonths(2)->format('Y-m-d'),
            'status' => 'planned',
        ]);

        $this->get(route('erkap.risk-treatments.index'))
            ->assertOk()
            ->assertSee('Berbagi')
            ->assertSee('PIC');
    }

    public function test_create_page_renders(): void
    {
        $this->get(route('erkap.risk-treatments.create'))
            ->assertOk()
            ->assertSee('Buat Perlakuan Risiko');
    }

    public function test_stores_treatment_with_sharing_type(): void
    {
        $strategy = $this->chain['risk']->departmentRiskStrategies()->first();

        $response = $this->post(route('erkap.risk-treatments.store'), [
            'erkap_risk_identification_id' => $this->chain['risk']->id,
            'erkap_department_risk_strategy_id' => $strategy->id,
            'treatment_type' => 'sharing',
            'description' => 'Alihkan sebagian risiko melalui asuransi',
            'responsible_party' => 'Head of Finance',
            'target_date' => now()->addMonths(3)->format('Y-m-d'),
            'status' => 'planned',
            'result' => null,
        ]);

        $response->assertRedirect(route('erkap.risk-treatments.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('erkap_risk_treatments', [
            'erkap_risk_identification_id' => $this->chain['risk']->id,
            'treatment_type' => 'sharing',
            'responsible_party' => 'Head of Finance',
        ]);
    }

    public function test_store_rejects_legacy_treatment_type(): void
    {
        $this->post(route('erkap.risk-treatments.store'), [
            'erkap_risk_identification_id' => $this->chain['risk']->id,
            'treatment_type' => 'avoid',
            'description' => 'Hindari risiko',
            'responsible_party' => 'PIC',
            'target_date' => now()->addMonths(1)->format('Y-m-d'),
            'status' => 'planned',
        ])->assertSessionHasErrors('treatment_type');
    }

    public function test_updates_risk_treatment(): void
    {
        $treatment = RiskTreatment::create([
            'erkap_risk_identification_id' => $this->chain['risk']->id,
            'treatment_type' => 'reduction',
            'description' => 'Pasang sistem pengendalian',
            'responsible_party' => 'Manajer Risiko',
            'target_date' => now()->addMonths(2)->format('Y-m-d'),
            'status' => 'planned',
        ]);

        $this->put(route('erkap.risk-treatments.update', $treatment->id), [
            'erkap_risk_identification_id' => $this->chain['risk']->id,
            'treatment_type' => 'acceptance',
            'description' => 'Terima dengan pemantauan rutin',
            'responsible_party' => 'Direksi',
            'target_date' => now()->addMonths(4)->format('Y-m-d'),
            'status' => 'in_progress',
            'result' => 'Dipantau bulanan',
        ])
            ->assertRedirect(route('erkap.risk-treatments.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('erkap_risk_treatments', [
            'id' => $treatment->id,
            'treatment_type' => 'acceptance',
            'status' => 'in_progress',
        ]);
    }

    public function test_deletes_risk_treatment(): void
    {
        $treatment = RiskTreatment::create([
            'erkap_risk_identification_id' => $this->chain['risk']->id,
            'treatment_type' => 'reduction',
            'description' => 'Perlakuan yang akan dihapus',
            'responsible_party' => 'PIC',
            'target_date' => now()->addMonths(2)->format('Y-m-d'),
            'status' => 'planned',
        ]);

        $this->delete(route('erkap.risk-treatments.destroy', $treatment->id))
            ->assertRedirect(route('erkap.risk-treatments.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('erkap_risk_treatments', ['id' => $treatment->id]);
    }

    public function test_submit_risk_without_treatment_is_rejected(): void
    {
        $chain = $this->buildErkapChain(['treatment' => false]);

        $this->post(route('erkap.risk-identifications.submit', $chain['risk']->id))
            ->assertRedirect(route('erkap.risk-identifications.index'))
            ->assertSessionHas('error');

        $this->assertSame('draft', $chain['risk']->fresh()->status);
    }

    public function test_submit_risk_with_treatment_succeeds(): void
    {
        $this->createRiskManager();

        $this->post(route('erkap.risk-identifications.submit', $this->chain['risk']->id))
            ->assertRedirect(route('erkap.risk-identifications.index'))
            ->assertSessionHas('success');

        $this->assertSame('submitted', $this->chain['risk']->fresh()->status);
    }
}