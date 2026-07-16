# Planning Perbaikan Chat Duplikat (ID-Based Deduplication)

## Root Cause
Polling membandingkan `created_at > lastCommentTimestamp`. Presisi timestamp antara JS (`moment`) dan MySQL Carbon bisa berbeda → pesan lama terus dianggap "baru" dan di-append ulang.

## Solusi
Ganti timestamp-based dengan **ID-based deduplication**:
- Simpan Set of IDs yang sudah ditampilkan (`chatSeenIds`)
- `loadChatMessages`: populate `chatSeenIds` dari semua data yang dimuat
- `pollNewMessages`: hanya append pesan yang ID-nya BELUM ada di `chatSeenIds`
- `sendChatMessage`: tambahkan ID baru ke `chatSeenIds` setelah append

### Perubahan di `index.blade.php`:
1. Hapus variabel `lastCommentTimestamp`
2. Tambah variabel `chatSeenIds = new Set()`
3. `loadChatMessages()`: setelah render, isi `chatSeenIds` dengan semua ID
4. `pollNewMessages()`: filter `res.data` hanya yang `!chatSeenIds.has(item.id)`, lalu append + add ID ke Set
5. `sendChatMessage()`: setelah append, add ID ke `chatSeenIds`
