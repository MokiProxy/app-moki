<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spph16368Bobot extends Model
{
    use HasFactory;

    protected $table = 'simka_spph16368_bobot';

    protected $fillable = [
        'name',
        'kategori_pendapatan_id',
        'bobot_bbm',
        'bobot_umsp',
    ];

    protected $casts = [
        'bobot_bbm' => 'decimal:2',
        'bobot_umsp' => 'decimal:2',
    ];

    public function kategoriPendapatan()
    {
        return $this->belongsTo(KategoriPendapatan::class, 'kategori_pendapatan_id', 'id');
    }
}
