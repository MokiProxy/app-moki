<?php

namespace App\Services;

use App\Models\Ticket;

class MessageTemplates
{
    public static function newTicket(Ticket $ticket): string
    {
        $ticket->with(["requester.employee.division"]);
        return <<<TEXT
🔔 *TICKET BARU*

Halo Admin,

Sebuah tiket baru telah dibuat oleh *{$ticket->requester->name}*.

━━━━━━━━━━━━━━━━━━
📌 No. Ticket : {$ticket->ticket_number}
📝 Judul : {$ticket->title}
🏢 Pemohon : {$ticket->requester->name} - {$ticket->requester->employee->division->name}
📂 Kategori : {$ticket->ticketCategory->name}
⚡ Prioritas : {$ticket->ticketPriority->name}
⌚ SLA/Batas Waktu : {$ticket->sla} Jam / {$ticket->due_time}
📅 Dibuat : {$ticket->created_at->format('d M Y H:i')}
━━━━━━━━━━━━━━━━━━

Silakan login ke Helpdesk Management System untuk melakukan review dan assignment kepada Teknisi.

Terima kasih.
TEXT;
    }
    public static function reopen(Ticket $ticket): string
    {
        $ticket->with(["requester.employee.division"]);
        return <<<TEXT
🔔 *TICKET BARU*

Halo Admin,

Sebuah tiket telah dibuka kembali oleh *{$ticket->requester->name}*.

━━━━━━━━━━━━━━━━━━
📌 No. Ticket : {$ticket->ticket_number}
📝 Judul : {$ticket->title}
🏢 Pemohon : {$ticket->requester->name} - {$ticket->requester->employee->division->name}
📂 Kategori : {$ticket->ticketCategory->name}
⚡ Prioritas : {$ticket->ticketPriority->name}
⌚ SLA/Batas Waktu : {$ticket->sla} Jam / {$ticket->due_time}
📅 Dibuat : {$ticket->created_at->format('d M Y H:i')}
━━━━━━━━━━━━━━━━━━

Silakan login ke Helpdesk Management System untuk melakukan review dan assignment kepada Teknisi.

Terima kasih.
TEXT;
    }

    public static function assignToTeknisi(Ticket $ticket)
    {
        $ticket->with(["requester.employee.division", "assignedTo"])->first();
        return <<<TEXT
🔔 *TICKET BARU*

Halo {$ticket->assignedTo->name},

Anda telah ditugaskan untuk menangani tiket berikut.

━━━━━━━━━━━━━━━━━━
📌 No. Ticket : {$ticket->ticket_number}
📝 Judul : {$ticket->title}
🏢 Pemohon : {$ticket->requester->name} - {$ticket->requester->employee->division->name}
📂 Kategori : {$ticket->ticketCategory->name}
⚡ Prioritas : {$ticket->ticketPriority->name}
⌚ SLA/Batas Waktu : {$ticket->sla} Jam / {$ticket->due_time}
📅 Dibuat : {$ticket->created_at->format('d M Y H:i')}
━━━━━━━━━━━━━━━━━━

Silakan login ke Helpdesk Management System untuk menyetujui dan menindak lanjuti tiket tersebut.

Terima kasih.
TEXT;
    }

    public static function teknisiApproved(Ticket $ticket)
    {
        $ticket->with(["requester.employee.division", "assignedTo"])->first();
        return <<<TEXT
🔔 *TICKET BARU*

Halo {$ticket->requester->name},

Teknisi telah ditugaskan untuk menangani tiket anda di bawah ini:

━━━━━━━━━━━━━━━━━━
📌 No. Ticket : {$ticket->ticket_number}
📝 Judul : {$ticket->title}
🏢 Pemohon : {$ticket->requester->name} - {$ticket->requester->employee->division->name}
⚙️ Teknisi : {$ticket->assignedTo->name}
📂 Kategori : {$ticket->ticketCategory->name}
⚡ Prioritas : {$ticket->ticketPriority->name}
⌚ SLA/Batas Waktu : {$ticket->sla} Jam / {$ticket->due_time}
📅 Dibuat : {$ticket->created_at->format('d M Y H:i')}
━━━━━━━━━━━━━━━━━━

Periksa berkala status tiket, dan gunakan fitur chat di tiket untuk melakukan konsultasi atau mengajukan pertanyaan ke teknisi.

Terima kasih.
TEXT;
    }

    public static function teknisiResolved(Ticket $ticket)
    {
        $ticket->with(["requester.employee.division", "assignedTo"])->first();
        return <<<TEXT
🔔 *TICKET BARU*

Halo {$ticket->requester->name},

Teknisi telah menyelesaikan penanganan tiket anda di bawah ini:

━━━━━━━━━━━━━━━━━━
📌 No. Ticket : {$ticket->ticket_number}
📝 Judul : {$ticket->title}
🏢 Pemohon : {$ticket->requester->name} - {$ticket->requester->employee->division->name}
⚙️ Teknisi : {$ticket->assignedTo->name}
📂 Kategori : {$ticket->ticketCategory->name}
⚡ Prioritas : {$ticket->ticketPriority->name}
⌚ SLA/Batas Waktu : {$ticket->sla} Jam / {$ticket->due_time}
📅 Dibuat : {$ticket->created_at->format('d M Y H:i')}
━━━━━━━━━━━━━━━━━━

Tekan tombol centang di action tiket untuk menutup tiket atau tekan tombol re-open untuk membuka tiket kembali jika permasalahan belum benar-benar selesai.
Jangan lupa berikan rating untuk kinerja teknisi MSI SBS.

Terima kasih.
Salam Hangat, MSI SBS
TEXT;
    }

    public static function requesterConfirmed(Ticket $ticket)
    {
        $ticket->with(["requester.employee.division", "assignedTo"])->first();
        return <<<TEXT
🔔 *TICKET BARU*

Halo {$ticket->assignedTo->name},

Pemohon telah mengkonfirmasi penyelesaian tiket berikut:

━━━━━━━━━━━━━━━━━━
📌 No. Ticket : {$ticket->ticket_number}
📝 Judul : {$ticket->title}
🏢 Pemohon : {$ticket->requester->name} - {$ticket->requester->employee->division->name}
📂 Kategori : {$ticket->ticketCategory->name}
⚡ Prioritas : {$ticket->ticketPriority->name}
⌚ SLA/Batas Waktu : {$ticket->sla} Jam / {$ticket->due_time}
⭐ Rating : {$ticket->rating}/5
📅 Dibuat : {$ticket->created_at->format('d M Y H:i')}
━━━━━━━━━━━━━━━━━━

Terima kasih telah menyelesaikan tiket tersebut😊

Terima kasih.
TEXT;
    }
}
