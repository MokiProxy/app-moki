<?php

namespace App\Models\Erkap\Traits;

use App\Models\Erkap\Approval;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasApprovalWorkflow
{
    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvalable');
    }

    public function latestApproval()
    {
        return $this->approvals()->latest()->first();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'submitted' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'revised' => 'Direvisi',
            default => ucfirst($this->status ?? '-'),
        };
    }

    public function statusClass(): string
    {
        return match ($this->status) {
            'draft' => 'secondary',
            'submitted' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'revised' => 'info',
            default => 'secondary',
        };
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function canBeSubmitted(): bool
    {
        return in_array($this->status, ['draft', 'rejected'], true);
    }
}