<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spph773AsumsiDasar extends Model
{
    use HasFactory;

    protected $table = 'simka_spph773_asumsi_dasar';

    protected $fillable = [
        'name',
        'harga_acuan',
        'harga_aktual',
    ];
}
