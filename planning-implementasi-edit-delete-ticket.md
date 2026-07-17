# Planning: Implementasi Edit & Delete Ticket (Soft Delete)

## Kondisi Saat Ini

- **Create form** (`resources/views/helpdesk/tickets/create.blade.php`): Form standalone dengan field title, description, category, priority, SLA, attachments. POST ke `helpdesk.tickets.store`.
- **Controller `update()`**: Hanya menangani `assigned_to` (assign teknisi), bukan edit konten tiket.
- **Controller `destroy()`**: Kosong (`//`).
- **Model `Ticket`**: Tidak menggunakan trait `SoftDeletes`, tidak ada kolom `deleted_at`.
- **Migration tickets table**: Tidak ada kolom `deleted_at`.

---

## 1. Edit Ticket (role_id=3, status OPEN)

### Route

```php
Route::get('tickets/{id}/edit', [HelpDeskTicketController::class, 'edit'])->name('tickets.edit');
Route::put('tickets/{id}/update-content', [HelpDeskTicketController::class, 'updateContent'])->name('tickets.update-content');
```

### Controller

**Method `edit($id)`:**
- Fetch ticket berdasarkan `$id`, pastikan `requester_id` = auth user dan `status` = OPEN
- Return view `helpdesk.tickets.create` dengan compact `$ticketCategories`, `$ticketPriorities`, `$ticket`

**Method `updateContent(Request $request, $id)`:**
- Validasi: `title` required, `ticket_category_id` required, `ticket_priority_id` required, `sla` required
- Fetch ticket, pastikan requester_id = auth user dan status = OPEN
- Update field: `title`, `description`, `ticket_category_id`, `ticket_priority_id`, `sla`, `due_time` (recalculate dari SLA baru)
- Log history via `$this->historyService->statusChanged()` atau buat method baru `contentUpdated()`
- Return JSON success

### View (`create.blade.php`)

Modifikasi agar form mendukung dual-mode (create & edit):

- Jika `$ticket` tersedia → mode edit:
  - Ganti heading "Buat Tiket Baru" → "Edit Tiket"
  - Pre-fill form fields dengan data `$ticket`
  - Set hidden field `ticket_id`
  - Ubah form action ke `{{ route('helpdesk.tickets.update-content', $ticket->id) }}`
  - Set method PUT via `_method` field
  - Set flag `data-mode="edit"` di form
- Jika `$ticket` tidak ada → mode create (tetap seperti sekarang)

Modifikasi JS submit handler:
- Cek `data-mode` form
- Jika `edit`: submit ke URL edit dengan PUT, redirect ke `/helpdesk` setelah sukses
- Jika `create`: tetap seperti sekarang

### Action Column (TicketController datatable)

Tambahkan tombol Edit untuk role_id=3 saat status OPEN:

```php
if ($row->status == "OPEN") {
    $buttons .= '<a href="' . url('helpdesk/tickets/' . $row->id . '/edit') . '" class="btn btn-sm btn-primary" title="Edit"><i class="mdi mdi-pencil"></i></a>';
    $buttons .= '<button class="btn btn-sm btn-danger btn-delete" data-id="' . $row->id . '" title="Hapus"><i class="mdi mdi-trash-can"></i></button>';
}
```

---

## 2. Delete Ticket — Soft Delete (role_id=3, status OPEN)

### Migration

Tambah kolom `deleted_at` ke tabel `tickets`:

```php
$table->softDeletes();
```

### Model (`Ticket.php`)

Tambahkan trait `SoftDeletes`:

```php
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;
    // ...
}
```

### Controller

**Method `destroy($id)`:**
- Fetch ticket (termasuk yang soft-deleted tidak perlu, cukup `findOrFail`)
- Pastikan `requester_id` = auth user dan `status` = OPEN
- `$ticket->delete()` (soft delete otomatis karena trait)
- Return JSON success

### Action Column

Tombol Delete (`.btn-delete`) dengan SweetAlert konfirmasi, hanya muncul untuk role_id=3 saat status OPEN.

### JS Handler

```javascript
$(document).on('click', '.btn-delete', function() {
    var ticketId = $(this).data('id');
    Swal.fire({
        title: 'Hapus Tiket?',
        text: "Tiket akan dihapus secara permanen.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Ya, Hapus!',
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "{{ url('helpdesk/tickets') }}/" + ticketId,
                type: "POST",
                data: { _token: CSRF_TOKEN, _method: 'DELETE' },
                success: function(res) { /* reload table */ },
            });
        }
    });
});
```

