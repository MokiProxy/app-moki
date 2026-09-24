<?php

namespace Database\Seeders\Erkap\Support;

use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Erkap\BudgetCapex;
use App\Models\Erkap\BudgetRealization;
use App\Models\Erkap\CompanyTarget;
use App\Models\Erkap\CostCenter;
use App\Models\Erkap\CostElement;
use App\Models\Erkap\CostElementCategory;
use App\Models\Erkap\DepartmentRiskStrategy;
use App\Models\Erkap\DepartmentTarget;
use App\Models\Erkap\InvestationCriteria;
use App\Models\Erkap\InvestationType;
use App\Models\Erkap\InvestattionCategory;
use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\KickoffAttendee;
use App\Models\Erkap\PerformanceScorecard;
use App\Models\Erkap\ProgramRealization;
use App\Models\Erkap\RatingCriteria;
use App\Models\Erkap\RiskAnalysis;
use App\Models\Erkap\RiskAssessmentMonthly;
use App\Models\Erkap\RiskAppetite;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RiskIdentificationImpact;
use App\Models\Erkap\RiskIdentificationReason;
use App\Models\Erkap\RiskImpact;
use App\Models\Erkap\RiskProbability;
use App\Models\Erkap\RiskRanking;
use App\Models\Erkap\RiskScoreLevel;
use App\Models\Erkap\RiskTaxonomy;
use App\Models\Erkap\RiskTreatment;
use App\Models\Erkap\RiskType;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\RoutineCost;
use App\Models\Erkap\WorkProgram;
use App\Models\Erkap\ZBBReview;
use App\Models\Regional;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\BudgetOpexConsolidationService;
use App\Services\Erkap\InvestmentGateReviewService;
use App\Services\Erkap\RKAPLifecycleService;
use App\Services\Erkap\ZBBReviewService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Helper tahapan simulasi alur RKAP satu divisi (tahap 0 - 7).
 *
 * Idempoten: seluruh data dibuat ulang lewat updateOrCreate pada kunci stabil
 * dan approval/gate dibersihkan lalu dibentuk ulang setiap kali run.
 */
class RkapSimulasi
{
    public const DEFAULT_YEAR = '2027';

    public const DEFAULT_DIVISION = 'SIMULASI-MS';

    protected static string $year;

    protected static string $divisionName;

    protected static ?Company $company = null;

    protected static ?Division $division = null;

    /** @var array<string, mixed> */
    protected static array $ref = [];

    /** @var array<string, User> */
    protected static array $users = [];

    protected static RKAP $rkap;

    /**
     * Jalankan seluruh alur dan kembalikan ringkasan untuk verifikasi.
     *
     * @return array<string, int|string>
     */
    public static function run(?string $year = null, ?string $divisionName = null): array
    {
        static::$year = (string) ($year ?: (env('ERKAP_SIM_YEAR') ?: static::DEFAULT_YEAR));
        static::$divisionName = (string) ($divisionName ?: (env('ERKAP_SIM_DIVISION') ?: static::DEFAULT_DIVISION));

        // Bypass Mailhog/SMTP: notifikasi pada ApprovalService & distribute
        // berjalan sinkron (QUEUE_CONNECTION=sync) dan tidak boleh memblokir seeder.
        $previousMail = config('mail.default');
        config(['mail.default' => 'array']);

        try {
            static::prepareReference();
            static::prepareDivision();
            static::prepareApproverUsers();

            static::fillAs('erkap-ppk', function () {
                static::initialiseRkap();
            });

            static::fillAs('erkap-cost-owner', function () {
                static::prepareTargets();
                static::prepareRisksAndPrograms();
            });

            static::submitAndApproveDocuments();
            static::runZbb();
            static::consolidateBudgets();
            static::finaliseRkap();

            static::fillAs('erkap-cost-owner', function () {
                static::seedRealization();
            });
        } finally {
            config(['mail.default' => $previousMail]);
        }

        return static::summary();
    }

