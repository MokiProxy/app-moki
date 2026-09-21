<?php

namespace App\Models\Erkap;

use App\Models\Erkap\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetRealization extends Model
{
    use HasFactory, HasAuditTrail;

    protected $table = 'erkap_budget_realizations';

    protected $fillable = [
        'erkap_rkap_id',
        'erkap_routine_cost_id',
        'erkap_investment_plan_id',
        'month',
        'year',
        'budgeted',
        'realized',
        'variance',
        'variance_percent',
        'source',
        'created_by',
        'updated_by',
    ];

    public function rkap()
    {
        return $this->belongsTo(RKAP::class, 'erkap_rkap_id');
    }

    public function routineCost()
    {
        return $this->belongsTo(RoutineCost::class, 'erkap_routine_cost_id');
    }

    public function investmentPlan()
    {
        return $this->belongsTo(InvestmentPlan::class, 'erkap_investment_plan_id');
    }

    public function calculateVariance(): void
    {
        $this->variance = $this->realized - $this->budgeted;
        $this->variance_percent = $this->budgeted > 0
            ? round((($this->realized - $this->budgeted) / $this->budgeted) * 100, 2)
            : 0;
        $this->save();
    }
}