### DataTable Query

Pastikan query tidak mengambil tiket yang sudah soft-deleted. Karena pakai `Ticket::query()`, SoftDeletes otomatis filter out records dengan `deleted_at IS NOT NULL`.

---

## 3. Filter Semua Query Ticket — Sembunyikan Tiket yang Sudah Dihapus

### Prinsip

Setelah SoftDeletes diaktifkan, **semua query Eloquent** (`Ticket::query()`, `Ticket::where(...)`, `Ticket::find(...)`) **otomatis** hanya mengembalikan record yang `deleted_at = NULL`. Record yang sudah soft delete (ada nilai `deleted_at`) akan **dikecualikan** secara default.

### Lokasi Query yang Perlu Dipastikan

Semua lokasi berikut sudah menggunakan Eloquent, sehingga **tidak perlu ubahan kode** — SoftDeletes bekerja otomatis:

| # | Lokasi | Method | Penjelasan |
|---|---|---|---|
| 1 | `TicketController::datatable()` | `Ticket::with([...])->where(...)` | Data table tiket — otomatis filter |
| 2 | `TicketController::show($id)` | `Ticket::with([...])->find($id)` | Detail tiket — otomatis filter |
| 3 | `TicketController::updateContent()` | `Ticket::findOrFail($id)` | Edit tiket — otomatis filter |
| 4 | `TicketController::destroy($id)` | `Ticket::findOrFail($id)` | Hapus tiket — otomatis filter |
| 5 | `TicketController::approve()` | `Ticket::findOrFail($id)` | Approve tiket — otomatis filter |
| 6 | `TicketController::resolved()` | `Ticket::findOrFail($id)` | Resolved tiket — otomatis filter |
| 7 | `TicketController::reopen()` | `Ticket::findOrFail($id)` | Reopen tiket — otomatis filter |
| 8 | `TicketController::assignTeknisi()` | `Ticket::findOrFail($id)` | Assign teknisi — otomatis filter |
| 9 | `DashboardController::getTicketQuery()` | `Ticket::query()` / `Ticket::whereHas(...)` | Dashboard stats & charts — otomatis filter |

### Yang TIDAK Boleh Dilakukan

Jangan gunakan metode berikut karena akan **mengabaikan** soft delete:

- `Ticket::withTrashed()` — menampilkan termasuk yang sudah dihapus
- `Ticket::onlyTrashed()` — hanya menampilkan yang sudah dihapus
- `DB::table('tickets')` — query raw bypass SoftDeletes

### Kesimpulan

Tidak ada perubahan kode yang diperlukan untuk filter ini. SoftDeletes di Laravel bekerja secara **global scope** — semua query Eloquent otomatis menambahkan kondisi `WHERE deleted_at IS NULL`. Cukup pastikan tidak ada kode yang menggunakan `withTrashed()` atau `DB::table('tickets')`.

---

## Ringkasan Perubahan

| # | File | Aksi |
|---|---|---|
| 1 | `database/migrations/xxxx_add_soft_deletes_to_tickets_table.php` | **Baru** — tambah kolom `deleted_at` |
| 2 | `app/Models/Ticket.php` | Ubah — tambah trait `SoftDeletes` |
| 3 | `app/Http/Controllers/HelpDesk/TicketController.php` | Ubah — tambah `edit()`, `updateContent()`, isi `destroy()`, update `update()` method |
| 4 | `routes/web.php` | Ubah — tambah 3 route (edit, update-content, delete) |
| 5 | `resources/views/helpdesk/tickets/create.blade.php` | Ubah — dual-mode create/edit, pre-fill data, ubah JS submit |
| 6 | `resources/views/helpdesk/tickets/index.blade.php` | Ubah — tambah JS handler `.btn-delete` |

---

## Flow Edit

```
role_id=3 klik tombol Edit (pencil) → GET /helpdesk/tickets/{id}/edit
→ Form terbuka dengan data terpre-fill → Submit → PUT /helpdesk/tickets/{id}/update-content
→ Back to /helpdesk
```

## Flow Delete

```
role_id=3 klik tombol Delete (trash) → SweetAlert konfirmasi
→ DELETE /helpdesk/tickets/{id} → Soft delete → DataTable reload
```
