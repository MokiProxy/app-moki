<?php

namespace App\Models\Erkap;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetCapex extends Model
{
    use HasFactory;

    protected $table = 'erkap_budget_capex';

    protected $fillable = [
        'erkap_rkap_id',
        'division_id',
        'total_investment',
        'status',
        'notes',
    ];

    public function rkap()
    {
        return $this->belongsTo(RKAP::class, 'erkap_rkap_id');
    }

    public function division()
    {
        return $this->belongsTo(\App\Models\Division::class, 'division_id');
    }

    public function investmentPlans()
    {
        return $this->hasManyThrough(
            InvestmentPlan::class,
            WorkProgram::class,
            'erkap_work_program_id',
            'erkap_work_program_id',
            'id',
            'id'
        );
    }
}
