<?php

namespace Database\Seeders;

use App\Models\JenisKelamin;
use Illuminate\Database\Seeder;

class JenisKelaminSeeder extends Seeder
{
    public function run(): void
    {
        $data = ['Laki-laki', 'Perempuan'];

        foreach ($data as $nama) {
            JenisKelamin::firstOrCreate(
                ['nama' => $nama]
            );
        }
    }
}