    /**
     * Tahap 0a - Master data & referensi bersama (rating, risiko, biaya).
     */
    protected static function prepareReference(): void
    {
        static::$company = Company::orderBy('id')->first()
            ?? Company::create(['name' => 'PT Simulasi RKAP']);

        $regional = Regional::orderBy('id')->first()
            ?? Regional::create(['name' => 'Simulasi']);

        $rating = RatingCriteria::where('rating', 'A')->first()
            ?? RatingCriteria::firstOrCreate(
                ['rating' => 'A'],
                [
                    'qualification' => 'Cukup Kritis',
                    'description' => 'Sangat mendukung operasi perusahaan secara keseluruhan',
                ]
            );

        $riskAppetite = RiskAppetite::first()
            ?? RiskAppetite::create(['name' => 'Selera Risiko Simulasi']);

        $taxonomy = RiskTaxonomy::first() ?? RiskTaxonomy::firstOrCreate(
            ['name' => 'Risiko Operasional'],
            ['risk_appetite_id' => $riskAppetite->id]
        );

        $riskType = RiskType::first() ?? RiskType::firstOrCreate(
            ['name' => 'Risiko Proyek'],
            ['risk_taxonomy_id' => $taxonomy->id]
        );

        $probability = RiskProbability::where('point', 3)->first()
            ?? RiskProbability::first()
            ?? RiskProbability::create(['name' => 'Kemungkinan Sedang', 'point' => 3]);

        $impact = RiskImpact::where('point', 3)->first()
            ?? RiskImpact::first()
            ?? RiskImpact::create(['name' => 'Dampak Sedang', 'point' => 3]);

        $scoreLevel = RiskScoreLevel::where('score', (float) ($probability->point * $impact->point))->first()
            ?? RiskScoreLevel::first()
            ?? RiskScoreLevel::create([
                'erkap_risk_probability_id' => $probability->id,
                'erkap_risk_impact_id' => $impact->id,
                'score' => (float) ($probability->point * $impact->point),
                'level' => 'Moderate',
            ]);

        // Cost element + COA (unit biaya global; dipakai konsolidasi OPEX).
        $costElement = CostElement::whereNotNull('chart_of_account_id')->first()
            ?? CostElement::first();

        $category = CostElementCategory::first()
            ?? CostElementCategory::create(['name' => 'Biaya Umum Simulasi']);

        $coa = null;
        if ($costElement) {
            $coa = $costElement->chart_of_account_id
                ? $costElement->chartOfAccount
                : $costElement->coaSuggestion();
        }

        if (! $coa) {
            $coa = ChartOfAccount::expense()->first()
                ?? ChartOfAccount::where('type', 'expense')->first()
                ?? ChartOfAccount::create(['code' => '8000', 'name' => 'Biaya Gaji', 'type' => 'expense']);
        }

        if (! $costElement) {
            $costElement = CostElement::firstOrCreate(
                ['code' => 'SIM-8000'],
                [
                    'name' => 'Biaya Operasional SIMULASI',
                    'erkap_cost_element_category_id' => $category->id,
                    'chart_of_account_id' => $coa->id,
                ]
            );
        }

        $investationCategory = InvestattionCategory::first()
            ?? InvestattionCategory::firstOrCreate(['code' => 'SIM-CAT'], ['name' => 'Kategori Simulasi']);

        $investationType = InvestationType::first()
            ?? InvestationType::firstOrCreate(['code' => 'SIM-TYPE'], ['name' => 'Tipe Simulasi']);

        $investationCriteria = InvestationCriteria::first()
            ?? InvestationCriteria::firstOrCreate(['code' => 'SIM-CRIT'], ['name' => 'Kriteria Simulasi']);

        static::$ref = [
            'company' => static::$company,
            'regional' => $regional,
            'rating' => $rating,
            'taxonomy' => $taxonomy,
            'riskType' => $riskType,
            'probability' => $probability,
            'impact' => $impact,
            'scoreLevel' => $scoreLevel,
            'costElement' => $costElement,
            'coa' => $coa,
            'investationCategory' => $investationCategory,
            'investationType' => $investationType,
            'investationCriteria' => $investationCriteria,
        ];
    }

    /**
     * Tahap 0b - Divisi target + cost center milik divisi.
     */
    protected static function prepareDivision(): void
    {
        static::$division = Division::updateOrCreate(
            ['name' => static::$divisionName],
            [
                'code' => 'SIM-MS',
                'abbreviation' => 'SIM',
                'company_id' => static::$company->id,
                'regional_id' => static::$ref['regional']->id,
            ]
        );

        static::$ref['costCenter'] = CostCenter::firstOrCreate(
            ['code' => 'CC-SIM-01'],
            [
                'name' => 'Cost Center '.static::$divisionName,
                'owner' => 'Kepala Departemen',
                'division_id' => static::$division->id,
                'is_swakelola' => true,
            ]
        );
    }

