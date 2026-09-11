<?php

namespace App\Models\Erkap;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoutineCost extends Model
{
    use HasFactory;

    protected $table = 'erkap_routine_costs';

    protected $fillable = [
        'erkap_work_program_id',
        'cost_category',
        'need',
        'cost_center_id',
        'cost_center_owner',
        'qty',
        'unit_price',
        'erkap_cost_element_id',
        'jan_cost',
        'feb_cost',
        'mar_cost',
        'apr_cost',
        'may_cost',
        'jun_cost',
        'jul_cost',
        'aug_cost',
        'sep_cost',
        'oct_cost',
        'nov_cost',
        'des_cost',
        'total',
    ];

    public function workProgram()
    {
        return $this->belongsTo(WorkProgram::class, 'erkap_work_program_id');
    }

    public function costElement()
    {
        return $this->belongsTo(CostElement::class, 'erkap_cost_element_id');
    }
}
