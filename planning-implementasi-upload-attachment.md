# Planning: Implementasi Multi-Upload Attachment pada Form Buat Tiket

## 1. Analisis Kondisi Saat Ini

### Ringkasan Temuan

| Aspek | Status |
|-------|--------|
| Framework | Laravel 8.75, PostgreSQL, jQuery + Bootstrap 4 |
| Database `ticket_attachments` | Sudah ada dan terstruktur dengan baik |
| `TicketAttachment` model | Kosong (tanpa `$fillable`, tanpa relationships) |
| `Ticket` model | Tanpa relationships ke attachment |
| `TicketController::store()` | Tidak memproses file upload sama sekali |
| Frontend file upload | Input file ada tapi AJAX pakai `.serialize()` yang **tidak bisa mengirim file** |
| `TicketAttachmentController` | Seluruh method kosong (stub) |

### Bug yang Ditemukan

1. **`.serialize()` tidak meng-encode file** - AJAX submit di `create.blade.php:93` menggunakan `$(this).serialize()` yang secara teknis tidak dapat membaca data file input
2. **Tidak ada validasi file** di frontend maupun backend
3. **Tidak ada handler untuk menyimpan file** di controller `store()`
4. **Model `TicketAttachment`** belum memiliki `$fillable` sehingga tidak bisa mass-assign

### Database Schema `ticket_attachments` (Sudah Benar)

```
id              BIGINT PK
ticket_id       FK -> tickets.id (CASCADE)
uploaded_by     FK -> users.id (CASCADE)
file_name       STRING (original filename)
file_path       TEXT (path penyimpanan di disk)
mime_type       STRING (contoh: image/png)
file_size       BIGINT (bytes)
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

---

## 2. Spesifikasi Fitur

### Perilaku yang Diinginkan

- Form upload attachment awalnya menampilkan **1 input file** saja
- Terdapat tombol **"+ Tambah Attachment"** untuk menambah baris input file baru
- Setiap baris input file memiliki tombol **hapus (X)** untuk menghapus baris tersebut
- Tombol hapus tidak muncul jika hanya tersisa 1 baris input file
- Setiap input file menerima **1 file** (tidak menggunakan atribut `multiple`)
- User dapat memilih file berbeda untuk setiap baris
- File dikirim bersamaan dengan data tiket saat form disubmit
- Validasi file: tipe yang diizinkan (gambar, PDF, dokumen) dan ukuran maksimal
- File disimpan ke disk `public` dalam folder `ticket-attachments/`
- Record attachment dibuat di database `ticket_attachments` terkait tiket yang baru dibuat

---

## 3. Daftar File yang Perlu Diubah/Dibuat

### No. | File | Aksi | Prioritas

1. `app/Models/TicketAttachment.php` | **Edit** | Tinggi
2. `app/Models/Ticket.php` | **Edit** | Tinggi
3. `app/Http/Controllers/HelpDesk/TicketController.php` | **Edit** | Tinggi
4. `resources/views/helpdesk/tickets/create.blade.php` | **Edit** | Tinggi

> **Catatan**: Tidak perlu membuat migration baru karena `ticket_attachments` sudah ada.
> **Catatan**: Tidak perlu menambah route baru karena attachment di-handle inline di `TicketController::store()`.

---

## 4. Detail Implementasi per File

---

### 4.1 `app/Models/TicketAttachment.php`

**Aksi**: Edit (saat ini kosong total)

**Yang perlu ditambahkan:**
- `$fillable` array untuk semua kolom yang bisa di-mass-assign
- Relationship `belongsTo()` ke model `Ticket`
- Relationship `belongsTo()` ke model `User` (untuk `uploaded_by`)

```php
protected $fillable = [
    'ticket_id',
    'uploaded_by',
    'file_name',
    'file_path',
    'mime_type',
    'file_size',
];

public function ticket()
{
    return $this->belongsTo(Ticket::class);
}

public function uploader()
{
    return $this->belongsTo(User::class, 'uploaded_by');
}
```

---

### 4.2 `app/Models/Ticket.php`

**Aksi**: Edit

**Yang perlu ditambahkan:**
- Relationship `hasMany()` ke model `TicketAttachment`

```php
public function attachments()
{
    return $this->hasMany(TicketAttachment::class);
}
```

---

### 4.3 `app/Http/Controllers/HelpDesk/TicketController.php`

**Aksi**: Edit method `store()`

**Yang perlu diubah:**

#### a) Import
Tambahkan import:
```php
use App\Models\TicketAttachment;
use Illuminate\Support\Facades\Storage;
```

#### b) Validation rules
Tambahkan validasi untuk file attachment:
```php
'attachments'         => 'nullable|array',
'attachments.*'       => 'file|max:5120|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar',
```

#### c) Logic simpan attachment
Setelah tiket berhasil dibuat, proses setiap file dari `$request->file('attachments')`:

```php
if ($request->hasFile('attachments')) {
    foreach ($request->file('attachments') as $file) {
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->store('ticket-attachments', 'public');

        TicketAttachment::create([
            'ticket_id'   => $ticket->id,
            'uploaded_by' => auth()->id(),
            'file_name'   => $file->getClientOriginalName(),
            'file_path'   => $filePath,
            'mime_type'   => $file->getMimeType(),
            'file_size'   => $file->getSize(),
        ]);
    }
}
```

> **Note**: Nama file di-disk menggunakan `time() + original name` untuk menghindari konflik nama. Kolom `file_name` tetap menyimpan nama original.

---

### 4.4 `resources/views/helpdesk/tickets/create.blade.php`

**Aksi**: Edit (perubahan terbesar)

#### a) Struktur HTML: Ganti input file tunggal menjadi container dinamis

Sebelumnya (satu input `multiple`):
```html
<label class="form-label fw-bold">Upload Attachment</label>
<input type="file" name="attachments" id="tc-ticket_attachment" class="form-control" multiple>
```

Sesudahnya (container dinamis dengan tombol tambah):
```html
<label class="form-label fw-bold">Upload Attachment</label>
<div id="attachment-container">
    <div class="attachment-row input-group mb-2">
        <input type="file" name="attachments[]" class="form-control">
        <button type="button" class="btn btn-outline-danger btn-remove-attachment" title="Hapus">
            <i class="mdi mdi-close"></i>
        </button>
    </div>
