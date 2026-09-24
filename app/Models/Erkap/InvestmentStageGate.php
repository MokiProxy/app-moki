<?php

namespace App\Models\Erkap;

use App\Models\Erkap\Traits\HasAuditTrail;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestmentStageGate extends Model
{
    use HasFactory, HasAuditTrail;

    protected $table = 'erkap_investment_stage_gates';

    protected $fillable = [
        'erkap_investment_plan_id',
        'stage',
        'stage_order',
        'status',
        'reviewer_role',
        'reviewed_by',
        'reviewed_at',
        'notes',
        'result',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(InvestmentPlan::class, 'erkap_investment_plan_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeForRoles(Builder $query, array $roles): Builder
    {
        return $query->whereIn('reviewer_role', $roles);
    }

    public static function stageLabel(?string $stage): string
    {
        return match ($stage) {
            'proposal' => 'Proposal Investasi',
            'cba' => 'Kajian Kelayakan (CBA)',
            'aset' => 'Dept. Manajemen Aset',
            'direksi_keuangan' => 'Direksi Keuangan',
            'gate_review_bmi' => 'Gate Review PT BMI',
            default => $stage ?: '-',
        };
    }

    public static function resultLabel(?string $result): string
    {
        return match ($result) {
            'layak' => 'Layak',
            'tidak_layak' => 'Tidak Layak',
            'revisi' => 'Perlu Revisi',
            default => $result ?: '-',
        };
    }

    public function label(): string
    {
        return static::stageLabel($this->stage);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'revised' => 'Revisi',
            default => 'Menunggu',
        };
    }

    public function statusClass(): string
    {
        return match ($this->status) {
            'approved' => 'success',
            'rejected' => 'danger',
            'revised' => 'info',
            default => 'warning',
        };
    }

    public function canReviewBy(User $user): bool
    {
        return $user->hasRole($this->reviewer_role);
    }
}