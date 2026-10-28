<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spph773TarifTotal extends Model
{
    use HasFactory;

    protected $table = 'simka_spph773_tarif_total';

    protected $fillable = [
        'jarak_awal',
        'jarak_akhir',
        'tarif_total_dasar',
        'tarif_total_ekskalasi',
    ];
}
