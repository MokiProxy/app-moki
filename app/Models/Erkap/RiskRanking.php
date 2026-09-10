<?php

namespace App\Models\Erkap;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskRanking extends Model
{
    use HasFactory;

    protected $table = 'erkap_risk_rankings';

    protected $fillable = ['erkap_risk_identification_id', 'ranking'];

    public function riskIdentification()
    {
        return $this->belongsTo(RiskIdentification::class, 'erkap_risk_identification_id');
    }
}
