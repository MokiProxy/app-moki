<?php

namespace App\Models\Erkap;

use App\Models\Erkap\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceScorecard extends Model
{
    use HasFactory, HasAuditTrail;

    protected $table = 'erkap_performance_scorecards';

    protected $fillable = [
        'erkap_rkap_id',
        'erkap_department_target_id',
        'quarter',
        'year',
        'kpi_name',
        'kpi_target',
        'kpi_actual',
        'kpi_score',
        'weight',
        'weighted_score',
        'created_by',
        'updated_by',
    ];

    public function rkap()
    {
        return $this->belongsTo(RKAP::class, 'erkap_rkap_id');
    }

    public function departmentTarget()
    {
        return $this->belongsTo(DepartmentTarget::class, 'erkap_department_target_id');
    }

    public function calculateWeightedScore(): void
    {
        $this->kpi_score = $this->kpi_target > 0
            ? round(($this->kpi_actual / $this->kpi_target) * 100, 2)
            : 0;
        $this->weighted_score = round($this->kpi_score * ($this->weight / 100), 2);
        $this->save();
    }
}