</div>
<button type="button" class="btn btn-sm btn-outline-primary mt-1" id="btn-add-attachment">
    <i class="mdi mdi-plus"></i> Tambah Attachment
</button>
```

**Detail:**
- `name="attachments[]"` menggunakan format array agar Laravel menerima sebagai array
- Setiap baris (`.attachment-row`) berisi input file + tombol hapus
- Tombol hapus hanya visible jika jumlah baris > 1
- Tombol "Tambah Attachment" di bawah container

#### b) JavaScript: Tambah handler untuk tombol tambah/hapus

```javascript
// Tambah attachment
$('#btn-add-attachment').on('click', function() {
    var $container = $('#attachment-container');
    var $row = $container.find('.attachment-row:first').clone();
    $row.find('input').val(''); // Reset value file input
    $container.append($row);
    updateRemoveButtons();
});

// Hapus attachment
$(document).on('click', '.btn-remove-attachment', function() {
    $(this).closest('.attachment-row').remove();
    updateRemoveButtons();
});

// Update visibility tombol hapus
function updateRemoveButtons() {
    var count = $('#attachment-container .attachment-row').length;
    if (count > 1) {
        $('.btn-remove-attachment').show();
    } else {
        $('.btn-remove-attachment').hide();
    }
}
```

#### c) JavaScript: Ubah AJAX submit dari `.serialize()` ke `FormData`

Sebelumnya (tidak bisa kirim file):
```javascript
data: $(this).serialize(),
```

Sesudahnya (bisa kirim file):
```javascript
var formData = new FormData(this);
$.ajax({
    url: $(this).attr('action'),
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    success: function(res) { ... },
    error: function(xhr) { ... }
});
```

> **Penting**: `processData: false` dan `contentType: false` wajib ada agar browser tidak meng-encode FormData ke string.

---

## 5. Konstanta dan Validasi

| Aspek | Nilai |
|-------|-------|
| Ukuran maksimal per file | 5 MB (`max:5120` dalam KB) |
| Ekstensi yang diizinkan | `jpg, jpeg, png, gif, webp, pdf, doc, docx, xls, xlsx, ppt, pptx, zip, rar` |
| Penyimpanan di disk | `storage/app/public/ticket-attachments/` (via `Storage::disk('public')`) |
| Penamaan file di-disk | `{timestamp}_{original_name}` untuk hindari duplikasi |

---

## 6. Urutan Implementasi

| Langkah | Aksi | File |
|---------|------|------|
| 1 | Edit model `TicketAttachment` - tambah `$fillable` dan relationships | `app/Models/TicketAttachment.php` |
| 2 | Edit model `Ticket` - tambah relationship `attachments()` | `app/Models/Ticket.php` |
| 3 | Edit controller `TicketController::store()` - tambah validasi + logika simpan file | `app/Http/Controllers/HelpDesk/TicketController.php` |
| 4 | Edit view `create.blade.php` - ubah input file menjadi dinamis, perbaiki AJAX submit | `resources/views/helpdesk/tickets/create.blade.php` |
| 5 | Jalankan `php artisan storage:link` (jika belum) agar file di `storage/` bisa diakses via web | Terminal |
| 6 | Testing: buat tiket dengan attachment, cek file tersimpan dan database terisi | Browser + DB |

---

## 7. Risiko dan Catatan

1. **`php artisan storage:link`** harus dijalankan agar file di `storage/app/public` bisa diakses via URL publik. Cek apakah symlink `public/storage` sudah ada.
2. **Ukuran upload** tergantung konfigurasi `php.ini` (`upload_max_filesize` dan `post_max_size`). Pastikan minimal 5MB. Jika perlu ubah di `php.ini`:
   ```ini
   upload_max_filesize = 10M
   post_max_size = 12M
   ```
3. **Validasi sisi server** tetap penting meskipun ada validasi frontend, karena request bisa dimodifikasi.
4. **Tidak ada perubahan migration** - schema `ticket_attachments` sudah sesuai.
5. **Tidak ada route baru** - attachment di-handle langsung di `TicketController::store()` bersamaan dengan pembuatan tiket.
