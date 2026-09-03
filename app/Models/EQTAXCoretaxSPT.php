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
        "entity",
        "kode_transaksi",
        "masa_pajak_pengkreditan",
        "tahun_pajak_pengkreditan",
        "status_faktur",
        "esign_status",
        "harga_jual",
        "dpp",
        "ppn",
        "ppnbm",
        "penandatangan",
        "perekam",
        "referensi",
        "metode_input",
        "uraian",
        "is_show_clear_name",
        "no_sp2d",
        "jenis_transaksi",
        "keterangan",
        "dibuat_oleh",
        "valid",
        "dilaporkan",
        "dilaporkan_oleh_penjual",
    ];
}
