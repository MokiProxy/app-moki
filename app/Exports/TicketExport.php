<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TicketExport implements FromCollection, WithHeadings, WithMapping
{
    protected $tickets;
    protected $row = 0;

    public function __construct($tickets)
    {
        $this->tickets = $tickets;
    }

    public function collection()
    {
        return $this->tickets;
    }

    public function headings(): array
    {
        return ['No', 'Nomor Tiket', 'Pemohon', 'Divisi', 'Judul', 'Kategori', 'Prioritas', 'Teknisi', 'Batas Waktu', 'Status', 'Rating'];
    }

    public function map($ticket): array
    {
        $this->row++;

        return [
            $this->row,
            $ticket->ticket_number,
            $ticket->requester->employee->name ?? '-',
            $ticket->requester->employee->division->name ?? '-',
            $ticket->title,
            $ticket->ticketCategory->name ?? '-',
            $ticket->ticketPriority->name ?? '-',
            $ticket->assignedTo->name ?? 'Belum Ditugaskan',
            $ticket->due_time,
            $ticket->status,
            $ticket->rating ?? '-',
        ];
    }
}
