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
            ["name" => "Astri Hutasoit", "email" => null],
            ["name" => "Sentot Santoso", "email" => "sentot@tpm-security.com"],
            ["name" => "Purwanto", "email" => "purwanto@tpm-facility.com"],
            ["name" => "Evi", "email" => null],
            ["name" => "Roni Kabo", "email" => null],
            ["name" => "Erna Wahyu Winarsih", "email" => "erna.wahyu@tpm-facility.com"],
            ["name" => "Endro Setyantono", "email" => "endro.setyantono@tpm-facility.com"],
            ["name" => "Angga Aditya Ramdani", "email" => "angga.aditya@mindotek.com"],
            ["name" => "Abdul Fatah", "email" => "abdul.fatah@tpm-facility.com"],
            ["name" => "Gatot Purnawan", "email" => "gatotpurnawan@tpm-facility.com"],
            ['name' => 'AGUSTINUS'], ['name' => 'WH BATAM'], ['name' => 'SALSADILA'],
            ['name' => 'APRILIA DHEA'], ['name' => 'YOGI S'], ['name' => 'RA FETTY'],
            ['name' => 'AHMAD MUZAKI'], ['name' => 'SITE - KOKAS'], ['name' => 'SITE - MEAINA'],
            ['name' => 'HERI'], ['name' => 'DANIEL'], ['name' => 'WH SUMBAGUT'],
            ['name' => 'SAEFUL'], ['name' => 'EKO'], ['name' => 'REY ISMU'],
            ['name' => 'SITE - PAKARTA'], ['name' => 'N/A'], ['name' => 'YOGA'],
            ['name' => 'PALEMBANG'], ['name' => 'YOGI'], ['name' => 'PEKANBARU'],
            ['name' => 'SITE - PERTAMINA CIREBON'], ['name' => 'SITE - PLAZA BRI SBY'],
            ['name' => 'YUDHA P'], ['name' => 'SITE - SEIBU'], ['name' => 'SITE - SOGO CENTRAL PARK'],
            ['name' => 'SITE - SOGO KOKAS'], ['name' => 'SITE - SOGO PIM'], ['name' => 'SITE - SOGO PS'],
            ['name' => 'SITE - SOGO TP SBY'], ['name' => 'SANDI R'], ['name' => 'ARIFFUDIN'],
            ['name' => 'HUSNI'], ['name' => 'Riki'], ['name' => 'Nahdo'],
            ['name' => 'WH JATIM'], ['name' => 'SITE - TARQ'], ['name' => 'TPM SBY'],
            ['name' => 'WH JAKARTA'], ['name' => 'EKA M'],
        ];

        foreach ($employees as $index => $data) {
            Employee::create([
                'employee_id'   => 'EMP' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                'name'          => $data['name'],
                'division_code' => 'IT', // Default code
                'division_id'   => 1,    // Pastikan ID 1 ada di tabel divisions
                'regional_id'   => 1,    // Pastikan ID 1 ada di tabel regionals
                'email'         => $data['email'] ?? null,
            ]);
        }
    }
}