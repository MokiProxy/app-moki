<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriTarif extends Model
{
    use HasFactory;

    protected $table = 'simka_kategori_tarif';

    protected $fillable = [
        'name',
        'kategori_pendapatan_id',
    ];

    public function kategoriPendapatan()
    {
        return $this->belongsTo(KategoriPendapatan::class, 'kategori_pendapatan_id', 'id');
    }

    public function spph773Tarif()
    {
        return $this->hasMany(Spph773Tarif::class, 'kategori_tarif_id', 'id');
    }

    public function spph2287Tarif()
    {
        return $this->hasMany(Spph2287Tarif::class, 'kategori_tarif_id', 'id');
    }
}
