<?php

namespace App\Models\Erkap;

use App\Models\Erkap\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskAppetite extends Model
{
    use HasFactory, HasAuditTrail;

    protected $table = 'erkap_risk_appetites';

    protected $fillable = ['name'];

    public function riskTaxonomies()
    {
        return $this->hasMany(RiskTaxonomy::class);
    }
}
