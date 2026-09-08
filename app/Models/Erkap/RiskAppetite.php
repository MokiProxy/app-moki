<?php

namespace App\Models\Erkap;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskAppetite extends Model
{
    use HasFactory;

    protected $table = 'erkap_risk_appetites';

    protected $fillable = ['name'];

    public function riskTaxonomies()
    {
        return $this->hasMany(RiskTaxonomy::class);
    }
}
