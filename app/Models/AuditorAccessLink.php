<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditorAccessLink extends Model
{
    protected $table = 'auditor_access_links';

    protected $fillable = [
        'name',
        'token',
        'description',
        'allowed_years',
        'is_active',
        'created_by',
        'last_accessed_at',
    ];

    protected $casts = [
        'allowed_years' => 'array',
        'is_active' => 'boolean',
        'last_accessed_at' => 'datetime',
    ];

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    public function isYearAllowed(int $year): bool
    {
        return in_array($year, $this->allowed_years ?? []);
    }
}
