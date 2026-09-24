<?php

namespace App\Models\Erkap;

use App\Models\Erkap\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskScoreLevel extends Model
{
    use HasFactory, HasAuditTrail;

    protected $table = 'erkap_risk_score_levels';

    protected $fillable = ['erkap_risk_probability_id', 'erkap_risk_impact_id', 'score', 'level'];

    public function riskProbability()
    {
        return $this->belongsTo(RiskProbability::class, 'erkap_risk_probability_id');
    }

    public function riskImpact()
    {
        return $this->belongsTo(RiskImpact::class, 'erkap_risk_impact_id');
    }

    public function riskAnalysis()
    {
        return $this->hasMany(RiskAnalysis::class, 'erkap_risk_score_value_id');
    }
}
