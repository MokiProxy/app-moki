# Planning Implementasi Tampilan History di Detail Ticket

## Overview
Menampilkan data ticket history (timeline) di dalam modal detail ticket (`#modal-ticket`) pada `resources/views/helpdesk/tickets/index.blade.php`.

**Backend sudah tersedia:**
- Route: `GET helpdesk/tickets/{id}/timeline` → `helpdesk.tickets.timeline`
- Controller: `TicketController::timeline($id)` → return JSON `TicketHistory` ASC
- Model: `TicketHistory` dengan relasi `user`

**Yang perlu dibangun:** Frontend UI (HTML + AJAX + JS rendering + CSS).

---

## Tahap 1: Tambah HTML Section Timeline di Dalam Modal

Lokasi: `index.blade.php`, di dalam `<div class="modal-body p-4">` (sekitar baris 92-93), **setelah section attachment** dan **sebelum section assign teknisi**.

### Struktur HTML yang ditambahkan:
```html
{{-- Timeline Section --}}
<div class="mt-3">
    <h6 class="fw-bold">
        <i class="mdi mdi-history me-1"></i> Riwayat Aktivitas
        <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="btn-toggle-timeline">
            <i class="mdi mdi-clock-outline" id="timeline-icon"></i>
            <span id="timeline-label">Tampilkan Riwayat</span>
        </button>
    </h6>
    <div id="timeline-container" class="ps-3 mt-2" style="display: none;">
        <div id="timeline-list"></div>
    </div>
</div>
```

**Penempatan:** tepat sebelum `</div>` penutup `modal-body` (sebelum baris 108), atau setelah `</div>` penutup section `#attachment-list` (baris 92).

---

## Tahap 2: Tambah Fungsi JS `loadTimeline(ticketId)`

Di dalam `@section('plugin')`, tambahkan fungsi baru:

```javascript
function loadTimeline(ticketId) {
    var container = $('#timeline-list');
    container.html('<div class="text-center py-2"><div class="spinner-border spinner-border-sm text-primary"></div> Memuat riwayat...</div>');

    $.get("{{ url('helpdesk/tickets') }}/" + ticketId + "/timeline", function(res) {
        if (res.success && res.data.length > 0) {
            var html = '';
            $.each(res.data, function(i, item) {
                html += renderTimelineItem(item, i);
            });
            container.html(html);
        } else {
            container.html('<p class="text-muted small fst-italic">Belum ada riwayat aktivitas.</p>');
        }
    }).fail(function() {
        container.html('<p class="text-danger small">Gagal memuat riwayat.</p>');
    });
}
```

---

## Tahap 3: Tambah Fungsi JS `renderTimelineItem(item, index)`

Fungsi ini mengubah satu record history menjadi HTML timeline item. Setiap action memiliki ikon dan warna yang berbeda.

### Mapping Action → Icon & Warna:
| Action | Icon (MDI) | Warna |
|--------|-----------|-------|
| TICKET_CREATED | `mdi-plus-circle` | primary (biru) |
| ASSIGNED_AGENT | `mdi-account-plus` | info (cyan) |
| REASSIGNED_AGENT | `mdi-account-switch` | warning (kuning) |
| STATUS_CHANGED | `mdi-arrow-right-bold-circle` | secondary (abu) |
| PRIORITY_CHANGED | `mdi-flag` | warning (kuning) |
| CATEGORY_CHANGED | `mdi-tag` | primary (biru) |
| ATTACHMENT_UPLOADED | `mdi-paperclip` | success (hijau) |
| COMMENT_ADDED | `mdi-comment-text` | info (cyan) |
| TICKET_RESOLVED | `mdi-check-circle` | success (hijau) |
| TICKET_CLOSED | `mdi-close-circle` | secondary (abu) |
| TICKET_REOPENED | `mdi-refresh` | danger (merah) |

### Struktur HTML per item (vertical timeline):
```html
<div class="timeline-item d-flex mb-3">
    <div class="timeline-icon bg-{color} me-3">
        <i class="mdi mdi-{icon} text-white"></i>
    </div>
    <div class="timeline-content flex-grow-1">
        <div class="d-flex justify-content-between">
            <strong>{description atau action formatted}</strong>
            <small class="text-muted">{created_at formatted}</small>
        </div>
        <small class="text-muted">oleh {user.name}</small>
        {opsional: old_value → new_value jika ada}
    </div>
</div>
```

---

## Tahap 4: Tambah Custom CSS untuk Timeline

Di dalam `@section('css')` (belum ada di index.blade.php saat ini, perlu ditambahkan `@extends` section), atau inline di `<style>` di dalam `@section('plugin')`.

### CSS yang dibutuhkan:
```css
.timeline-item {
    position: relative;
    padding-left: 0;
}
.timeline-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 16px;
}
.timeline-content {
    border-left: 2px solid #e9ecef;
    padding-left: 15px;
    padding-bottom: 5px;
}
.timeline-item:last-child .timeline-content {
    border-left-color: transparent;
}
```

**Catatan:** Karena `index.blade.php` belum punya `@section('css')`, gunakan `<style>` langsung di dalam `@section('plugin')` sebelum `<script>`.

---

## Tahap 5: Integrasi dengan `btn-view` Click & `fillForm()`

### Modifikasi tombol `.btn-view` click handler (baris 279-288):
Tambahkan panggilan `loadTimeline(id)` setelah `fillForm(res.data)`:

```javascript
$(document).on('click', '.btn-view', function() {
    var id = $(this).data('id');
    $.get("{{ url('helpdesk/tickets') }}/" + id, function(res) {
        if (res.success) {
            fillForm(res.data);
            loadTimeline(id);  // <-- TAMBAHAN BARU
            $('#modal-ticket').modal('show');
        }
    });
});
```

---

## Tahap 6: Toggle Show/Hide Timeline

Tambahkan event handler untuk tombol `#btn-toggle-timeline` (mirip dengan toggle folder attachment yang sudah ada):

```javascript
$(document).on('click', '#btn-toggle-timeline', function() {
    var list = $('#timeline-container');
    var icon = $('#timeline-icon');
    var label = $('#timeline-label');
    if (list.is(':visible')) {
        list.slideUp();
        icon.removeClass('mdi-clock-outline').addClass('mdi-clock-outline');
        label.text('Tampilkan Riwayat');
    } else {
        list.slideDown();
        label.text('Sembunyikan Riwayat');
    }
});
```

---

## Ringkasan File yang Diubah

| File | Perubahan |
|------|-----------|
| `resources/views/helpdesk/tickets/index.blade.php` | Tambah HTML timeline section di modal, tambah JS `loadTimeline()`, `renderTimelineItem()`, toggle handler, CSS inline |

**Tidak ada perubahan backend** — endpoint timeline sudah tersedia.

---

## Urutan Pengerjaan

1. Tambah HTML `#timeline-container` section di dalam modal body (sebelum assign teknisi section)
2. Tambah CSS inline untuk timeline styling
3. Tambah fungsi `renderTimelineItem()` (mapping action → icon + warna + deskripsi)
4. Tambah fungsi `loadTimeline(ticketId)` (AJAX GET ke timeline endpoint)
5. Modifikasi `.btn-view` click handler → panggil `loadTimeline(id)` setelah `fillForm()`
6. Tambah toggle handler `#btn-toggle-timeline`
