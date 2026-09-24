<?php

namespace App\Models\Erkap;

use App\Models\Erkap\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BudgetOpex extends Model
{
    use HasFactory, HasAuditTrail, SoftDeletes;

    protected $table = 'erkap_budget_opex';

    protected $fillable = [
        'erkap_rkap_id',
        'division_id',
        'cost_center_id',
        'chart_of_account_id',
        'budget_amount',
        'realization_amount',
        'variance',
        'status',
    ];

    protected $casts = [
        'budget_amount' => 'decimal:2',
        'realization_amount' => 'decimal:2',
        'variance' => 'decimal:2',
    ];

    public function rkap()
    {
        return $this->belongsTo(RKAP::class, 'erkap_rkap_id');
    }

    public function division()
    {
        return $this->belongsTo(\App\Models\Division::class, 'division_id');
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    public function chartOfAccount()
    {
        return $this->belongsTo(\App\Models\ChartOfAccount::class, 'chart_of_account_id');
    }
}