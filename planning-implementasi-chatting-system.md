# Planning Implementasi Chatting System untuk Ticket

## Overview
Sistem chatting/messaging di dalam modal detail ticket. Memungkinkan komunikasi langsung antara **pembuat tiket (requester)** dan **superadmin**. Menggunakan pendekatan **polling** (bukan WebSocket) untuk mensimulasikan realtime.

---

## Tahap 1: Database (Migration & Model)

### 1.1 Tabel `ticket_comments`
Buat migration `create_ticket_comments_table`:

| Field | Tipe | Keterangan |
|-------|------|------------|
| id | BIGINT (PK) | Auto-increment |
| ticket_id | UNSIGNED BIGINT | FK → tickets.id, ON DELETE CASCADE |
| user_id | UNSIGNED BIGINT | FK → users.id, ON DELETE SET NULL, nullable |
| comment | TEXT | Isi pesan |
| created_at | TIMESTAMP | Waktu kirim |
| updated_at | TIMESTAMP | Waktu edit |

### 1.2 Modifikasi Tabel `ticket_comments` — Kaitkan ke Attachment
Tambahkan kolom `ticket_attachment_id` (nullable) agar komentar bisa menyertakan file:

| Field | Tipe | Keterangan |
|-------|------|------------|
| ticket_attachment_id | UNSIGNED BIGINT | FK → ticket_attachments.id, nullable, ON DELETE SET NULL |

**Logika:** Saat user mengirim chat dengan file, file di-upload ke `ticket_attachments` dulu, lalu `ticket_attachment_id` disimpan di record `ticket_comments`.

### 1.3 Model `TicketComment`
Buat `app/Models/TicketComment.php`:
- **Fillable**: `ticket_id`, `user_id`, `comment`, `ticket_attachment_id`
- **Relations**:
  - `ticket()` → belongsTo(Ticket)
  - `user()` → belongsTo(User)
  - `attachment()` → belongsTo(TicketAttachment)

### 1.4 Relasi di Model `Ticket`
Tambahkan `comments()` hasMany(TicketComment) di `app/Models/Ticket.php`.

---

## Tahap 2: Backend — Controller & Routes

### 2.1 Controller `TicketCommentController`
Buat `app/Http/Controllers/HelpDesk/TicketCommentController.php`:

**Method `index($ticketId)`** — Ambil semua komentar (untuk load awal):
```
GET /helpdesk/tickets/{id}/comments
```
- Query `TicketComment::where('ticket_id', $id)->with('user', 'attachment')->orderBy('created_at', 'ASC')->get()`
- Return JSON

**Method `store(Request $request, $ticketId)`** — Kirim pesan baru:
```
POST /helpdesk/tickets/{id}/comments
```
- Validasi: `comment` required|string, `attachment` nullable|file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx
- Jika ada file upload → simpan ke `ticket_attachments` (path: `ticket-chat-attachments`), dapatkan ID-nya
- Buat record `TicketComment` dengan `ticket_attachment_id` jika ada file
- Return JSON comment baru (dengan eager-load user + attachment)
- **Authorization**: Hanya `requester_id` ticket atau `role_id == 1` (superadmin) yang boleh mengirim

### 2.2 Route
Tambahkan di `routes/web.php` dalam group helpdesk:
```php
Route::get('tickets/{id}/comments', [TicketCommentController::class, 'index'])->name('tickets.comments.index');
Route::post('tickets/{id}/comments', [TicketCommentController::class, 'store'])->name('tickets.comments.store');
```

---

## Tahap 3: Frontend — HTML (Modal Chat Section)

Lokasi: di dalam `#modal-ticket`, **sebelum** section Assign Teknisi (`#assign-teknisi-section`), setelah section Timeline.

