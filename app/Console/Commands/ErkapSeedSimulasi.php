<?php

namespace App\Console\Commands;

use Database\Seeders\ErkapRkapSimulasiSeeder;
use Illuminate\Console\Command;

class ErkapSeedSimulasi extends Command
{
    protected $signature = 'erkap:seed-simulasi
        {--year= : Tahun RKAP (default: env ERKAP_SIM_YEAR / 2027)}
        {--division= : Nama divisi target (default: env ERKAP_SIM_DIVISION / SIMULASI-MS)}';

    protected $description = 'Seed simulasi alur RKAP lengkap satu divisi (tahap 0-7, idempoten)';

    public function handle(): int
    {
        $seeder = new ErkapRkapSimulasiSeeder;

        try {
            $summary = $seeder->run($this->option('year'), $this->option('division'));

            $this->info("RKAP {$summary['year']} ({$summary['division']}): status={$summary['rkap_status']}, phase={$summary['rkap_phase']}.");
            $this->info("work_programs={$summary['work_programs']}, routine_costs={$summary['routine_costs']}, investment_plans={$summary['investment_plans']}, zbb_reviews={$summary['zbb_reviews']}.");

            return self::SUCCESS;
        } catch (\Throwable $err) {
            $this->error($err->getMessage());
            $this->line($err->getFile().':'.$err->getLine());

            return self::FAILURE;
        }
    }
}