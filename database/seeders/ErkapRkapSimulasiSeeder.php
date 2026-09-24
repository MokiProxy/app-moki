<?php

namespace Database\Seeders;

use Database\Seeders\Erkap\Support\RkapSimulasi;
use Illuminate\Database\Seeder;

class ErkapRkapSimulasiSeeder extends Seeder
{
    /**
     * Seed simulasi alur RKAP lengkap satu divisi (tahap 0-7).
     *
     * @return array<string, int|string>
     */
    public function run(?string $year = null, ?string $division = null): array
    {
        $summary = RkapSimulasi::run($year ?? env('ERKAP_SIM_YEAR', RkapSimulasi::DEFAULT_YEAR), $division ?? env('ERKAP_SIM_DIVISION', RkapSimulasi::DEFAULT_DIVISION));

        if (isset($this->command)) {
            $this->command->info("Seeder simulasi RKAP {$summary['year']} ({$summary['division']}) selesai.");
            $this->command->info("RKAP #{$summary['rkap_id']}: status={$summary['rkap_status']}, phase={$summary['rkap_phase']}.");
            $this->command->info("WorkPrograms={$summary['work_programs']}, RoutineCosts={$summary['routine_costs']}, InvestmentPlans={$summary['investment_plans']}, ZBBReviews={$summary['zbb_reviews']}.");
        }

        return $summary;
    }
}