    /**
     * Tahap 0c - User approver + employee-nya pada divisi target.
     */
    protected static function prepareApproverUsers(): void
    {
        $roles = static::approverRoles();
        $index = 0;

        foreach ($roles as $role => $meta) {
            Role::firstOrCreate(['name' => $role]);

            $index++;
            $employeeId = 'SIM-'.static::$year.'-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT);
            $email = Str::after($role, 'erkap-').'@simulasi-ms.local';

            Employee::updateOrCreate(
                ['employee_id' => $employeeId],
                [
                    'name' => $meta['name'],
                    'jabatan' => $meta['jabatan'],
                    'division_id' => static::$division->id,
                    'regional_id' => static::$ref['regional']->id,
                ]
            );

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $meta['name'],
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'employee_id' => $employeeId,
                ]
            );

            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }

            static::$users[$role] = $user;
        }

        // Diperlukan oleh RKAPLifecycleService::distribute() sebagai penerima notifikasi.
        Role::firstOrCreate(['name' => 'erkap-admin']);
    }

    /**
     * Jalankan aksi sambil "login sebagai" user tertentu agar HasAuditTrail
     * mengisi created_by/updated_by (+ AuditLog.user_id) dengan benar, lalu
     * kembalikan ke keadaan tanpa user.
     */
    protected static function fillAs(string $role, callable $callback): void
    {
        Auth::guard('web')->login(static::$users[$role]);

        try {
            $callback();
        } finally {
            Auth::guard('web')->logout();
        }
    }

    /**
     * @return array<string, array{name: string, jabatan: string}>
     */
    protected static function approverRoles(): array
    {
        return [
            'erkap-ppk' => ['name' => 'PPK '.static::$divisionName, 'jabatan' => 'Pejabat Pembuat Komitmen'],
            'erkap-controller' => ['name' => 'Controller '.static::$divisionName, 'jabatan' => 'Budget Controller'],
            'erkap-manajemen-aset' => ['name' => 'Manajemen Aset '.static::$divisionName, 'jabatan' => 'Manajer Manajemen Aset'],
            'erkap-direksi-keuangan' => ['name' => 'Direksi Keuangan', 'jabatan' => 'Direktur Keuangan'],
            'erkap-komisaris' => ['name' => 'Komisaris', 'jabatan' => 'Komisaris'],
            'erkap-direksi' => ['name' => 'Direksi', 'jabatan' => 'Direktur Utama'],
            'erkap-gate-review' => ['name' => 'Gate Review PT BMI', 'jabatan' => 'Kepala Unit Gate Review'],
            'erkap-risk-manager' => ['name' => 'Risk Manager '.static::$divisionName, 'jabatan' => 'Manajer Risiko'],
            'erkap-bmi-admin' => ['name' => 'BMI Admin', 'jabatan' => 'Administrator PT BMI'],
            'erkap-cost-owner' => ['name' => 'Cost Owner '.static::$divisionName, 'jabatan' => 'Pemilik Anggaran (Pengisi Form 1-4)'],
        ];
    }

    /**
     * Tahap 1 - Inisiasi: RKAP 2027 + kickoff + arahan direksi.
     */
    protected static function initialiseRkap(): void
    {
        $rkap = RKAP::updateOrCreate(
            ['year' => static::$year],
            [
                'company_id' => static::$company->id,
                'status' => 'draft',
                'phase' => 'initiation',
                'phase_started_at' => now(),
                'kickoff_date' => now()->toDateString(),
                'kickoff_notes' => 'Kick-off penyusunan RKAP '.static::$year.' divisi '.static::$divisionName.'.',
                'direction_notes' => 'Arahan direksi: wujudkan efisiensi dan percepatan pengadaan melalui skenario simulasi.',
                'bmi_alignment_status' => 'none',
                'bmi_notes' => null,
                'distribution_status' => 'not_distributed',
                'resolution_date' => null,
            ]
        );

        $rkap->kickoffAttendees()->delete();
        foreach (['PPK '.static::$divisionName, 'Controller '.static::$divisionName, static::$divisionName] as $index => $attendee) {
            KickoffAttendee::create([
                'erkap_rkap_id' => $rkap->id,
                'name' => $attendee,
                'division_id' => static::$division->id,
                'attended' => true,
            ]);
        }

        static::$rkap = $rkap->refresh();
        static::$rkap = RKAPLifecycleService::advance(static::$rkap);
    }

    /**
     * Tahap 2 - Penyusunan: sasaran perusahaan -> departemen -> risiko.
     */
    protected static function prepareTargets(): void
    {
        $rkap = static::$rkap;

        $companyTarget = CompanyTarget::updateOrCreate(
            ['erkap_rkap_id' => $rkap->id, 'target' => 'RKAP '.static::$year],
            ['company_id' => static::$company->id]
        );

        DepartmentTarget::updateOrCreate(
            ['erkap_company_target_id' => $companyTarget->id, 'division_id' => static::$division->id],
            [
                'target' => 'Sasaran '.static::$divisionName.' '.static::$year,
                'erkap_rating_criteria_id' => static::$ref['rating']->id,
                'priority' => 1,
            ]
        );

        static::$ref['companyTarget'] = $companyTarget;
    }

    /**
     * Tahap 2 (lanjutan) - Form 1 risiko, strategi, program kerja & anggaran.
     */
    protected static function prepareRisksAndPrograms(): void
    {
        $departmentTarget = DepartmentTarget::where('division_id', static::$division->id)
            ->where('erkap_company_target_id', static::$ref['companyTarget']->id)
            ->firstOrFail();

        $risks = static::riskPayloads();
        static::$ref['risks'] = [];
        static::$ref['workPrograms'] = [];

        foreach ($risks as $riskData) {
            $risk = RiskIdentification::updateOrCreate(
                [
                    'risk' => $riskData['risk'],
                    'erkap_department_target_id' => $departmentTarget->id,
                ],
                [
                    'risk_direction' => $riskData['risk_direction'],
                    'erkap_risk_taxonomy_id' => static::$ref['taxonomy']->id,
                    'erkap_risk_type_id' => static::$ref['riskType']->id,
                    'status' => 'draft',
                ]
            );

            RiskIdentificationReason::updateOrCreate(
                ['erkap_risk_identification_id' => $risk->id, 'reason' => $riskData['reason']],
                []
            );

            RiskIdentificationImpact::updateOrCreate(
                ['erkap_risk_identification_id' => $risk->id, 'impact' => $riskData['impact']],
                []
            );

            RiskAnalysis::updateOrCreate(
                [
                    'erkap_risk_identification_id' => $risk->id,
                    'erkap_risk_probability_id' => static::$ref['probability']->id,
                    'erkap_risk_impact_id' => static::$ref['impact']->id,
                ],
                ['erkap_risk_score_value_id' => static::$ref['scoreLevel']->id]
            );

            RiskRanking::updateOrCreate(
                ['erkap_risk_identification_id' => $risk->id, 'ranking' => $riskData['ranking']],
                []
            );

            $strategy = DepartmentRiskStrategy::updateOrCreate(
                ['erkap_risk_identification_id' => $risk->id, 'strategy' => $riskData['strategy']],
                []
            );

            RiskTreatment::updateOrCreate(
                [
                    'erkap_risk_identification_id' => $risk->id,
                    'erkap_department_risk_strategy_id' => $strategy->id,
                    'treatment_type' => $riskData['treatment_type'],
                    'description' => $riskData['treatment_desc'],
                ],
                [
                    'responsible_party' => static::$divisionName,
                    'target_date' => now()->addMonths(6)->toDateString(),
                    'status' => 'in_progress',
                ]
            );

            foreach ($riskData['programs'] as $programData) {
                $program = WorkProgram::updateOrCreate(
                    ['code' => $programData['code']],
                    [
                        'erkap_risk_identification_id' => $risk->id,
                        'name' => $programData['name'],
                        'units' => $programData['units'],
                        'year_plan' => (float) $programData['year_plan'],
                    ] + static::monthlyPlan((float) $programData['year_plan'], 'plan')
                );

                static::$ref['workPrograms'][$programData['code']] = $program;

                foreach ($programData['routine_costs'] as $rcData) {
                    RoutineCost::updateOrCreate(
                        ['erkap_work_program_id' => $program->id, 'need' => $rcData['need']],
                        [
                            'erkap_cost_element_id' => static::$ref['costElement']->id,
                            'cost_center_id' => static::$ref['costCenter']->id,
                            'cost_center_owner' => static::$divisionName,
                            'chart_of_account_id' => static::$ref['coa']->id,
                            'qty' => $rcData['qty'],
                            'units' => $rcData['units'],
                            'unit_price' => $rcData['unit_price'],
                            'is_kumulatif' => false,
                            'total' => $rcData['total'],
                        ] + static::monthlyPlan($rcData['total'], 'cost')
                    );
                }

                foreach ($programData['investment_plans'] as $ipData) {
                    $total = round($ipData['qty'] * $ipData['unit_price'], 2);

                    InvestmentPlan::updateOrCreate(
                        ['erkap_work_program_id' => $program->id, 'name' => $ipData['name']],
                        [
                            'cost_center_id' => static::$ref['costCenter']->id,
                            'chart_of_account_id' => static::$ref['coa']->id,
                            'erkap_investattion_category_id' => static::$ref['investationCategory']->id,
                            'erkap_investation_type_id' => static::$ref['investationType']->id,
                            'erkap_investation_criteria_id' => static::$ref['investationCriteria']->id,
                            'description' => $ipData['description'],
                            'unit' => $ipData['unit'],
                            'qty' => $ipData['qty'],
                            'unit_price' => $ipData['unit_price'],
                            'is_kumulatif' => false,
                            'total' => $total,
                            'priority_order' => $ipData['priority_order'],
                            'proposal_file_path' => static::proposalPath($program, $ipData['name']),
                            'proposal_original_name' => $ipData['name'].'.pdf',
                            'gate_review_status' => 'none',
                            'status' => 'draft',
                        ] + static::monthlyPlan($total, 'plan')
                    );
                }
            }

            static::$ref['risks'][$riskData['risk']] = $risk;
        }

        // Penyusunan selesai -> lanjut fase konsolidasi.
        static::$rkap = RKAPLifecycleService::advance(static::$rkap);
    }

    /**
     * Tahap 3 - Submit & approval bertingkat lewat ApprovalService (idempoten:
     * approval & gate lama dibersihkan lalu dibentuk ulang).
     */
    protected static function submitAndApproveDocuments(): void
    {
        $workPrograms = WorkProgram::query()
            ->whereIn('code', array_keys(static::$ref['workPrograms']))
            ->get();

        foreach ($workPrograms as $program) {
            static::submitAndApprove($program);
        }

        foreach ($workPrograms as $program) {
            foreach ($program->routineCosts()->get() as $cost) {
                static::submitAndApprove($cost);
            }
        }

        foreach ($workPrograms as $program) {
            foreach ($program->investmentPlans()->get() as $plan) {
                $plan->stageGates()->delete();
                $plan->update(['status' => 'draft', 'gate_review_status' => 'none']);
                static::submitAndApprove($plan);
            }
        }

        foreach (static::$ref['risks'] as $risk) {
            $risk->refresh();
            static::submitAndApprove($risk);
        }
    }

    protected static function submitAndApprove(Model $model): void
    {
        static::fillAs('erkap-ppk', function () use ($model) {
            $model->update(['status' => 'draft']);
            ApprovalService::submit($model);
            static::realignApprovers($model);
        });

        if ($model instanceof InvestmentPlan) {
            foreach (array_keys(InvestmentGateReviewService::STAGES) as $stage) {
                $role = InvestmentGateReviewService::roleForStage($stage);

                static::fillAs($role, function () use ($model, $stage) {
                    InvestmentGateReviewService::review(
                        $model,
                        $stage,
                        static::$users[InvestmentGateReviewService::roleForStage($stage)],
                        [
                            'status' => 'approved',
                            'result' => 'layak',
                            'notes' => 'Disetujui otomatis oleh seeder simulasi RKAP '.static::$year.'.',
                        ]
                    );
                });
            }

            return;
        }

        $type = ApprovalService::typeFor($model);
        $matrix = ApprovalService::getApprovalMatrix()[$type];

        foreach ($matrix as $level => $role) {
            static::fillAs($role, function () use ($model, $role) {
                ApprovalService::approve($model, static::$users[$role], 'Disetujui otomatis oleh seeder simulasi RKAP '.static::$year.'.');
            });
        }
    }

    /**
     * Pastikan setiap level approval ber-Giliran user simulasi, apa pun isi DB.
     */
    protected static function realignApprovers(Model $model): void
    {
        foreach ($model->approvals()->get() as $approval) {
            $approval->update(['approver_id' => static::$users[$approval->role]->id]);
        }
    }

    /**
     * Tahap 4 - ZBB: build + approve semua baris yang memblokir konsolidasi.
     */
    protected static function runZbb(): void
    {
        ZBBReviewService::buildReviews(static::$rkap);

        ZBBReview::where('erkap_rkap_id', static::$rkap->id)
            ->get()
            ->filter(fn (ZBBReview $review) => $review->blocksConsolidation())
            ->each(function (ZBBReview $review) {
                static::fillAs('erkap-ppk', function () use ($review) {
                    ZBBReviewService::review(static::$rkap, $review->id, static::$users['erkap-ppk'], [
                        'zbb_status' => 'approved',
                        'increase_rationale' => 'Justifikasi kenaikan pos anggaran divisi '.static::$divisionName.' tahun '.static::$year.' (seed simulasi).',
                        'review_notes' => 'Disetujui otomatis oleh seeder simulasi.',
                    ]);
                });
            });

        ZBBReviewService::requireRationale(static::$rkap);
    }

    /**
     * Tahap 5 - Konsolidasi OPEX + CAPEX.
     */
    protected static function consolidateBudgets(): void
    {
        $service = new BudgetOpexConsolidationService;
        $service->consolidate(static::$rkap);
        $service->recalculateVariance(static::$rkap);

        static::consolidateCapex();
    }

    protected static function consolidateCapex(): void
    {
        $investmentPlans = InvestmentPlan::query()
            ->with('workProgram.riskIdentification.departmentTarget')
            ->whereHas('workProgram.riskIdentification.departmentTarget.companyTarget', function ($query) {
                $query->where('erkap_rkap_id', static::$rkap->id);
            })
            ->get();

        $grouped = $investmentPlans->groupBy(function (InvestmentPlan $plan) {
            return $plan->workProgram?->riskIdentification?->departmentTarget?->division_id;
        });

        foreach ($grouped as $divisionId => $plans) {
            if (! $divisionId) {
                continue;
            }

            BudgetCapex::updateOrCreate(
                ['erkap_rkap_id' => static::$rkap->id, 'division_id' => $divisionId],
                ['total_investment' => round($plans->sum('total'), 2)]
            );
        }
    }

    /**
     * Tahap 6 - Finalisasi, pengesahan, BMI, distribusi, arsip.
     */
