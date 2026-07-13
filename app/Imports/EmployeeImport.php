<?php

namespace App\Imports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EmployeeImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Employee([
            'employee_id'   => $row['id_employee'],
            'name'          => $row['nama_lengkap'],
            'division_code' => $row['kode_divisi'],
            'jabatan'       => $row['jabatan'],
            'email'         => $row['email'],
            'hp'            => $row['hp'],
            'address'       => $row['alamat'],
            'division_id'   => $row['division_id'],
            'regional_id'   => $row['regional_id'],
        ]);
    }
}