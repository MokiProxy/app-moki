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
                "nopeg"       => '12345678',
                'email'       => 'admin@admin.com',
                'password'    => Hash::make('password'),
                'role_id'     => User::ROLE_SUPERADMIN,
                'employee_id' => null,
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
