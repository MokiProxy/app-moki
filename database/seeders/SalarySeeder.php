<?php

namespace Database\Seeders;

use App\Models\Salary;
use Illuminate\Database\Seeder;

class SalarySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $salaries = [
            [
                "nopeg" => "20260001",
                "bulan" => "JAN",
                "tahun" => "2026"
            ],
            [
                "nopeg" => "20260002",
                "bulan" => "JAN",
                "tahun" => "2026"
            ],
            [
                "nopeg" => "20260003",
                "bulan" => "JAN",
                "tahun" => "2026"
            ],
            [
                "nopeg" => "20260004",
                "bulan" => "JAN",
                "tahun" => "2026"
            ],
        ];

        foreach ($salaries as $salary) {
            Salary::create($salary);
        }
    }
}
