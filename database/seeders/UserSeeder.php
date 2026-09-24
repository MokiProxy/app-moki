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
                'employee_id' => '20250812',
                'role'        => 'super-admin',
            ],
            [
                'name'        => 'WAHID',
                'nopeg'       => '12345679',
                'email'       => 'admin@sistem.com',
                'password'    => Hash::make('password'),
                'employee_id' => '20250813',
                'role'        => 'admin',
            ],
            [
                'name'        => 'Agung',
                'nopeg'       => '2025081',
                'email'       => 'agung@satriabahana.co.id',
                'password'    => Hash::make('password'),
                'employee_id' => '2025081',
                'role'        => ['staff', 'erkap-controller', 'erkap-admin'],
            ],
            [
                'name'        => 'Sahlul',
                'nopeg'       => '2025082',
                'email'       => 'sahlul@satriabahana.co.id',
                'password'    => Hash::make('password'),
                'employee_id' => '2025082',
                'role'        => ['staff', 'erkap-ppk', 'erkap-admin'],
            ],
            [
                'name'        => 'Fajriwan',
                'nopeg'       => '2025083',
                'email'       => 'lorem@satriabahana.co.id',
                'password'    => Hash::make('password'),
                'employee_id' => '2025083',
                'role'        => 'staff',
            ],
            [
                'name'        => 'Karmono',
                'nopeg'       => '2025084',
                'email'       => 'karmono@satriabahana.co.id',
                'password'    => Hash::make('password'),
                'employee_id' => '2025084',
                'role'        => 'staff',
            ],
            [
                'name'        => 'Harmoko',
                'nopeg'       => '2025085',
                'email'       => 'harmoko@satriabahana.co.id',
                'password'    => Hash::make('password'),
                'employee_id' => '2025085',
                'role'        => ["helpdesk-admin", "staff", 'erkap-cost-owner'],
            ],
            [
                'name'        => 'Vita',
                'nopeg'       => '2025086',
                'email'       => 'vita@satriabahana.co.id',
                'password'    => Hash::make('password'),
                'employee_id' => '2025086',
                'role'        => ["helpdesk-technician", "staff"],
            ],
            [
                'name'        => 'Rudi',
                'nopeg'       => '2025087',
                'email'       => 'rudi@satriabahana.co.id',
                'password'    => Hash::make('password'),
                'employee_id' => '2025087',
                'role'        => ["eqtax-user", "staff", 'erkap-cost-owner'],
            ],
            [
                'name'        => 'ErkapCost',
                'nopeg'       => '2025088',
                'email'       => 'erkapcost@satriabahana.co.id',
                'password'    => Hash::make('password'),
                'employee_id' => '2025088',
                'role'        => 'erkap-cost-owner',
            ],
            [
                'name'        => 'ErkapAdmin',
                'nopeg'       => '2025088',
                'email'       => 'erkapadmin@satriabahana.co.id',
                'password'    => Hash::make('password'),
                'employee_id' => '2025088',
                'role'        => 'erkap-admin',
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
