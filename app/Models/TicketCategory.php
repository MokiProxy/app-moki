<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Ticket;

class TicketCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "description"
    ];

    public function ticket() {
        $this->hasMany(Ticket::class, "id", "ticket_category_id");
    }
}
