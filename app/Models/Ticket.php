<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TicketComment;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        "ticket_number",
        "requester_id",
        "assigned_to",
        "ticket_category_id",
        "ticket_priority_id",
        "title",
        "description",
        "sla",
        "due_time",
        "status",
        "rating",
        "resolved_at",
        "closed_at",
        "created_at",
        "updated_at"
    ];

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class);
    }

    public function requester() {
        return $this->belongsTo(User::class, "requester_id", "id");
    }

    public function assignedTo() {
        return $this->belongsTo(User::class, "assigned_to", "id");
    }

    public function ticketCategory() {
        return $this->belongsTo(TicketCategory::class, "ticket_category_id", "id");
    }

    public function ticketPriority() {
        return $this->belongsTo(TicketPriority::class, "ticket_priority_id", "id");
    }

}
