<?php

namespace App\Models\Erkap;

use App\Models\Erkap\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentRiskStrategy extends Model
{
    use HasFactory, HasAuditTrail;

    protected $table = 'erkap_department_risk_strategies';

    protected $fillable = ['erkap_risk_identification_id', 'strategy'];

    public static function getStrategies()
    {
        return [
            'avoid' => 'Hindari',
            'reduce' => 'Kurangi',
            'transfer' => 'Transfer',
            'accept' => 'Terima',
        ];
    }

    public function riskIdentification()
    {
        return $this->belongsTo(RiskIdentification::class, 'erkap_risk_identification_id');
    }
}
