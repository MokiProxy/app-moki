<?php

namespace Tests\Feature\Erkap;

use App\Models\Employee;
use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\InvestmentStageGate;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\RiskAnalysis;
use App\Models\Erkap\RiskIdentificationImpact;
use App\Models\Erkap\RiskIdentificationReason;
use App\Models\Erkap\RiskImpact;
use App\Models\Erkap\RiskProbability;
use App\Models\Erkap\RiskScoreLevel;
use App\Models\Erkap\RoutineCost;
use App\Models\Erkap\WorkProgram;
use App\Models\Regional;
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

class ApprovalDetailViewTest extends TestCase
{
    use ActsAsSuperAdmin, BuildsErkapChain, RefreshDatabase;

    private array $chain;

    /** @var array<string, User> */
    private array $users = [];

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->setUpSuperAdmin();

        foreach ([
            'erkap-risk-manager', 'erkap-ppk', 'erkap-controller',
            'erkap-manajemen-aset', 'erkap-direksi-keuangan',
            'erkap-komisaris', 'erkap-direksi',
        ] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        Permission::firstOrCreate(['name' => 'erkap.approvals.view', 'guard_name' => 'web']);

        $this->chain = $this->buildErkapChain();

        foreach ([
            'erkap-ppk', 'erkap-controller', 'erkap-manajemen-aset',
            'erkap-direksi-keuangan', 'erkap-komisaris', 'erkap-direksi',
            'erkap-risk-manager',
        ] as $roleName) {
            $this->users[$roleName] = $this->makeUser($roleName);
        }
    }

    private function makeUser(string $roleName, ?int $divisionId = null): User
    {
        $employee = Employee::create([
            'employee_id' => 'EMP-DETAIL-'.uniqid(),
            'name' => 'Approver '.$roleName,
            'division_id' => $divisionId ?? $this->chain['division']->id,
            'regional_id' => Regional::factory()->create()->id,
        ]);

        $user = User::factory()->create(['employee_id' => $employee->employee_id]);
        $user->assignRole($roleName);
        $user->givePermissionTo('erkap.approvals.view');

        return $user;
    }

    private function seedRiskDetail(): void
    {
        $risk = $this->chain['risk'];

        foreach (['Keterbatasan sumber daya manusia', 'Ketergantungan pada sistem legacy'] as $reason) {
            RiskIdentificationReason::create([
                'erkap_risk_identification_id' => $risk->id,
                'reason' => $reason,
            ]);
        }

        foreach (['Proyek terlambat dari deadline', 'Biaya naik tidak terduga'] as $impact) {
            RiskIdentificationImpact::create([
                'erkap_risk_identification_id' => $risk->id,
                'impact' => $impact,
            ]);
        }

        $probability = RiskProbability::create(['name' => 'Sering', 'point' => 4]);
        $impact = RiskImpact::create(['name' => 'Berat', 'point' => 5]);
        $scoreValue = RiskScoreLevel::create([
            'erkap_risk_probability_id' => $probability->id,
            'erkap_risk_impact_id' => $impact->id,
            'score' => 20,
            'level' => 'High',
        ]);

        RiskAnalysis::create([
            'erkap_risk_identification_id' => $risk->id,
            'erkap_risk_probability_id' => $probability->id,
            'erkap_risk_impact_id' => $impact->id,
            'erkap_risk_score_value_id' => $scoreValue->id,
        ]);

        WorkProgram::create([
            'erkap_risk_identification_id' => $risk->id,
            'code' => 'WP-DETAIL',
            'name' => 'Program Pendampingan Risiko',
            'units' => 'Kegiatan',
            'year_plan' => 100,
            'jan_plan' => 8, 'feb_plan' => 8, 'mar_plan' => 9, 'apr_plan' => 8,
            'may_plan' => 8, 'jun_plan' => 8, 'jul_plan' => 8, 'aug_plan' => 8,
            'sep_plan' => 8, 'oct_plan' => 8, 'nov_plan' => 8, 'dec_plan' => 11,
            'status' => 'draft',
        ]);
    }

    public function test_risk_register_detail_renders_collections_as_tables(): void
    {
        $this->seedRiskDetail();
        ApprovalService::submit($this->chain['risk']);

        $this->actingAs($this->users['erkap-risk-manager'])
            ->get(route('erkap.approvals.show', ['risk_register', $this->chain['risk']->id]))
            ->assertOk()
            ->assertSee('Sumber Risiko')
            ->assertSee('Dampak')
            ->assertSee('Analisis Risiko')
            ->assertSee('Strategi Mitigasi')
            ->assertSee('Program Kerja')
            ->assertSee('Keterbatasan sumber daya manusia')
            ->assertSee('Ketergantungan pada sistem legacy')
            ->assertSee('Proyek terlambat dari deadline')
            ->assertSee('Biaya naik tidak terduga')
            ->assertSee('Program Pendampingan Risiko')
            ->assertSee('WP-DETAIL')
            ->assertSee('High');
    }

    public function test_risk_register_detail_shows_probability_and_impact_with_point_value(): void
    {
        $this->seedRiskDetail();
        ApprovalService::submit($this->chain['risk']);

        $response = $this->actingAs($this->users['erkap-risk-manager'])
            ->get(route('erkap.approvals.show', ['risk_register', $this->chain['risk']->id]));

        $response->assertOk()
            ->assertSee('Sering (4)')
            ->assertSee('Berat (5)');
    }

