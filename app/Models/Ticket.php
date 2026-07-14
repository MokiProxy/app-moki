<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        "resolved_at",
        "closed_at",
        "created_at",
        "updated_at"
    ];

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class);
    }
}
