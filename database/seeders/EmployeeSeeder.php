<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $employees = [
            // Employee For SBS Case
            ["nik" => "0595987493811", "name" => "Agung", "email" => "agung@satriabahana.co.id", "hp" => null, "jabatan" => "DIREKTUR UTAMA", "address" => null],
            ["nik" => "0595987493821", "name" => "Sahlul", "email" => "sahlul@satriabahana.co.id", "hp" => null, "jabatan" => "DIREKTUR PEMBINA", "address" => null],
            ["nik" => "0595987493831", "name" => "Fajriwan", "email" => "fajriwan@satriabahana.co.id", "hp" => null, "jabatan" => "SENIOR MANAGER MSI", "address" => null],
            ["nik" => "0595987493841", "name" => "Karmono", "email" => "karmono@satriabahana.co.id", "hp" => null, "jabatan" => "MANAGER MSI", "address" => null],
            ["nik" => "0595987493851", "name" => "Harmoko", "email" => "harmoko@satriabahana.co.id", "hp" => null, "jabatan" => "ASISTEN MANAGER INFRA DAN KEAMANAN SISTEM", "address" => null],
            ["nik" => "0595987493861", "name" => "Vita", "email" => "vita@satriabahana.co.id", "hp" => null, "jabatan" => "IT TEKNOLOGI", "address" => null],

            // Employee For Test Hierarchy
            ["nik" => "0595987493881", "name" => "Abimana CEO", "email" => "abimana.ceo@tpm-facility.com", "hp" => null, "jabatan" => "CEO", "address" => null],
            ["nik" => "0595987493882", "name" => "Bayu GMIT", "email" => "bayu.gmit@tpm-facility.com", "hp" => null, "jabatan" => "GMIT", "address" => null],
            ["nik" => "0595987493883", "name" => "Irsyad MGRDEV", "email" => "irsyad.mgrdev@tpm-facility.com", "hp" => null, "jabatan" => "MGRDEV", "address" => null],
            ["nik" => "0595987493884", "name" => "Humam SPVBE", "email" => "humam.spvbe@tpm-facility.com", "hp" => null, "jabatan" => "SPVBE", "address" => null],
            ["nik" => "0595987493885", "name" => "Wahid PGRBE", "email" => "wahid.pgrbe@tpm-facility.com", "hp" => null, "jabatan" => "PGRBE", "address" => null],
            ["name" => "MOKODEV", "email" => "superadmin@sistem.com", "hp" => "087893244578", "jabatan" => "SUPER ADMIN", "address" => null],
            ["name" => "HIDDEV", "email" => "admin@sistem.com", "hp" => "087893244578", "jabatan" => "ADMIN", "address" => null],

            ["name" => "Prasojo Utomo", "email" => "prasojo.utomo@tpm-facility.com", "hp" => null, "address" => null],
            ["name" => "Arvita Tiarawati", "email" => "arvitatiarawati@tpm-facility.com", "hp" => null, "address" => null],
            ["name" => "Rekha Kisnawaty", "email" => "rekha.kisnawaty@tpm-facility.com", "hp" => null, "address" => null],
            ["name" => "Cece Koswara", "email" => "koswara@tpm-security.com", "hp" => null, "address" => null],
            ["name" => "Tumbur M Manurung", "email" => "tumbur.manurung@tpm-security.com", "hp" => null, "address" => null],
            ["name" => "Iman Suherman", "email" => "iman.suherman@tpm-facility.com", "hp" => null, "address" => null],
            ["name" => "Ashafa Anggraeni", "email" => "ashafa.anggraeni@tpm-facility.com", "hp" => null, "address" => null],
            ["name" => "Riyadi", "email" => "riyadi@tpm-facility.com", "hp" => null, "address" => null],
            ["name" => "Depriana", "email" => "depriana@tpm-facility.com", "hp" => null, "address" => null],
            ["name" => "Ridho Ridwan", "email" => "ridho.ridwan@tpm-facility.com", "hp" => null, "address" => null],
            ["name" => "Irsyad Al Fahriza", "email" => "irsyad.alfahriza@mindotek.com", "hp" => null, "address" => null],
            ["name" => "Septi Dwi Rahayu", "email" => "septi.dwirahayu@tpm-facility.com", "hp" => null, "address" => null],
            ["name" => "Dimas Yogi Mugiarto", "email" => "dimas@tpm-facility.com", "hp" => null, "address" => null],
            ["name" => "Agung Rahayudi", "email" => "agung.rahayudi@tpm-facility.com", "hp" => null, "address" => null],
            ["name" => "Astrilia Hapsari", "email" => "astrilia.hapsari@tpm-facility.com", "hp" => null, "address" => null],
            ["name" => "Lilis Laeliya", "email" => "lilis.laeliya@tpm-facility.com", "hp" => null, "address" => null],
            ["name" => "Fitrian Ansori", "email" => "fitrian.ansori@tpm-facility.com", "hp" => null, "address" => null],
            ["name" => "Ade Sebastian", "email" => "ade.sebastian@tpm-security.com", "hp" => null, "address" => null],
            ["name" => "Leonardo Limeng", "email" => "leonardo.limeng@tpm-facility.com", "hp" => null, "address" => null],
            ["name" => "Ilham Taufik", "email" => "ilham.taufik@mindotek.com", "hp" => null, "address" => null],
            ["name" => "Risa Nurhanipah", "email" => "risa.nurhanipah@mindotek.com", "hp" => null, "address" => null],
            ["name" => "Tyas Anggraini", "email" => "tyas.anggraini@tpm-facility.com", "hp" => null, "address" => null],
            ["name" => "Keiko Angel Shantiony", "email" => "keiko.angelshantiony@tpm-facility.com", "hp" => null, "address" => null],
            ["name" => "Ahmad Fatoni", "email" => "ahmad.fatoni@mindotek.com", "hp" => null, "address" => null],
            ["name" => "Sri Rahayuningsih", "email" => "srirahayu@tpm-facility.com", "hp" => null, "address" => null],
            ["name" => "Desti Ayu Nandari", "email" => "desty@tpm-facility.com", "hp" => null, "address" => null],
            ["name" => "Tia Setiani Tasim", "email" => "tia.setiani@tpm-facility.com", "hp" => null, "address" => null],
            ["name" => "Nofrizal", "email" => "novrizal@tpm-security.com", "hp" => null, "address" => null],
            ["name" => "Fahmi Husaini", "email" => "fahmi.husaini@tpm-facility.com", "hp" => null, "address" => null],
            ["name" => "Astri Hutasoit", "email" => null, "hp" => null, "address" => null],
            ["name" => "Sentot Santoso", "email" => "sentot@tpm-security.com", "hp" => null, "address" => null],
            ["name" => "Purwanto", "email" => "purwanto@tpm-facility.com", "hp" => null, "address" => null],
            ["name" => "Evi", "email" => null, "hp" => null, "address" => null],
            ["name" => "Roni Kabo", "email" => null, "hp" => null, "address" => null],
            ["name" => "Erna Wahyu Winarsih", "email" => "erna.wedhyu@tpm-facility.com", "hp" => null, "address" => null],
            ["name" => "Endro Setyantono", "email" => "endro.setyantono@tpm-facility.com", "hp" => null, "address" => null],
            ["name" => "Angga Aditya Ramdani", "email" => "angga.aditya@mindotek.com", "hp" => null, "address" => null],
            ["name" => "Abdul Fatah", "email" => "abdul.fatah@tpm-facility.com", "hp" => null, "address" => null],
            ["name" => "Gatot Purnawan", "email" => "gatotpurnawan@tpm-facility.com", "hp" => null, "address" => null],
            ['name' => 'AGUSTINUS', "email" => null, "hp" => null, "address" => null],
            ['name' => 'WH BATAM', "email" => null, "hp" => null, "address" => null],
            ['name' => 'SALSADILA', "email" => null, "hp" => null, "address" => null],
            ['name' => 'APRILIA DHEA', "email" => null, "hp" => null, "address" => null],
            ['name' => 'YOGI S', "email" => null, "hp" => null, "address" => null],
            ['name' => 'RA FETTY', "email" => null, "hp" => null, "address" => null],
            ['name' => 'AHMAD MUZAKI', "email" => null, "hp" => null, "address" => null],
            ['name' => 'SITE - KOKAS', "email" => null, "hp" => null, "address" => null],
            ['name' => 'SITE - MEAINA', "email" => null, "hp" => null, "address" => null],
            ['name' => 'HERI', "email" => null, "hp" => null, "address" => null],
            ['name' => 'DANIEL', "email" => null, "hp" => null, "address" => null],
            ['name' => 'WH SUMBAGUT', "email" => null, "hp" => null, "address" => null],
            ['name' => 'SAEFUL', "email" => null, "hp" => null, "address" => null],
            ['name' => 'EKO', "email" => null, "hp" => null, "address" => null],
            ['name' => 'REY ISMU', "email" => null, "hp" => null, "address" => null],
            ['name' => 'SITE - PAKARTA', "email" => null, "hp" => null, "address" => null],
            ['name' => 'N/A', "email" => null, "hp" => null, "address" => null],
            ['name' => 'YOGA', "email" => null, "hp" => null, "address" => null],
            ['name' => 'PALEMBANG', "email" => null, "hp" => null, "address" => null],
            ['name' => 'YOGI', "email" => null, "hp" => null, "address" => null],
            ['name' => 'PEKANBARU', "email" => null, "hp" => null, "address" => null],
            ['name' => 'SITE - PERTAMINA CIREBON', "email" => null, "hp" => null, "address" => null],
            ['name' => 'SITE - PLAZA BRI SBY', "email" => null, "hp" => null, "address" => null],
            ['name' => 'YUDHA P', "email" => null, "hp" => null, "address" => null],
            ['name' => 'SITE - SEIBU', "email" => null, "hp" => null, "address" => null],
            ['name' => 'SITE - SOGO CENTRAL PARK', "email" => null, "hp" => null, "address" => null],
            ['name' => 'SITE - SOGO KOKAS', "email" => null, "hp" => null, "address" => null],
            ['name' => 'SITE - SOGO PIM', "email" => null, "hp" => null, "address" => null],
            ['name' => 'SITE - SOGO PS', "email" => null, "hp" => null, "address" => null],
            ['name' => 'SITE - SOGO TP SBY', "email" => null, "hp" => null, "address" => null],
            ['name' => 'SANDI R', "email" => null, "hp" => null, "address" => null],
            ['name' => 'ARIFFUDIN', "email" => null, "hp" => null, "address" => null],
            ['name' => 'HUSNI', "email" => null, "hp" => null, "address" => null],
            ['name' => 'Riki', "email" => null, "hp" => null, "address" => null],
            ['name' => 'Nahdo', "email" => null, "hp" => null, "address" => null],
            ['name' => 'WH JATIM', "email" => null, "hp" => null, "address" => null],
            ['name' => 'SITE - TARQ', "email" => null, "hp" => null, "address" => null],
            ['name' => 'TPM SBY', "email" => null, "hp" => null, "address" => null],
            ['name' => 'WH JAKARTA', "email" => null, "hp" => null, "address" => null],
            ['name' => 'EKA M', "email" => null, "hp" => null, "address" => null],
        ];

        foreach ($employees as $index => $data) {
            $empId = '202508' . $index + 1;

            // Menggunakan updateOrCreate agar aman dijalankan berkali-kali tanpa duplikasi data
            Employee::updateOrCreate(
                ['employee_id' => $empId], // Unique identifier
                [
                    'name'          => $data['name'],
                    'division_code' => $data['name'] != 'Abimana CEO' ? "IT" : "DIREKSI", // Default code
                    'division_id'   => $data['name'] != 'Abimana CEO' ? 2 : 1,    // Pastikan ID 1 ada di tabel divisions
                    'regional_id'   => 1,    // Pastikan ID 1 ada di tabel regionals
                    'email'         => $data['email'] ?? null,
                    'hp'            => $data['hp'] ?? null,
                    'address'       => $data['address'] ?? null,
                ]
            );
        }
    }
}
