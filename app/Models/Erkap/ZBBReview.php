<?php

namespace App\Models\Erkap;

use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZBBReview extends Model
{
    use HasFactory;

    protected $table = 'erkap_zbb_reviews';

    protected $fillable = [
        'erkap_rkap_id',
        'division_id',
        'subject_type',
        'subject_id',
        'display_name',
        'prior_year_amount',
        'proposed_amount',
        'delta_amount',
        'delta_percent',
        'increase_rationale',
        'zbb_status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'prior_year_amount' => 'float',
        'proposed_amount' => 'float',
        'delta_amount' => 'float',
        'delta_percent' => 'float',
        'reviewed_at' => 'datetime',
    ];

    public const STATUSES = ['pending', 'reviewed', 'approved', 'rejected', 'skipped'];

    public const STATUS_LABELS = [
        'pending' => 'Menunggu Review',
        'reviewed' => 'Sedang Direview',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'skipped' => 'Auto-skip (tidak naik)',
    ];

    public function rkap(): BelongsTo
    {
        return $this->belongsTo(RKAP::class, 'erkap_rkap_id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isIncrease(): bool
    {
        return $this->delta_percent > 0;
    }

    public function needsRationale(): bool
    {
        return $this->isIncrease() && blank($this->increase_rationale);
    }

    public function blocksConsolidation(): bool
    {
        return $this->isIncrease()
            && ($this->needsRationale() || $this->zbb_status !== 'approved');
    }

    public function statusLabel(): string
    {
        return static::STATUS_LABELS[$this->zbb_status] ?? ucfirst((string) $this->zbb_status);
    }

    public function statusClass(): string
    {
        return match ($this->zbb_status) {
            'approved' => 'success',
            'reviewed' => 'info',
            'rejected' => 'danger',
            'skipped' => 'secondary',
            default => 'warning',
        };
    }

    public function subjectTypeLabel(): string
    {
        return match ($this->subject_type) {
            'routine_cost' => 'Biaya Rutin (OPEX)',
            'investment_plan' => 'Rencana Investasi (CAPEX)',
            'revenue_plan' => 'Rencana Pendapatan',
            'work_program' => 'Program Kerja',
            default => ucfirst((string) $this->subject_type),
        };
    }
}