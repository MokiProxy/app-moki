<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        $users = [
            [
                'name'        => 'MOKODEV',
                'nopeg'       => '12345678',
                'email'       => 'superadmin@sistem.com',
                'password'    => Hash::make('password'),
                'role_id'     => User::ROLE_SUPERADMIN,
                'employee_id' => 'EMP0009',
            ],
            [
                'name'        => 'WAHID',
                'nopeg'       => '12345679',
                'email'       => 'admin@sistem.com',
                'password'    => Hash::make('password'),
                'role_id'     => User::ROLE_ADMIN,
                'employee_id' => 'EMP0008',
            ],
            [
                'name'        => 'Abimana CEO',
                'nopeg'       => '20260001',
                'email'       => 'abimana.ceo@tpm-facility.com',
                'password'    => Hash::make('password'),
                'role_id'     => User::ROLE_ATASAN,
                'employee_id' => 'EMP0001',
            ],
            [
                'name'        => 'Bayu GMIT',
                'nopeg'       => '20260002',
                'email'       => 'bayu.gmit@tpm-facility.com',
                'password'    => Hash::make('password'),
                'role_id'     => User::ROLE_ATASAN,
                'employee_id' => 'EMP0002',
            ],
            [
                'name'        => 'Irsyad MGRDEV',
                'nopeg'       => '20260003',
                'email'       => 'irsyad.mgrdev@tpm-facility.com',
                'password'    => Hash::make('password'),
                'role_id'     => User::ROLE_ATASAN,
                'employee_id' => 'EMP0003',
            ],
            [
                'name'        => 'Humam SPVBE',
                'nopeg'       => '20260004',
                'email'       => 'humam.spvbe@tpm-facility.com',
                'password'    => Hash::make('password'),
                'role_id'     => User::ROLE_ATASAN,
                'employee_id' => 'EMP0004',
            ],
            [
                'name'        => 'Wahid PGRBE',
                'nopeg'       => '20260005',
                'email'       => 'wahid.pgrbe@tpm-facility.com',
                'password'    => Hash::make('password'),
                'role_id'     => User::ROLE_ATASAN,
                'employee_id' => 'EMP0005',
            ],
        ];

        foreach ($users as $user) {
            User::firstOrCreate(
                ['nopeg' => $user['nopeg']],
                $user
            );
        }
    }
}
