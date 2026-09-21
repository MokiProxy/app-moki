<?php

namespace App\Models\Erkap;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Approval extends Model
{
    protected $table = 'erkap_approvals';

    protected $fillable = [
        'approvalable_type',
        'approvalable_id',
        'level',
        'role',
        'status',
        'approver_id',
        'notes',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function approvalable(): MorphTo
    {
        return $this->morphTo();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getLevelLabel(): string
    {
        return self::roleLabel($this->role) ?: 'Level '.$this->level;
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => 'Menunggu',
        };
    }

    public function getStatusClass(): string
    {
        return match ($this->status) {
            'approved' => 'success',
            'rejected' => 'danger',
            default => 'warning',
        };
    }

    public static function roleLabel(?string $role): ?string
    {
        return match ($role) {
            'erkap-cost-owner' => 'Cost Owner',
            'erkap-ppk' => 'PPK',
            'erkap-controller' => 'Controller',
            'erkap-direksi-keuangan' => 'Direksi Keuangan',
            'erkap-direksi' => 'Direksi',
            'erkap-komisaris' => 'Komisaris',
            'erkap-accounting' => 'Accounting',
            'erkap-risk-manager' => 'Manajemen Risiko',
            'erkap-auditor' => 'Auditor',
            default => $role ?: null,
        };
    }
}