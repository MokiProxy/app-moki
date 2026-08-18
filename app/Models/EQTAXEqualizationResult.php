<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EQTAXEqualizationResult extends Model
{
    use HasFactory;

    protected $table = "eqtax_equalization_results";

    protected $fillable = [
        "period",
        "entity",
        "no_faktur_pajak",
        "nama_penjual",
        "dpp_spt",
        "dpp_gl",
        "ppn_spt",
        "ppn_gl",
        "selisih_ppn",
        "status",
        "keterangan",
    ];

    public function scopePeriod($query, $period)
    {
        return $query->where('period', $period);
    }

    public function scopeEntity($query, $entity)
    {
        return $query->where('entity', $entity);
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public static function toPeriod($masaPajak, $tahun): string
    {
        $monthMap = [
            'Januari' => '01',
            'Februari' => '02',
            'Maret' => '03',
            'April' => '04',
            'Mei' => '05',
            'Juni' => '06',
            'Juli' => '07',
            'Agustus' => '08',
            'September' => '09',
            'Oktober' => '10',
            'November' => '11',
            'Desember' => '12',
        ];
        $month = $monthMap[$masaPajak] ?? '01';
        return "{$tahun}-{$month}";
    }
}
