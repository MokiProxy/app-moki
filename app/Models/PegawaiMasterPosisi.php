<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PegawaiMasterPosisi extends Model
{
    protected $table = 'pegawai_master_posisi';

    protected $fillable = [
        'position_id',
        'superior_id',
        'pos_title',
        'last_mode_date',
        'last_mode_time',
    ];

    protected $primaryKey = 'position_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'superior_id', 'position_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'superior_id', 'position_id');
    }

    public function hierarchy(): HasOne
    {
        return $this->hasOne(MasterPegawaiHirarki::class, 'position_id', 'position_id');
    }
}
