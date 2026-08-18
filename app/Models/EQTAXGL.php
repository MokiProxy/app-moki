<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EQTAXGL extends Model
{
    use HasFactory;

    protected $table = "eqtax_gl";

    protected $fillable = [
        "sheet",
        "entity",
        "no_supplier",
        "nama_supplier",
        "jurnal_date",
        "jurnal_no",
        "invoice_date",
        "invoice_no",
        "invoice_item",
        "no_faktur_pajak",
        "dpp",
        "ppn",
        "keterangan",
    ];
}
