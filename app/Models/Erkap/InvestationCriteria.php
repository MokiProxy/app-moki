<?php

namespace App\Models\Erkap;

use App\Models\Erkap\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestationCriteria extends Model
{
    use HasFactory, HasAuditTrail;

    protected $table = 'erkap_investation_criterias';

    protected $fillable = ['code', 'name'];

    public function investmentPlans()
    {
        return $this->hasMany(InvestmentPlan::class, 'erkap_investation_criteria_id');
    }
}