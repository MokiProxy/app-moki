# Planning Implementasi View File Attachment di Modal Detail Tiket

## Ringkasan

Di modal detail tiket (`#modal-ticket`) yang sudah ada, menambahkan section "File Attachment" dengan tombol folder yang bisa diklik untuk menampilkan daftar file attachment tiket tersebut. File PDF akan otomatis terbuka di tab baru (browser PDF reader), file lain (docx, xlsx, dll) akan otomatis terdownload.

---

## Analisis Existing

### 1. Modal Detail Tiket (`resources/views/helpdesk/tickets/index.blade.php`)

- Modal dengan id `#modal-ticket`
- Tombol view di DataTable memicu AJAX GET ke `helpdesk/tickets/{id}` via `TicketController@show`
- `TicketController@show` saat ini return data tiket **tanpa relasi attachments**
- Data diisi ke elemen-elemen seperti `#tc-ticket_number`, `#tc-ticket_title`, dll. via fungsi `fillForm(data)`

### 2. TicketController@show (perlu diubah)

```php
public function show($id)
{
    $ticket = ModelsTicket::with([
        "requester.employee.division",
        "assignedTo",
        "ticketCategory",
        "ticketPriority"
        // HARUS ditambah: "attachments"
    ])->find($id);
    return response()->json(['success' => true, 'data' => $ticket]);
}
```

### 3. TicketAttachment Model

```php
// Kolom: id, ticket_id, uploaded_by, file_name, file_path, mime_type, file_size
// Relasi: ticket()
// Storage: storage/app/public/ticket-attachments/
```

### 4. Tidak ada route untuk download file attachment

Saat ini attachment hanya bisa diupload, belum ada endpoint untuk download/view.

---

## Spesifikasi Fitur

### Tampilan di Modal Detail Tiket

Tambahkan section di dalam modal body:

```
┌─────────────────────────────────────┐
│ [Judul Tiket]                       │
│ ...                                 │
│                                     │
│ ─── File Attachment ───             │
│  📁 folder (nama folder) [Tombol]   │  ← folder tertutup
│                                     │
│ [Tutup]                             │
└─────────────────────────────────────┘
```

Setelah folder diklik:

```
┌─────────────────────────────────────┐
│ [Judul Tiket]                       │
│ ...                                 │
│                                     │
│ ─── File Attachment ───             │
│  📂 folder (nama folder) [Tombol]   │  ← folder terbuka
│    ├── 📄 document.pdf              │  ← klik → buka tab baru
│    ├── 📄 laporan.docx              │  ← klik → download
│    └── 📄 data.xlsx                 │  ← klik → download
│                                     │
│ [Tutup]                             │
└─────────────────────────────────────┘
```

### Behavior:

| Tipe File | Action |
|-----------|--------|
| `application/pdf` | Buka di tab baru browser (`window.open(url)`) |
| `application/vnd.openxmlformats-officedocument.wordprocessingml.document` (.docx) | Download |
| `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` (.xlsx) | Download |
| `application/vnd.openxmlformats-officedocument.presentationml.presentation` (.pptx) | Download |
| `image/jpeg`, `image/png`, `image/gif`, `image/webp` | Buka di tab baru |
| Lainnya (.zip, .rar, dll) | Download |

---

## Yang Harus Diimplementasikan

### Backend

| No | Item | Keterangan |
|----|------|------------|
| 1 | Tambah route download attachment | `GET /helpdesk/tickets/attachments/{id}/download` |
| 2 | Tambah method `download()` di `TicketAttachmentController` | Serve file untuk download/view |
| 3 | Update `TicketController@show` | Load relasi `attachments` |

### Frontend

| No | Item | Keterangan |
|----|------|------------|
| 4 | Update modal view (`index.blade.php`) | Tambah section file attachment di modal body |
| 5 | Update JS `fillForm()` | Render daftar attachment setelah folder dibuka |
| 6 | Update JS event `btn-view` | Load attachments setelah data tiket diterima |
| 7 | JS handler klik file | PDF/image → tab baru, lainnya → download |

---

## Detail Implementasi

### 1. Route (di `routes/web.php`)

```php
// Di dalam group helpdesk, tambah:
Route::get('tickets/attachments/{id}/download', [TicketAttachmentController::class, 'download'])->name('tickets.attachments.download');
```

### 2. TicketAttachmentController@download

```php
public function download($id)
{
    $attachment = TicketAttachment::findOrFail($id);
    $filePath = Storage::disk('public')->path($attachment->file_path);

    if (!file_exists($filePath)) {
        abort(404, 'File tidak ditemukan');
    }

    $mime = $attachment->mime_type;
    $isViewable = in_array($mime, [
        'application/pdf',
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
    ]);

    if ($isViewable) {
        return response()->file($filePath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . $attachment->file_name . '"'
        ]);
    }

    return response()->download($filePath, $attachment->file_name);
}
```

### 3. TicketController@show (update)

