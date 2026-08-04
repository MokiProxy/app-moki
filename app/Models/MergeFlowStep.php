<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MergeFlowStep extends Model
{
    protected $fillable = ['merge_flow_id', 'document_type_id', 'order', 'link_regex', 'link_label'];

    public function mergeFlow(): BelongsTo
    {
        return $this->belongsTo(MergeFlow::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }
}
