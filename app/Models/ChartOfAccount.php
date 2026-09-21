<?php

namespace App\Models;

use App\Models\Erkap\CostElement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'type', 'description'];

    public function costElements()
    {
        return $this->hasMany(CostElement::class, 'chart_of_account_id');
    }

    public function scopeRevenue($query)
    {
        return $query->where('type', 'revenue');
    }

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }
}