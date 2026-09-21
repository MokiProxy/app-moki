<?php

namespace App\Models\Erkap;

use App\Models\Erkap\Traits\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RKAP extends Model
{
    use HasFactory, HasApprovalWorkflow;

    protected $table = 'erkap_rkap';

    protected $fillable = ['year', 'status'];

    public function companyTargets()
    {
        return $this->hasMany(CompanyTarget::class, 'erkap_rkap_id');
    }
}