<?php

namespace App\Services;

use App\Enums\TicketAction;
use App\Models\Ticket;
use App\Models\TicketHistory;
use Illuminate\Support\Facades\Auth;

class TicketHistoryService
{
    /**
     * Log a history record for a ticket.
     */
    public function log(Ticket $ticket, string $action, ?string $field = null, $old = null, $new = null, ?string $desc = null): TicketHistory
    {
        return TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'field_name' => $field,
            'old_value' => $old ? (string) $old : null,
            'new_value' => $new ? (string) $new : null,
            'description' => $desc,
        ]);
    }

    public function created(Ticket $ticket): TicketHistory
    {
        return $this->log($ticket, TicketAction::TICKET_CREATED, null, null, null, 'Tiket berhasil dibuat');
    }

    public function assigned(Ticket $ticket, $oldAgentId, $newAgentId): TicketHistory
    {
        $action = is_null($oldAgentId) ? TicketAction::ASSIGNED_AGENT : TicketAction::REASSIGNED_AGENT;
        return $this->log($ticket, $action, 'assigned_to', $oldAgentId, $newAgentId);
    }

    public function statusChanged(Ticket $ticket, $oldStatus, $newStatus): TicketHistory
    {
        $desc = 'Status berubah dari ' . $oldStatus . ' ke ' . $newStatus;
        return $this->log($ticket, TicketAction::STATUS_CHANGED, 'status', $oldStatus, $newStatus, $desc);
    }

    public function priorityChanged(Ticket $ticket, $oldPriorityId, $newPriorityId): TicketHistory
    {
        return $this->log($ticket, TicketAction::PRIORITY_CHANGED, 'ticket_priority_id', $oldPriorityId, $newPriorityId);
    }

    public function categoryChanged(Ticket $ticket, $oldCategoryId, $newCategoryId): TicketHistory
    {
        return $this->log($ticket, TicketAction::CATEGORY_CHANGED, 'ticket_category_id', $oldCategoryId, $newCategoryId);
    }

    public function attachmentUploaded(Ticket $ticket, $fileName): TicketHistory
    {
        return $this->log($ticket, TicketAction::ATTACHMENT_UPLOADED, null, null, $fileName, 'Lampiran ' . $fileName . ' diunggah');
    }
    
    public function resolved(Ticket $ticket): TicketHistory
    {
        return $this->log($ticket, TicketAction::TICKET_RESOLVED, 'status', $ticket->getOriginal('status'), 'RESOLVED', 'Tiket telah diselesaikan');
    }

    public function closed(Ticket $ticket): TicketHistory
    {
        return $this->log($ticket, TicketAction::TICKET_CLOSED, 'status', $ticket->getOriginal('status'), 'CLOSED', 'Tiket telah ditutup');
    }
    
    public function reopened(Ticket $ticket): TicketHistory
    {
        return $this->log($ticket, TicketAction::TICKET_REOPENED, 'status', $ticket->getOriginal('status'), 'OPEN', 'Tiket telah dibuka kembali');
    }
}
