<?php

namespace App\Console\Commands;

use App\Models\Erkap\ReportItem;
use App\Models\Erkap\RKAP;
use App\Models\User;
use App\Notifications\ReportReady;
use App\Services\Reporting\ReportGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class GenerateScheduledReports extends Command
{
    protected $signature = 'erkap:generate-scheduled-reports';

    protected $description = 'Generate laporan terjadwal (bulanan, kuartalan, tahunan) sesuai frekuensi';

    public function handle(): int
    {
        $generator = app(ReportGenerator::class);
        $today = now();
        $periods = $this->periodsFor($today);

        if (empty($periods)) {
            $this->info('Tidak ada laporan yang jatuh tempo hari ini.');

            return self::SUCCESS;
        }

        $rkaps = RKAP::where('year', $today->year)->get();
        $generated = 0;

        foreach ($rkaps as $rkap) {
            foreach ($periods as $period) {
                foreach ($period['types'] as $reportType) {
                    $format = ($reportType === 'risk' && $period['key'] === 'monthly') ? 'excel' : 'pdf';
                    $title = "Laporan {$period['label']} {$reportType}";

                    try {
                        $path = $generator->store($reportType, [
                            'rkap' => $rkap,
                            'year' => $today->year,
                            'month' => $today->month,
                        ], $format);

                        $report = ReportItem::create([
                            'report_type' => $reportType,
                            'title' => $title,
                            'frequency' => $period['key'],
                            'year' => $today->year,
                            'month' => $today->month,
                            'format' => $format,
                            'file_path' => $path,
                            'status' => ReportItem::STATUS_GENERATED,
                            'created_by' => null,
                        ]);

                        $this->notifyReaders($report);
                        $generated++;
                    } catch (\Throwable $e) {
                        ReportItem::create([
                            'report_type' => $reportType,
                            'title' => $title,
                            'frequency' => $period['key'],
                            'year' => $today->year,
                            'month' => $today->month,
                            'format' => $format,
                            'status' => ReportItem::STATUS_FAILED,
                            'error' => $e->getMessage(),
                            'created_by' => null,
                        ]);

                        $this->error("Gagal generate {$reportType}: {$e->getMessage()}");
                    }
                }
            }
        }

        $this->info("{$generated} laporan terjadwal berhasil digenerate.");

        return self::SUCCESS;
    }

    protected function notifyReaders(ReportItem $report): void
    {
        $readers = User::whereHas('roles.permissions', fn ($q) => $q->where('name', 'erkap.reports.view'))
            ->orWhereHas('roles', fn ($q) => $q->where('name', 'super-admin'))
            ->get();

        Notification::send($readers, new ReportReady($report));
    }

    protected function periodsFor(\Illuminate\Support\Carbon $today): array
    {
        $periods = [
            'monthly' => [
                'key' => 'monthly',
                'label' => 'Bulanan',
                'day' => 1,
                'types' => ['realization', 'risk'],
            ],
            'quarterly' => [
                'key' => 'quarterly',
                'label' => 'Kuartalan',
                'day' => 1,
                'month_condition' => fn (int $month) => in_array($month, [1, 4, 7, 10], true),
                'types' => ['performance'],
            ],
            'annual' => [
                'key' => 'annual',
                'label' => 'Tahunan',
                'day' => 1,
                'month_condition' => fn (int $month) => $month === 12,
                'types' => ['rkap', 'financial'],
            ],
        ];

        return array_values(array_filter($periods, function (array $period) use ($today) {
            if ($today->day !== $period['day']) {
                return false;
            }

            if (isset($period['month_condition']) && ! $period['month_condition']($today->month)) {
                return false;
            }

            return true;
        }));
    }
}