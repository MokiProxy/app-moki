<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedAssetDeviceCompletion extends Model
{
    use HasFactory;

    protected $table = 'formit_fixed_asset_device_completions';

    protected $fillable = [
        'fixed_asset_borrowing_id',
        'uraian',
        'ada',
        'tidak_ada',
        'keterangan',
    ];

    protected $casts = [
        'ada' => 'boolean',
        'tidak_ada' => 'boolean',
    ];

    public function fixedAssetBorrowing()
    {
        return $this->belongsTo(FixedAssetBorrowing::class, 'fixed_asset_borrowing_id');
    }
}
