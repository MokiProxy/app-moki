# Planning Implementasi Assign Teknisi di Modal Detail Tiket

## Ringkasan

Di modal detail tiket (`#modal-ticket`) yang sudah ada, menambahkan dropdown untuk memilih user teknisi (`role_id = 4`) dan tombol "Pilih" untuk meng-assign teknisi ke tiket tersebut.

---

## Analisis Existing

### 1. Sistem Role Saat Ini

| Role ID | Nama | Konstanta di User Model |
|---------|------|------------------------|
| 1 | Super Admin | `User::ROLE_SUPERADMIN` |
| 2 | Admin / Approver | `User::ROLE_ADMIN` |
| 3 | Atasan / User / Staff | `User::ROLE_ATASAN` |

**Belum ada role_id = 4 (Teknisi).** Perlu ditambahkan.

### 2. Model User (`app/Models/User.php`)

- Kolom `role_id` di tabel `users` (integer, default: 3)
- Sudah ada relasi `assignedTo()`: `$this->hasMany(Ticket::class, "assigned_to")`

### 3. Model Ticket (`app/Models/Ticket.php`)

- Kolom `assigned_to` (bigint, FK ke `users.id`, nullable)
- Relasi `assignedTo()`: `belongsTo(User::class, "assigned_to", "id")`

### 4. Route yang Ada

- `GET helpdesk/tickets/{id}` → `TicketController@show` (ambil detail tiket)
- `PUT helpdesk/tickets/{id}` → `TicketController@update` (masih kosong)

### 5. Modal Detail Tiket (`index.blade.php`)

- Menampilkan informasi teknisi saat ini di badge `#tc-ticket_assignedto`
- Teknisi saat belum di-assign menampilkan "Teknisi Belum Ditugaskan" dengan badge merah
- Jika sudah di-assign, menampilkan nama user dengan badge hijau

---

## Spesifikasi Fitur

### Alur User

1. User membuka modal detail tiket
2. Di bagian "Teknisi" saat ini, ada dropdown untuk memilih user dengan role_id = 4 (Teknisi)
3. User memilih teknisi dari dropdown yang menampilkan nama user
4. User mengklik tombol "Pilih" untuk mengonfirmasi
5. Assign teknisi disimpan (update kolom `assigned_to` di tabel tickets + ubah status menjadi "ASSIGNED")
6. Modal menampilkan nama teknisi baru yang sudah di-assign
7. DataTable di-refresh

### Tampilan di Modal

```
┌──────────────────────────────────────┐
│  ...                                 │
│  [Teknisi Saat Ini: Budi (hijau)]    │
│                                      │
│  Assign Teknisi:                     │
│  [Dropdown teknisi ▼]  [Pilih]       │
│                                      │
│  ...                                 │
└──────────────────────────────────────┘
```

---

## Yang Harus Diimplementasikan

### Backend

| No | Item | Keterangan |
|----|------|------------|
| 1 | Tambah konstanta `ROLE_TEKNISI = 4` di `User` model | Role baru untuk teknisi |
| 2 | Tambah migration untuk update default role_id di tabel users & user_roles | Agar nanti bisa menyimpan role_id 4 |
| 3 | Tambah method `getTeknisi()` di `TicketController` | Endpoint untuk list users dengan role_id = 4 |
| 4 | Update method `update()` di `TicketController` | Simpan `assigned_to` + ubah status jadi "ASSIGNED" |
| 5 | Tambah route untuk ambil data teknisi & assign | `GET/POST` |

### Frontend

| No | Item | Keterangan |
|----|------|------------|
| 6 | Update modal view `index.blade.php` | Tambah dropdown select + tombol Pilih |
| 7 | Update JS `fillForm()` | Tampilkan dropdown teknisi |
| 8 | JS handler tombol Pilih | AJAX POST assign teknisi |
| 9 | Update JS after assign | Refresh data + DataTable |

---

## Detail Implementasi

### 1. User Model - Tambah Konstanta

```php
const ROLE_TEKNISI = 4;
```

### 2. Route (`web.php`)

```php
// Di dalam group helpdesk:
Route::get('tickets/teknisi', [HelpDeskTicketController::class, 'getTeknisi'])->name('tickets.teknisi');
```

### 3. TicketController - Method Baru

```php
use App\Models\User;

public function getTeknisi()
{
    $teknisi = User::where('role_id', User::ROLE_TEKNISI)
        ->orderBy('name')
        ->get(['id', 'name', 'email']);

    return response()->json(['success' => true, 'data' => $teknisi]);
}
```

### 4. TicketController - Update Method `update()`

```php
public function update(Request $request, $id)
{
    $request->validate([
        'assigned_to' => 'required|exists:users,id',
    ]);

    try {
        $ticket = Ticket::findOrFail($id);
        $ticket->update([
            'assigned_to' => $request->assigned_to,
            'status' => 'ASSIGNED',
        ]);

        return response()->json(['success' => true, 'message' => 'Teknisi berhasil ditugaskan!']);
    } catch (Exception $err) {
        return response()->json(['success' => false, 'message' => $err->getMessage()]);
    }
}
```

