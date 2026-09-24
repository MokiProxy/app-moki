<?php

namespace App\Models\Erkap;

use App\Models\Erkap\Traits\HasAuditTrail;
use App\Models\Erkap\Traits\HasApprovalWorkflow;
use App\Models\ChartOfAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class RoutineCost extends Model
{
    use HasFactory, HasApprovalWorkflow, HasAuditTrail;

    protected $table = 'erkap_routine_costs';

    protected $fillable = [
        'erkap_work_program_id',
        'need',
        'cost_center_id',
        'cost_center_owner',
        'qty',
        'units',
        'unit_price',
        'erkap_cost_element_id',
        'chart_of_account_id',
        'jan_cost',
        'feb_cost',
        'mar_cost',
        'apr_cost',
        'may_cost',
        'jun_cost',
        'jul_cost',
        'aug_cost',
        'sep_cost',
        'oct_cost',
        'nov_cost',
        'des_cost',
        'total',
        'prior_year_amount',
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

    public function costElement()
    {
        return $this->belongsTo(CostElement::class, 'erkap_cost_element_id');
    }

    public function chartOfAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    public function budgetRealizations()
    {
        return $this->hasMany(BudgetRealization::class, 'erkap_routine_cost_id');
    }

    protected static function booted(): void
    {
        // Monthly breakdown validation is called explicitly via validateMonthlyBreakdown()
        // or during approval process in WorkProgram::canSubmitForApproval()
    }

    public function validateMonthlyBreakdown(): void
    {
        $months = ['jan_cost', 'feb_cost', 'mar_cost', 'apr_cost', 'may_cost', 'jun_cost', 'jul_cost', 'aug_cost', 'sep_cost', 'oct_cost', 'nov_cost', 'des_cost'];
        $monthlySum = collect($months)->sum(fn ($month) => (float) ($this->$month ?? 0));
        $total = (float) ($this->total ?? 0);

        if ($total > 0 && abs($monthlySum - $total) > 0.01) {
            throw ValidationException::withMessages([
                'monthly_breakdown' => 'Total bulanan harus sama dengan total biaya',
            ]);
        }
    }
}
