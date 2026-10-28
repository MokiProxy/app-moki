<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spph16368AsumsiDasar extends Model
{
    use HasFactory;

    protected $table = 'simka_spph16368_asumsi_dasar';

    protected $fillable = [
        'name',
        'harga_acuan',
        'harga_aktual',
    ];
}
