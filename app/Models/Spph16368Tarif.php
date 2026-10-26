<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spph16368Tarif extends Model
{
    use HasFactory;

    protected $table = 'simka_spph16368_tarif';

    protected $fillable = [
        'kategori_tarif_id',
        'jarak_awal',
        'jarak_akhir',
        'tarif_dasar',
        'tarif_adjustment',
        'tarif_ekskalasi',
        'notes',
    ];

    public function kategoriTarif()
    {
        return $this->belongsTo(Spph16368KategoriTarif::class, 'kategori_tarif_id', 'id');
    }
}
