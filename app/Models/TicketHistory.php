<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\TicketAction;

class TicketHistory extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'action',
        'field_name',
        'old_value',
        'new_value',
        'description',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFormattedActionAttribute()
    {
        return ucwords(str_replace('_', ' ', strtolower($this->action)));
    }
}
