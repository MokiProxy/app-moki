<?php

namespace App\Models\Erkap;

use App\Models\Erkap\Traits\HasApprovalWorkflow;
use App\Models\Erkap\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class RiskIdentification extends Model
{
    use HasApprovalWorkflow, HasAuditTrail, HasFactory;

    protected $table = 'erkap_risk_identifications';

    protected $fillable = ['risk', 'risk_direction', 'erkap_department_target_id', 'erkap_risk_type_id', 'erkap_risk_taxonomy_id', 'status', 'approval_status', 'approved_by', 'approved_at'];

    public function setStatusAttribute($value)
    {
        $this->attributes['status'] = $value;
        $this->attributes['approval_status'] = $value;
    }

    public function scopePositive($query)
    {
        return $query->where('risk_direction', 'positive');
    }

    public function scopeNegative($query)
    {
        return $query->where('risk_direction', 'negative');
    }

    public function departmentTarget()
    {
        return $this->belongsTo(DepartmentTarget::class, 'erkap_department_target_id');
    }

    public function riskType()
    {
        return $this->belongsTo(RiskType::class, 'erkap_risk_type_id');
    }

    public function riskTaxonomy()
    {
        return $this->belongsTo(RiskTaxonomy::class, 'erkap_risk_taxonomy_id');
    }

    public function reasons()
    {
        return $this->hasMany(RiskIdentificationReason::class, 'erkap_risk_identification_id');
    }

    public function impacts()
    {
        return $this->hasMany(RiskIdentificationImpact::class, 'erkap_risk_identification_id');
    }

    public function analysis()
    {
        return $this->hasMany(RiskAnalysis::class, 'erkap_risk_identification_id');
    }

    public function departmentRiskStrategies()
    {
        return $this->hasMany(DepartmentRiskStrategy::class, 'erkap_risk_identification_id');
    }

    public function workPrograms()
    {
        return $this->hasMany(WorkProgram::class, 'erkap_risk_identification_id');
    }

    public function hasWorkProgram(): bool
    {
        return $this->workPrograms()->count() > 0;
    }

    public function assessmentsMonthly()
    {
        return $this->hasMany(RiskAssessmentMonthly::class, 'erkap_risk_identification_id');
    }

    protected static function booted(): void
    {
        static::deleting(function (self $risk) {
            $errors = [];

            if ($risk->departmentRiskStrategies()->exists()) {
                $errors['strategies'] = 'Risiko tidak bisa dihapus karena memiliki strategi mitigasi';
            }

            if ($risk->workPrograms()->exists()) {
                $errors['work_programs'] = 'Risiko tidak bisa dihapus karena memiliki Program Kerja';
            }

            if (! empty($errors)) {
                throw ValidationException::withMessages($errors);
            }
        });

        static::created(function (self $risk) {
            // Risk created - strategies and work programs will be validated on their creation
        });
    }

    public function hasStrategy(): bool
    {
        return $this->departmentRiskStrategies()->exists();
    }

    public function validateHasStrategyAndWorkProgram(): void
    {
        if (! $this->hasStrategy()) {
            throw ValidationException::withMessages([
                'strategy' => 'Setiap Risiko wajib memiliki Strategi Mitigasi',
            ]);
        }

        if (! $this->hasWorkProgram()) {
            throw ValidationException::withMessages([
                'work_program' => 'Setiap Risiko wajib memiliki Program Kerja',
            ]);
        }
    }
}
