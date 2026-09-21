<?php

namespace App\Models\Erkap;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'erkap_audit_logs';

    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';

    protected $fillable = [
        'user_id',
        'auditable_type',
        'auditable_id',
        'action',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            self::ACTION_CREATE => 'Buat',
            self::ACTION_UPDATE => 'Ubah',
            self::ACTION_DELETE => 'Hapus',
            default => ucfirst($this->action),
        };
    }

    public function actionClass(): string
    {
        return match ($this->action) {
            self::ACTION_CREATE => 'success',
            self::ACTION_UPDATE => 'primary',
            self::ACTION_DELETE => 'danger',
            default => 'secondary',
        };
    }

    public static function typeLabel(?string $type): string
    {
        if (! $type) {
            return '-';
        }

        $map = [
            \App\Models\Erkap\RKAP::class => 'Periode RKAP',
            \App\Models\Erkap\CompanyTarget::class => 'Sasaran Perusahaan',
            \App\Models\Erkap\DepartmentTarget::class => 'Sasaran Departemen',
            \App\Models\Erkap\RiskIdentification::class => 'Identifikasi Risiko',
            \App\Models\Erkap\RiskIdentificationReason::class => 'Alasan Identifikasi',
            \App\Models\Erkap\RiskIdentificationImpact::class => 'Dampak Identifikasi',
            \App\Models\Erkap\RiskAnalysis::class => 'Analisis Risiko',
            \App\Models\Erkap\RiskRanking::class => 'Peringkat Risiko',
            \App\Models\Erkap\DepartmentRiskStrategy::class => 'Strategi Risiko',
            \App\Models\Erkap\WorkProgram::class => 'Program Kerja',
            \App\Models\Erkap\RoutineCost::class => 'Biaya Rutin',
            \App\Models\Erkap\InvestmentPlan::class => 'Rencana Investasi',
            \App\Models\Erkap\BudgetCapex::class => 'Anggaran Investasi',
            \App\Models\Erkap\RevenuePlan::class => 'Rencana Pendapatan',
            \App\Models\Erkap\ExpensePlan::class => 'Rencana Beban',
            \App\Models\Erkap\ProfitLossStatement::class => 'Laba Rugi (P&L)',
            \App\Models\Erkap\BudgetRealization::class => 'Realisasi Anggaran',
            \App\Models\Erkap\ProgramRealization::class => 'Realisasi Program Kerja',
            \App\Models\Erkap\RiskAssessmentMonthly::class => 'Risk Assessment Bulanan',
            \App\Models\Erkap\PerformanceScorecard::class => 'Performance Scorecard',
            \App\Models\Erkap\Approval::class => 'Persetujuan (Approval)',
        ];

        return $map[$type] ?? class_basename($type);
    }
}