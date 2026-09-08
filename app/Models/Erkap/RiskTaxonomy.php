<?php

namespace App\Models\Erkap;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskTaxonomy extends Model
{
    use HasFactory;

    protected $table = 'erkap_risk_taxonomies';

    protected $fillable = ['name', 'risk_appetite_id'];

    public function riskAppetite()
    {
        return $this->belongsTo(RiskAppetite::class, 'risk_appetite_id');
    }

    public function riskTypes()
    {
        return $this->hasMany(RiskType::class);
    }
}