### Struktur HTML:
```html
{{-- Chat Section --}}
<div class="mt-3">
    <hr>
    <h6 class="fw-bold">
        <i class="mdi mdi-chat-processing me-1"></i> Diskusi
    </h6>
    <div id="chat-container" style="max-height: 400px; overflow-y: auto;" class="border rounded p-3 bg-light">
        <div id="chat-messages"></div>
    </div>
    <div id="chat-attachment-preview" class="mt-2" style="display:none;">
        <span class="badge bg-info" id="chat-file-name"></span>
        <button type="button" class="btn btn-sm btn-outline-danger ms-1" id="btn-remove-chat-file">&times;</button>
    </div>
    <div class="input-group mt-2">
        <label class="input-group-text" for="chat-file-input" style="cursor:pointer;">
            <i class="mdi mdi-paperclip"></i>
        </label>
        <input type="file" id="chat-file-input" class="d-none" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx">
        <input type="text" id="chat-input" class="form-control" placeholder="Ketik pesan..." autocomplete="off">
        <button type="button" class="btn btn-primary" id="btn-send-chat">
            <i class="mdi mdi-send"></i>
        </button>
    </div>
</div>
```

### Posisi dalam modal (urutan dari atas):
1. Detail Tiket (nomor, judul, badge, deskripsi, SLA)
2. File Attachment (folder toggle)
3. Riwayat Aktivitas (timeline toggle)
4. **Diskusi/Chat** ← BARU
5. Assign Teknisi

---

## Tahap 4: Frontend — CSS (Bubble Chat)

Tambahkan di `<style>` yang sudah ada di `index.blade.php`:

```css
/* Chat bubbles */
.chat-bubble {
    max-width: 75%;
    padding: 10px 14px;
    border-radius: 16px;
    font-size: 0.9rem;
    word-wrap: break-word;
    position: relative;
    margin-bottom: 12px;
}
.chat-bubble-right {
    background-color: #0d6efd;
    color: #fff;
    margin-left: auto;
    border-bottom-right-radius: 4px;
}
.chat-bubble-left {
    background-color: #fff;
    color: #212529;
    border: 1px solid #dee2e6;
    margin-right: auto;
    border-bottom-left-radius: 4px;
}
.chat-avatar {
    width: 34px; height: 34px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 0.8rem; color: #fff; flex-shrink: 0;
}
.chat-meta {
    font-size: 0.72rem; opacity: 0.75; margin-top: 2px;
}
.chat-file-link {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 8px; border-radius: 8px; font-size: 0.8rem;
    text-decoration: none; margin-top: 4px;
}
.chat-bubble-right .chat-file-link { background: rgba(255,255,255,0.15); color: #fff; }
.chat-bubble-left .chat-file-link { background: #f0f0f0; color: #333; }
#chat-container { scroll-behavior: smooth; }
```

---

## Tahap 5: Frontend — JavaScript (Chat Logic)

### 5.1 Variabel State
```javascript
var currentTicketId = null;
var chatPollingTimer = null;
var lastCommentTimestamp = null;
var chatFile = null;
```

### 5.2 Fungsi `loadChatMessages(ticketId)`
- GET `/helpdesk/tickets/{id}/comments`
- Render semua pesan ke `#chat-messages`
- Set `lastCommentTimestamp` dari created_at terakhir
- Auto-scroll ke bawah

### 5.3 Fungsi `renderChatMessage(comment, isOwn)`
Parameter `isOwn` = true jika `comment.user_id == authUserId`.

**Struktur HTML per pesan:**
```html
<!-- Pesan orang lain (kiri) -->
<div class="d-flex mb-3 align-items-end">
    <div class="chat-avatar bg-secondary me-2">SA</div>
    <div>
        <div class="chat-bubble chat-bubble-left">
            Isi pesan...
            <!-- Jika ada attachment -->
            <a href="..." class="chat-file-link"><i class="mdi mdi-file"></i> nama_file.pdf</a>
        </div>
        <div class="chat-meta">Nama User &middot; 2 menit lalu</div>
    </div>
</div>

<!-- Pesan sendiri (kanan) -->
<div class="d-flex mb-3 align-items-end justify-content-end">
    <div class="text-end">
        <div class="chat-bubble chat-bubble-right">
            Isi pesan...
        </div>
        <div class="chat-meta">Anda &middot; baru saja</div>
    </div>
    <div class="chat-avatar bg-primary ms-2">WD</div>
</div>
```

