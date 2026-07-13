<?php

namespace App\Imports;

use App\Models\Asset;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date; // Tambahkan ini untuk handle tanggal

class AssetImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Fungsi untuk menangani konversi tanggal dari Excel
        $purchaseDate = null;
        if (isset($row['purchase_date'])) {
            if (is_numeric($row['purchase_date'])) {
                // Jika formatnya angka serial Excel (45311)
                $purchaseDate = Date::excelToDateTimeObject($row['purchase_date'])->format('Y-m-d');
            } else {
                // Jika formatnya sudah string tanggal biasa (2024-01-20)
                $purchaseDate = date('Y-m-d', strtotime($row['purchase_date']));
            }
        }

        return new Asset([
            'category_id'     => $row['category_id'],
            'supplier_id'     => $row['supplier_id'],
            'regional_id'     => $row['regional_id'],
            'brand'           => $row['brand'],
            'serial_number'   => $row['serial_number'] ?? null,
            'uid'             => 'AST-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2))),
            'specification'   => $row['specification'],
            'production_year' => $row['production_year'] ?? null,
            'purchase_date'   => $purchaseDate ?? date('Y-m-d'),
            'purchase_price'  => $row['purchase_price'] ?? 0,
            'condition'       => $row['condition'] ?? 'baru',
            'status'          => 0, // Standby
        ]);
    }
}