<?php

namespace App\Models\Erkap;

use App\Models\Erkap\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskAssessmentMonthly extends Model
{
    use HasFactory, HasAuditTrail;

    protected $table = 'erkap_risk_assessments_monthly';

    protected $fillable = [
        'erkap_risk_identification_id',
        'month',
        'year',
        'inherent_probability',
        'inherent_impact',
        'inherent_score',
        'current_probability',
        'current_impact',
        'current_score',
        'residual_probability',
        'residual_impact',
        'residual_score',
        'mitigation_plan',
        'mitigation_status',
        'risk_owner',
        'created_by',
        'updated_by',
    ];

    public function riskIdentification()
    {
        return $this->belongsTo(RiskIdentification::class, 'erkap_risk_identification_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->applyScores();
        });
    }

    public function calculateScores(): void
    {
        $this->applyScores();
        $this->save();
    }

    protected function applyScores(): void
    {
        $this->inherent_score = (int) $this->inherent_probability * (int) $this->inherent_impact;
        $this->current_score = $this->current_probability !== null && $this->current_impact !== null
            ? (int) $this->current_probability * (int) $this->current_impact
            : null;
        $this->residual_score = $this->residual_probability !== null && $this->residual_impact !== null
            ? (int) $this->residual_probability * (int) $this->residual_impact
            : null;
    }
}