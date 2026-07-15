<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterPegawaiHirarki extends Model
{
    protected $table = 'master_pegawai_hirarki';

    protected $fillable = [
        'position_id',
        'superior_1',
        'superior_2',
        'superior_3',
        'superior_4',
        'superior_5',
        'superior_6',
        'superior_7',
        'superior_8',
    ];

    protected $primaryKey = 'position_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public function position(): BelongsTo
    {
        return $this->belongsTo(PegawaiMasterPosisi::class, 'position_id', 'position_id');
    }
}