protected static function finaliseRkap(): void
    {
        static::$rkap->refresh();

        // Periode RKAP diajukan di fase finalisasi oleh PPK (komisaris -> direksi).
        static::fillAs('erkap-ppk', function () {
            RKAPLifecycleService::advance(static::$rkap); // consolidation -> finalization

            static::$rkap->update(['status' => 'draft']);
            ApprovalService::submit(static::$rkap);
            static::realignApprovers(static::$rkap);
        });

        static::fillAs('erkap-komisaris', function () {
            ApprovalService::approve(static::$rkap, static::$users['erkap-komisaris'], 'Disetujui otomatis oleh seeder simulasi RKAP '.static::$year.'.');
        });

        static::fillAs('erkap-direksi', function () {
            ApprovalService::approve(static::$rkap, static::$users['erkap-direksi'], 'Disetujui otomatis oleh seeder simulasi RKAP '.static::$year.'.');
        });

        static::fillAs('erkap-bmi-admin', function () {
            RKAPLifecycleService::markBmiAligned(static::$rkap, static::$users['erkap-bmi-admin'], [
                'bmi_alignment_status' => 'aligned',
                'bmi_notes' => 'Selaras dengan arah PT BMI (seed simulasi).',
            ]);
        });

        static::fillAs('erkap-ppk', function () {
            RKAPLifecycleService::advance(static::$rkap); // finalization -> approved

            RKAPLifecycleService::distribute(static::$rkap, static::$users['erkap-ppk']);

            RKAPLifecycleService::advance(static::$rkap); // approved -> archived
        });
    }
    /**
     * Tahap 7 - Realisasi pasca pengesahan (budget, progres, asesmen, scorecard).
     */
    protected static function seedRealization(): void
    {
        $rkap = static::$rkap;
        $year = (int) static::$year;

        $workPrograms = WorkProgram::query()
            ->whereIn('code', array_keys(static::$ref['workPrograms']))
            ->with('routineCosts', 'investmentPlans')
            ->get();

        $departmentTarget = DepartmentTarget::where('division_id', static::$division->id)->first();

        foreach ($workPrograms as $program) {
            foreach ($program->routineCosts as $cost) {
                foreach (range(1, 6) as $month) {
                    $budgeted = (float) $cost->{static::monthColumn('cost', $month)};

                    BudgetRealization::updateOrCreate(
                        [
                            'erkap_rkap_id' => $rkap->id,
                            'erkap_routine_cost_id' => $cost->id,
                            'month' => $month,
                            'year' => $year,
                        ],
                        [
                            'budgeted' => $budgeted,
                            'realized' => round($budgeted * (0.7 + ($month * 0.05)), 2),
                            'source' => 'manual',
                        ]
                    )->calculateVariance();
                }
            }

            foreach ($program->investmentPlans as $plan) {
                foreach (range(1, 6) as $month) {
                    $budgeted = (float) $plan->{static::monthColumn('plan', $month)};

                    BudgetRealization::updateOrCreate(
                        [
                            'erkap_rkap_id' => $rkap->id,
                            'erkap_investment_plan_id' => $plan->id,
                            'month' => $month,
                            'year' => $year,
                        ],
                        [
                            'budgeted' => $budgeted,
                            'realized' => round($budgeted * (0.5 + ($month * 0.1)), 2),
                            'source' => 'manual',
                        ]
                    )->calculateVariance();
                }
            }

            foreach (range(1, 6) as $month) {
                $target = (float) ($program->year_plan ?? 100);

                ProgramRealization::updateOrCreate(
                    [
                        'erkap_work_program_id' => $program->id,
                        'month' => $month,
                        'year' => $year,
                    ],
                    [
                        'target' => $target,
                        'realized' => round($target * (0.6 + ($month * 0.05)), 2),
                        'notes' => 'Progres bulanan program kerja (seed simulasi).',
                    ]
                )->calculatePercentComplete();
            }
        }

        foreach ($workPrograms->pluck('erkap_risk_identification_id')->unique() as $riskId) {
            foreach (range(1, 6) as $month) {
                RiskAssessmentMonthly::updateOrCreate(
                    [
                        'erkap_risk_identification_id' => $riskId,
                        'month' => $month,
                        'year' => $year,
                    ],
                    [
                        'inherent_probability' => 4,
                        'inherent_impact' => 5,
                        'current_probability' => 3,
                        'current_impact' => 4,
                        'residual_probability' => 2,
                        'residual_impact' => 3,
                        'mitigation_plan' => 'Peningkatan kontrol & monitoring berkala (seed simulasi).',
                        'risk_owner' => static::$divisionName,
                    ]
                )->calculateScores();
            }
        }

        if ($departmentTarget) {
            foreach ([1, 2] as $quarter) {
                PerformanceScorecard::updateOrCreate(
                    [
                        'erkap_rkap_id' => $rkap->id,
                        'erkap_department_target_id' => $departmentTarget->id,
                        'quarter' => $quarter,
                        'year' => $year,
                        'kpi_name' => 'Penyelesaian Program Kerja',
                    ],
                    [
                        'kpi_target' => 100,
                        'kpi_actual' => 80,
                        'weight' => 30,
                    ]
                )->calculateWeightedScore();

                PerformanceScorecard::updateOrCreate(
                    [
                        'erkap_rkap_id' => $rkap->id,
                        'erkap_department_target_id' => $departmentTarget->id,
                        'quarter' => $quarter,
                        'year' => $year,
                        'kpi_name' => 'Efisiensi Anggaran',
                    ],
                    [
                        'kpi_target' => 10,
                        'kpi_actual' => 8,
                        'weight' => 20,
                    ]
                )->calculateWeightedScore();
            }
        }
    }

    /**
     * Daftar payload risiko/program/anggaran simulasi (2 risiko -> 2 program).
     */
    protected static function riskPayloads(): array
    {
        return [
            [
                'risk' => 'Keterlambatan penyelesaian pengadaan aset pendukung operasi',
                'risk_direction' => 'negative',
                'reason' => 'Proses lelang dan persetujuan multi-level memakan waktu lama',
                'impact' => 'Target operasional divisi tidak tercapai tepat waktu',
                'ranking' => 1,
                'strategy' => 'reduction',
                'treatment_type' => 'reduction',
                'treatment_desc' => 'Percepatan koordinasi review dan pemantauan jadwal pengadaan secara berkala',
                'programs' => [
                    [
                        'code' => 'WP-SIM/'.static::$year.'-01',
                        'name' => 'Optimalisasi Pengelolaan Aset Divisi '.static::$divisionName,
                        'units' => 'Proyek',
                        'year_plan' => 100,
                        'routine_costs' => [
                            [
                                'need' => 'Honorarium Tim Evaluasi Aset',
                                'qty' => 4,
                                'units' => 'orang',
                                'unit_price' => 5_000_000,
                                'total' => 120_000_000,
                            ],
                            [
                                'need' => 'Biaya ATK dan Konsumsi Rapat',
                                'qty' => 12,
                                'units' => 'bulan',
                                'unit_price' => 3_000_000,
                                'total' => 36_000_000,
                            ],
                        ],
                        'investment_plans' => [
                            [
                                'name' => 'Peralatan Penunjang Digitalisasi',
                                'description' => 'Pengadaan perangkat keras pendukung digitalisasi divisi',
                                'unit' => 'unit',
                                'qty' => 25,
                                'unit_price' => 6_000_000,
                                'priority_order' => 1,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'risk' => 'Pembengkakan biaya operasional divisi',
                'risk_direction' => 'negative',
                'reason' => 'Fluktuasi harga material dan jasa pendukung',
                'impact' => 'Efisiensi anggaran menurun dan realisasi melebihi pagu',
                'ranking' => 2,
                'strategy' => 'sharing',
                'treatment_type' => 'sharing',
                'treatment_desc' => 'Kerja sama pemasok dan peninjauan harga secara berkala',
                'programs' => [
                    [
                        'code' => 'WP-SIM/'.static::$year.'-02',
                        'name' => 'Penguatan Efisiensi Biaya Operasional',
                        'units' => 'Program',
                        'year_plan' => 100,
                        'routine_costs' => [
                            [
                                'need' => 'Biaya Sewa Peralatan Pendukung',
                                'qty' => 12,
                                'units' => 'bulan',
                                'unit_price' => 7_000_000,
                                'total' => 84_000_000,
                            ],
                        ],
                        'investment_plans' => [
                            [
                                'name' => 'Pengadaan Alat Monitoring',
                                'description' => 'Pengadaan perangkat monitoring operasional',
                                'unit' => 'unit',
                                'qty' => 15,
                                'unit_price' => 6_000_000,
                                'priority_order' => 2,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Sebar total anggaran merata ke 12 kolom bulanan (jumlah == total).
     * Catatan: kolom Desember RoutineCost memakai `des_cost` (bukan `dec_cost`).
     *
     * @return array<string, float>
     */
    protected static function monthlyPlan(float $total, string $suffix): array
    {
        $months = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
        $per = round($total / 12, 2);
        $data = [];

        foreach ($months as $index => $month) {
            $column = ($suffix === 'cost' && $month === 'dec') ? 'des' : $month;
            $data["{$column}_{$suffix}"] = ($index === count($months) - 1)
                ? round($total - ($per * (count($months) - 1)), 2)
                : $per;
        }

        return $data;
    }

    protected static function monthColumn(string $suffix, int $month): string
    {
        $names = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
        $column = ($suffix === 'cost' && $names[$month - 1] === 'dec') ? 'des' : $names[$month - 1];

        return $column.'_'.$suffix;
    }

    protected static function proposalPath(WorkProgram $program, string $name): string
    {
        $path = 'erkap-simulasi/proposal/'.Str::slug($program->code.'-'.$name).'.pdf';

        if (! Storage::disk('public')->exists($path)) {
            Storage::disk('public')->put($path, 'Proposal placeholder simulasi RKAP '.static::$year.'.');
        }

        return $path;
    }

    /**
     * @return array<string, int|string>
     */
    protected static function summary(): array
    {
        $rkap = static::$rkap->refresh();

        return [
            'year' => static::$year,
            'division' => static::$divisionName,
            'rkap_id' => $rkap->id,
            'rkap_status' => $rkap->status,
            'rkap_phase' => $rkap->phase,
            'work_programs' => WorkProgram::whereIn('code', array_keys(static::$ref['workPrograms']))->count(),
            'routine_costs' => RoutineCost::whereIn('erkap_work_program_id', array_map(fn ($wp) => $wp->id, static::$ref['workPrograms']))->count(),
            'investment_plans' => InvestmentPlan::whereIn('erkap_work_program_id', array_map(fn ($wp) => $wp->id, static::$ref['workPrograms']))->count(),
            'zbb_reviews' => ZBBReview::where('erkap_rkap_id', $rkap->id)->count(),
        ];
    }
}