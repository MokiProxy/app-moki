<?php

namespace App\Models\Erkap;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestationType extends Model
{
    use HasFactory;

    protected $table = 'erkap_investation_types';

    protected $fillable = ['code', 'name'];

    public function investmentPlans()
    {
        return $this->hasMany(InvestmentPlan::class, 'erkap_investation_type_id');
    }
}