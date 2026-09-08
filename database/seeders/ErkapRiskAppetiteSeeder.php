<?php

namespace Database\Seeders;

use App\Models\Erkap\RiskAppetite;
use Illuminate\Database\Seeder;

class ErkapRiskAppetiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $appetites = [
            ['name' => 'Strategis'],
            ['name' => 'Konservatif'],
            ['name' => 'Averse'],
            ['name' => 'Moderat'],
        ];

        foreach($appetites as $appetite) {
            RiskAppetite::create($appetite);
        }
    }
}
