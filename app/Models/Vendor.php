<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Override;

class Vendor extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    #[Override]
    protected static function booted()
    {
        static::creating(function ($vendor) {
            $vendor->slug = Str::slug($vendor->name);
        });
    }

    public function documentTypes(): BelongsToMany
    {
        return $this->belongsToMany(DocumentType::class);
    }
}
