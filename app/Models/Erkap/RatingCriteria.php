<?php

namespace App\Models\Erkap;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RatingCriteria extends Model
{
    use HasFactory;

    protected $table = 'erkap_rating_criterias';

    protected $fillable = ['rating', 'qualification', 'description'];
}
