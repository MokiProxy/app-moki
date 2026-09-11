<?php

namespace App\Models\Erkap;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkProgram extends Model
{
    use HasFactory;

    protected $table = 'erkap_work_programs';

    protected $fillable = [
        'erkap_risk_identification_id',
        'name',
        'units',
        'year_plan',
        'jan_plan',
        'feb_plan',
        'mar_plan',
        'apr_plan',
        'may_plan',
        'jun_plan',
        'jul_plan',
        'aug_plan',
        'sep_plan',
        'oct_plan',
        'nov_plan',
        'dec_plan',
    ];

    public function riskIdentification()
    {
        return $this->belongsTo(RiskIdentification::class, 'erkap_risk_identification_id');
    }

    public function routineCosts()
    {
        return $this->hasMany(RoutineCost::class, 'erkap_work_program_id');
    }
}
