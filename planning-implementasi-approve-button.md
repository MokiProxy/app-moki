# Planning: Implementasi Tombol Approve pada Helpdesk Tickets

## Ringkasan

Tombol **Approve** pada halaman `/helpdesk/tickets` sudah dirender di dalam DataTable (kolom `action`), tetapi **belum berfungsi** karena:
1. Tidak ada JavaScript click handler untuk `.btn-approve` di `index.blade.php`
2. Endpoint `approve()` di `TicketController` memiliki **bug early return** (line 249) sehingga status tiket tidak pernah berubah

## Temuan Analisis

### Struktur Database (Tabel `tickets`)
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint | Primary Key |
| `ticket_number` | string | Format: `TIX-YYYYMMDD-NNNN` |
| `status` | enum | `OPEN`, `ASSIGNED`, `IN_PROGRESS`, `PENDING`, `RESOLVED`, `CLOSED`, `REJECTED` |
| `assigned_to` | bigint (FK) | User ID teknisi yang ditugaskan |
| `requester_id` | bigint (FK) | User ID pembuat tiket |
| `ticket_category_id` | bigint (FK) | Kategori tiket |
| `ticket_priority_id` | bigint (FK) | Prioritas tiket |

### Route yang Sudah Ada
```
PUT /helpdesk/tickets/approve/{id} -> TicketController@approve [helpdesk.tickets.approve]
```

### Tombol di DataTable (Controller line 76)
```html
<button class="btn btn-sm btn-warning btn-approve" data-id="{id}" title="Approve">
    <i class="mdi mdi-check"></i>
</button>
```
- Hanya ditampilkan untuk **role 4 (teknisi)**
- Atribut `data-id` sudah terisi dengan `$row->id`

### Bug di Controller (line 246-259)
```php
public function approve($id)
{
    try {
        return response()->json(['success' => true, 'message' => $id]); // <-- BUG: early return
        $ticket = Ticket::findOrFail($id);     // DEAD CODE
        $ticket->update(['status' => 'IN_PROGRESS']); // DEAD CODE
        return response()->json(['success' => true, 'message' => 'Tiket Berhasil Disetujui!']);
    } catch (Exception $err) {
        return response()->json(['success' => false, 'message' => $err->getMessage()]);
    }
}
```

### Handler JavaScript yang Ada
- `.btn-view` click handler ✅ (line 279)
- `#btn-assign-teknisi` click handler ✅ (line 291)
- `.btn-approve` click handler ❌ **TIDAK ADA**

---

## Implementation Plan

### Langkah 1: Fix Bug Early Return di Controller
**File:** `app/Http/Controllers/HelpDesk/TicketController.php`

Hapus baris 249 (`return response()->json(['success' => true, 'message' => $id]);`) agar kode update status dapat berjalan.

```php
public function approve($id)
{
    try {
        $ticket = Ticket::findOrFail($id);
        $ticket->update([
            'status' => 'IN_PROGRESS',
        ]);

        return response()->json(['success' => true, 'message' => 'Tiket Berhasil Disetujui!']);
    } catch (Exception $err) {
        return response()->json(['success' => false, 'message' => $err->getMessage()]);
    }
}
```

### Langkah 2: Tambah JavaScript Click Handler untuk `.btn-approve`
**File:** `resources/views/helpdesk/tickets/index.blade.php`

Tambahkan handler setelah handler `.btn-view` (sekitar line 288):

```javascript
// Tombol Approve
$(document).on('click', '.btn-approve', function() {
    var ticketId = $(this).data('id');

    Swal.fire({
        title: 'Approve Tiket?',
        text: "Tiket akan disetujui dan status berubah menjadi In Progress.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Approve!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "{{ url('helpdesk/tickets/approve') }}/" + ticketId,
                type: "POST",
                data: {
                    _token: CSRF_TOKEN,
                    _method: 'PUT',
                },
                success: function(res) {
                    if (res.success) {
                        Swal.fire('Berhasil', res.message, 'success');
                        table.ajax.reload();
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Error Sistem', 'error');
                }
            });
        }
    });
});
```

### Langkah 3 (Opsional): Hapus Attribute `id="btn-approve"` dari Button
**File:** `app/Http/Controllers/HelpDesk/TicketController.php` (line 76)

Karena `id` harus unik per halaman dan DataTable bisa memiliki banyak baris, attribute `id="btn-approve"` sebaiknya dihapus. Selector `.btn-approve` (class) sudah cukup.

```php
// Sebelum:
'<button class="btn btn-sm btn-warning btn-approve" data-id="' . $row->id . '" title="Approve" id="btn-approve">'

// Sesudah:
'<button class="btn btn-sm btn-warning btn-approve" data-id="' . $row->id . '" title="Approve">'
```

---

## File yang Diubah

| No | File | Perubahan |
|----|------|-----------|
| 1 | `app/Http/Controllers/HelpDesk/TicketController.php` | Hapus early return di method `approve()`, hapus `id="btn-approve"` |
| 2 | `resources/views/helpdesk/tickets/index.blade.php` | Tambah JS click handler untuk `.btn-approve` dengan SweetAlert confirmation |

## Alur Setelah Implementasi

```
User klik tombol Approve (btn-approve)
    ↓
SweetAlert confirmation dialog muncul
    ↓ [Konfirmasi]
AJAX PUT /helpdesk/tickets/approve/{ticket_id}
    ↓
Controller: find tiket, update status -> IN_PROGRESS
    ↓
Response JSON { success: true, message: "Tiket Berhasil Disetujui!" }
    ↓
SweetAlert success -> DataTable reload
```

## Testing Checklist

- [ ] Tombol approve hanya muncul untuk role 4 (teknisi)
- [ ] Klik tombol approve muncul dialog konfirmasi
- [ ] Setelah konfirmasi, status tiket berubah ke `IN_PROGRESS`
- [ ] DataTable otomatis reload setelah approve
- [ ] Error handling berfungsi jika tiket tidak ditemukan
- [ ] Role selain 4 tidak melihat tombol approve
