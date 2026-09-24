<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RkapLifecycleNotification extends Notification
{
    use Queueable;

    public $rkap;

    public $context;

    public function __construct($rkap, $context = 'distributed')
    {
        $this->rkap = $rkap;
        $this->context = $context;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $message = match ($this->context) {
            'distributed' => "RKAP {$this->rkap->year} telah didistribusikan dan ditetapkan pada tanggal {$this->rkap->resolution_date}.",
            default => "Perubahan fase lifecycle untuk RKAP {$this->rkap->year}.",
        };

        return [
            'type' => 'erkap_rkap_lifecycle',
            'context' => $this->context,
            'rkap_id' => $this->rkap->id,
            'year' => $this->rkap->year,
            'phase' => $this->rkap->phase,
            'phase_label' => $this->rkap->phaseLabel(),
            'message' => $message,
            'url' => route('erkap.rkap.show', $this->rkap->id),
        ];
    }
}