<?php

namespace App\Models\Erkap;

use App\Models\Division;
use App\Models\Erkap\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KickoffAttendee extends Model
{
    use HasFactory, HasAuditTrail;

    protected $table = 'erkap_kickoff_attendees';

    protected $fillable = ['erkap_rkap_id', 'name', 'division_id', 'attended'];

    protected $casts = [
        'attended' => 'boolean',
    ];

    public function rkap(): BelongsTo
    {
        return $this->belongsTo(RKAP::class, 'erkap_rkap_id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'division_id');
    }
}