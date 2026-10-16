<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spph773Tarif extends Model
{
    use HasFactory;

    protected $table = 'simka_spph773_tarif';

    protected $fillable = [
        'kategori_tarif_id',
        'jarak_awal',
        'jarak_akhir',
        'tarif_dasar',
        'tarif_adjustment',
        'tarif_ekskalasi',
    ];

    public function kategoriTarif()
    {
        return $this->belongsTo(KategoriTarif::class, 'kategori_tarif_id', 'id');
    }
}
