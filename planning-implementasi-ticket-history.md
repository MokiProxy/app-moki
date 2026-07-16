# Planning Implementasi Ticket History

## Overview
Modul **Ticket History** akan mencatat setiap perubahan status atau data pada ticket sebagai audit trail yang tidak dapat diubah (immutable). Data hanya di-INSERT dan dibaca.

---

## Tahap 1: Fondasi (Model, Enum & Migration)

### 1.1 Buat Enum Actions
Buat file `app/Enums/TicketAction.php`.
Karena project menggunakan Laravel 8, kita bisa menggunakan PHP Enum (jika PHP >= 8.1) atau class constants biasa.
```php
<?php
namespace App\Enums;

enum TicketAction: string {
    case TICKET_CREATED = 'TICKET_CREATED';
    case ASSIGNED_AGENT = 'ASSIGNED_AGENT';
    case REASSIGNED_AGENT = 'REASSIGNED_AGENT';
    case STATUS_CHANGED = 'STATUS_CHANGED';
    case PRIORITY_CHANGED = 'PRIORITY_CHANGED';
    case CATEGORY_CHANGED = 'CATEGORY_CHANGED';
    case COMMENT_ADDED = 'COMMENT_ADDED';
    case ATTACHMENT_UPLOADED = 'ATTACHMENT_UPLOADED';
    case TICKET_RESOLVED = 'TICKET_RESOLVED';
    case TICKET_CLOSED = 'TICKET_CLOSED';
    case TICKET_REOPENED = 'TICKET_REOPENED';
}
```

### 1.2 Buat Model `TicketHistory`
Buat file `app/Models/TicketHistory.php`.
- **Fillable**: `ticket_id`, `user_id`, `action`, `field_name`, `old_value`, `new_value`, `description`.
- **Relations**:
  - `ticket()` -> belongsTo(Ticket::class)
  - `user()` -> belongsTo(User::class)
- **Method**: Tambahkan helper `getFormattedAction()` atau `getIcon()` untuk kebutuhan UI.

### 1.3 Buat Migration
Jalankan `php artisan make:migration create_ticket_histories_table`.
Isi schema:
```php
$table->id();
$table->uuid('ticket_id')->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
$table->uuid('user_id')->nullable()->foreign('user_id')->references('id')->on('users')->onDelete('set null');
$table->string('action', 100);
$table->string('field_name', 100)->nullable();
$table->text('old_value')->nullable();
$table->text('new_value')->nullable();
$table->text('description')->nullable();
$table->timestamps(); // created_at otomatis ada
```

---

## Tahap 2: Logic Service (TicketHistoryService)
Buat file `app/Services/TicketHistoryService.php`.
Service ini akan menjadi jantung dari logging aktivitas.

### Struktur Method
```php
class TicketHistoryService {
    public function log(Ticket $ticket, string $action, ?string $field = null, $old = null, $new = null, ?string $desc = null): TicketHistory
    {
        return TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'field_name' => $field,
            'old_value' => $old ? (string) $old : null,
            'new_value' => $new ? (string) $new : null,
            'description' => $desc,
        ]);
    }
    
    // Shortcut methods
    public function created(Ticket $ticket) { ... }
    public function assigned(Ticket $ticket, $oldAgent, $newAgent) { ... }
    public function statusChanged(Ticket $ticket, $oldStatus, $newStatus) { ... }
    // dll.
}
```

---

## Tahap 3: Integrasi ke Controller
Edit `app/Http/Controllers/HelpDesk/TicketController.php`. Inject `TicketHistoryService` ke constructor.

### 3.1 Metode `store` (Pembuatan Tiket)
- Bungkus logic `Ticket::create` dan `TicketAttachment::create` dalam `DB::transaction()`.
- Panggil `TicketHistoryService::created()` setelah tiket dibuat.
- Panggil `TicketHistoryService::log(...)` dengan action `ATTACHMENT_UPLOADED` di dalam loop attachment.

### 3.2 Metode `update` & `assignTeknisi`
- Logic saat ini: `$ticket->update(['assigned_to' => ..., 'status' => 'ASSIGNED'])`.
- Lakukan pengecekan: Jika `assigned_to` berubah:
  - Jika nilai awal kosong -> Action `ASSIGNED_AGENT`.
  - Jika nilai awal ada -> Action `REASSIGNED_AGENT`.
- Masukkan dalam `DB::transaction()`.

### 3.3 Metode `approve` (Status Change)
- Ambil `$oldStatus = $ticket->status`.
- Tentukan `$newStatus` baru.
- Update status.
- Panggil `TicketHistoryService::statusChanged()`.

---

## Tahap 4: Endpoint Timeline (Read)
Buat endpoint baru di `TicketController` atau buat controller baru `TicketHistoryController`.

### Route
```php
Route::get('/helpdesk/tickets/{id}/timeline', [TicketController::class, 'timeline'])->name('helpdesk.tickets.timeline');
```

### Logic `timeline` method
```php
public function timeline($id)
{
    $ticket = Ticket::findOrFail($id);
    $histories = TicketHistory::where('ticket_id', $id)
        ->with('user') // eager load user data
        ->orderBy('created_at', 'ASC')
        ->get();
        
    // Gabungkan dengan comments jika diperlukan
    return response()->json(['success' => true, 'data' => $histories]);
}
```

---

## Tahap 5: Testing
Buat file test `tests/Feature/TicketHistoryTest.php`.
- **Test Case 1**: Membuat tiket harus menghasilkan 1 record history dengan action `TICKET_CREATED`.
- **Test Case 2**: Mengubah status tiket harus menghasilkan record history dengan action `STATUS_CHANGED` dan data `old_value`/`new_value` yang benar.
- **Test Case 3**: Endpoint timeline mengembalikan data history yang terurut berdasarkan waktu.
