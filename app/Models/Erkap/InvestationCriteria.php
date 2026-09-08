<?php

namespace App\Models\Erkap;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestationCriteria extends Model
{
    use HasFactory;

    protected $table = 'erkap_investation_criterias';

    protected $fillable = ['code', 'name'];
}