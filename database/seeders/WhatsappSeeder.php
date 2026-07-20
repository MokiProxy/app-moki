<?php

namespace Database\Seeders;

use App\Models\Whatsapp;
use Illuminate\Database\Seeder;

class WhatsappSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Whatsapp::firstOrCreate([
            "key" => 'fonnte_api_key',
            "value" => '@FqXtUBQQLAMhE-ccMm7',
            "group" => 'whatsapp'
        ]);
    }
}
