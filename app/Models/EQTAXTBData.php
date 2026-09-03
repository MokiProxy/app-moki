<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EQTAXTBData extends Model
{
    use HasFactory;

    protected $table = 'eqtax_tb_data';

    protected $fillable = ['period', 'ppn_tb', 'keterangan'];

    public function scopePeriod($query, $period)
    {
        return $query->where('period', $period);
    }
}
