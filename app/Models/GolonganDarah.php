<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GolonganDarah extends Model
{
    protected $table = 'golongan_darah';

    protected $fillable = [
        'nama',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'golongan_darah_id');
    }
}
