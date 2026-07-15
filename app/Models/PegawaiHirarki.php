<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PegawaiHirarki extends Model
{
    protected $table = 'pegawai_hirarki';

    protected $fillable = [
        'position_id',
        'nik',
        'nopeg',
        'nama',
        'email',
        'jabatan0',
        'kode_satker',
        'superior_1', 'superior_2', 'superior_3', 'superior_4',
        'superior_5', 'superior_6', 'superior_7', 'superior_8',
        'nik1', 'nik2', 'nik3', 'nik4',
        'nik5', 'nik6', 'nik7', 'nik8',
        'nopeg_hier1', 'nopeg_hier2', 'nopeg_hier3', 'nopeg_hier4',
        'nopeg_hier5', 'nopeg_hier6', 'nopeg_hier7', 'nopeg_hier8',
        'nama_hier1', 'nama_hier2', 'nama_hier3', 'nama_hier4',
        'nama_hier5', 'nama_hier6', 'nama_hier7', 'nama_hier8',
        'ilinier1', 'ilinier2', 'ilinier3', 'ilinier4',
        'ilinier5', 'ilinier6', 'ilinier7', 'ilinier8',
        'email1', 'email2', 'email3', 'email4',
        'email5', 'email6', 'email7', 'email8',
        'jabatan1', 'jabatan2', 'jabatan3', 'jabatan4',
        'jabatan5', 'jabatan6', 'jabatan7', 'jabatan8',
        'kode_satker1', 'kode_satker2', 'kode_satker3', 'kode_satker4',
        'kode_satker5', 'kode_satker6', 'kode_satker7', 'kode_satker8',
    ];

    public function posisi(): BelongsTo
    {
        return $this->belongsTo(PegawaiMasterPosisi::class, 'position_id', 'position_id');
    }

    public function satker(): BelongsTo
    {
        return $this->belongsTo(PegawaiSatker::class, 'kode_satker', 'kode_satker');
    }
}
