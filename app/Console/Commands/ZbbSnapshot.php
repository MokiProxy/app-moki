<?php

namespace App\Console\Commands;

use App\Services\Erkap\ZBBReviewService;
use Illuminate\Console\Command;

class ZbbSnapshot extends Command
{
    protected $signature = 'erkap:zbb-snapshot';

    protected $description = 'Membangun snapshot Zero Based Budgeting (prior year vs proposed) untuk semua RKAP';

    public function handle(): int
    {
        $summaries = ZBBReviewService::autoSnapshot();

        if (empty($summaries)) {
            $this->warn('Tidak ada RKAP yang ditemukan.');

            return self::SUCCESS;
        }

        foreach ($summaries as $summary) {
            $this->info(sprintf(
                'RKAP %s: %d pos (%d kenaikan, %d auto-skip) | prior year %s',
                $summary['year'],
                $summary['total'],
                $summary['increase'],
                $summary['skipped'],
                $summary['previous_year'] ?? '-'
            ));
        }

        $this->info('Snapshot ZBB selesai dibangun.');

        return self::SUCCESS;
    }
}