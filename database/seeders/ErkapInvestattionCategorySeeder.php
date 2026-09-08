<?php

namespace Database\Seeders;

use App\Models\Erkap\InvestattionCategory;
use Illuminate\Database\Seeder;

class ErkapInvestattionCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            ['code' => 'SDU', 'name' => 'Strategic Delivery Unit'],
            ['code' => 'PSN', 'name' => 'Proyek Strategis Nasional'],
            ['code' => 'OTH', 'name' => 'Lainnya'],
        ];

        foreach ($categories as $category) {
            InvestattionCategory::create($category);
        }
    }
}