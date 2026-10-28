<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spph773AdjustmentValue extends Model
{
    use HasFactory;

    protected $table = 'simka_spph773_adjustment_value';

    protected $fillable = [
        'value',
    ];

    protected $casts = [
        'value' => 'decimal:2',
    ];
}
