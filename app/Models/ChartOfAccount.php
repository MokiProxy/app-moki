<?php

namespace App\Models;

use App\Models\Erkap\CostElement;
use App\Support\CoaCode;
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

    public function scopeSearch($query, ?string $term)
    {
        return $query->when(filled($term), function ($query) use ($term) {
            $query->where(function ($query) use ($term) {
                $query->where('code', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%");
            });
        });
    }

    public function getFormattedCodeAttribute(): string
    {
        return CoaCode::format((string) $this->code);
    }
}