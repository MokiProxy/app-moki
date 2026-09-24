<?php

namespace App\Models\Erkap;

use App\Models\Erkap\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskBusinessProcess extends Model
{
    use HasFactory, HasAuditTrail;

    protected $table = 'risk_business_processes';

    protected $fillable = [
        'risk_assessment_monthly_id',
        'process_name',
        'description',
        'owner',
        'risk_level',
    ];

    protected $casts = [
        'risk_level' => 'string',
    ];

    public function riskAssessmentMonthly()
    {
        return $this->belongsTo(RiskAssessmentMonthly::class, 'risk_assessment_monthly_id');
    }
}