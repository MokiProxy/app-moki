<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriPendapatan extends Model
{
    use HasFactory;

    protected $table = 'simka_kategori_pendapatan';

    protected $fillable = [
        'name',
    ];

    public function spph773Bobot()
    {
        return $this->hasMany(Spph773Bobot::class, 'kategori_pendapatan_id', 'id');
    }

    public function spph773KomponenTarif()
    {
        return $this->hasMany(Spph773KomponenTarif::class, 'kategori_pendapatan_id', 'id');
    }

    public function kategoriTarif()
    {
        return $this->hasMany(KategoriTarif::class, 'kategori_pendapatan_id', 'id');
    }

    public function spph16368Bobot()
    {
        return $this->hasMany(Spph16368Bobot::class, 'kategori_pendapatan_id', 'id');
    }

    public function spph16368KomponenTarif()
    {
        return $this->hasMany(Spph16368KomponenTarif::class, 'kategori_pendapatan_id', 'id');
    }

    public function spph16368KategoriTarif()
    {
        return $this->hasMany(Spph16368KategoriTarif::class, 'kategori_pendapatan_id', 'id');
    }
}
