<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {


        User::create([
            'name'        => 'MOKODEV',
            'email'       => 'admin@admin.com',
            'password'    => Hash::make('password'),
            'role_id'     => User::ROLE_SUPERADMIN, // Otomatis jadi Superadmin (1)
            'employee_id' => null, // Admin utama biasanya tidak terikat data karyawan fisik
        ]);
    }
}