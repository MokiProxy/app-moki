<?php

namespace Tests\Feature\Erkap;

use App\Models\ChartOfAccount;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Erkap\CompanyTarget;
use App\Models\Erkap\CostCenter;
use App\Models\Erkap\CostElement;
use App\Models\Erkap\CostElementCategory;
use App\Models\Erkap\DepartmentRiskStrategy;
use App\Models\Erkap\DepartmentTarget;
use App\Models\Erkap\ExpensePlan;
use App\Models\Erkap\InvestationCriteria;
use App\Models\Erkap\InvestationType;
use App\Models\Erkap\InvestattionCategory;
use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\RatingCriteria;
use App\Models\Erkap\RevenuePlan;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RiskTaxonomy;
use App\Models\Erkap\RiskType;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\RoutineCost;
use App\Models\Erkap\WorkProgram;
use App\Models\Regional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ActsAsErkapRole;
use Tests\Concerns\ActsAsSuperAdmin;
use Tests\TestCase;

/**
 * F7 — integritas Chain of Custody pada form transaksi.
 *
 * Prinsip yang diuji: COA adalah hasil komposisi Pusat Biaya (a..d) dengan
 * Elemen Biaya (e), dan setiap baris transaksi harus menunjuk COA yang benar-benar
 * milik Pusat Biaya yang dipilih. Sebelum F7, keempat form memakai dropdown datar
 * sehingga COA milik Pusat Biaya lain bisa tersimpan tanpa ada yang menolak.
 */
class TransactionCoaIntegrityTest extends TestCase
{
    use ActsAsErkapRole, ActsAsSuperAdmin, RefreshDatabase;

    private Division $divisionA;

    private Division $divisionB;

    private RKAP $rkap;

    private WorkProgram $workProgram;

    private CostCenter $costCenterA;

    private CostCenter $costCenterB;

    private CostElement $expenseElement;

    private CostElement $revenueElement;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSuperAdmin();

        $this->divisionA = Division::factory()->create(['name' => 'Divisi A']);
        $this->divisionB = Division::factory()->create(['name' => 'Divisi B']);

        $this->costCenterA = CostCenter::factory()->create(['division_id' => $this->divisionA->id]);
        $this->costCenterB = CostCenter::factory()->create(['division_id' => $this->divisionB->id]);

        $category = CostElementCategory::factory()->create();
        $this->expenseElement = CostElement::factory()->create([
            'code' => '5001',
            'erkap_cost_element_category_id' => $category->id,
        ]);
        $this->revenueElement = CostElement::factory()->create([
            'code' => '6001',
            'erkap_cost_element_category_id' => $category->id,
        ]);

        $this->rkap = RKAP::factory()->create(['year' => 2026]);
        $rating = RatingCriteria::factory()->create(['rating' => 'A']);

        $risk = RiskIdentification::factory()->create([
            'erkap_department_target_id' => DepartmentTarget::factory()->create([
                'division_id' => $this->divisionA->id,
                'erkap_rating_criteria_id' => $rating->id,
                'erkap_company_target_id' => CompanyTarget::factory()->create([
                    'erkap_rkap_id' => $this->rkap->id,
                ])->id,
            ])->id,
            'erkap_risk_type_id' => RiskType::factory()->create()->id,
            'erkap_risk_taxonomy_id' => RiskTaxonomy::factory()->create()->id,
        ]);

        // `WorkProgram::creating` menolak risiko yang belum punya strategi
        // mitigasi, jadi strateginya harus lebih dulu dari WorkProgram.
        DepartmentRiskStrategy::create([
            'erkap_risk_identification_id' => $risk->id,
            'strategy' => 'reduction',
        ]);

