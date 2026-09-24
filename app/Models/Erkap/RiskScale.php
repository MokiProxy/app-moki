<?php

namespace App\Models\Erkap;

use App\Models\Erkap\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskScale extends Model
{
    use HasFactory, HasAuditTrail;

    protected $table = "erkap_risk_scales";

    protected $fillable = ['scale', 'level'];
}
