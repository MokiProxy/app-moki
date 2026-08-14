<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EQTAXCoretaxSPT extends Model
{
    use HasFactory;

    protected $table = "eqtax_coretax_spt";

    protected $fillable = [
        "npwp_penjual",
        "nama_penjual",
        "no_faktur_pajak",
        "tgl_faktur_pajak",
        "masa_pajak",
        "tahun",
        "masa_pajak_pengkreditan",
        "tahun_pajak_pengkreditan",
        "status_faktur",
        "harga_jual",
        "dpp",
        "ppn",
        "ppnbm",
        "perekam",
        "referensi",
        "no_sp2d",
        "valid",
        "dilaporkan",
        "dilaporkan_oleh_penjual",
    ];
}
