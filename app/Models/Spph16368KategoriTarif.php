<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spph16368KategoriTarif extends Model
{
    use HasFactory;

    protected $table = 'simka_spph16368_kategori_tarif';

    protected $fillable = [
        'name',
        'kategori_pendapatan_id',
    ];

    public function kategoriPendapatan()
    {
        return $this->belongsTo(KategoriPendapatan::class, 'kategori_pendapatan_id', 'id');
    }

    public function spph16368Tarif()
    {
        return $this->hasMany(Spph16368Tarif::class, 'kategori_tarif_id', 'id');
    }
}
