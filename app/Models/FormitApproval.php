<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormitApproval extends Model
{
    protected $table = 'formit_approvals';

    protected $fillable = [
        'formit_software_installation_id',
        'approver_id',
        'level',
        'status',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function softwareInstallation(): BelongsTo
    {
        return $this->belongsTo(SoftwareInstallation::class, 'formit_software_installation_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approver_id', 'employee_id');
    }

    public function getLevelLabel(): string
    {
        return match ($this->level) {
            1 => 'Superior 1',
            2 => 'Manager IT',
            default => '-',
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => '-',
        };
    }

    public function getStatusClass(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            default => 'secondary',
        };
    }
}
