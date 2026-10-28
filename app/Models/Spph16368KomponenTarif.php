<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spph16368KomponenTarif extends Model
{
    use HasFactory;

    protected $table = 'simka_spph16368_komponen_tarif';

    protected $fillable = [
        'kategori_pendapatan_id',
        'usd',
        'rupiah',
    ];

    protected $casts = [
        'usd' => 'decimal:2',
        'rupiah' => 'decimal:2',
    ];

    public function kategoriPendapatan()
    {
        return $this->belongsTo(KategoriPendapatan::class, 'kategori_pendapatan_id', 'id');
    }
}