### 5. Modal View - HTML Tambahan

Di dalam modal body, setelah badge teknisi saat ini:

```html
<div class="mt-3" id="assign-teknisi-section">
    <hr>
    <h6 class="fw-bold">Assign Teknisi</h6>
    <div class="row g-2 align-items-center">
        <div class="col">
            <select class="form-control" id="select-teknisi">
                <option value="">-- Pilih Teknisi --</option>
            </select>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-primary" id="btn-assign-teknisi">Pilih</button>
        </div>
    </div>
</div>
```

### 6. JavaScript - Update fillForm()

```javascript
function fillForm(data) {
    // ... existing code ...

    // Load dropdown teknisi
    loadTeknisiDropdown(data.assigned_to);
}

function loadTeknisiDropdown(selectedId) {
    $.get("{{ route('helpdesk.tickets.teknisi') }}", function(res) {
        if (res.success) {
            var select = $('#select-teknisi');
            select.empty().append('<option value="">-- Pilih Teknisi --</option>');
            $.each(res.data, function(i, teknisi) {
                var isSelected = teknisi.id == selectedId ? 'selected' : '';
                select.append('<option value="' + teknisi.id + '" ' + isSelected + '>' + teknisi.name + '</option>');
            });
            // Sembunyikan section jika sudah ada teknisi
            if (selectedId) {
                $('#assign-teknisi-section').hide();
            } else {
                $('#assign-teknisi-section').show();
            }
        }
    });
}

// Tombol Assign Teknisi
$(document).on('click', '#btn-assign-teknisi', function() {
    var ticketId = $('#tc-ticket_number').data('id');
    var teknisiId = $('#select-teknisi').val();

    if (!teknisiId) {
        Swal.fire('Peringatan', 'Silakan pilih teknisi terlebih dahulu', 'warning');
        return;
    }

    $.ajax({
        url: "{{ url('helpdesk/tickets') }}/" + ticketId,
        type: "POST",
        data: {
            _token: CSRF_TOKEN,
            _method: 'PUT',
            assigned_to: teknisiId,
        },
        success: function(res) {
            if (res.success) {
                Swal.fire('Berhasil', res.message, 'success');
                $('#modal-ticket').modal('hide');
                table.ajax.reload();
            }
        },
        error: function(xhr) {
            Swal.fire('Error', xhr.responseJSON?.message || 'Error Sistem', 'error');
        }
    });
});
```

---

## Catatan Penting

### Role ID 4 (Teknisi) belum ada di sistem

Saat ini role yang terdefinisi hanya:

| ID | Nama |
|----|------|
| 1 | Super Admin |
| 2 | Admin / Approver |
| 3 | Atasan / User / Staff |

Karena kamu menyebutkan `role_id 4`, ada **dua opsi**:

**Opsi A (Rekomendasi):** Gunakan role_id yang sudah ada.
- Jika teknisi cukup pakai role_id = 3 (Staff/User yang sudah ada), filter di query menjadi `whereIn('role_id', [3])` atau buat kolom `is_teknisi` di tabel users.

**Opsi B (Sesuai permintaan):** Buat role_id = 4.
- Migration baru untuk mengubah default role_id constraint di tabel `users` dan `user_roles` (tambah opsi 4)
- Buat seeder untuk contoh user teknisi
- Tambah konstanta `ROLE_TEKNISI = 4` di `User` model

Planning ini menggunakan **Opsi B** (role_id = 4) sesuai permintaan.

---

## Struktur Folder yang Diubah

```
routes/
└── web.php                                 (modifikasi: tambah 2 route)

app/
├── Models/
│   └── User.php                            (modifikasi: tambah konstanta ROLE_TEKNISI)
└── Http/Controllers/HelpDesk/
    └── TicketController.php                (modifikasi: tambah getTeknisi() + update())

resources/views/helpdesk/tickets/
└── index.blade.php                         (modifikasi: tambah HTML + JS)
```

---

## Urutan Pengerjaan

| Step | Task | Dependency |
|------|------|-----------|
| 1 | Tambah konstanta `ROLE_TEKNISI = 4` di User model | - |
| 2 | Tambah route `tickets/teknisi` + `tickets/{id}` (PUT) | - |
| 3 | Tambah method `getTeknisi()` di TicketController | Step 1, 2 |
| 4 | Update method `update()` di TicketController | Step 2 |
| 5 | Update modal view `index.blade.php` - tambah HTML dropdown + tombol | - |
| 6 | Update JS `fillForm()` + event handler assign | Step 3, 4, 5 |
| 7 | Uji coba: view tiket tanpa teknisi, assign teknisi, verifikasi | All |
