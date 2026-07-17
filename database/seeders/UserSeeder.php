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
                'employee_id' => 'EMP0001',
            ],
            [
                'name'        => 'WAHID',
                'nopeg'       => '12345679',
                'email'       => 'admin@sistem.com',
                'password'    => Hash::make('password'),
                'role_id'     => User::ROLE_ADMIN,
                'employee_id' => 'EMP0002',
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
