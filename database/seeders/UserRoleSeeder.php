<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserRoleSeeder extends Seeder
{
    public function run()
    {
        $assignments = [
            'Fajriwan' => 'erkap-risk-manager',
            'Karmono' => 'erkap-accounting',
            'Vita' => 'erkap-auditor',
        ];

        foreach ($assignments as $name => $role) {
            $user = User::where('name', $name)->first();

            if ($user && ! $user->hasRole($role)) {
                $user->assignRole($role);
                $this->command?->info("Role {$role} ditambahkan ke {$user->name}.");
            }
        }
    }
}