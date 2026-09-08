<?php

namespace App\Models\Erkap;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RKAP extends Model
{
    use HasFactory;

    protected $table = 'erkap_rkap';

    protected $fillable = ['year'];

    public function companyTargets()
    {
        return $this->hasMany(CompanyTarget::class, 'erkap_rkap_id');
    }
}