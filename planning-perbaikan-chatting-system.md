# Planning Perbaikan Chatting System

## Analisis Bug & Issues

### Bug 1: Pesan duplikat (terkirim berulang)
**Root cause:** Setelah `sendChatMessage` berhasil, pesan di-append ke DOM + `lastCommentTimestamp` diupdate. Tapi polling berikutnya (tiap 5 detik) bisa masih mengambil pesan yang sama karena timestamp `>` belum tentu akurat (precision issue / race condition).

**Fix:**
- Tambah flag `chatSending` — saat true, polling skip
- Setelah send success, set `lastCommentTimestamp` + `chatSending = false`
- Di `pollNewMessages`, skip jika `chatSending === true`

### Bug 2: Akses chatting terbatas
**Root cause:** Controller hanya cek `$ticket->requester_id != $authUserId && $userRoleId != 1`. Hanya requester dan superadmin yang bisa akses.

**Fix:** Ubah authorize check di `TicketCommentController@store`:
- `role_id == 1` (superadmin) → boleh
- `role_id == 4` (teknisi) → boleh
- `role_id == 2` (staff/admin) → boleh
- `requester_id` tiket → boleh
- Selain itu → tolak

### Bug 3: Bubble chat terlalu kecil
**Root cause:** `.chat-bubble` punya `max-width: 75%` tapi parent `<div>` tidak punya width constraint, sehingga bubble collapse.

**Fix:**
- Tambah `.chat-bubble { display: inline-block; width: fit-content; }`
- Tambah `min-width` atau pastikan parent punya `max-width`

---

## File yang Diubah

| File | Perubahan |
|------|-----------|
| `app/Http/Controllers/HelpDesk/TicketCommentController.php` | Ubah authorize check (tambah role 2, 4) |
| `resources/views/helpdesk/tickets/index.blade.php` | Fix CSS bubble + JS flag `chatSending` + polling skip |

---

## Urutan Pengerjaan

1. **Controller** — Ubah authorize check di `TicketCommentController@store`
2. **CSS** — Tambah `display: inline-block; width: fit-content;` di `.chat-bubble`
3. **JS: Flag `chatSending`** — Deklarasi variabel + set true saat send + set false di success/error
4. **JS: Polling skip** — Cek `chatSending` di `pollNewMessages`, skip jika true
