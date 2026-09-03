<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class SPTSingleSheetImport implements ToArray, WithTitle
{
    protected string $sheetName;
    protected SPTSheetImport $parent;

    public function __construct(string $sheetName, SPTSheetImport $parent)
    {
        $this->sheetName = $sheetName;
        $this->parent = $parent;
    }

    public function title(): string
    {
        return $this->sheetName;
    }

    public function array(array $rows)
    {
        if (empty($rows)) {
            return;
        }

        $header = $rows[0];

        $format = $this->detectFormat($header);

        if ($format === null) {
            return;
        }

        $map = match ($format) {
            'format2' => $this->mapFormat2($header),
            'format3' => $this->mapFormat3($header),
            default  => $this->mapFormat1($header),
        };

        if ($map['no_faktur_pajak'] === null) {
            return;
        }

        foreach (array_slice($rows, 1) as $row) {
            $record = $this->buildRecord($row, $map);

            if (empty($record['no_faktur_pajak']) && empty($record['npwp_penjual']) && empty($record['nama_penjual'])) {
                continue;
            }

            $this->parent->result[] = $record;
        }
    }

    protected function detectFormat(array $header): ?string
    {
        $headerText = implode(' ', array_map(fn($c) => $this->normalize($c), $header));

        if (str_contains($headerText, 'npwppembeli')) {
            return 'format2';
        }

        if (str_contains($headerText, 'nomordokumen')
            || str_contains($headerText, 'jenistransaksi')
            || str_contains($headerText, 'nila_tagihan')
            || str_contains($headerText, 'dibuatoleh')) {
            return 'format3';
        }

        if (str_contains($headerText, 'npwppenjual')) {
            return 'format1';
        }

        return null;
    }

    protected function normalize($value): string
    {
        return strtolower(preg_replace('/\s+/', '', (string) $value));
    }

    protected function findColumn(array $header, array $needles, $default = null)
    {
        foreach ($header as $index => $cell) {
            $hit = $this->normalize($cell);
            foreach ($needles as $needle) {
                if ($hit === $this->normalize($needle)) {
                    return $index;
                }
            }
        }
        return $default;
    }

    protected function mapFormat1(array $header): array
    {
        $col = function (array $needles) use ($header) {
            return $this->findColumn($header, $needles);
        };

        return [
            'npwp_penjual'            => $col(['NPWP Penjual']),
            'nama_penjual'            => $col(['Nama Penjual']),
            'no_faktur_pajak'         => $col(['Nomor Faktur Pajak']),
            'tgl_faktur_pajak'        => $col(['Tanggal Faktur Pajak']),
            'masa_pajak'              => $col(['Masa Pajak']),
            'tahun'                   => $col(['Tahun']),
            'masa_pajak_pengkreditan' => $col(['Masa Pajak Pengkreditkan', 'Masa Pajak Pengkreditan']),
            'tahun_pajak_pengkreditan' => $col(['Tahun Pajak Pengkreditan']),
            'status_faktur'           => $col(['Status Faktur']),
            'harga_jual'              => $col(['Harga Jual/Penggantian/DPP']),
            'dpp'                     => $col(['DPP Nilai Lain/DPP']),
            'ppn'                     => $col(['PPN']),
            'ppnbm'                   => $col(['PPnBM']),
            'penandatangan'         => $col(['Penandatangan']),
            'referensi'               => $col(['Referensi']),
            'no_sp2d'                 => $col(['Nomor SP2D']),
            'valid'                   => $col(['Valid']),
            'dilaporkan'              => $col(['Dilaporkan']),
            'dilaporkan_oleh_penjual' => $col(['Dilaporkan oleh Penjual']),
        ];
    }

    protected function mapFormat2(array $header): array
    {
        $col = function (array $needles) use ($header) {
            return $this->findColumn($header, $needles);
        };

        return [
            'npwp_penjual'            => $col(['NPWP Pembeli / Identitas lainnya', 'NPWP Pembeli']),
            'nama_penjual'            => $col(['Nama Pembeli']),
            'no_faktur_pajak'         => $col(['Nomor Faktur Pajak']),
            'tgl_faktur_pajak'        => $col(['Tanggal Faktur Pajak']),
            'masa_pajak'              => $col(['Masa Pajak']),
            'tahun'                   => $col(['Tahun']),
            'kode_transaksi'          => $col(['Kode Transaksi']),
            'status_faktur'           => $col(['Status Faktur']),
            'esign_status'            => $col(['ESignStatus']),
            'harga_jual'              => $col(['Harga Jual/Penggantian/DPP']),
            'dpp'                     => $col(['DPP Nilai Lain/DPP']),
            'ppn'                     => $col(['PPN']),
            'ppnbm'                   => $col(['PPnBM']),
            'perekam'                 => $col(['Perekam']),
            'referensi'               => $col(['Referensi']),
            'metode_input'            => $col(['Metode Input']),
            'dilaporkan_oleh_penjual' => $col(['Dilaporkan oleh', 'Dilaporkan oleh Penjual']),
            'is_show_clear_name'      => $col(['IsShowClearName']),
            'uraian'                  => $col(['Uraian']),
        ];
    }

    protected function mapFormat3(array $header): array
    {
        $col = function (array $needles) use ($header) {
            return $this->findColumn($header, $needles);
        };

        return [
            'npwp_penjual'            => $col(['NPWP Penjual']),
            'nama_penjual'            => $col(['Nama Penjual']),
            'no_faktur_pajak'         => $col(['Nomor Dokumen', 'Nomor Dokumen Pajak']),
            'tgl_faktur_pajak'        => $col(['Tanggal Dokumen']),
            'masa_pajak'              => $col(['Masa Pajak']),
            'tahun'                   => $col(['Tahun']),
            'masa_pajak_pengkreditan' => $col(['Masa Pajak Pengkreditkan', 'Masa Pajak Pengkreditan']),
            'tahun_pajak_pengkreditan' => $col(['Tahun Pajak Pengkreditan']),
            'harga_jual'              => $col(['Nilai Tagihan']),
            'dpp'                     => $col(['DPP']),
            'ppn'                     => $col(['PPN']),
            'ppnbm'                   => $col(['PPnBM']),
            'status_faktur'           => $col(['Status']),
            'valid'                   => $col(['Valid']),
            'dilaporkan'              => $col(['Dilaporkan']),
            'keterangan'              => $col(['Keterangan']),
            'perekam'                 => $col(['Perekam']),
            'jenis_transaksi'         => $col(['Jenis Transaksi']),
            'dibuat_oleh'             => $col(['Dibuat Oleh']),
            'is_show_clear_name'      => $col(['IsShowClearName']),
        ];
    }

    protected function buildRecord(array $row, array $map): array
    {
        $record = [
            'npwp_penjual'             => null,
            'nama_penjual'             => null,
            'no_faktur_pajak'          => null,
            'tgl_faktur_pajak'         => null,
            'masa_pajak'               => null,
            'tahun'                    => null,
            'entity'                   => $this->sheetName,
            'kode_transaksi'           => null,
            'masa_pajak_pengkreditan'  => null,
            'tahun_pajak_pengkreditan' => null,
            'status_faktur'            => null,
            'esign_status'             => null,
            'harga_jual'               => null,
            'dpp'                      => null,
            'ppn'                      => null,
            'ppnbm'                    => null,
            'penandatangan'          => null,
            'perekam'                  => null,
            'referensi'                => null,
            'metode_input'             => null,
            'uraian'                   => null,
            'is_show_clear_name'       => null,
            'no_sp2d'                  => null,
            'jenis_transaksi'          => null,
            'keterangan'               => null,
            'dibuat_oleh'              => null,
            'valid'                    => null,
            'dilaporkan'               => null,
            'dilaporkan_oleh_penjual'  => null,
        ];

        foreach ($map as $field => $index) {
            if ($index === null || !array_key_exists((int) $index, $row)) {
                continue;
            }

            $value = $row[(int) $index];

            if (is_null($value) || $value === '') {
                continue;
            }

            $record[$field] = $this->castValue($field, $value);
        }

        return $record;
    }

    protected function castValue(string $field, $value)
    {
        if (in_array($field, ['tgl_faktur_pajak'], true)) {
            return $this->toDate($value);
        }

        if (in_array($field, ['valid', 'dilaporkan', 'dilaporkan_oleh_penjual', 'is_show_clear_name'], true)) {
            return $this->toBoolean($value);
        }

        if (in_array($field, ['harga_jual', 'dpp', 'ppn', 'ppnbm'], true)) {
            return $this->toNumber($value);
        }

        return $this->toText($value);
    }

    protected function toText($value): ?string
    {
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }
        $text = trim((string) $value);
        return $text === '' ? null : $text;
    }

    protected function toNumber($value): int
    {
        if (is_int($value) || is_float($value)) {
            return (int) round($value);
        }
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        $cleaned = preg_replace('/[^0-9\\-]/', '', (string) $value);
        return (int) $cleaned;
    }

    protected function toBoolean($value): ?bool
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

    protected function toDate($value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        if (is_numeric($value)) {
            try {
                return Date::excelToDateTimeObject((float) $value)->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                return null;
            }
        }
        if (is_string($value) && trim($value) !== '') {
            $str = trim($value);
            if (strtolower($str) === 'null' || $str === '#n/a') {
                return null;
            }
            $timestamp = strtotime($str);
            if ($timestamp !== false) {
                return date('Y-m-d H:i:s', $timestamp);
            }
            try {
                return Date::excelToDateTimeObject((float) $str)->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                return null;
            }
        }
        return null;
    }
}
