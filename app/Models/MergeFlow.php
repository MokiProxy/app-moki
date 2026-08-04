<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MergeFlow extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(MergeFlowStep::class)->orderBy('order');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(DocumentMergeGroup::class);
    }
}
