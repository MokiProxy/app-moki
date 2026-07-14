<?php

namespace Database\Seeders;

use App\Models\TicketCategory;
use Illuminate\Database\Seeder;

class TicketCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $ticketCategories = [
            [
                "name" => "Hardware",
                "description" => "Masalah terkait perangkat keras seperti komputer, printer, atau perangkat lainnya.",
            ],
            [
                "name" => "Software",
                "description" => "Masalah terkait perangkat lunak seperti aplikasi, sistem operasi, atau program lainnya.",
            ],
            [
                "name" => "Network",
                "description" => "Masalah terkait jaringan seperti koneksi internet, router, atau perangkat jaringan lainnya.",
            ],
            [
                "name" => "Account",
                "description" => "Masalah terkait akun pengguna seperti login, password, atau akses ke sistem.",
            ],
            [
                "name" => "Other",
                "description" => "Masalah lainnya yang tidak termasuk dalam kategori di atas.",
            ],
        ];

        foreach ($ticketCategories as $category) {
            TicketCategory::create($category);
        }
    }
}
