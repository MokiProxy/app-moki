<?php

namespace App\Models\Erkap;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportItem extends Model
{
    use HasFactory;

    protected $table = 'erkap_report_items';

    protected $fillable = [
        'report_type',
        'title',
        'frequency',
        'year',
        'month',
        'format',
        'file_path',
        'status',
        'error',
        'created_by',
    ];

    public const FREQUENCIES = ['manual', 'monthly', 'quarterly', 'annual'];

    public const STATUS_GENERATED = 'generated';
    public const STATUS_FAILED = 'failed';

    public const STATUSES = [self::STATUS_GENERATED, self::STATUS_FAILED];

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function getStoragePathAttribute(): string
    {
        return storage_path('app/public/' . $this->file_path);
    }

    public function scopeForFrequency($query, string $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('report_type', $type);
    }
}