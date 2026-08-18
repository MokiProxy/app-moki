<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithTitle;

class PPNSingleSheetImport implements ToCollection, WithTitle
{
    protected string $sheetName;
    protected PPNSheetImport $parent;

    protected array $entityMap = [
        'PPNMO' => 'TJMO',
        'PPNHO' => 'SBHO',
        'PPNPLTR' => 'PLTR',
    ];

    public function __construct(string $sheetName, PPNSheetImport $parent)
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
        $entity = $this->entityMap[$this->sheetName] ?? $this->sheetName;

        foreach ($rows as $row) {
            $supplierNo = trim((string) ($this->sheetName == "PPNMO" ? $row[1] : $row[0]  ?? ''));

            // skip baris kosong / metadata / header
            if (empty($supplierNo) || empty($this->sheetName == "PPNMO" ? $row[2] : $row[1]) | !is_numeric($row[12] ?? null)) {
                continue;
            }

            $this->parent->result[] = [
                'sheet'         => $this->sheetName,
                'entity'        => $entity,
                'no_supplier'   => $supplierNo,
                'nama_supplier' => trim((string) ($this->sheetName == "PPNMO" ? $row[2] : $row[1] ?? '')),
                'jurnal_date'   => $this->sheetName == "PPNMO" ? $row[4] : $row[3] ?? null,
                'jurnal_no'     => trim((string) ($this->sheetName == "PPNMO" ? $row[6] : $row[5] ?? '')),
                'invoice_date'  => $this->sheetName == "PPNMO" ? $row[7] : $row[6] ?? null,
                'invoice_no'    => trim((string) ($this->sheetName == "PPNMO" ? $row[9] : $row[8] ?? '')),
                'invoice_item'  => trim((string) ($this->sheetName == "PPNMO" ? $row[10] : $row[9] ?? '')),
                'no_faktur_pajak'         => trim((string) ($this->sheetName == "PPNMO" ? $row[11] : $row[10] ?? '')),
                'dpp'           => $this->sheetName == "PPNMO" ? $row[12] : $row[11] ?? 0,
                'ppn'   => $this->sheetName == "PPNMO" ? $row[13] : $row[12] ?? 0,
                'keterangan'    => trim((string) ($this->sheetName == "PPNMO" ? $row[15] : $row[14] ?? '')),
            ];
        }
    }
}
