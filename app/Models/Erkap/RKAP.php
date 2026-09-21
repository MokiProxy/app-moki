<?php

namespace App\Models\Erkap;

use App\Models\Erkap\Traits\HasAuditTrail;
use App\Models\Erkap\Traits\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RKAP extends Model
{
    use HasFactory, HasApprovalWorkflow, HasAuditTrail;

    protected $table = 'erkap_rkap';

    protected $fillable = ['year', 'status'];

    public function companyTargets()
    {
        return $this->hasMany(CompanyTarget::class, 'erkap_rkap_id');
    }

    public function budgetRealizations()
    {
        return $this->hasMany(BudgetRealization::class, 'erkap_rkap_id');
    }

    public function performanceScorecards()
    {
        return $this->hasMany(PerformanceScorecard::class, 'erkap_rkap_id');
    }
}