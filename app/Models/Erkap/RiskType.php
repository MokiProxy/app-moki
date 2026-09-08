<?php

namespace App\Models\Erkap;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskType extends Model
{
    use HasFactory;

    protected $table = 'erkap_risk_types';

    protected $fillable = ['name', 'risk_taxonomy_id'];

    public function riskTaxonomy()
    {
        return $this->belongsTo(RiskTaxonomy::class, 'risk_taxonomy_id');
    }

    public function riskIdentifications()
    {
        return $this->hasMany(RiskIdentification::class, 'erkap_risk_type_id');
    }
}
