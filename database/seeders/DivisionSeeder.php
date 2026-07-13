<?php

namespace Database\Seeders;

use App\Models\Division;
use Illuminate\Database\Seeder;

class DivisionSeeder extends Seeder
{
    public function run()
    {
        $divisions = [
            ["name" => "LOGISTIK", "company_id" => 2, "regional_id" => 26],
            ["name" => "OFFICE SUPPORT", "company_id" => 1, "regional_id" => 1],
            ["name" => "OFFICE SUPPORT", "company_id" => 1, "regional_id" => 2],
            ["name" => "OFFICE SUPPORT", "company_id" => 1, "regional_id" => 3],
            ["name" => "BUSINESS DEVELOPMENT", "company_id" => 1, "regional_id" => 4],
            ["name" => "CLEANING SERVICE", "company_id" => 1, "regional_id" => 4],
            ["name" => "SEKRETARIS", "company_id" => 1, "regional_id" => 4],
            ["name" => "FINANCE", "company_id" => 1, "regional_id" => 4],
            ["name" => "HO", "company_id" => 1, "regional_id" => 4],
            ["name" => "HRD", "company_id" => 1, "regional_id" => 4],
            ["name" => "IT", "company_id" => 1, "regional_id" => 4],
            ["name" => "SECURITY", "company_id" => 1, "regional_id" => 4],
            ["name" => "OFFICE SUPPORT", "company_id" => 1, "regional_id" => 4],
            ["name" => "LOGISTIK", "company_id" => 2, "regional_id" => 4],
            ["name" => "KOMINFO", "company_id" => 5, "regional_id" => 4],
            ["name" => "IT", "company_id" => 7, "regional_id" => 4],
            ["name" => "OFFICE SUPPORT", "company_id" => 3, "regional_id" => 25],
            ["name" => "OFFICE SUPPORT", "company_id" => 1, "regional_id" => 5],
            ["name" => "SECURITY", "company_id" => 1, "regional_id" => 9],
            ["name" => "SECURITY", "company_id" => 1, "regional_id" => 10],
            ["name" => "LOGISTIK", "company_id" => 1, "regional_id" => 27],
            ["name" => "IMPLEMENTASI", "company_id" => 2, "regional_id" => 27],
            ["name" => "OFFICE SUPPORT", "company_id" => 5, "regional_id" => 6],
            ["name" => "SECURITY", "company_id" => 1, "regional_id" => 11],
            ["name" => "LOGISTIK", "company_id" => 1, "regional_id" => 28],
            ["name" => "IMPLEMENTASI", "company_id" => 2, "regional_id" => 28],
            ["name" => "OFFICE SUPPORT", "company_id" => 5, "regional_id" => 7],
            ["name" => "LOGISTIK", "company_id" => 1, "regional_id" => 7],
            ["name" => "SECURITY", "company_id" => 2, "regional_id" => 12],
            ["name" => "SECURITY", "company_id" => 1, "regional_id" => 13],
            ["name" => "OFFICE SUPPORT", "company_id" => 1, "regional_id" => 8],
            ["name" => "SECURITY", "company_id" => 1, "regional_id" => 14],
            ["name" => "SECURITY", "company_id" => 1, "regional_id" => 15],
            ["name" => "SECURITY", "company_id" => 1, "regional_id" => 16],
            ["name" => "SECURITY", "company_id" => 1, "regional_id" => 17],
            ["name" => "SECURITY", "company_id" => 1, "regional_id" => 18],
            ["name" => "SECURITY", "company_id" => 1, "regional_id" => 19],
            ["name" => "OFFICE SUPPORT", "company_id" => 1, "regional_id" => 22],
            ["name" => "OFFICE SUPPORT", "company_id" => 1, "regional_id" => 23],
            ["name" => "OFFICE SUPPORT", "company_id" => 1, "regional_id" => 24],
            ["name" => "LOGISTIK", "company_id" => 1, "regional_id" => 24],
            ["name" => "SECURITY", "company_id" => 2, "regional_id" => 20],
            ["name" => "SECURITY", "company_id" => 1, "regional_id" => 21],
            ["name" => "LOGISTIK", "company_id" => 1, "regional_id" => 29],
            ["name" => "OFFICE SUPPORT", "company_id" => 2, "regional_id" => 25],
            ["name" => "OFFICE SUPPORT", "company_id" => 1, "regional_id" => 25],
        ];

       foreach ($divisions as $data) {
        Division::create([
            'name'         => $data['name'],
            'abbreviation' => $data['abbreviation'] ?? '-', 
            'company_id'   => $data['company_id'],
            'regional_id'  => $data['regional_id'],
            'code'         => strtoupper(substr($data['name'], 0, 3)) . rand(100, 999), // Menghasilkan kode otomatis seperti LOG123
        ]);
        }
    }
}