        $this->workProgram = WorkProgram::create([
            'erkap_risk_identification_id' => $risk->id,
            'code' => 'WP-F7',
            'name' => 'Program F7',
            'units' => 'Unit',
            'year_plan' => 100,
            'jan_plan' => 8,
            'feb_plan' => 8,
            'mar_plan' => 8,
            'apr_plan' => 8,
            'may_plan' => 8,
            'jun_plan' => 8,
            'jul_plan' => 8,
            'aug_plan' => 8,
            'sep_plan' => 8,
            'oct_plan' => 8,
            'nov_plan' => 8,
            'dec_plan' => 12,
            'status' => 'draft',
        ]);

        // Dua COA per elemen: satu milik Pusat Biaya divisi A, satu milik divisi B.
        // `chart_of_accounts_composition_unique` melarang duplikat pasangan, jadi
        // test selanjutnya cukup memakai `coaFor()`.
        ChartOfAccount::factory()->composed($this->costCenterA, $this->expenseElement)->create();
        ChartOfAccount::factory()->composed($this->costCenterB, $this->expenseElement)->create();
        ChartOfAccount::factory()->composed($this->costCenterA, $this->revenueElement)->revenue()->create();
        ChartOfAccount::factory()->composed($this->costCenterB, $this->revenueElement)->revenue()->create();
    }

    /* ---------------------------------------------------------------------
     | Rencana Investasi — Pusat Biaya → COA
     | ------------------------------------------------------------------ */

    private function investmentPayload(array $overrides = []): array
    {
        return array_merge([
            'erkap_work_program_id' => $this->workProgram->id,
            'erkap_investattion_category_id' => InvestattionCategory::factory()->create()->id,
            'erkap_investation_type_id' => InvestationType::factory()->create()->id,
            'erkap_investation_criteria_id' => InvestationCriteria::factory()->create()->id,
            'name' => 'Rencana Uji F7',
            'unit' => 'Unit',
            'qty' => 2,
            'unit_price' => 5000,
            'jan_plan' => 10000,
            'feb_plan' => 0,
            'mar_plan' => 0,
            'apr_plan' => 0,
            'may_plan' => 0,
            'jun_plan' => 0,
            'jul_plan' => 0,
            'aug_plan' => 0,
            'sep_plan' => 0,
            'oct_plan' => 0,
            'nov_plan' => 0,
            'dec_plan' => 0,
            'total' => 10000,
        ], $overrides);
    }

    /**
     * Satu pasangan Pusat Biaya + Elemen Biaya hanya boleh punya satu COA, jadi
     * test memakai COA yang sudah disiapkan `setUp()` alih-alih membuat duplikat.
     */
    private function coaFor(CostCenter $costCenter, CostElement $element): ChartOfAccount
    {
        return ChartOfAccount::query()->forPair($costCenter->id, $element->id)->firstOrFail();
    }

    public function test_investment_plan_accepts_a_coa_belonging_to_the_chosen_cost_center(): void
    {
        $coa = $this->coaFor($this->costCenterA, $this->expenseElement);

        $this->post(route('erkap.investment-plans.store'), $this->investmentPayload([
            'cost_center_id' => $this->costCenterA->id,
            'chart_of_account_id' => $coa->id,
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('erkap_investment_plans', [
            'cost_center_id' => $this->costCenterA->id,
            'chart_of_account_id' => $coa->id,
        ]);
    }

    public function test_investment_plan_rejects_a_coa_from_another_cost_center(): void
    {
        $foreign = $this->coaFor($this->costCenterB, $this->expenseElement);

        $this->post(route('erkap.investment-plans.store'), $this->investmentPayload([
            'cost_center_id' => $this->costCenterA->id,
            'chart_of_account_id' => $foreign->id,
        ]))->assertSessionHasErrors('chart_of_account_id');

        $this->assertDatabaseCount('erkap_investment_plans', 0);
    }

    public function test_investment_plan_rejects_a_revenue_account(): void
    {
        $revenue = $this->coaFor($this->costCenterA, $this->revenueElement);

        $this->post(route('erkap.investment-plans.store'), $this->investmentPayload([
            'cost_center_id' => $this->costCenterA->id,
            'chart_of_account_id' => $revenue->id,
        ]))->assertSessionHasErrors('chart_of_account_id');

        $this->assertDatabaseCount('erkap_investment_plans', 0);
    }

    public function test_investment_plan_rejects_an_account_without_a_cost_center(): void
    {
        $coa = $this->coaFor($this->costCenterA, $this->expenseElement);

        // Pusat Biaya sengaja dikosongkan: COA hasil komposisi tidak boleh
        // menggantung tanpa Pusat Biaya pemiliknya, walau field-nya opsional
        // untuk rencana yang memang tidak terpusat pada Pusat Biaya.
        $this->post(route('erkap.investment-plans.store'), $this->investmentPayload([
            'cost_center_id' => null,
            'chart_of_account_id' => $coa->id,
        ]))->assertSessionHasErrors('chart_of_account_id');

        $this->assertDatabaseCount('erkap_investment_plans', 0);
    }

    public function test_investment_plan_rejects_a_cost_center_without_an_account(): void
    {
        $this->post(route('erkap.investment-plans.store'), $this->investmentPayload([
            'cost_center_id' => $this->costCenterA->id,
            'chart_of_account_id' => null,
        ]))->assertSessionHasErrors('chart_of_account_id');

        $this->assertDatabaseCount('erkap_investment_plans', 0);
    }

    public function test_investment_plan_stays_valid_without_a_cost_center(): void
    {
        // Rencana investasi tingkat perusahaan tidak wajib punya Pusat Biaya.
        $this->post(route('erkap.investment-plans.store'), $this->investmentPayload([
            'cost_center_id' => null,
            'chart_of_account_id' => null,
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('erkap_investment_plans', [
            'erkap_work_program_id' => $this->workProgram->id,
            'cost_center_id' => null,
        ]);
    }

    /* ---------------------------------------------------------------------
     | Rencana Pendapatan & Beban — Divisi → COA
     | ------------------------------------------------------------------ */

    private function planPayload(array $overrides = []): array
    {
        return array_merge([
            'erkap_rkap_id' => $this->rkap->id,
            'division_id' => $this->divisionA->id,
            'description' => 'Baris uji F7',
            'jan_plan' => 100,
            'feb_plan' => 0,
            'mar_plan' => 0,
            'apr_plan' => 0,
            'may_plan' => 0,
            'jun_plan' => 0,
            'jul_plan' => 0,
            'aug_plan' => 0,
            'sep_plan' => 0,
            'oct_plan' => 0,
            'nov_plan' => 0,
            'dec_plan' => 0,
            'total' => 100,
        ], $overrides);
    }

    public function test_revenue_plan_accepts_a_coa_from_the_chosen_division(): void
    {
        $coa = $this->coaFor($this->costCenterA, $this->revenueElement);

        $this->post(route('erkap.revenue-plans.store'), $this->planPayload([
            'chart_of_account_id' => $coa->id,
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('erkap_revenue_plans', [
            'division_id' => $this->divisionA->id,
            'chart_of_account_id' => $coa->id,
        ]);
    }

    public function test_revenue_plan_rejects_a_coa_from_another_division(): void
    {
        $foreign = $this->coaFor($this->costCenterB, $this->revenueElement);

        $this->post(route('erkap.revenue-plans.store'), $this->planPayload([
            'chart_of_account_id' => $foreign->id,
        ]))->assertSessionHasErrors('chart_of_account_id');

        $this->assertDatabaseCount('erkap_revenue_plans', 0);
    }

    public function test_expense_plan_rejects_a_coa_from_another_division(): void
    {
        $foreign = $this->coaFor($this->costCenterB, $this->expenseElement);

        $this->post(route('erkap.expense-plans.store'), $this->planPayload([
            'chart_of_account_id' => $foreign->id,
        ]))->assertSessionHasErrors('chart_of_account_id');

        $this->assertDatabaseCount('erkap_expense_plans', 0);
    }

    public function test_expense_plan_accepts_a_coa_from_the_chosen_division(): void
    {
        $coa = $this->coaFor($this->costCenterA, $this->expenseElement);

        $this->post(route('erkap.expense-plans.store'), $this->planPayload([
            'chart_of_account_id' => $coa->id,
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('erkap_expense_plans', [
            'division_id' => $this->divisionA->id,
            'chart_of_account_id' => $coa->id,
        ]);
    }

    public function test_expense_plan_rejects_a_revenue_account(): void
    {
        $revenue = $this->coaFor($this->costCenterA, $this->revenueElement);

        $this->post(route('erkap.expense-plans.store'), $this->planPayload([
            'chart_of_account_id' => $revenue->id,
        ]))->assertSessionHasErrors('chart_of_account_id');
    }

    public function test_updating_a_revenue_plan_to_another_division_revalidates_the_account(): void
    {
        $plan = RevenuePlan::create([
            'erkap_rkap_id' => $this->rkap->id,
            'division_id' => $this->divisionA->id,
            'chart_of_account_id' => $this->coaFor($this->costCenterA, $this->revenueElement)->id,
            'description' => 'Pendapatan awal',
            'jan_plan' => 100,
            'total' => 100,
        ]);

        $foreign = $this->coaFor($this->costCenterB, $this->revenueElement);

        $this->put(route('erkap.revenue-plans.update', $plan), $this->planPayload([
            'chart_of_account_id' => $foreign->id,
        ]))->assertSessionHasErrors('chart_of_account_id');

        $this->assertDatabaseHas('erkap_revenue_plans', [
            'id' => $plan->id,
            'division_id' => $this->divisionA->id,
        ]);
    }

    /* ---------------------------------------------------------------------
     | API opsi cascade
     | ------------------------------------------------------------------ */

    public function test_accounts_endpoint_filters_by_division_and_type(): void
    {
        $response = $this->getJson(route('erkap.coa-options.accounts', [
            'division_id' => $this->divisionA->id,
            'type' => 'revenue',
        ]));

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id');
        $expected = ChartOfAccount::query()
            ->where('type', 'revenue')
            ->whereHas('costCenter', fn ($query) => $query->where('division_id', $this->divisionA->id))
            ->pluck('id');

        $this->assertNotEmpty($expected);
        $this->assertEqualsCanonicalizing($expected->sort()->values()->all(), $ids->sort()->values()->all());
    }

    public function test_divisions_endpoint_lists_divisions(): void
    {
        $response = $this->getJson(route('erkap.coa-options.divisions'));

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($this->divisionA->id));
        $this->assertTrue($ids->contains($this->divisionB->id));
    }

    public function test_accounts_endpoint_still_filters_by_cost_center_for_transaction_forms(): void
    {
        $response = $this->getJson(route('erkap.coa-options.accounts', [
            'cost_center_id' => $this->costCenterA->id,
            'type' => 'expense',
        ]));

        $response->assertOk();

        $rows = collect($response->json('data'));

        $this->assertCount(1, $rows);
        $this->assertSame($this->costCenterA->id, $rows->first()['cost_center_id']);
    }

    /* ---------------------------------------------------------------------
     | Cakupan divisi untuk pengguna berscope
     | ------------------------------------------------------------------ */

    public function test_accounts_endpoint_ignores_a_division_id_from_another_division(): void
    {
        $this->actAsDivisionScopedUser($this->divisionA);

        $response = $this->getJson(route('erkap.coa-options.accounts', [
            'division_id' => $this->divisionB->id,
            'type' => 'expense',
        ]));

        $response->assertOk();

        // Paramater divisi lain diabaikan, hasilnya tetap cakupan sendiri —
        // bukan error, karena dropdown milik user tersebut memang hanya berisi
        // COA divisinya sendiri.
        $ids = collect($response->json('data'))->pluck('cost_center_id');

        $this->assertNotContains($this->costCenterB->id, $ids);
        $this->assertNotEmpty($ids);
    }

    public function test_accounts_endpoint_still_returns_own_division(): void
    {
        $this->actAsDivisionScopedUser($this->divisionA);

        $response = $this->getJson(route('erkap.coa-options.accounts', [
            'type' => 'expense',
        ]));

        $response->assertOk();

        $rows = collect($response->json('data'));

        $this->assertCount(1, $rows);
        $this->assertSame($this->costCenterA->id, $rows->first()['cost_center_id']);
    }

    public function test_divisions_endpoint_only_lists_the_own_division(): void
    {
        $this->actAsDivisionScopedUser($this->divisionA);

        $response = $this->getJson(route('erkap.coa-options.divisions'));

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($this->divisionA->id));
        $this->assertFalse($ids->contains($this->divisionB->id));
    }

    public function test_cost_centers_endpoint_ignores_a_foreign_division_filter(): void
    {
        $this->actAsDivisionScopedUser($this->divisionA);

        $response = $this->getJson(route('erkap.coa-options.cost-centers', [
            'division_id' => $this->divisionB->id,
        ]));

        $response->assertOk();

        $rows = collect($response->json('data'));

        $this->assertCount(1, $rows);
        $this->assertSame($this->costCenterA->id, $rows->first()['id']);
    }

    public function test_cost_elements_endpoint_rejects_a_cost_center_of_another_division(): void
    {
        $this->actAsDivisionScopedUser($this->divisionA);

        $this->getJson(route('erkap.coa-options.cost-elements', [
            'cost_center_id' => $this->costCenterB->id,
        ]))->assertForbidden();
    }

    private function actAsDivisionScopedUser(Division $division): void
    {
        $regional = Regional::factory()->create();

        $employee = Employee::create([
            'employee_id' => 'EMP-COA-'.uniqid(),
            'name' => 'Pemilik Biaya',
            'division_id' => $division->id,
            'regional_id' => $regional->id,
        ]);

        // `ErkapAccess` membaca divisi dari `Auth::user()->employee`, jadi user
        // yang login harus yang tertaut employee — bukan user bawaan trait.
        $this->actAsErkapRole('erkap-cost-owner');

        $owner = User::factory()->create(['employee_id' => $employee->employee_id]);
        $owner->assignRole('erkap-cost-owner');
        $this->actingAs($owner);
    }

    /* ---------------------------------------------------------------------
     | Halaman form
     | ------------------------------------------------------------------ */

    public function test_transaction_forms_render_the_cascade(): void
    {
        $this->get(route('erkap.routine-costs.create'))->assertOk()->assertSee('ErkapCascade.init', false);
        $this->get(route('erkap.investment-plans.create'))->assertOk()->assertSee('ErkapCascade.init', false);
        $this->get(route('erkap.revenue-plans.create'))->assertOk()->assertSee('ErkapCascade.init', false);
        $this->get(route('erkap.expense-plans.create'))->assertOk()->assertSee('ErkapCascade.init', false);
    }

    public function test_routine_cost_form_keeps_the_old_swakelola_split_selects_gone(): void
    {
        $response = $this->get(route('erkap.routine-costs.create'));

        $response->assertOk();
        $response->assertDontSee('cost_center_swakelola', false);
        $response->assertDontSee('cost_center_non_swakelola', false);
    }

    public function test_routine_cost_form_does_not_send_a_finished_account_id(): void
    {
        $response = $this->get(route('erkap.routine-costs.create'));

        $response->assertOk();
        $response->assertDontSee('name="chart_of_account_id"', false);
        $response->assertDontSee('name="code"', false);
    }

    public function test_expense_plan_page_renders(): void
    {
        $this->get(route('erkap.expense-plans.index'))->assertOk();
        $this->assertDatabaseCount('erkap_expense_plans', 0);
    }

    public function test_investment_plan_edit_page_renders_for_an_existing_row(): void
    {
        $plan = InvestmentPlan::factory()->create([
            'erkap_work_program_id' => $this->workProgram->id,
            'cost_center_id' => $this->costCenterA->id,
        ]);

        $this->get(route('erkap.investment-plans.edit', $plan))->assertOk();
    }

    public function test_revenue_plan_edit_page_renders_for_an_existing_row(): void
    {
        $plan = RevenuePlan::create([
            'erkap_rkap_id' => $this->rkap->id,
            'division_id' => $this->divisionA->id,
            'chart_of_account_id' => $this->coaFor($this->costCenterA, $this->revenueElement)->id,
            'description' => 'Pendapatan awal',
            'jan_plan' => 100,
            'total' => 100,
        ]);

        $this->get(route('erkap.revenue-plans.edit', $plan))
            ->assertOk()
            ->assertSee('ErkapCascade.init', false);
    }

    public function test_expense_plan_edit_page_renders_for_an_existing_row(): void
    {
        $plan = ExpensePlan::create([
            'erkap_rkap_id' => $this->rkap->id,
            'division_id' => $this->divisionA->id,
            'chart_of_account_id' => $this->coaFor($this->costCenterA, $this->expenseElement)->id,
            'description' => 'Beban awal',
            'jan_plan' => 100,
            'total' => 100,
        ]);

        $this->get(route('erkap.expense-plans.edit', $plan))
            ->assertOk()
            ->assertSee('ErkapCascade.init', false);
    }

    /* ---------------------------------------------------------------------
     | Pemulihan nilai tersimpan pada form edit
     | ------------------------------------------------------------------ */

    /**
     * Select anak dirender kosong lalu diisi lewat API, jadi nilai tersimpan
     * harus diteruskan lewat `initialValues`. Tanpa itu, buka form edit akan
     * menampilkan dropdown kosong walau baris datanya punya COA.
     */
    public function test_forms_restore_the_stored_account_on_edit(): void
    {
        $coaId = $this->coaFor($this->costCenterA, $this->expenseElement)->id;

        $revenue = RevenuePlan::create([
            'erkap_rkap_id' => $this->rkap->id,
            'division_id' => $this->divisionA->id,
            'chart_of_account_id' => $this->coaFor($this->costCenterA, $this->revenueElement)->id,
            'description' => 'Pendapatan awal',
            'jan_plan' => 100,
            'total' => 100,
        ]);

        $this->get(route('erkap.revenue-plans.edit', $revenue))
            ->assertOk()
            ->assertSee('account: '.$this->coaFor($this->costCenterA, $this->revenueElement)->id, false);

        $expense = ExpensePlan::create([
            'erkap_rkap_id' => $this->rkap->id,
            'division_id' => $this->divisionA->id,
            'chart_of_account_id' => $coaId,
            'description' => 'Beban awal',
            'jan_plan' => 100,
            'total' => 100,
        ]);

        $this->get(route('erkap.expense-plans.edit', $expense))
            ->assertOk()
            ->assertSee('account: '.$coaId, false);

        $investment = InvestmentPlan::factory()->create([
            'erkap_work_program_id' => $this->workProgram->id,
            'cost_center_id' => $this->costCenterA->id,
            'chart_of_account_id' => $coaId,
        ]);

        $this->get(route('erkap.investment-plans.edit', $investment))
            ->assertOk()
            ->assertSee('account: '.$coaId, false);
    }

    public function test_routine_cost_edit_restores_the_stored_element(): void
    {
        $routine = RoutineCost::create([
            'erkap_work_program_id' => $this->workProgram->id,
            'need' => 'Kebutuhan uji F7',
            'cost_center_id' => $this->costCenterA->id,
            'cost_center_owner' => 'Pemilik',
            'qty' => 1,
            'units' => 'Unit',
            'unit_price' => 1000,
            'erkap_cost_element_id' => $this->expenseElement->id,
            'is_kumulatif' => true,
            'jan_plan' => 1000,
            'total' => 1000,
        ]);

        $this->get(route('erkap.routine-costs.edit', $routine))
            ->assertOk()
            ->assertSee('cost_element: '.$this->expenseElement->id, false);
    }
}
