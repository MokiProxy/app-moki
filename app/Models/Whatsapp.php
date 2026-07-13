<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Whatsapp extends Model
{
    protected $table = 'whatsapp';

    protected $fillable = [
        'key',
        'value'
    ];
}
