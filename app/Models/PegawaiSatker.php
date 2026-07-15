<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PegawaiSatker extends Model
{
    protected $table = 'pegawai_satker';

    protected $fillable = [
        'kode_satker',
        'nama_satker',
    ];

    protected $primaryKey = 'kode_satker';

    public $incrementing = false;

    protected $keyType = 'string';

    public function pegawaiHirarki(): HasMany
    {
        return $this->hasMany(PegawaiHirarki::class, 'kode_satker', 'kode_satker');
    }
}
