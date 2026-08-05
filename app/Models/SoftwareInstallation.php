<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoftwareInstallation extends Model
{
    protected $table = 'formit_software_installations';

    protected $fillable = [
        'pemohon_id',
        'superior1_id',
        'manager_it_id',
        'softwares',
        'keterangan',
        'status',
        'rejected_by',
        'rejection_reason',
        'approved_at',
    ];

    protected $casts = [
        'softwares' => 'array',
        'approved_at' => 'datetime',
    ];

    public function pemohon(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'pemohon_id', 'employee_id');
    }

    public function superior1(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'superior1_id', 'employee_id');
    }

    public function managerIt(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_it_id', 'employee_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(FormitApproval::class, 'formit_software_installation_id');
    }

    public function isApprovedByLevel(int $level): bool
    {
        return $this->approvals()
            ->where('level', $level)
            ->where('status', 'approved')
            ->exists();
    }

    public function canApproveLevel(int $level): bool
    {
        if ($level === 1) {
            return $this->status === 'pending';
        }
        if ($level === 2) {
            return $this->status === 'process' && $this->isApprovedByLevel(1);
        }
        return false;
    }

    public function getApprovalStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu',
            'process' => 'Proses Approval',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => '-',
        };
    }

    public function getApprovalStatusClass(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'process' => 'info',
            'approved' => 'success',
            'rejected' => 'danger',
            default => 'secondary',
        };
    }
}
