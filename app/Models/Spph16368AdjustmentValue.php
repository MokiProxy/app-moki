<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spph16368AdjustmentValue extends Model
{
    use HasFactory;

    protected $table = 'simka_spph16368_adjustment_value';

    protected $fillable = [
        'value',
    ];

    protected $casts = [
        'value' => 'decimal:2',
    ];
}
