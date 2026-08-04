<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentMergeGroupItem extends Model
{
    protected $fillable = [
        'merge_group_id', 'document_type_id', 'scan_log_id',
        'document_number', 'order', 'ftp_path',
    ];

    public function mergeGroup(): BelongsTo
    {
        return $this->belongsTo(DocumentMergeGroup::class, 'merge_group_id');
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function scanLog(): BelongsTo
    {
        return $this->belongsTo(ScanLog::class);
    }
}
