<?php

namespace App\Models\Erkap;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskScale extends Model
{
    use HasFactory;

    protected $table = "erkap_risk_scales";

    protected $fillable = ['scale', 'level'];
}
