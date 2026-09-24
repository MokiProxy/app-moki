<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReportReady extends Notification
{
    use Queueable;

    public $report;

    public function __construct($report)
    {
        $this->report = $report;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'erkap_report',
            'context' => 'generated',
            'report_id' => $this->report->id,
            'title' => $this->report->title,
            'report_type' => $this->report->report_type,
            'format' => $this->report->format,
            'year' => $this->report->year,
            'message' => "Laporan \"{$this->report->title}\" ({$this->report->format}) siap diunduh.",
            'url' => route('erkap.reports.index'),
        ];
    }
}