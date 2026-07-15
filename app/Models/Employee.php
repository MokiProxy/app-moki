<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'golongan_darah_id',
        'jenis_kelamin_id',
    ];

    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function regional()
    {
        return $this->belongsTo(Regional::class, 'regional_id');
    }

    public function golonganDarah()
    {
        return $this->belongsTo(GolonganDarah::class, 'golongan_darah_id');
    }

    public function jenisKelamin()
    {
        return $this->belongsTo(JenisKelamin::class, 'jenis_kelamin_id');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

}
