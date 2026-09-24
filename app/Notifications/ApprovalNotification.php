<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ApprovalNotification extends Notification
{
    use Queueable;

    public $model;

    public $type;

    public $context;

    public function __construct($model, $type, $context = 'submitted')
    {
        $this->model = $model;
        $this->type = $type;
        $this->context = $context;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $typeLabel = \App\Services\ApprovalService::documentTypes()[$this->type]['label'] ?? $this->type;
        $title = \App\Services\ApprovalService::documentTypes()[$this->type]['title']($this->model) ?? $this->model->id;

        $message = match ($this->context) {
            'approved' => "Dokumen {$typeLabel} \"{$title}\" membutuhkan persetujuan level berikutnya.",
            'rejected' => "Dokumen {$typeLabel} \"{$title}\" telah ditolak.",
            default => "Dokumen {$typeLabel} \"{$title}\" membutuhkan persetujuan Anda.",
        };

        return [
            'type' => 'erkap_approval',
            'context' => $this->context,
            'document_type' => $this->type,
            'document_type_label' => $typeLabel,
            'document_id' => $this->model->id,
            'document_title' => $title,
            'message' => $message,
            'url' => route('erkap.approvals.show', [$this->type, $this->model->id]),
        ];
    }
}