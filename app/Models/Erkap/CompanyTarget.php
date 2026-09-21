<?php

namespace App\Models\Erkap;

use App\Models\Erkap\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyTarget extends Model
{
    use HasFactory, HasAuditTrail;

    protected $table = 'erkap_company_targets';

    protected $fillable = ['target', 'erkap_rkap_id'];

    public function rkap()
    {
        return $this->belongsTo(RKAP::class, 'erkap_rkap_id');
    }

    public function departmentTargets()
    {
        return $this->hasMany(DepartmentTarget::class, 'erkap_company_target_id');
    }
}