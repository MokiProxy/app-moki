<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilyData extends Model
{
    use HasFactory;

    protected $fillable = [
        "employee_id",
        "status_keluarga",
        "nama_keluarga",
        "tanggal_lahir",
        "jenis_kelamin",
        "status_list",
        "lampiran",
        "status_approval",
        "catatan",
        "flag",
        "lokasi_kerja",
        "tempat_lahir",
        "alamat"
    ];
}