    public function test_risk_register_detail_escapes_html_in_data(): void
    {
        $risk = $this->chain['risk'];
        $risk->update(['risk' => '<script>alert(1)</script> Risiko XSS']);

        ApprovalService::submit($risk);

        $this->actingAs($this->users['erkap-risk-manager'])
            ->get(route('erkap.approvals.show', ['risk_register', $risk->id]))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt; Risiko XSS', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_risk_register_detail_escapes_html_in_collection_rows(): void
    {
        $risk = $this->chain['risk'];
        $risk->update(['risk' => 'Risiko Utama']);

        RiskIdentificationReason::create([
            'erkap_risk_identification_id' => $risk->id,
            'reason' => '<b>来源</b>Risiko Injeksi',
        ]);

        ApprovalService::submit($risk);

        $this->actingAs($this->users['erkap-risk-manager'])
            ->get(route('erkap.approvals.show', ['risk_register', $risk->id]))
            ->assertOk()
            ->assertSee('&lt;b&gt;来源&lt;/b&gt;Risiko Injeksi', false)
            ->assertDontSee('<b>来源</b>', false);
    }

    public function test_risk_register_detail_shows_empty_collection_message(): void
    {
        $risk = $this->chain['risk'];

        ApprovalService::submit($risk);

        $risk->departmentRiskStrategies()->delete();
        $risk->workPrograms()->delete();
        $risk->reasons()->delete();
        $risk->impacts()->delete();
        $risk->analysis()->delete();

        $this->actingAs($this->users['erkap-risk-manager'])
            ->get(route('erkap.approvals.show', ['risk_register', $risk->id]))
            ->assertOk()
            ->assertSee('Belum ada sumber risiko')
            ->assertSee('Belum ada dampak')
            ->assertSee('Belum ada analisis risiko')
            ->assertSee('Belum ada strategi mitigasi')
            ->assertSee('Belum ada program kerja');
    }

    public function test_work_program_detail_renders_monthly_table(): void
    {
        $workProgram = $this->chain['workProgram'];

        RoutineCost::factory()->create(['erkap_work_program_id' => $workProgram->id]);
        ApprovalService::submit($workProgram);

        $this->actingAs($this->users['erkap-ppk'])
            ->get(route('erkap.approvals.show', ['work_program', $workProgram->id]))
            ->assertOk()
            ->assertSee('Rencana Bulanan')
            ->assertSee('Januari')
            ->assertSee('Desember')
            ->assertSee('Total Tahunan');
    }

    public function test_routine_cost_detail_renders_monthly_table(): void
    {
        $routineCost = RoutineCost::factory()->create([
            'erkap_work_program_id' => $this->chain['workProgram']->id,
        ]);

        ApprovalService::submit($routineCost);

        $this->actingAs($this->users['erkap-ppk'])
            ->get(route('erkap.approvals.show', ['routine_cost', $routineCost->id]))
            ->assertOk()
            ->assertSee('Rincian Biaya Bulanan')
            ->assertSee('Total Biaya')
            ->assertSee('Januari')
            ->assertSee('Desember');
    }

    public function test_investment_plan_detail_renders_stage_gate_table(): void
    {

        $investmentPlan = InvestmentPlan::factory()->withProposal()->create([
            'erkap_work_program_id' => $this->chain['workProgram']->id,
        ]);

        ApprovalService::submit($investmentPlan);

        $response = $this->actingAs($this->users['erkap-ppk'])
            ->get(route('erkap.approvals.show', ['investment_plan', $investmentPlan->id]));

        $response->assertOk()
            ->assertSee('Rencana Investasi Bulanan')
            ->assertSee('Stage Gate Review')
            ->assertSee('Total Investasi');

        foreach (array_keys(InvestmentGateReviewService::STAGES) as $stage) {
            $response->assertSee(InvestmentStageGate::stageLabel($stage));
        }
    }

    public function test_rkap_detail_renders(): void
    {
        $rkap = RKAP::factory()->create([
            'year' => (int) date('Y') + 1,
            'status' => 'draft',
        ]);

        ApprovalService::submit($rkap);

        $this->actingAs($this->users['erkap-komisaris'])
            ->get(route('erkap.approvals.show', ['rkap', $rkap->id]))
            ->assertOk()
            ->assertSee('Ringkasan Periode RKAP')
            ->assertSee('Fase Lifecycle')
            ->assertSee('Status Distribusi');
    }

    public function test_every_document_type_has_a_detail_partial(): void
    {
        foreach (array_keys(ApprovalService::documentTypes()) as $type) {
            $view = 'erkap.approvals.partials.details.'.str_replace('_', '-', $type);

            $this->assertTrue(
                view()->exists($view),
                "Partial detail [{$view}] tidak ada untuk tipe dokumen [{$type}]."
            );
        }
    }

    public function test_detail_view_uses_collection_table_instead_of_inline_badges(): void
    {
        $content = file_get_contents(
            resource_path('views/erkap/approvals/partials/details/risk-register.blade.php')
        );

        $this->assertStringContainsString('partials.collection', $content);
        $this->assertStringNotContainsString('class="badge bg-light text-dark me-1"', $content);
    }
}
