<?php

namespace App\Models\Erkap;

use App\Enums\ErkapRiskTreatmentType;
use App\Models\Erkap\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RiskTreatment extends Model
{
    use HasFactory, HasAuditTrail, SoftDeletes;

    protected $table = 'erkap_risk_treatments';

    protected $fillable = [
        'erkap_risk_identification_id',
        'erkap_department_risk_strategy_id',
        'treatment_type',
        'description',
        'responsible_party',
        'target_date',
        'status',
        'result',
    ];

    protected $casts = [
        'target_date' => 'date',
    ];

    public function riskIdentification()
    {
        return $this->belongsTo(RiskIdentification::class, 'erkap_risk_identification_id');
    }

    public function departmentRiskStrategy()
    {
        return $this->belongsTo(DepartmentRiskStrategy::class, 'erkap_department_risk_strategy_id');
    }

    public static function getTreatmentTypes(): array
    {
        return collect(ErkapRiskTreatmentType::cases())
            ->mapWithKeys(fn (ErkapRiskTreatmentType $type) => [$type->value => $type->label()])
            ->all();
    }
}