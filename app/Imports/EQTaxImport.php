<?php

namespace App\Imports;

use App\Models\EQTAXCoretaxSPT;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EQTaxImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new EQTAXCoretaxSPT([
            "npwp_penjual" => $row['npwp_penjual'],
            "nama_penjual" => $row['nama_penjual'],
            "no_faktur_pajak" => $row['nomor_faktur_pajak'],
            "tgl_faktur_pajak" => $row['tanggal_faktur_pajak'],
            "masa_pajak" => $row['masa_pajak'],
            "tahun" => $row['tahun'],
            "masa_pajak_pengkreditan" => $row['masa_pajak_pengkreditkan'],
            "tahun_pajak_pengkreditan" => $row['tahun_pajak_pengkreditan'],
            "status_faktur" => $row['status_faktur'],
            "harga_jual" => $row['harga_jualpenggantiandpp'],
            "dpp" => $row['dpp_nilai_laindpp'],
            "ppn" => $row['ppn'],
            "ppnbm" => $row['ppnbm'],
            "perekam" => $row['perekam'],
            "referensi" => $row['referensi'],
            "no_sp2d" => $row['nomor_sp2d'],
            "valid" => $this->parseBoolean($row['valid']),
            "dilaporkan" => $this->parseBoolean($row['dilaporkan']),
            "dilaporkan_oleh_penjual" => $this->parseBoolean($row['dilaporkan_oleh_penjual']),
        ]);
    }

    private function parseBoolean($value): ?bool
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        $cleaned = strtoupper(trim((string) $value));
        $cleaned = str_replace(['=TRUE()', '=FALSE()', '=TRUE', '=FALSE', '()'], '', $cleaned);

        if ($cleaned === 'TRUE' || $cleaned === '1') {
            return true;
        }

        if ($cleaned === 'FALSE' || $cleaned === '0') {
            return false;
        }

        return null;
    }
}
