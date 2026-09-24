<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('erkap:consolidate-budget-opex')->monthlyOn(1, '01:00');
        $schedule->command('erkap:mark-overdue-risk-assessments')->dailyAt('00:05');
        $schedule->command('erkap:generate-scheduled-reports')->monthlyOn(1, '02:00');
        $schedule->command('erkap:zbb-snapshot')->yearlyOn(1, 1, '00:10');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
