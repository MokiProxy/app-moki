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
                'employee_id' => 'EMP0006',
                'role'        => 'super-admin',
            ],
            [
                'name'        => 'WAHID',
                'nopeg'       => '12345679',
                'email'       => 'admin@sistem.com',
                'password'    => Hash::make('password'),
                'employee_id' => 'EMP0007',
                'role'        => 'admin',
            ],
            [
                'name'        => 'Abimana CEO',
                'nopeg'       => '20260001',
                'email'       => 'abimana.ceo@tpm-facility.com',
                'password'    => Hash::make('password'),
                'employee_id' => 'EMP0001',
                'role'        => 'staff',
            ],
            [
                'name'        => 'Bayu GMIT',
                'nopeg'       => '20260002',
                'email'       => 'bayu.gmit@tpm-facility.com',
                'password'    => Hash::make('password'),
                'employee_id' => 'EMP0002',
                'role'        => 'staff',
            ],
            [
                'name'        => 'Irsyad MGRDEV',
                'nopeg'       => '20260003',
                'email'       => 'irsyad.mgrdev@tpm-facility.com',
                'password'    => Hash::make('password'),
                'employee_id' => 'EMP0003',
                'role'        => 'staff',
            ],
            [
                'name'        => 'Humam SPVBE',
                'nopeg'       => '20260004',
                'email'       => 'humam.spvbe@tpm-facility.com',
                'password'    => Hash::make('password'),
                'employee_id' => 'EMP0004',
                'role'        => 'staff',
            ],
            [
                'name'        => 'Wahid PGRBE',
                'nopeg'       => '20260005',
                'email'       => 'wahid.pgrbe@tpm-facility.com',
                'password'    => Hash::make('password'),
                'employee_id' => 'EMP0005',
                'role'        => 'teknisi',
            ],
        ];

        foreach ($users as $item) {
            $role = $item['role'];
            unset($item['role']);

            $user = User::firstOrCreate(
                ['nopeg' => $item['nopeg']],
                $item
            );

            $user->assignRole($role);
        }
    }
}
