<?php

namespace App\Console\Commands;

use App\Models\Erkap\RKAP;
use App\Services\BudgetOpexConsolidationService;
use Illuminate\Console\Command;

class ConsolidateBudgetOpex extends Command
{
    protected $signature = 'erkap:consolidate-budget-opex {--year= : Tahun RKAP}';

    protected $description = 'Agregasi RoutineCost ke tabel BudgetOpex (konsolidasi operasional)';

    public function handle(BudgetOpexConsolidationService $service): int
    {
        $rkapQuery = RKAP::query();

        if ($year = $this->option('year')) {
            $rkapQuery->where('year', $year);
        }

        $rkapList = $rkapQuery->get();

        if ($rkapList->isEmpty()) {
            $this->error('Tidak ada RKAP yang ditemukan.');

            return self::FAILURE;
        }

        foreach ($rkapList as $rkap) {
            $count = $service->consolidate($rkap);
            $service->recalculateVariance($rkap);
            $this->info("RKAP {$rkap->year}: {$count} baris BudgetOpex dikonsolidasi.");
        }

        return self::SUCCESS;
    }
}