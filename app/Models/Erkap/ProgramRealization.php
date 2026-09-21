<?php

namespace App\Models\Erkap;

use App\Models\Erkap\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramRealization extends Model
{
    use HasFactory, HasAuditTrail;

    protected $table = 'erkap_program_realizations';

    protected $fillable = [
        'erkap_work_program_id',
        'month',
        'year',
        'target',
        'realized',
        'percent_complete',
        'status',
        'notes',
        'evidence_url',
        'created_by',
        'updated_by',
    ];

    public function workProgram()
    {
        return $this->belongsTo(WorkProgram::class, 'erkap_work_program_id');
    }

    public function calculatePercentComplete(): void
    {
        $this->percent_complete = $this->target > 0
            ? round(($this->realized / $this->target) * 100, 2)
            : 0;

        if ($this->percent_complete >= 100) {
            $this->status = 'done';
        } elseif ($this->percent_complete <= 0) {
            $this->status = 'overdue';
        } else {
            $this->status = 'on_progress';
        }

        $this->save();
    }
}