<?php

namespace App\Models\Erkap;

use App\Enums\ErkapRiskTreatmentType;
use App\Models\Erkap\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class DepartmentRiskStrategy extends Model
{
    use HasFactory, HasAuditTrail;

    protected $table = 'erkap_department_risk_strategies';

    protected $fillable = ['erkap_risk_identification_id', 'strategy'];

    public static function getStrategies()
    {
        return collect(ErkapRiskTreatmentType::cases())
            ->mapWithKeys(fn (ErkapRiskTreatmentType $type) => [$type->value => $type->label()])
            ->all();
    }

    public function riskIdentification()
    {
        return $this->belongsTo(RiskIdentification::class, 'erkap_risk_identification_id');
    }

    public function riskTreatments()
    {
        return $this->hasMany(RiskTreatment::class, 'erkap_department_risk_strategy_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $strategy) {
            if (! $strategy->riskIdentification?->exists) {
                throw ValidationException::withMessages([
                    'risk_identification' => 'Strategi Mitigasi wajib terhubung ke Risiko yang valid',
                ]);
            }
        });
    }
}
