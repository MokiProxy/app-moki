<?php

namespace App\Models;

use App\Models\Erkap\CompanyTarget;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'abbreviation',
        'code',
        'short_name',
        'address',
        'npwp',
        'logo_path',
        'is_active',
        'is_parent',
        'parent_company_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_parent' => 'boolean',
    ];

    /**
     * Get all of the division for the Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function divisions()
    {
        return $this->hasMany(Division::class);
    }

    public function division()
    {
        return $this->divisions();
    }

    public function companyTargets()
    {
        return $this->hasMany(CompanyTarget::class, 'company_id');
    }

    public function parentCompany()
    {
        return $this->belongsTo(Company::class, 'parent_company_id');
    }

    public function childCompanies()
    {
        return $this->hasMany(Company::class, 'parent_company_id');
    }
}