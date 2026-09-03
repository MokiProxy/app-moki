<?php

namespace App\Imports;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class GLSheetImport implements ToCollection, WithTitle
{
    protected string $sheetName;
    protected GLImport $parent;

    public function __construct(string $sheetName, GLImport $parent)
    {
        $this->sheetName = $sheetName;
        $this->parent = $parent;
    }

    public function title(): string
    {
        return $this->sheetName;
    }

    public function collection(Collection $rows)
    {
        $result = [];

        foreach ($rows as $row) {
            $noFp = trim((string) ($row[11] ?? ''));

            // Lewati baris kosong, header tabel ("No FP"), Saldo Awal, Total Bulan Berjalan, dst.
            if ($noFp === '' || strcasecmp($noFp, 'No FP') === 0) {
                continue;
            }

            $result[] = [
                'sheet'           => $this->sheetName,
                'no_supplier'     => trim((string) ($row[1]  ?? '')),
                'nama_supplier'   => trim((string) ($row[2]  ?? '')),
                'jurnal_date'     => $this->formatDate($row[4]  ?? null),
                'jurnal_no'       => trim((string) ($row[6]  ?? '')),
                'invoice_date'    => $this->formatDate($row[7]  ?? null),
                'invoice_no'      => trim((string) ($row[9]  ?? '')),
                'invoice_item'    => trim((string) ($row[10] ?? '')),
                'no_faktur_pajak' => $noFp,
                'dpp'             => $this->parseNumber($row[12] ?? 0),
                'ppn'             => $this->parseNumber($row[13] ?? 0),
                'keterangan'      => trim((string) ($row[14] ?? '')),
            ];
        }

        $this->parent->result = array_merge($this->parent->result, $result);
    }

    protected function formatDate($value): ?string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return null;
        }

        // Format YYYYMMDD (misal "20260707")
        if (preg_match('/^\d{8}$/', $value)) {
            return $value;
        }

        // Nilai numerik dari Excel Date
        if (is_numeric($value)) {
            $conv = Date::excelToDateTimeObject((float) $value);
            return $conv->format('Ymd');
        }

        // Format tanggal lain
        try {
            return date('Ymd', strtotime($value));
        } catch (\Throwable $e) {
            return $value;
        }
    }

    protected function parseNumber($value): float
    {
        $clean = str_replace([',', ' '], '', trim((string) $value));

        if ($clean === '') {
            return 0;
        }

        return (float) $clean;
    }
}
