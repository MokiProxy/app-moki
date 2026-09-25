<?php

namespace App\Models\Erkap;

use App\Models\Erkap\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class DepartmentRiskStrategy extends Model
{
    use HasFactory, HasAuditTrail;

    protected $table = 'erkap_department_risk_strategies';

    protected $fillable = ['erkap_risk_identification_id', 'strategy'];

    public function riskIdentification()
    {
        return $this->belongsTo(RiskIdentification::class, 'erkap_risk_identification_id');
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
