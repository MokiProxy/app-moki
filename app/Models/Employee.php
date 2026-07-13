<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'employee_id',
        'name',
        'jabatan',
        'division_id',
        'regional_id',
        'hp',
        'email',
        'address',
    ];

    // Relasi ke Division (Sebagai pengganti Department)
    public function division()
{
    // Mengacu pada kolom division_id di tabel employees
    return $this->belongsTo(Division::class, 'division_id');
}

// File: app/Models/Employee.php
public function regional() {
    return $this->belongsTo(Regional::class, 'regional_id'); 
}

}