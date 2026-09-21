<?php

namespace App\Models\Erkap;

use App\Models\Erkap\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskAnalysis extends Model
{
    use HasFactory, HasAuditTrail;

    protected $table = 'erkap_risk_analysis';

    protected $fillable = [
        'erkap_risk_identification_id',
        'erkap_risk_probability_id',
        'erkap_risk_impact_id',
        'erkap_risk_score_value_id',
    ];

    public function riskIdentification()
    {
        return $this->belongsTo(RiskIdentification::class, 'erkap_risk_identification_id');
    }

    public function riskProbability()
    {
        return $this->belongsTo(RiskProbability::class, 'erkap_risk_probability_id');
    }

    public function riskImpact()
    {
        return $this->belongsTo(RiskImpact::class, 'erkap_risk_impact_id');
    }

    public function riskScoreValue()
    {
        return $this->belongsTo(RiskScoreLevel::class, 'erkap_risk_score_value_id');
    }
}
