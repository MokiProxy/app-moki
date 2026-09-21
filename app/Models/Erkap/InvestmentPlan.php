<?php

namespace App\Models\Erkap;

use App\Models\Erkap\Traits\HasAuditTrail;
use App\Models\Erkap\Traits\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestmentPlan extends Model
{
    use HasFactory, HasApprovalWorkflow, HasAuditTrail;

    protected $table = 'erkap_investment_plans';

    protected $fillable = [
        'erkap_work_program_id',
        'erkap_investattion_category_id',
        'erkap_investation_type_id',
        'erkap_investation_criteria_id',
        'name',
        'description',
        'unit',
        'qty',
        'unit_price',
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
        'total',
        'is_kumulatif',
        'status',
    ];

    protected $casts = [
        'is_kumulatif' => 'boolean',
    ];

    public function workProgram()
    {
        return $this->belongsTo(WorkProgram::class, 'erkap_work_program_id');
    }

    public function investattionCategory()
    {
        return $this->belongsTo(InvestattionCategory::class, 'erkap_investattion_category_id');
    }

    public function investationType()
    {
        return $this->belongsTo(InvestationType::class, 'erkap_investation_type_id');
    }

    public function investationCriteria()
    {
        return $this->belongsTo(InvestationCriteria::class, 'erkap_investation_criteria_id');
    }

    public function budgetRealizations()
    {
        return $this->hasMany(BudgetRealization::class, 'erkap_investment_plan_id');
    }
}
