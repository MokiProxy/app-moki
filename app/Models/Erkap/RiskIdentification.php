<?php

namespace App\Models\Erkap;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskIdentification extends Model
{
    use HasFactory;

    protected $table = 'erkap_risk_identifications';

    protected $fillable = ['risk', 'erkap_department_target_id', 'erkap_risk_type_id', 'erkap_risk_taxonomy_id'];

    public function departmentTarget()
    {
        return $this->belongsTo(DepartmentTarget::class, 'erkap_department_target_id');
    }

    public function riskType()
    {
        return $this->belongsTo(RiskType::class, 'erkap_risk_type_id');
    }

    public function riskTaxonomy()
    {
        return $this->belongsTo(RiskTaxonomy::class, 'erkap_risk_taxonomy_id');
    }

    public function reasons()
    {
        return $this->hasMany(RiskIdentificationReason::class, 'erkap_risk_identification_id');
    }

    public function impacts()
    {
        return $this->hasMany(RiskIdentificationImpact::class, 'erkap_risk_identification_id');
    }

    public function analysis()
    {
        return $this->hasMany(RiskAnalysis::class, 'erkap_risk_identification_id');
    }

    public function rankings()
    {
        return $this->hasMany(RiskRanking::class, 'erkap_risk_identification_id');
    }

    public function departmentRiskStrategies()
    {
        return $this->hasMany(DepartmentRiskStrategy::class, 'erkap_risk_identification_id');
    }
}