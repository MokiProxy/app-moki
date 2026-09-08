<?php

namespace App\Models\Erkap;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostElementCategory extends Model
{
    use HasFactory;

    protected $table = 'erkap_cost_element_categories';

    protected $fillable = ['name'];

    public function costElements()
    {
        return $this->hasMany(CostElement::class);
    }
}
