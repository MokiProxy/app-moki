<?php

namespace Tests\Feature\Simka;

use App\Models\ChartOfAccount;
use App\Models\Division;
use App\Models\Erkap\BudgetCapex;
use App\Models\Erkap\BudgetOpex;
use App\Models\Erkap\BudgetRealization;
use App\Models\Erkap\CostCenter;
use App\Models\Erkap\ExpensePlan;
use App\Models\Erkap\RevenuePlan;
use App\Models\Erkap\RKAP;
use App\Models\User;
use App\Services\Simka\DashboardSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DashboardSummaryTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(array $permissions): User
    {
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $user = User::factory()->create();
        $user->givePermissionTo($permissions);
        $this->actingAs($user);

        return $user;
    }

    /**
     * @return array{0: RKAP, 1: Division}
     */
    private function seedPlanData(int $year = 2026): array
    {
        $rkap = RKAP::factory()->create(['year' => $year]);
        $division = Division::factory()->create();
        $coa = ChartOfAccount::factory()->create();
        $costCenter = CostCenter::factory()->create(['division_id' => $division->id]);

        $months = [
            'jan_plan' => 100000,
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
        ];

        RevenuePlan::create($months + [
            'erkap_rkap_id' => $rkap->id,
            'division_id' => $division->id,
            'chart_of_account_id' => $coa->id,
            'total' => 1000000,
            'status' => 'draft',
        ]);

        ExpensePlan::create([
            'jan_plan' => 40000,
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
            'erkap_rkap_id' => $rkap->id,
            'division_id' => $division->id,
            'chart_of_account_id' => $coa->id,
            'total' => 400000,
            'status' => 'draft',
        ]);

        BudgetOpex::factory()->create([
            'erkap_rkap_id' => $rkap->id,
            'division_id' => $division->id,
            'cost_center_id' => $costCenter->id,
            'chart_of_account_id' => $coa->id,
            'budget_amount' => 300000,
            'realization_amount' => 0,
            'variance' => 0,
        ]);

        BudgetCapex::create([
            'erkap_rkap_id' => $rkap->id,
            'division_id' => $division->id,
            'total_investment' => 200000,
            'status' => 'draft',
        ]);

        BudgetRealization::factory()->create([
            'erkap_rkap_id' => $rkap->id,
            'month' => 1,
            'year' => $year,
            'budgeted' => 500000,
            'realized' => 150000,
            'variance' => -350000,
            'variance_percent' => -70,
        ]);

        return [$rkap, $division];
    }

    public function test_it_sums_revenue_and_expense_for_the_selected_rkap(): void
    {
        $this->actingUser(['simka.menu', 'erkap.menu']);

        [$rkap] = $this->seedPlanData();

        $summary = app(DashboardSummaryService::class)->build($rkap);

        $this->assertSame(1000000.0, $summary['revenue_plan']);
        $this->assertSame(400000.0, $summary['expense_plan']);
        $this->assertSame(300000.0, $summary['opex_budget']);
        $this->assertSame(200000.0, $summary['capex_budget']);
        $this->assertSame(500000.0, $summary['expense_budget']);
        $this->assertSame(500000.0, $summary['expense_total']);
        $this->assertSame(150000.0, $summary['realized']);
        $this->assertSame(500000.0, $summary['balance']);
        $this->assertSame(30.0, $summary['absorption']);
        $this->assertSame(100000.0, $summary['monthly'][0]['revenue']);
        $this->assertSame(40000.0, $summary['monthly'][0]['expense']);
    }

    public function test_it_defaults_to_the_latest_rkap(): void
    {
        $this->actingUser(['simka.menu', 'erkap.menu']);

        $this->seedPlanData(2025);
        [$latest] = $this->seedPlanData(2026);

        $summary = app(DashboardSummaryService::class)->build();

        $this->assertSame($latest->id, $summary['rkap']?->id);
        $this->assertSame(1000000.0, $summary['revenue_plan']);
    }

    public function test_dashboard_renders_the_combined_kpi_cards(): void
    {
        $this->actingUser(['simka.menu', 'erkap.menu']);

        [$rkap] = $this->seedPlanData();

        $this->get(route('simka.index', ['rkap_id' => $rkap->id]))
            ->assertOk()
            ->assertSee('Pendapatan (Rencana)')
            ->assertSee('Pengeluaran (Opex + Capex)')
            ->assertSee('Anggaran Opex + Capex')
            ->assertSee('Realisasi Pengeluaran')
            ->assertSee('Rp 1,00 Jt')
            ->assertSee('Rp 400.000')
            ->assertSee('Selisih pendapatan vs pengeluaran')
            ->assertSee('30,00% dari anggaran');
    }

    public function test_combined_report_preview_includes_simka_and_erkap_metrics(): void
    {
        $this->actingUser(['simka.menu', 'erkap.menu', 'erkap.reports.view']);

        [$rkap] = $this->seedPlanData();

        $this->get(route('erkap.reports.preview', [
            'type' => 'combined',
            'rkap_id' => $rkap->id,
            'year' => 2026,
        ]))
            ->assertOk()
            ->assertSee('Laporan Gabungan SIMKA')
            ->assertSee('Pendapatan (SIMKA) vs Pengeluaran (E-RKAP)')
            ->assertSee('Baris Tarif SPPH')
            ->assertSee('Rencana Pendapatan per Akun (CoA)')
            ->assertSee('1.000.000');
    }

    public function test_combined_report_can_be_filtered_to_the_revenue_domain(): void
    {
        $this->actingUser(['simka.menu', 'erkap.menu', 'erkap.reports.view']);

        [$rkap] = $this->seedPlanData();

        $this->get(route('erkap.reports.preview', [
            'type' => 'combined',
            'domain' => 'revenue',
            'rkap_id' => $rkap->id,
            'year' => 2026,
        ]))
            ->assertOk()
            ->assertSee('Laporan Pendapatan SIMKA')
            ->assertSee('Baris Tarif SPPH')
            ->assertDontSee('Anggaran Capex');
    }

    public function test_combined_report_can_be_filtered_to_the_expense_domain(): void
    {
        $this->actingUser(['simka.menu', 'erkap.menu', 'erkap.reports.view']);

        [$rkap] = $this->seedPlanData();

        $this->get(route('erkap.reports.preview', [
            'type' => 'combined',
            'domain' => 'expense',
            'rkap_id' => $rkap->id,
            'year' => 2026,
        ]))
            ->assertOk()
            ->assertSee('Laporan Pengeluaran E-RKAP')
            ->assertSee('Anggaran Capex')
            ->assertDontSee('Baris Tarif SPPH');
    }

    public function test_dashboard_shows_a_hint_when_no_rkap_exists(): void
    {
        $this->actingUser(['simka.menu']);

        $this->get(route('simka.index'))
            ->assertOk()
            ->assertSee('Belum ada periode RKAP');
    }
}
