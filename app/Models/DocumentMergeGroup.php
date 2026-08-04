<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentMergeGroup extends Model
{
    protected $fillable = [
        'merge_flow_id', 'vendor_name', 'root_document_number',
        'status', 'final_pdf_path', 'merged_at',
    ];

    protected $casts = [
        'merged_at' => 'datetime',
    ];

    public function mergeFlow(): BelongsTo
    {
        return $this->belongsTo(MergeFlow::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DocumentMergeGroupItem::class, 'merge_group_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            0 => 'Pending',
            1 => 'Lengkap',
            2 => 'Selesai',
            default => 'Unknown',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            0 => 'bg-warning',
            1 => 'bg-info',
            2 => 'bg-success',
            default => 'bg-secondary',
        };
    }
}
