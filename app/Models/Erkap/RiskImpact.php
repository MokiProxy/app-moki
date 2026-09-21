<?php

namespace App\Models\Erkap;

use App\Models\Erkap\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskImpact extends Model
{
    use HasFactory, HasAuditTrail;
    protected $table = "erkap_risk_impacts";

    protected $fillable = ['name', 'point'];

    public function riskScoreLevels()
    {
        return $this->hasMany(RiskScoreLevel::class, 'erkap_risk_impact_id');
    }

    public function riskAnalysis()
    {
        return $this->hasMany(RiskAnalysis::class, 'erkap_risk_impact_id');
    }
}
