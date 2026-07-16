# Planning Implementasi Tombol Resolved

## Overview
Menambahkan tombol **Resolved** untuk teknisi (role_id 4) saat status tiket `IN_PROGRESS`. Tombol ini mengubah status tiket menjadi `RESOLVED` dengan history logging.

---

## Tahap 1: Backend — Controller

### 1.1 Modifikasi `TicketController::datatable()` (baris 71-91)
Ubah logic action column untuk role 4:
```php
if ($role == 4) {
    if ($row->status == "IN_PROGRESS") {
        // Tambah tombol Resolved di sini
        return '
        <div class="btn-group">
            <button class="btn btn-sm btn-info btn-view" data-id="' . $row->id . '" title="Detail"><i class="mdi mdi-eye"></i></button>
            <button class="btn btn-sm btn-success btn-resolved" data-id="' . $row->id . '" title="Resolved"><i class="mdi mdi-check-all"></i></button>
        </div>';
    } else {
        return '
        <div class="btn-group">
            <button class="btn btn-sm btn-info btn-view" data-id="' . $row->id . '" title="Detail"><i class="mdi mdi-eye"></i></button>
        </div>';
    }
}
```

### 1.2 Tambah Method `TicketController::resolved($id)`
Mirip dengan `approve()`, tapi status tujuan = `RESOLVED`:
```php
public function resolved($id)
{
    DB::beginTransaction();
    try {
        $ticket = Ticket::findOrFail($id);
        $oldStatus = $ticket->status;
        
        $ticket->update([
            'status' => 'RESOLVED',
            'resolved_at' => now(),
        ]);

        $this->historyService->statusChanged($ticket, $oldStatus, 'RESOLVED');

        DB::commit();
        return response()->json(['success' => true, 'message' => 'Tiket berhasil diselesaikan!']);
    } catch (Exception $err) {
        DB::rollBack();
        return response()->json(['success' => false, 'message' => $err->getMessage()]);
    }
}
```

---

## Tahap 2: Route

Tambahkan di `routes/web.php` dalam group helpdesk:
```php
Route::put('tickets/resolve/{id}', [HelpDeskTicketController::class, 'resolved'])->name('tickets.resolve');
```

---

## Tahap 3: Frontend — JavaScript

Tambahkan click handler `.btn-resolved` di `index.blade.php` (mirip dengan `.btn-approve`):
- Tampilkan SweetAlert konfirmasi
- AJAX PUT ke `helpdesk/tickets/resolve/{id}`
- Reload DataTable setelah sukses

---

## File yang Diubah

| File | Perubahan |
|------|-----------|
| `app/Http/Controllers/HelpDesk/TicketController.php` | Modifikasi `datatable()` action column + tambah method `resolved()` |
| `routes/web.php` | Tambah route `PUT tickets/resolve/{id}` |
| `resources/views/helpdesk/tickets/index.blade.php` | Tambah JS handler `.btn-resolved` |
