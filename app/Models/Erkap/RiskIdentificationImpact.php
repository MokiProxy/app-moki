<?php

namespace App\Models\Erkap;

use App\Models\Erkap\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskIdentificationImpact extends Model
{
    use HasFactory, HasAuditTrail;

    protected $table = 'erkap_risk_identification_impacts';

    protected $fillable = ['impact', 'erkap_risk_identification_id'];

    public function riskIdentification()
    {
        return $this->belongsTo(RiskIdentification::class, 'erkap_risk_identification_id');
    }
}
