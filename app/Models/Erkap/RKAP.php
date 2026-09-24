<?php

namespace App\Models\Erkap;

use App\Models\Company;
use App\Models\Erkap\Traits\HasAuditTrail;
use App\Models\Erkap\Traits\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RKAP extends Model
{
    use HasFactory, HasApprovalWorkflow, HasAuditTrail;

    protected $table = 'erkap_rkap';

    protected $fillable = [
        'year',
        'status',
        'company_id',
        'phase',
        'phase_started_at',
        'kickoff_date',
        'kickoff_notes',
        'direction_file_path',
        'direction_notes',
        'bmi_alignment_status',
        'bmi_notes',
        'resolution_date',
        'distribution_status',
    ];

    protected $casts = [
        'phase_started_at' => 'datetime',
        'kickoff_date' => 'date',
        'resolution_date' => 'date',
    ];

    public const PHASES = [
        'initiation', 'preparation', 'consolidation', 'finalization', 'approved', 'archived',
    ];

    public const PHASE_LABELS = [
        'initiation' => 'Inisiasi & Kick-off',
        'preparation' => 'Penyusunan',
        'consolidation' => 'Konsolidasi & Review',
        'finalization' => 'Finalisasi & Pengesahan',
        'approved' => 'Disahkan',
        'archived' => 'Arsip',
    ];

    public const BMI_STATUS_LABELS = [
        'none' => 'Belum Ada Alignment',
        'in_review' => 'Dalam Review PT BMI',
        'aligned' => 'Selaras',
        'rejected' => 'Perlu Penyesuaian',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function companyTargets(): HasMany
    {
        return $this->hasMany(CompanyTarget::class, 'erkap_rkap_id');
    }

    public function budgetRealizations(): HasMany
    {
        return $this->hasMany(BudgetRealization::class, 'erkap_rkap_id');
    }

    public function performanceScorecards(): HasMany
    {
        return $this->hasMany(PerformanceScorecard::class, 'erkap_rkap_id');
    }

    public function kickoffAttendees(): HasMany
    {
        return $this->hasMany(KickoffAttendee::class, 'erkap_rkap_id');
    }

    public function scopeLockedForInput(Builder $query): Builder
    {
        return $query->whereIn('phase', ['finalization', 'approved', 'archived']);
    }

    public function phaseLabel(): string
    {
        return static::PHASE_LABELS[$this->phase] ?? ucfirst((string) ($this->phase ?? '-'));
    }

    public function nextPhase(): ?string
    {
        $index = array_search($this->phase, static::PHASES, true);

        return $index !== false ? (static::PHASES[$index + 1] ?? null) : null;
    }

    public function canTransitionTo(string $next): bool
    {
        $current = array_search($this->phase, static::PHASES, true);
        $target = array_search($next, static::PHASES, true);

        return $current !== false
            && $target !== false
            && $target === $current + 1;
    }

    public function isLockedForInput(): bool
    {
        return in_array($this->phase, ['finalization', 'approved', 'archived'], true);
    }

    public function bmiStatusLabel(): string
    {
        return static::BMI_STATUS_LABELS[$this->bmi_alignment_status] ?? ucfirst((string) ($this->bmi_alignment_status ?? '-'));
    }

    public function bmiStatusClass(): string
    {
        return match ($this->bmi_alignment_status) {
            'aligned' => 'success',
            'in_review' => 'info',
            'rejected' => 'danger',
            default => 'secondary',
        };
    }

    public function distributionLabel(): string
    {
        return $this->distribution_status === 'distributed' ? 'Telah Didistribusikan' : 'Belum Didistribusikan';
    }

    public function distributionClass(): string
    {
        return $this->distribution_status === 'distributed' ? 'success' : 'secondary';
    }
}