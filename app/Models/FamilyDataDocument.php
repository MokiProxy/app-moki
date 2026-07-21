<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilyDataDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        "family_data_id",
        "nama_dokumen",
        "lampiran"
    ];
}
