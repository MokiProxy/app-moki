<?php

namespace App\Models\Erkap;

use App\Models\Erkap\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RatingCriteria extends Model
{
    use HasFactory, HasAuditTrail;

    protected $table = 'erkap_rating_criterias';

    protected $fillable = ['rating', 'qualification', 'description'];

    public function departmentTargets()
    {
        return $this->hasMany(DepartmentTarget::class, 'erkap_rating_criteria_id');
    }
}