### 5.4 Fungsi `sendChatMessage()`
- Ambil teks dari `#chat-input` + file dari `#chat-file-input`
- POST `/helpdesk/tickets/{id}/comments` dengan FormData (karena ada file)
- Jika berhasil:
  - Append pesan baru ke chat (render langsung, jangan reload semua)
  - Kosongkan input + reset file preview
  - Auto-scroll ke bawah
  - Update `lastCommentTimestamp`

### 5.5 Fungsi `getInitials(name)`
Ambil 2 huruf pertama dari nama user untuk avatar.

### 5.6 Polling Mechanism
```javascript
function startChatPolling(ticketId) {
    stopChatPolling();
    chatPollingTimer = setInterval(function() {
        // GET comments where created_at > lastCommentTimestamp
        // Jika ada data baru, append ke chat (jangan render ulang semua)
        // Update lastCommentTimestamp
    }, 5000); // setiap 5 detik
}

function stopChatPolling() {
    if (chatPollingTimer) {
        clearInterval(chatPollingTimer);
        chatPollingTimer = null;
    }
}
```

**Penting:** Endpoint GET comments perlu mendukung query parameter `since` (timestamp) agar polling hanya mengambil data baru, bukan semua data.

### 5.7 Integrasi dengan btn-view
Modifikasi handler `.btn-view`:
```javascript
$(document).on('click', '.btn-view', function() {
    var id = $(this).data('id');
    currentTicketId = id;
    $.get("{{ url('helpdesk/tickets') }}/" + id, function(res) {
        if (res.success) {
            fillForm(res.data);
            loadChatMessages(id);   // ← LOAD CHAT
            startChatPolling(id);   // ← MULAI POLLING
            // ... existing code
        }
    });
});
```

### 5.8 Hentikan Polling saat Modal Ditutup
```javascript
$('#modal-ticket').on('hidden.bs.modal', function () {
    stopChatPolling();
    currentTicketId = null;
});
```

---

## Tahap 6: Endpoint Polling dengan Filter `since`

Modifikasi `TicketCommentController@index` untuk mendukung parameter `since`:

```php
public function index($ticketId, Request $request)
{
    $query = TicketComment::where('ticket_id', $ticketId)
        ->with('user', 'attachment')
        ->orderBy('created_at', 'ASC');

    if ($request->has('since')) {
        $query->where('created_at', '>', $request->since);
    }

    return response()->json(['success' => true, 'data' => $query->get()]);
}
```

---

## Ringkasan File yang Diubah/Dibuat

| File | Status | Keterangan |
|------|--------|------------|
| `database/migrations/xxxx_create_ticket_comments_table.php` | **Baru** | Tabel ticket_comments |
| `database/migrations/xxxx_add_attachment_id_to_ticket_comments.php` | **Baru** | Kolom ticket_attachment_id |
| `app/Models/TicketComment.php` | **Baru** | Model TicketComment |
| `app/Models/Ticket.php` | **Diubah** | Tambah relasi `comments()` |
| `app/Http/Controllers/HelpDesk/TicketCommentController.php` | **Baru** | Controller chat (index + store) |
| `routes/web.php` | **Diubah** | Tambah 2 route comment |
| `resources/views/helpdesk/tickets/index.blade.php` | **Diubah** | Tambah HTML chat section + CSS + JS |

---

## Urutan Pengerjaan

1. **Migration** — Buat tabel `ticket_comments` + tambah kolom `ticket_attachment_id`
2. **Model** — Buat `TicketComment`, tambah relasi di `Ticket`
3. **Controller** — Buat `TicketCommentController` (index + store)
4. **Route** — Tambah route GET + POST comments
5. **HTML** — Tambah chat section di modal (sebelum assign teknisi)
6. **CSS** — Tambah styling bubble chat, avatar, file link
7. **JS: load + render** — Fungsi loadChatMessages + renderChatMessage
8. **JS: send** — Fungsi sendChatMessage + file upload
9. **JS: polling** — Fungsi startChatPolling + stopChatPolling
10. **Integrasi** — Modifikasi btn-view + modal close handler
