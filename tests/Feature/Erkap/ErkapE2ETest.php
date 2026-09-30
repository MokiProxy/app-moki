<?php

namespace Tests\Feature\Erkap;

use App\Models\Company;
use App\Models\Division;
use App\Models\Erkap\CompanyTarget;
use App\Models\Erkap\DepartmentRiskStrategy;
use App\Models\Erkap\DepartmentTarget;
use App\Models\Erkap\InvestattionCategory;
use App\Models\Erkap\InvestationCriteria;
use App\Models\Erkap\InvestationType;
use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\InvestmentStageGate;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\RatingCriteria;
use App\Models\Erkap\RiskAppetite;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RiskImpact;
use App\Models\Erkap\RiskProbability;
use App\Models\Erkap\RiskScoreLevel;
use App\Models\Erkap\RiskTaxonomy;
use App\Models\Erkap\RiskType;
use App\Models\Erkap\RoutineCost;
use App\Models\Erkap\WorkProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ErkapE2ETest extends TestCase
{
    use RefreshDatabase;

    protected $ppk;
    protected $costOwner;
    protected $controller;
    protected $riskManager;
    protected $manajemenAset;
    protected $direksiKeuangan;
    protected $komisaris;
    protected $direksi;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();

        $this->ppk = $this->createUserWithRole('erkap-ppk');
        $this->costOwner = $this->createUserWithRole('erkap-cost-owner');
        $this->controller = $this->createUserWithRole('erkap-controller');
        $this->riskManager = $this->createUserWithRole('erkap-risk-manager');
        $this->manajemenAset = $this->createUserWithRole('erkap-manajemen-aset');
        $this->direksiKeuangan = $this->createUserWithRole('erkap-direksi-keuangan');
        $this->komisaris = $this->createUserWithRole('erkap-komisaris');
        $this->direksi = $this->createUserWithRole('erkap-direksi');
        $this->admin = $this->createUserWithRole('erkap-admin');

        $this->seedMasterData();
    }

    protected function seedRolesAndPermissions()
    {
        $permissions = [
            'erkap.menu',
            'erkap.rkap.view', 'erkap.rkap.create', 'erkap.rkap.edit', 'erkap.rkap.delete', 'erkap.rkap.submit',
            'erkap.company-targets.view', 'erkap.company-targets.create', 'erkap.company-targets.edit', 'erkap.company-targets.delete',
            'erkap.department-targets.view', 'erkap.department-targets.create', 'erkap.department-targets.edit', 'erkap.department-targets.delete',
            'erkap.risk-identifications.view', 'erkap.risk-identifications.create', 'erkap.risk-identifications.edit', 'erkap.risk-identifications.delete', 'erkap.risk-identifications.submit',
            'erkap.risk-analysis.view', 'erkap.risk-analysis.create', 'erkap.risk-analysis.edit', 'erkap.risk-analysis.delete',
            'erkap.department-risk-strategies.view', 'erkap.department-risk-strategies.create', 'erkap.department-risk-strategies.edit', 'erkap.department-risk-strategies.delete',
            'erkap.work-programs.view', 'erkap.work-programs.create', 'erkap.work-programs.edit', 'erkap.work-programs.delete', 'erkap.work-programs.submit',
            'erkap.routine-costs.view', 'erkap.routine-costs.create', 'erkap.routine-costs.edit', 'erkap.routine-costs.delete', 'erkap.routine-costs.submit',
            'erkap.investment-plans.view', 'erkap.investment-plans.create', 'erkap.investment-plans.edit', 'erkap.investment-plans.delete', 'erkap.investment-plans.submit', 'erkap.investment-plans.download',
            'erkap.investment-gates.view', 'erkap.investment-gates.review',
            'erkap.budget-capex.view', 'erkap.budget-capex.create', 'erkap.budget-capex.edit',
            'erkap.revenue-plans.view', 'erkap.revenue-plans.create', 'erkap.revenue-plans.edit', 'erkap.revenue-plans.delete',
            'erkap.expense-plans.view', 'erkap.expense-plans.create', 'erkap.expense-plans.edit', 'erkap.expense-plans.delete',
            'erkap.profit-loss.view', 'erkap.profit-loss.create',
            'erkap.budget-realizations.view', 'erkap.budget-realizations.create', 'erkap.budget-realizations.delete',
            'erkap.program-realizations.view', 'erkap.program-realizations.create', 'erkap.program-realizations.delete',
            'erkap.risk-assessments-monthly.view', 'erkap.risk-assessments-monthly.create', 'erkap.risk-assessments-monthly.edit', 'erkap.risk-assessments-monthly.delete',
            'erkap.performance-scorecards.view', 'erkap.performance-scorecards.create', 'erkap.performance-scorecards.delete',
            'erkap.zbb-reviews.view', 'erkap.zbb-reviews.create', 'erkap.zbb-reviews.edit',
            'erkap.approvals.view',
            'erkap.audit-logs.view',
            'erkap.reports.view', 'erkap.reports.generate',
            'erkap.structure.view', 'erkap.structure.edit',
            'erkap.business-units.view', 'erkap.business-units.create', 'erkap.business-units.edit', 'erkap.business-units.delete',
            'erkap.locations.view', 'erkap.locations.create', 'erkap.locations.edit', 'erkap.locations.delete',
            'erkap.management-areas.view', 'erkap.management-areas.create', 'erkap.management-areas.edit', 'erkap.management-areas.delete',
            'erkap.activities.view', 'erkap.activities.create', 'erkap.activities.edit', 'erkap.activities.delete',
            'erkap.cost-centers.view', 'erkap.cost-centers.create', 'erkap.cost-centers.edit', 'erkap.cost-centers.delete',
            'erkap.chart-of-accounts.view', 'erkap.chart-of-accounts.create', 'erkap.chart-of-accounts.edit', 'erkap.chart-of-accounts.delete',
            'erkap.cost-element-categories.view', 'erkap.cost-element-categories.create', 'erkap.cost-element-categories.edit', 'erkap.cost-element-categories.delete',
            'erkap.cost-elements.view', 'erkap.cost-elements.create', 'erkap.cost-elements.edit', 'erkap.cost-elements.delete',
            'erkap.risk-appetites.view', 'erkap.risk-appetites.create', 'erkap.risk-appetites.edit', 'erkap.risk-appetites.delete',
            'erkap.risk-taxonomies.view', 'erkap.risk-taxonomies.create', 'erkap.risk-taxonomies.edit', 'erkap.risk-taxonomies.delete',
            'erkap.risk-types.view', 'erkap.risk-types.create', 'erkap.risk-types.edit', 'erkap.risk-types.delete',
            'erkap.rating-criterias.view', 'erkap.rating-criterias.create', 'erkap.rating-criterias.edit', 'erkap.rating-criterias.delete',
            'erkap.risk-scales.view', 'erkap.risk-scales.create', 'erkap.risk-scales.edit', 'erkap.risk-scales.delete',
            'erkap.risk-probabilities.view', 'erkap.risk-probabilities.create', 'erkap.risk-probabilities.edit', 'erkap.risk-probabilities.delete',
            'erkap.risk-impacts.view', 'erkap.risk-impacts.create', 'erkap.risk-impacts.edit', 'erkap.risk-impacts.delete',
            'erkap.risk-score-levels.view', 'erkap.risk-score-levels.create', 'erkap.risk-score-levels.edit', 'erkap.risk-score-levels.delete',
            'erkap.investation-types.view', 'erkap.investation-types.create', 'erkap.investation-types.edit', 'erkap.investation-types.delete',
            'erkap.investation-criterias.view', 'erkap.investation-criterias.create', 'erkap.investation-criterias.edit', 'erkap.investation-criterias.delete',
            'erkap.investattion-categories.view', 'erkap.investattion-categories.create', 'erkap.investattion-categories.edit', 'erkap.investattion-categories.delete',
            'erkap.risk-identification-reasons.view', 'erkap.risk-identification-reasons.create', 'erkap.risk-identification-reasons.edit', 'erkap.risk-identification-reasons.delete',
            'erkap.risk-identification-impacts.view', 'erkap.risk-identification-impacts.create', 'erkap.risk-identification-impacts.edit', 'erkap.risk-identification-impacts.delete',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        $roles = ['erkap-admin', 'erkap-ppk', 'erkap-cost-owner', 'erkap-controller', 'erkap-risk-manager', 'erkap-manajemen-aset', 'erkap-direksi-keuangan', 'erkap-komisaris', 'erkap-direksi', 'erkap-auditor'];
        foreach ($roles as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            foreach ($permissions as $permName) {
                $perm = Permission::where('name', $permName)->first();
                if ($perm) {
                    $role->givePermissionTo($perm);
                }
            }
        }
    }

    protected function createUserWithRole(string $roleName): User
    {
        $user = User::factory()->create();
        $user->assignRole($roleName);
        return $user;
    }

    protected function seedMasterData()
    {
        $appetite = RiskAppetite::factory()->create(['threshold_score' => 12]);
        $taxonomy = RiskTaxonomy::factory()->create(['risk_appetite_id' => $appetite->id]);
        $riskType = RiskType::factory()->create(['risk_taxonomy_id' => $taxonomy->id]);
        $rating = RatingCriteria::factory()->create(['rating' => 'A', 'qualification' => 'Sangat Baik']);
        $probability = RiskProbability::factory()->create();
        $impact = RiskImpact::factory()->create();
        $scoreLevel = RiskScoreLevel::factory()->create([
            'erkap_risk_probability_id' => $probability->id,
            'erkap_risk_impact_id' => $impact->id,
            'score' => 10,
            'level' => 'Moderate',
        ]);
        $invType = InvestationType::factory()->create();
        $invCriteria = InvestationCriteria::factory()->create();
        $invCategory = InvestattionCategory::factory()->create();
        $costElement = \App\Models\Erkap\CostElement::factory()->create();
        $costCenter = \App\Models\Erkap\CostCenter::factory()->create();

        $this->masterData = compact('appetite', 'taxonomy', 'riskType', 'rating', 'probability', 'impact', 'scoreLevel', 'invType', 'invCriteria', 'invCategory', 'costElement', 'costCenter');
    }

    protected function createRkap(): RKAP
    {
        return RKAP::factory()->create([
            'year' => 2026,
            'status' => 'draft',
            'phase' => 'initiation',
            'company_id' => Company::factory()->create()->id,
        ]);
    }

    protected function createFullChain(RKAP $rkap): array
    {
        $division = Division::factory()->create();
        $companyTarget = CompanyTarget::factory()->create(['erkap_rkap_id' => $rkap->id]);
        $deptTarget = DepartmentTarget::factory()->create([
            'division_id' => $division->id,
            'erkap_rating_criteria_id' => $this->masterData['rating']->id,
            'erkap_company_target_id' => $companyTarget->id,
        ]);
        $risk = RiskIdentification::factory()->create([
            'erkap_department_target_id' => $deptTarget->id,
            'erkap_risk_type_id' => $this->masterData['riskType']->id,
            'erkap_risk_taxonomy_id' => $this->masterData['taxonomy']->id,
        ]);
        DepartmentRiskStrategy::create([
            'erkap_risk_identification_id' => $risk->id,
            'strategy' => 'reduction',
        ]);
        $workProgram = WorkProgram::create([
            'erkap_risk_identification_id' => $risk->id,
            'code' => 'WP-TEST',
            'name' => 'Program Kerja Uji',
            'units' => 'Unit',
            'year_plan' => 100,
            'jan_plan' => 8, 'feb_plan' => 8, 'mar_plan' => 8, 'apr_plan' => 8,
            'may_plan' => 8, 'jun_plan' => 8, 'jul_plan' => 8, 'aug_plan' => 8,
            'sep_plan' => 8, 'oct_plan' => 8, 'nov_plan' => 8, 'dec_plan' => 12,
            'status' => 'draft',
        ]);
        return compact('division', 'companyTarget', 'deptTarget', 'risk', 'workProgram');
    }

    // ==================== PHASE 1: INISIASI & KICK-OFF ====================

    public function test_phase1_ppk_can_create_rkap_period()
    {
        $this->actingAs($this->ppk);

        $response = $this->get('/erkap/rkap');
        $response->assertStatus(200);

        $response = $this->post('/erkap/rkap', [
            'year' => '2026',
        ]);
        $response->assertStatus(302);

        $this->assertDatabaseHas('erkap_rkap', [
            'year' => 2026,
            'phase' => 'initiation',
            'status' => 'draft',
        ]);
    }

    public function test_phase1_ppk_can_fill_kickoff()
    {
        $this->actingAs($this->ppk);
        $rkap = $this->createRkap();

        $response = $this->post("/erkap/rkap/{$rkap->id}/kickoff", [
            'kickoff_date' => '2026-01-15',
            'kickoff_notes' => 'Sosialisasi RKAP 2026',
        ]);
        $response->assertStatus(302);

        $rkap->refresh();
        $this->assertEquals('2026-01-15', $rkap->kickoff_date->toDateString());
        $this->assertEquals('Sosialisasi RKAP 2026', $rkap->kickoff_notes);
    }

    public function test_phase1_ppk_can_upload_direction()
    {
        $this->actingAs($this->ppk);
        $rkap = $this->createRkap();

        $file = UploadedFile::fake()->create('direction.pdf', 100, 'application/pdf');

        $response = $this->post("/erkap/rkap/{$rkap->id}/direction", [
            'direction_file' => $file,
            'direction_notes' => 'Arahan Direksi 2026',
        ]);
        $response->assertStatus(302);

        $rkap->refresh();
        $this->assertNotNull($rkap->direction_file_path);
        $this->assertEquals('Arahan Direksi 2026', $rkap->direction_notes);
    }

    public function test_phase1_ppk_can_advance_to_preparation()
    {
        $this->actingAs($this->ppk);
        $rkap = $this->createRkap();

        $response = $this->post("/erkap/rkap/{$rkap->id}/advance");
        $response->assertStatus(302);

        $rkap->refresh();
        $this->assertEquals('preparation', $rkap->phase);
    }

    // ==================== PHASE 2: PENYUSUNAN ====================

    public function test_phase2_company_target_can_be_created()
    {
        $this->actingAs($this->costOwner);
        $rkap = $this->createRkap();

        $response = $this->post('/erkap/company-targets', [
            'erkap_rkap_id' => $rkap->id,
            'target' => 'Meningkatkan pendapatan 20%',
        ]);
        $response->assertStatus(302);

        $this->assertDatabaseHas('erkap_company_targets', [
            'erkap_rkap_id' => $rkap->id,
        ]);
    }

    public function test_phase2_department_target_can_be_created()
    {
        $this->actingAs($this->ppk);
        $rkap = $this->createRkap();
        $companyTarget = CompanyTarget::factory()->create(['erkap_rkap_id' => $rkap->id]);
        $division = Division::factory()->create();

        $response = $this->post('/erkap/department-targets', [
            'division_id' => $division->id,
            'erkap_company_target_id' => $companyTarget->id,
            'erkap_rating_criteria_id' => $this->masterData['rating']->id,
            'target' => 'Sasaran departemen uji',
        ]);
        $response->assertStatus(302);

        $this->assertDatabaseHas('erkap_department_targets', [
            'division_id' => $division->id,
            'erkap_company_target_id' => $companyTarget->id,
        ]);
    }

    public function test_phase2_risk_identification_can_be_created()
    {
        $this->actingAs($this->ppk);
        $rkap = $this->createRkap();
        $chain = $this->createFullChain($rkap);

        $response = $this->post('/erkap/risk-identifications', [
            'erkap_department_target_id' => $chain['deptTarget']->id,
            'erkap_risk_type_id' => $this->masterData['riskType']->id,
            'erkap_risk_taxonomy_id' => $this->masterData['taxonomy']->id,
            'risk' => 'Risiko uji',
            'risk_direction' => 'negative',
        ]);
        $response->assertStatus(302);

        $this->assertDatabaseHas('erkap_risk_identifications', [
            'erkap_department_target_id' => $chain['deptTarget']->id,
            'risk' => 'Risiko uji',
        ]);
    }

    public function test_phase2_risk_analysis_can_be_created()
    {
        $this->actingAs($this->ppk);
        $rkap = $this->createRkap();
        $chain = $this->createFullChain($rkap);

        $response = $this->post('/erkap/risk-analysis', [
            'erkap_risk_identification_id' => $chain['risk']->id,
            'erkap_risk_probability_id' => $this->masterData['probability']->id,
            'erkap_risk_impact_id' => $this->masterData['impact']->id,
            'erkap_risk_score_level_id' => $this->masterData['scoreLevel']->id,
        ]);
        $response->assertStatus(302);

        $this->assertDatabaseHas('erkap_risk_analysis', [
            'erkap_risk_identification_id' => $chain['risk']->id,
        ]);
    }

    public function test_phase2_work_program_can_be_created()
    {
        $this->actingAs($this->costOwner);
        $rkap = $this->createRkap();
        $chain = $this->createFullChain($rkap);

        $response = $this->post('/erkap/work-programs', [
            'erkap_risk_identification_id' => $chain['risk']->id,
            'name' => 'Program Kerja Uji',
            'units' => 'Unit',
            'year_plan' => 100,
            'jan_plan' => 10, 'feb_plan' => 10, 'mar_plan' => 10, 'apr_plan' => 10,
            'may_plan' => 10, 'jun_plan' => 10, 'jul_plan' => 10, 'aug_plan' => 10,
            'sep_plan' => 10, 'oct_plan' => 10, 'nov_plan' => 10, 'dec_plan' => 10,
        ]);
        $response->assertStatus(302);

        $this->assertDatabaseHas('erkap_work_programs', [
            'erkap_risk_identification_id' => $chain['risk']->id,
            'name' => 'Program Kerja Uji',
        ]);
    }

    public function test_phase2_routine_cost_can_be_created()
    {
        $this->actingAs($this->costOwner);
        $rkap = $this->createRkap();
        $chain = $this->createFullChain($rkap);

        $response = $this->post('/erkap/routine-costs', [
            'erkap_work_program_id' => $chain['workProgram']->id,
            'need' => 'Biaya Gaji',
            'erkap_cost_element_id' => 1,
            'cost_center_id' => 1,
            'cost_center_owner' => 'Divisi Umum',
            'qty' => 10,
            'units' => 'Bulan',
            'unit_price' => 5000000,
            'jan_cost' => 50000000, 'feb_cost' => 50000000, 'mar_cost' => 50000000,
            'apr_cost' => 50000000, 'may_cost' => 50000000, 'jun_cost' => 50000000,
            'jul_cost' => 50000000, 'aug_cost' => 50000000, 'sep_cost' => 50000000,
            'oct_cost' => 50000000, 'nov_cost' => 50000000, 'dec_cost' => 50000000,
            'total' => 600000000,
        ]);
        $response->assertStatus(302);

        $this->assertDatabaseHas('erkap_routine_costs', [
            'erkap_work_program_id' => $chain['workProgram']->id,
            'need' => 'Biaya Gaji',
        ]);
    }

    public function test_phase2_investment_plan_can_be_created()
    {
        $this->actingAs($this->costOwner);
        $rkap = $this->createRkap();
        $chain = $this->createFullChain($rkap);

        $response = $this->post('/erkap/investment-plans', [
            'erkap_work_program_id' => $chain['workProgram']->id,
            'name' => 'Investasi Uji',
            'erkap_investattion_category_id' => $this->masterData['invCategory']->id,
            'erkap_investation_type_id' => $this->masterData['invType']->id,
            'erkap_investation_criteria_id' => $this->masterData['invCriteria']->id,
            'description' => 'Deskripsi investasi',
            'qty' => 1,
            'unit' => 'Unit',
            'unit_price' => 100000000,
            'priority_order' => 1,
            'jan_plan' => 100000000, 'feb_plan' => 0, 'mar_plan' => 0, 'apr_plan' => 0,
            'may_plan' => 0, 'jun_plan' => 0, 'jul_plan' => 0, 'aug_plan' => 0,
            'sep_plan' => 0, 'oct_plan' => 0, 'nov_plan' => 0, 'dec_plan' => 0,
        ]);
        $response->assertStatus(302);

        $this->assertDatabaseHas('erkap_investment_plans', [
            'erkap_work_program_id' => $chain['workProgram']->id,
            'name' => 'Investasi Uji',
        ]);
    }

    // ==================== PHASE 3: PENGAJUAN & PERSETUJUAN ====================

    public function test_phase3_risk_register_submission_and_approval()
    {
        $this->actingAs($this->ppk);
        $rkap = $this->createRkap();
        $chain = $this->createFullChain($rkap);

        $this->actingAs($this->ppk);
        $response = $this->post("/erkap/risk-identifications/{$chain['risk']->id}/submit");
        $response->assertStatus(302);

        $chain['risk']->refresh();
        $this->assertEquals('submitted', $chain['risk']->status);

        $this->actingAs($this->riskManager);
        $response = $this->post("/erkap/approvals/risk_register/{$chain['risk']->id}/approve", [
            'notes' => 'Disetujui',
        ]);
        $response->assertStatus(302);

        $chain['risk']->refresh();
        $this->assertEquals('approved', $chain['risk']->status);
    }

    public function test_phase3_work_program_submission_and_approval()
    {
        $this->actingAs($this->ppk);
        $rkap = $this->createRkap();
        $chain = $this->createFullChain($rkap);

        RoutineCost::create([
            'erkap_work_program_id' => $chain['workProgram']->id,
            'need' => 'Biaya Operasional',
            'erkap_cost_element_id' => 1,
            'cost_center_id' => 1,
            'cost_center_owner' => 'Divisi Umum',
            'qty' => 10,
            'units' => 'Bulan',
            'unit_price' => 1000000,
            'jan_cost' => 10000000, 'feb_cost' => 10000000, 'mar_cost' => 10000000,
            'apr_cost' => 10000000, 'may_cost' => 10000000, 'jun_cost' => 10000000,
            'jul_cost' => 10000000, 'aug_cost' => 10000000, 'sep_cost' => 10000000,
            'oct_cost' => 10000000, 'nov_cost' => 10000000, 'dec_cost' => 10000000,
            'total' => 120000000,
            'status' => 'draft',
        ]);

        $this->actingAs($this->ppk);
        $response = $this->post("/erkap/work-programs/{$chain['workProgram']->id}/submit");
        $response->assertStatus(302);

        $chain['workProgram']->refresh();
        $this->assertEquals('submitted', $chain['workProgram']->status);

        $this->actingAs($this->ppk);
        $response = $this->post("/erkap/approvals/work_program/{$chain['workProgram']->id}/approve", [
            'notes' => 'Disetujui PPK',
        ]);
        $response->assertStatus(302);

        $this->actingAs($this->controller);
        $response = $this->post("/erkap/approvals/work_program/{$chain['workProgram']->id}/approve", [
            'notes' => 'Disetujui Controller',
        ]);
        $response->assertStatus(302);

        $chain['workProgram']->refresh();
        $this->assertEquals('approved', $chain['workProgram']->status);
    }

    public function test_phase3_routine_cost_submission_and_approval()
    {
        $this->actingAs($this->ppk);
        $rkap = $this->createRkap();
        $chain = $this->createFullChain($rkap);

        $routineCost = RoutineCost::create([
            'erkap_work_program_id' => $chain['workProgram']->id,
            'need' => 'Biaya Operasional',
            'erkap_cost_element_id' => $this->masterData['costElement']->id,
            'cost_center_id' => $this->masterData['costCenter']->id,
            'cost_center_owner' => 'Divisi Umum',
            'qty' => 10,
            'units' => 'Bulan',
            'unit_price' => 1000000,
            'jan_cost' => 10000000, 'feb_cost' => 10000000, 'mar_cost' => 10000000,
            'apr_cost' => 10000000, 'may_cost' => 10000000, 'jun_cost' => 10000000,
            'jul_cost' => 10000000, 'aug_cost' => 10000000, 'sep_cost' => 10000000,
            'oct_cost' => 10000000, 'nov_cost' => 10000000, 'dec_cost' => 10000000,
            'total' => 120000000,
            'status' => 'draft',
        ]);

        $this->actingAs($this->ppk);
        $response = $this->post("/erkap/routine-costs/{$routineCost->id}/submit");
        $response->assertStatus(302);

        $routineCost->refresh();
        $this->assertEquals('submitted', $routineCost->status);

        $this->actingAs($this->ppk);
        $response = $this->post("/erkap/approvals/routine_cost/{$routineCost->id}/approve", [
            'notes' => 'Disetujui PPK',
        ]);
        $response->assertStatus(302);

        $this->actingAs($this->controller);
        $response = $this->post("/erkap/approvals/routine_cost/{$routineCost->id}/approve", [
            'notes' => 'Disetujui Controller',
        ]);
        $response->assertStatus(302);

        $routineCost->refresh();
        $this->assertEquals('approved', $routineCost->status);
    }

    public function test_phase3_investment_plan_submission_and_approval()
    {
        $this->actingAs($this->ppk);
        $rkap = $this->createRkap();
        $chain = $this->createFullChain($rkap);

        $investmentPlan = InvestmentPlan::create([
            'erkap_work_program_id' => $chain['workProgram']->id,
            'name' => 'Investasi Uji',
            'erkap_investattion_category_id' => $this->masterData['invCategory']->id,
            'erkap_investation_type_id' => $this->masterData['invType']->id,
            'erkap_investation_criteria_id' => $this->masterData['invCriteria']->id,
            'description' => 'Deskripsi investasi',
            'qty' => 1,
            'unit' => 'Unit',
            'unit_price' => 100000000,
            'priority_order' => 1,
            'jan_plan' => 100000000, 'feb_plan' => 0, 'mar_plan' => 0, 'apr_plan' => 0,
            'may_plan' => 0, 'jun_plan' => 0, 'jul_plan' => 0, 'aug_plan' => 0,
            'sep_plan' => 0, 'oct_plan' => 0, 'nov_plan' => 0, 'dec_plan' => 0,
            'total' => 100000000,
            'status' => 'draft',
            'proposal_file_path' => 'proposals/test.pdf',
        ]);

        $this->actingAs($this->ppk);
        $response = $this->post("/erkap/investment-plans/{$investmentPlan->id}/submit");
        $response->assertStatus(302);

        $investmentPlan->refresh();
        $this->assertEquals('submitted', $investmentPlan->status);

        $gates = InvestmentStageGate::where('erkap_investment_plan_id', $investmentPlan->id)->get();
        foreach ($gates as $gate) {
            $this->actingAs($this->ppk);
            $response = $this->post("/erkap/investment-gates/{$gate->id}/review", [
                'status' => 'approved',
                'notes' => 'Gate disetujui',
            ]);
            $response->assertStatus(302);
        }

        $this->actingAs($this->ppk);
        $response = $this->post("/erkap/approvals/investment_plan/{$investmentPlan->id}/approve", [
            'notes' => 'Disetujui PPK',
        ]);
        $response->assertStatus(302);

        $this->actingAs($this->manajemenAset);
        $response = $this->post("/erkap/approvals/investment_plan/{$investmentPlan->id}/approve", [
            'notes' => 'Disetujui Manajemen Aset',
        ]);
        $response->assertStatus(302);

        $this->actingAs($this->direksiKeuangan);
        $response = $this->post("/erkap/approvals/investment_plan/{$investmentPlan->id}/approve", [
            'notes' => 'Disetujui Direksi Keuangan',
        ]);
        $response->assertStatus(302);

        $investmentPlan->refresh();
        $this->assertEquals('approved', $investmentPlan->status);
    }

    public function test_phase3_stage_gate_review_flow()
    {
        $this->actingAs($this->ppk);
        $rkap = $this->createRkap();
        $chain = $this->createFullChain($rkap);

        $investmentPlan = InvestmentPlan::create([
            'erkap_work_program_id' => $chain['workProgram']->id,
            'name' => 'Investasi Uji',
            'erkap_investattion_category_id' => $this->masterData['invCategory']->id,
            'erkap_investation_type_id' => $this->masterData['invType']->id,
            'erkap_investation_criteria_id' => $this->masterData['invCriteria']->id,
            'description' => 'Deskripsi investasi',
            'qty' => 1,
            'unit' => 'Unit',
            'unit_price' => 100000000,
            'priority_order' => 1,
            'jan_plan' => 100000000, 'feb_plan' => 0, 'mar_plan' => 0, 'apr_plan' => 0,
            'may_plan' => 0, 'jun_plan' => 0, 'jul_plan' => 0, 'aug_plan' => 0,
            'sep_plan' => 0, 'oct_plan' => 0, 'nov_plan' => 0, 'dec_plan' => 0,
            'total' => 100000000,
            'status' => 'draft',
            'proposal_file_path' => 'proposals/test.pdf',
        ]);

        $this->actingAs($this->ppk);
        $response = $this->post("/erkap/investment-plans/{$investmentPlan->id}/submit");
        $response->assertStatus(302);

        $gates = InvestmentStageGate::where('erkap_investment_plan_id', $investmentPlan->id)->get();
        $this->assertGreaterThan(0, $gates->count());

        foreach ($gates as $gate) {
            $response = $this->post("/erkap/investment-gates/{$gate->id}/review", [
                'status' => 'approved',
                'notes' => 'Gate disetujui',
            ]);
            $response->assertStatus(302);
        }
    }

    public function test_phase3_rejection_flow()
    {
        $this->actingAs($this->ppk);
        $rkap = $this->createRkap();
        $chain = $this->createFullChain($rkap);

        $this->actingAs($this->ppk);
        $response = $this->post("/erkap/risk-identifications/{$chain['risk']->id}/submit");
        $response->assertStatus(302);

        $this->actingAs($this->riskManager);
        $response = $this->post("/erkap/approvals/risk_register/{$chain['risk']->id}/reject", [
            'notes' => 'Ditolak - perlu revisi',
        ]);
        $response->assertStatus(302);

        $chain['risk']->refresh();
        $this->assertEquals('rejected', $chain['risk']->status);
    }

    // ==================== PHASE 4: KONSOLIDASI & REVIEW ====================

    public function test_phase4_zbb_review_can_be_built()
    {
        $this->actingAs($this->ppk);
        $rkap = $this->createRkap();
        $chain = $this->createFullChain($rkap);

        $response = $this->post('/erkap/zbb-reviews/build');
        $response->assertStatus(302);
    }

    public function test_phase4_budget_capex_can_be_consolidated()
    {
        $this->actingAs($this->ppk);
        $rkap = $this->createRkap();
        $chain = $this->createFullChain($rkap);

        $response = $this->post('/erkap/budget-capex/consolidate');
        $response->assertStatus(302);
    }

    public function test_phase4_revenue_plan_can_be_created()
    {
        $this->actingAs($this->ppk);
        $rkap = $this->createRkap();

        $response = $this->post('/erkap/revenue-plans', [
            'erkap_rkap_id' => $rkap->id,
            'description' => 'Pendapatan Usaha',
            'amount' => 1000000000,
        ]);
        $response->assertStatus(302);
    }

    public function test_phase4_expense_plan_can_be_created()
    {
        $this->actingAs($this->ppk);
        $rkap = $this->createRkap();

        $response = $this->post('/erkap/expense-plans', [
            'erkap_rkap_id' => $rkap->id,
            'description' => 'Beban Operasional',
            'amount' => 500000000,
        ]);
        $response->assertStatus(302);
    }

    // ==================== PHASE 5: FINALISASI & PENGESAHAN ====================

    public function test_phase5_rkap_submission_and_approval()
    {
        $this->actingAs($this->ppk);
        $rkap = $this->createRkap();
        $rkap->update(['phase' => 'finalization']);

        $this->actingAs($this->ppk);
        $response = $this->post("/erkap/rkap/{$rkap->id}/submit");
        $response->assertStatus(302);

        $rkap->refresh();
        $this->assertEquals('submitted', $rkap->status);

        $this->actingAs($this->direksi);
        $response = $this->post("/erkap/approvals/rkap/{$rkap->id}/approve", [
            'notes' => 'Disetujui Direksi',
        ]);
        $response->assertStatus(302);

        $this->actingAs($this->komisaris);
        $response = $this->post("/erkap/approvals/rkap/{$rkap->id}/approve", [
            'notes' => 'Disetujui Komisaris',
        ]);
        $response->assertStatus(302);

        $rkap->refresh();
        $this->assertEquals('approved', $rkap->status);
    }

    public function test_phase5_rkap_can_be_distributed()
    {
        $this->actingAs($this->ppk);
        $rkap = $this->createRkap();
        $rkap->update(['status' => 'approved', 'phase' => 'approved']);

        $response = $this->post("/erkap/rkap/{$rkap->id}/distribute");
        $response->assertStatus(302);

        $rkap->refresh();
        $this->assertEquals('distributed', $rkap->distribution_status);
    }

    public function test_phase5_rkap_can_be_archived()
    {
        $this->actingAs($this->ppk);
        $rkap = $this->createRkap();
        $rkap->update(['status' => 'approved', 'phase' => 'approved', 'distribution_status' => 'distributed']);

        $response = $this->post("/erkap/rkap/{$rkap->id}/advance");
        $response->assertStatus(302);

        $rkap->refresh();
        $this->assertEquals('archived', $rkap->phase);
    }

    public function test_phase5_data_locked_after_finalization()
    {
        $this->actingAs($this->costOwner);
        $rkap = $this->createRkap();
        $rkap->update(['phase' => 'finalization']);

        $chain = $this->createFullChain($rkap);

        $response = $this->post('/erkap/work-programs', [
            'erkap_risk_identification_id' => $chain['risk']->id,
            'code' => 'WP-LOCK',
            'name' => 'Program Kerja Lock Test',
            'units' => 'Unit',
            'year_plan' => 100,
            'jan_plan' => 8, 'feb_plan' => 8, 'mar_plan' => 8, 'apr_plan' => 8,
            'may_plan' => 8, 'jun_plan' => 8, 'jul_plan' => 8, 'aug_plan' => 8,
            'sep_plan' => 8, 'oct_plan' => 8, 'nov_plan' => 8, 'dec_plan' => 12,
        ]);
        $response->assertStatus(302);

        $this->assertDatabaseMissing('erkap_work_programs', [
            'code' => 'WP-LOCK',
        ]);
    }

    // ==================== PHASE 6: MONITORING & REALISASI ====================

    public function test_phase6_budget_realization_can_be_created()
    {
        $this->actingAs($this->costOwner);
        $rkap = $this->createRkap();
        $rkap->update(['status' => 'approved', 'phase' => 'approved']);

        $response = $this->post('/erkap/budget-realizations', [
            'erkap_rkap_id' => $rkap->id,
            'month' => 1,
            'budget' => 10000000,
            'realization' => 8000000,
        ]);
        $response->assertStatus(302);
    }

    public function test_phase6_program_realization_can_be_created()
    {
        $this->actingAs($this->costOwner);
        $rkap = $this->createRkap();
        $rkap->update(['status' => 'approved', 'phase' => 'approved']);
        $chain = $this->createFullChain($rkap);

        $response = $this->post('/erkap/program-realizations', [
            'erkap_work_program_id' => $chain['workProgram']->id,
            'month' => 1,
            'target' => 8,
            'realization' => 6,
            'percentage' => 75,
        ]);
        $response->assertStatus(302);
    }

    public function test_phase6_risk_assessment_monthly_can_be_created()
    {
        $this->actingAs($this->costOwner);
        $rkap = $this->createRkap();
        $rkap->update(['status' => 'approved', 'phase' => 'approved']);
        $chain = $this->createFullChain($rkap);

        $response = $this->post('/erkap/risk-assessments-monthly', [
            'erkap_risk_identification_id' => $chain['risk']->id,
            'month' => 1,
            'erkap_risk_appetite_id' => $this->masterData['appetite']->id,
            'assessment' => 'Normal',
        ]);
        $response->assertStatus(302);
    }

    public function test_phase6_performance_scorecard_can_be_created()
    {
        $this->actingAs($this->costOwner);
        $rkap = $this->createRkap();
        $rkap->update(['status' => 'approved', 'phase' => 'approved']);

        $response = $this->post('/erkap/performance-scorecards', [
            'erkap_rkap_id' => $rkap->id,
            'kpi' => 'Revenus Growth',
            'target' => 20,
            'realization' => 15,
        ]);
        $response->assertStatus(302);
    }

    // ==================== PHASE 7: AUDIT TRAIL & VALIDATION ====================

    public function test_phase7_audit_log_is_created()
    {
        $this->actingAs($this->ppk);
        $rkap = $this->createRkap();

        $this->assertDatabaseHas('erkap_audit_logs', [
            'auditable_type' => RKAP::class,
            'auditable_id' => $rkap->id,
        ]);
    }

    public function test_phase7_non_approver_cannot_approve()
    {
        $this->actingAs($this->ppk);
        $rkap = $this->createRkap();
        $chain = $this->createFullChain($rkap);

        $this->actingAs($this->ppk);
        $response = $this->post("/erkap/risk-identifications/{$chain['risk']->id}/submit");
        $response->assertStatus(302);

        $this->actingAs($this->costOwner);
        $response = $this->post("/erkap/approvals/risk_register/{$chain['risk']->id}/approve", [
            'notes' => 'Should fail',
        ]);
        $response->assertStatus(302);

        $chain['risk']->refresh();
        $this->assertEquals('submitted', $chain['risk']->status);
    }

    public function test_phase7_coa_cascade_works()
    {
        $this->actingAs($this->costOwner);

        $response = $this->get('/erkap/coa-options/business-units');
        $response->assertStatus(200);

        $response = $this->get('/erkap/coa-options/locations');
        $response->assertStatus(200);

        $response = $this->get('/erkap/coa-options/management-areas');
        $response->assertStatus(200);

        $response = $this->get('/erkap/coa-options/activities');
        $response->assertStatus(200);
    }
}
