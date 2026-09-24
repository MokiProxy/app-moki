<?php

namespace App\Console\Commands;

use App\Models\Erkap\RiskAssessmentMonthly;
use Illuminate\Console\Command;

class MarkOverdueRiskAssessments extends Command
{
    protected $signature = 'erkap:mark-overdue-risk-assessments';

    protected $description = 'Transisi otomatis status mitigasi risk assessment bulanan ke overdue saat target_date terlewati';

    public function handle(): int
    {
        $updated = RiskAssessmentMonthly::markAllOverdueIfDue();

        $this->info("{$updated} risk assessment bulanan ditandai sebagai overdue.");

        return self::SUCCESS;
    }
}