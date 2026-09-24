<?php

namespace Tests\Feature\Erkap;

use App\Models\Division;
use App\Models\Erkap\Approval;
use App\Models\Erkap\AuditLog;
use App\Models\Erkap\BudgetCapex;
use App\Models\Erkap\BudgetOpex;
use App\Models\Erkap\BudgetRealization;
use App\Models\Erkap\CompanyTarget;
use App\Models\Erkap\DepartmentRiskStrategy;
use App\Models\Erkap\DepartmentTarget;
use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\InvestmentStageGate;
use App\Models\Erkap\KickoffAttendee;
use App\Models\Erkap\PerformanceScorecard;
use App\Models\Erkap\ProgramRealization;
use App\Models\Erkap\RiskAssessmentMonthly;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RiskTreatment;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\RoutineCost;
use App\Models\Erkap\WorkProgram;
use App\Models\Erkap\ZBBReview;
use Database\Seeders\Erkap\Support\RkapSimulasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ErkapRkapSimulasiSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_seeder_mencapai_end_state_lengkap(): void
    {
        RkapSimulasi::run('2027', 'SIMULASI-MS');

        $rkap = RKAP::where('year', '2027')->firstOrFail();
        $division = Division::where('name', 'SIMULASI-MS')->firstOrFail();

        // End-state periode RKAP.
        $this->assertSame('approved', $rkap->status);
        $this->assertSame('archived', $rkap->phase);
        $this->assertSame('aligned', $rkap->bmi_alignment_status);
        $this->assertSame('distributed', $rkap->distribution_status);
        $this->assertNotNull($rkap->resolution_date);

        // Semua dokumen penyusun berstatus approved.
        $this->assertTrue(WorkProgram::where('status', '!=', 'approved')->doesntExist());
        $this->assertTrue(RoutineCost::where('status', '!=', 'approved')->doesntExist());
        $this->assertTrue(InvestmentPlan::where('status', '!=', 'approved')->doesntExist());
        $this->assertTrue(RiskIdentification::where('status', '!=', 'approved')->doesntExist());

        // Jumlah approval bertingkat: 2/WP, 2/RC, 4/IP, 2/RKAP, 1/risiko.
        foreach (WorkProgram::all() as $doc) {
            $this->assertSame(2, $this->approvedApprovals($doc));
        }
        foreach (RoutineCost::all() as $doc) {
            $this->assertSame(2, $this->approvedApprovals($doc));
        }
        foreach (InvestmentPlan::all() as $doc) {
            $this->assertSame(4, $this->approvedApprovals($doc));
        }
        foreach (RiskIdentification::all() as $doc) {
            $this->assertSame(1, $this->approvedApprovals($doc));
        }
        $this->assertSame(2, $this->approvedApprovals($rkap));

        // Stage gate: 5 gate per IP, semua approved.
        $this->assertSame(2, InvestmentPlan::count());
        foreach (InvestmentPlan::all() as $plan) {
            $gates = $plan->stageGates()->get();
            $this->assertCount(5, $gates);
            $this->assertTrue($gates->every(fn ($gate) => $gate->status === 'approved'));
        }

        // ZBB terisi dan tidak memblokir konsolidasi.
        $zbb = ZBBReview::where('erkap_rkap_id', $rkap->id)->get();
        $this->assertNotEmpty($zbb);
        $this->assertTrue($zbb->every(fn ($review) => ! $review->blocksConsolidation()));

        // Konsolidasi OPEX & CAPEX terisi untuk divisi target.
        $this->assertTrue(BudgetOpex::where('division_id', $division->id)->exists());
        $this->assertSame(round(InvestmentPlan::sum('total'), 2), (float) BudgetCapex::where('division_id', $division->id)->firstOrFail()->total_investment);

        // Realisasi tahap 7: 6 bulan x (3 RC + 2 IP) = 30; 2 program x 6; 2 risiko x 6; 2 kuarter x 2 KPI.
        $this->assertSame(30, BudgetRealization::where('erkap_rkap_id', $rkap->id)->count());
        $this->assertSame(12, ProgramRealization::where('year', 2027)->count());
        $this->assertSame(12, RiskAssessmentMonthly::where('year', 2027)->count());
        $this->assertSame(4, PerformanceScorecard::where('erkap_rkap_id', $rkap->id)->count());

        // Rantai entitas lengkap.
        $this->assertTrue(CompanyTarget::where('erkap_rkap_id', $rkap->id)->exists());
        $this->assertTrue(DepartmentTarget::where('division_id', $division->id)->exists());
        $this->assertTrue(DepartmentRiskStrategy::count() >= 1);
        $this->assertTrue(RiskTreatment::count() >= 1);

        // Semua data ber-pengisi: created_by/updated_by terisi user yang tepat.
        $this->assertSemuaDataBerPengisi();
    }

    public function test_audit_trail_mencatat_user_pengisi(): void
    {
        RkapSimulasi::run('2027', 'SIMULASI-MS');

        $filler = \App\Models\User::where('email', 'cost-owner@simulasi-ms.local')->firstOrFail();
        $ppk = \App\Models\User::where('email', 'ppk@simulasi-ms.local')->firstOrFail();

        $wp = WorkProgram::firstOrFail();
        $rc = RoutineCost::firstOrFail();
        $ip = InvestmentPlan::firstOrFail();
        $risk = RiskIdentification::firstOrFail();

        $this->assertSame($filler->id, $wp->created_by);
        $this->assertSame($filler->id, $rc->created_by);
        $this->assertSame($filler->id, $ip->created_by);
        $this->assertSame($filler->id, $risk->created_by);
        $this->assertNotNull($wp->updated_by);

        $this->assertSame($ppk->id, RKAP::where('year', 2027)->firstOrFail()->created_by);
        $this->assertSame($ppk->id, $wp->approvals()->where('role', 'erkap-ppk')->firstOrFail()->approver_id);
        $this->assertSame($filler->id, \App\Models\Erkap\CompanyTarget::where('erkap_rkap_id', RKAP::where('year', 2027)->firstOrFail()->id)->firstOrFail()->created_by);

        $this->assertTrue(KickoffAttendee::where('created_by', $ppk->id)->exists());
        $this->assertTrue(AuditLog::where('user_id', $filler->id)->where('action', 'create')->exists());
        $this->assertTrue(AuditLog::where('user_id', $ppk->id)->exists());
        $this->assertTrue(AuditLog::whereNotNull('user_id')->count() > 0);
    }

    public function test_seeder_idempoten(): void
    {
        RkapSimulasi::run('2027', 'SIMULASI-MS');

        $before = [
            'rkaps' => RKAP::count(),
            'wps' => WorkProgram::count(),
            'rcs' => RoutineCost::count(),
            'ips' => InvestmentPlan::count(),
            'risks' => RiskIdentification::count(),
            'approvals' => Approval::count(),
            'gates' => InvestmentStageGate::count(),
            'zbb' => ZBBReview::count(),
            'opex' => BudgetOpex::count(),
            'capex' => BudgetCapex::count(),
            'realization' => BudgetRealization::count(),
        ];

        RkapSimulasi::run('2027', 'SIMULASI-MS');

        $after = [
            'rkaps' => RKAP::count(),
            'wps' => WorkProgram::count(),
            'rcs' => RoutineCost::count(),
            'ips' => InvestmentPlan::count(),
            'risks' => RiskIdentification::count(),
            'approvals' => Approval::count(),
            'gates' => InvestmentStageGate::count(),
            'zbb' => ZBBReview::count(),
            'opex' => BudgetOpex::count(),
            'capex' => BudgetCapex::count(),
            'realization' => BudgetRealization::count(),
        ];

        $this->assertSame($before, $after);
    }

    private function approvedApprovals($doc): int
    {
        return $doc->approvals()->where('status', 'approved')->count();
    }

    private function assertSemuaDataBerPengisi(): void
    {
        $fillerId = \App\Models\User::where('email', 'cost-owner@simulasi-ms.local')->firstOrFail()->id;
        $ppkId = \App\Models\User::where('email', 'ppk@simulasi-ms.local')->firstOrFail()->id;
        $allowed = [$fillerId, $ppkId];

        $audited = [
            \App\Models\Erkap\CompanyTarget::class,
            \App\Models\Erkap\DepartmentTarget::class,
            WorkProgram::class,
            RoutineCost::class,
            InvestmentPlan::class,
            RiskIdentification::class,
            \App\Models\Erkap\BudgetRealization::class,
            \App\Models\Erkap\ProgramRealization::class,
            \App\Models\Erkap\RiskAssessmentMonthly::class,
            \App\Models\Erkap\PerformanceScorecard::class,
        ];

        foreach ($audited as $model) {
            $rows = $model::query()->whereNull('created_by')->count();
            $this->assertSame(0, $rows, "{$model} tidak boleh punya baris tanpa pengisi (created_by null).");
        }

        $this->assertContains(RKAP::where('year', 2027)->firstOrFail()->created_by, $allowed);
        $this->assertContains(WorkProgram::firstOrFail()->created_by, $allowed);
        $this->assertContains(RoutineCost::firstOrFail()->created_by, $allowed);
        $this->assertContains(InvestmentPlan::firstOrFail()->created_by, $allowed);
    }
}