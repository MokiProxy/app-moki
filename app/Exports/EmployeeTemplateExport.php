<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;

class EmployeeTemplateExport implements WithHeadings
{
    public function headings(): array
    {
        return [
            'ID Employee',
            'Nama Lengkap',
            'Kode Divisi',
            'Jabatan',
            'Email',
            'HP',
            'Alamat',
            'Division_ID', // ID dari tabel divisions
            'Regional_ID', // ID dari tabel regionals
        ];
    }
}