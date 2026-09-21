<?php

namespace App\Models\Erkap;

use App\Models\Erkap\Traits\HasAuditTrail;
use App\Models\Division;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentTarget extends Model
{
    use HasFactory, HasAuditTrail;

    protected $table = 'erkap_department_targets';

    protected $fillable = ['target', 'division_id', 'erkap_rating_criteria_id', 'erkap_company_target_id'];

    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function ratingCriteria()
    {
        return $this->belongsTo(RatingCriteria::class, 'erkap_rating_criteria_id');
    }

    public function companyTarget()
    {
        return $this->belongsTo(CompanyTarget::class, 'erkap_company_target_id');
    }

    public function riskIdentifications()
    {
        return $this->hasMany(RiskIdentification::class, 'erkap_department_target_id');
    }

    public function performanceScorecards()
    {
        return $this->hasMany(PerformanceScorecard::class, 'erkap_department_target_id');
    }
}