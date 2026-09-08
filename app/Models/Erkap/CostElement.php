<?php

namespace App\Models\Erkap;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostElement extends Model
{
    use HasFactory;

    protected $table = 'erkap_cost_elements';

    protected $fillable = ['code', 'name', 'erkap_cost_element_category_id'];

    public function costElementCategory()
    {
        return $this->belongsTo(CostElementCategory::class, 'erkap_cost_element_category_id');
    }
}
