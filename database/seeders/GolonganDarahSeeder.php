<?php

namespace Database\Seeders;

use App\Models\GolonganDarah;
use Illuminate\Database\Seeder;

class GolonganDarahSeeder extends Seeder
{
    public function run(): void
    {
        $data = ['A', 'B', 'AB', 'O'];

        foreach ($data as $nama) {
            GolonganDarah::firstOrCreate(
                ['nama' => $nama]
            );
        }
    }
}
