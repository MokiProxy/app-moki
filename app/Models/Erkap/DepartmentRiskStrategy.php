<?php

namespace App\Models\Erkap;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentRiskStrategy extends Model
{
    use HasFactory;

    protected $table = 'erkap_department_risk_strategies';

    protected $fillable = ['erkap_risk_identification_id', 'strategy'];

    public function riskIdentification()
    {
        return $this->belongsTo(RiskIdentification::class, 'erkap_risk_identification_id');
    }
}
