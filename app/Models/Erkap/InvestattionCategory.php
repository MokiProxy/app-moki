<?php

namespace App\Models\Erkap;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestattionCategory extends Model
{
    use HasFactory;

    protected $table = 'erkap_investattion_categories';

    protected $fillable = ['code', 'name'];

    public function investmentPlans()
    {
        return $this->hasMany(InvestmentPlan::class, 'erkap_investattion_category_id');
    }
}