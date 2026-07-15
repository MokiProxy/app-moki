<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisKelamin extends Model
{
    protected $table = 'jenis_kelamin';

    protected $fillable = [
        'nama',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'jenis_kelamin_id');
    }
}
