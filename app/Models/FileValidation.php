<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class FileValidation extends Model
{
    protected $table = 'file_validations';

    protected $fillable = [
        'file_path',
        'file_name',
        'folder_path',
        'is_validated',
        'validated_by',
        'validated_at',
        'unvalidated_by',
        'unvalidated_at',
    ];

    protected $casts = [
        'is_validated' => 'boolean',
        'validated_at' => 'datetime',
        'unvalidated_at' => 'datetime',
    ];

    public function scopeValidated(Builder $query): Builder
    {
        return $query->where('is_validated', true);
    }

    public function scopeUnvalidated(Builder $query): Builder
    {
        return $query->where('is_validated', false);
    }
}
