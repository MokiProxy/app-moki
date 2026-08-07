<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FixedAssetBorrowing extends Model
{
    protected $table = 'formit_fixed_asset_borrowings';

    protected $fillable = [
        'pemohon_id',
        'pemohon_name',
        'pemohon_jabatan',
        'pemohon_departemen',
        'pemohon_area',
        'date_start',
        'date_end',
        'tujuan_lokasi',
        'keperluan',
        'tipe_perangkat',
        'penyerahkan_name',
        'penyerahkan_jabatan',
        'penyerahkan_departemen',
        'penyerahkan_area',
        'status',
        'rejected_by',
        'rejection_reason',
        'approved_at',
        'rejected_at',
        'approver_id',
    ];

    protected $casts = [
        'date_start' => 'date',
        'date_end' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function pemohon(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'pemohon_id', 'employee_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approver_id', 'employee_id');
    }

    public function deviceCompletions(): HasMany
    {
        return $this->hasMany(FixedAssetDeviceCompletion::class, 'fixed_asset_borrowing_id');
    }

    public function getApprovalStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => '-',
        };
    }

    public function getApprovalStatusClass(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            default => 'secondary',
        };
    }
}
