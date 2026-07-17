<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Tiket Helpdesk</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h2 { text-align: center; margin-bottom: 5px; }
        .meta { text-align: center; color: #666; font-size: 11px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; font-size: 10px; }
        th { background-color: #4f46e5; color: white; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .status { padding: 2px 8px; border-radius: 10px; color: white; font-size: 9px; font-weight: bold; display: inline-block; }
        .bg-primary { background-color: #4f46e5; }
        .bg-warning { background-color: #f59e0b; }
        .bg-success { background-color: #10b981; }
        .bg-danger { background-color: #ef4444; }
    </style>
</head>
<body>
    <h2>Laporan Tiket Helpdesk</h2>
    <p class="meta">Dicetak pada: {{ date('d F Y, H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nomor Tiket</th>
                <th>Pemohon</th>
                <th>Divisi</th>
                <th>Judul</th>
                <th>Kategori</th>
                <th>Prioritas</th>
                <th>Teknisi</th>
                <th>Batas Waktu</th>
                <th>Status</th>
                <th>Rating</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tickets as $ticket)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td><strong>{{ $ticket->ticket_number }}</strong></td>
                <td>{{ $ticket->requester->employee->name ?? '-' }}</td>
                <td>{{ $ticket->requester->employee->division->name ?? '-' }}</td>
                <td>{{ $ticket->title }}</td>
                <td>{{ $ticket->ticketCategory->name ?? '-' }}</td>
                <td>{{ $ticket->ticketPriority->name ?? '-' }}</td>
                <td>{{ $ticket->assignedTo->name ?? 'Belum Ditugaskan' }}</td>
                <td>{{ $ticket->due_time }}</td>
                <td>
                    @php
                        $bgClass = match($ticket->status) {
                            'OPEN' => 'bg-primary',
                            'ASSIGNED', 'PENDING', 'IN_PROGRESS' => 'bg-warning',
                            'RESOLVED', 'CLOSED' => 'bg-success',
                            'REJECTED' => 'bg-danger',
                            default => 'bg-primary'
                        };
                    @endphp
                    <span class="status {{ $bgClass }}">{{ $ticket->status }}</span>
                </td>
                <td>{{ $ticket->rating ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="11" style="text-align: center;">Tidak ada data tiket</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <p style="margin-top: 20px; font-size: 10px; color: #999; text-align: center;">
        Total: {{ $tickets->count() }} tiket
    </p>
</body>
</html>