```php
$ticket = ModelsTicket::with([
    "requester.employee.division",
    "assignedTo",
    "ticketCategory",
    "ticketPriority",
    "attachments" // TAMBAH INI
])->find($id);
```

### 4. Modal View - HTML tambahan

Di dalam `index.blade.php` modal body, setelah section deskripsi/SLA:

```html
<div class="mt-3">
    <hr>
    <h6 class="fw-bold">
        File Attachment
        <button class="btn btn-sm btn-outline-secondary ms-2" id="btn-toggle-folder">
            <i class="mdi mdi-folder" id="folder-icon"></i> <span id="folder-label">Buka Folder</span>
        </button>
    </h6>
    <div id="attachment-list" class="ps-3" style="display: none;">
        <!-- Akan diisi oleh JavaScript -->
    </div>
</div>
```

### 5. JavaScript - update fillForm()

```javascript
function fillForm(data) {
    // ... existing code untuk ticket fields ...

    // Render attachment list
    renderAttachments(data.attachments || []);
}

function renderAttachments(attachments) {
    var container = $('#attachment-list');
    container.empty();

    if (attachments.length === 0) {
        container.html('<p class="text-muted small">Tidak ada file attachment</p>');
        return;
    }

    var html = '<ul class="list-unstyled m-0 p-0">';
    $.each(attachments, function(i, file) {
        var icon = getFileIcon(file.mime_type);
        var url = "{{ url('helpdesk/tickets/attachments') }}/" + file.id + "/download";
        html += '<li class="py-1">';
        html += '  <a href="' + url + '" class="text-decoration-none file-attachment-link" ';
        html += '     data-mime="' + file.mime_type + '" data-filename="' + file.file_name + '">';
        html += '    ' + icon + ' ' + file.file_name;
        html += '  </a>';
        html += '  <span class="text-muted small ms-2">(' + formatFileSize(file.file_size) + ')</span>';
        html += '</li>';
    });
    html += '</ul>';
    container.html(html);
}

function getFileIcon(mime) {
    if (mime === 'application/pdf') return '<i class="mdi mdi-file-pdf text-danger"></i>';
    if (mime.includes('word') || mime.includes('document')) return '<i class="mdi mdi-file-word text-primary"></i>';
    if (mime.includes('spreadsheet') || mime.includes('excel')) return '<i class="mdi mdi-file-excel text-success"></i>';
    if (mime.includes('presentation') || mime.includes('powerpoint')) return '<i class="mdi mdi-file-powerpoint text-warning"></i>';
    if (mime.includes('image')) return '<i class="mdi mdi-file-image text-info"></i>';
    if (mime.includes('zip') || mime.includes('rar')) return '<i class="mdi mdi-folder-zip"></i>';
    return '<i class="mdi mdi-file"></i>';
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    var k = 1024;
    var sizes = ['B', 'KB', 'MB', 'GB'];
    var i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
// Tombol toggle folder
$(document).on('click', '#btn-toggle-folder', function() {
    var list = $('#attachment-list');
    var icon = $('#folder-icon');
    var label = $('#folder-label');
    if (list.is(':visible')) {
        list.slideUp();
        icon.removeClass('mdi-folder-open').addClass('mdi-folder');
        label.text('Buka Folder');
    } else {
        list.slideDown();
        icon.removeClass('mdi-folder').addClass('mdi-folder-open');
        label.text('Tutup Folder');
    }
});
```

---

## Struktur Folder File yang Akan Diubah/Ditambah

```
routes/
└── web.php                                      (modifikasi: tambah route)

app/Http/Controllers/HelpDesk/
├── TicketController.php                         (modifikasi: show() tambah with attachments)
└── TicketAttachmentController.php               (modifikasi: tambah method download())

resources/views/helpdesk/tickets/
└── index.blade.php                              (modifikasi: tambah section attachment di modal + JS)
```

---

## Urutan Pengerjaan

| Step | Task | Dependency |
|------|------|-----------|
| 1 | Tambah route download attachment di `web.php` | - |
| 2 | Tambah method `download()` di `TicketAttachmentController` | Step 1 |
| 3 | Update `TicketController@show` load relasi attachments | - |
| 4 | Update modal view di `index.blade.php` - tambah HTML section attachment | - |
| 5 | Update JS `fillForm()` + event handler folder toggle & klik file | Step 2, 3, 4 |
| 6 | Uji coba: buka modal, klik folder, klik file PDF/docx | All |

---

## Catatan

- File attachment disimpan di `storage/app/public/ticket-attachments/`. Pastikan `php artisan storage:link` sudah dijalankan (symlink `public/storage`). Jika belum, jalankan.
- Karena pakai `response()->file()` untuk PDF/image (inline), pastikan tidak ada Content-Disposition yang memaksa download.
- Untuk link file di HTML, langsung pakai route controller agar file di-serve melalui Laravel (bukan akses langsung ke storage).
- Handle kondisi kosong: jika tiket tidak punya attachment, tampilkan pesan "Tidak ada file attachment".
