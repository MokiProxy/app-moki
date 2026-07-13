<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetAssignment extends Model
{
    use HasFactory;

    // Izinkan semua kolom diisi (Mass Assignment)
    protected $guarded = [];

    // Relasi ke Model Asset
    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    // Relasi ke Model Employee
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}