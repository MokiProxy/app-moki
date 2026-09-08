<?php

namespace Database\Seeders;

use App\Models\Erkap\InvestationCriteria;
use Illuminate\Database\Seeder;

class ErkapInvestationCriteriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $criterias = [
            ['code' => 'A', 'name' => 'peningkatan pendapatan dan laba'],
            ['code' => 'B', 'name' => 'penugasan tetapi tidak merugikan'],
            ['code' => 'C', 'name' => 'peningkatan laba melalui usaha non core'],
            ['code' => 'D', 'name' => 'peningkatan kehandalan sistem dan efisiensi biaya'],
            ['code' => 'E', 'name' => 'sarana penunjang kebutuhan operasional'],
        ];

        foreach ($criterias as $criteria) {
            InvestationCriteria::create($criteria);
        }
    }
}