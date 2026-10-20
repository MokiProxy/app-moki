<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spph2287Bobot extends Model
{
    use HasFactory;

    protected $table = 'simka_spph2287_bobot';

    protected $fillable = [
        'name',
        'value',
    ];

    protected $casts = [
        'value' => 'decimal:2',
    ];
}
