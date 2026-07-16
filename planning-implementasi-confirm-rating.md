# Planning Implementasi Tombol Confirm + Rating

## Overview
Menambahkan tombol **Confirm** untuk atasan (role_id 3) saat status tiket `RESOLVED`. Klik Confirm menampilkan modal berisi form rating (bintang 1-5). Submit mengubah status menjadi `CLOSED` dan menyimpan rating ke tabel `tickets`.

---

## Tahap 1: Migration — Tambah Kolom `rating`

Buat migration `add_rating_to_tickets_table`:
```php
$table->tinyInteger('rating')->nullable()->after('closed_at');
```
- `tinyInteger` = 1 byte, cukup untuk nilai 1-5
- `nullable` karena tiket yang belum di-confirm belum punya rating

---

## Tahap 2: Model — Update `$fillable`

Tambah `"rating"` ke array `$fillable` di `app/Models/Ticket.php`.

---

## Tahap 3: Backend — Controller

### 3.1 Modifikasi `TicketController::datatable()` (action column)
Ubah logic untuk role 3:
```php
if ($role == 3) {
    if ($row->status == "RESOLVED") {
        // Tombol Detail + Confirm
        return '
        <div class="btn-group">
            <button class="btn btn-sm btn-info btn-view" ...><i class="mdi mdi-eye"></i></button>
            <button class="btn btn-sm btn-primary btn-confirm" data-id="..."><i class="mdi mdi-check-circle"></i></button>
        </div>';
    } else {
        // Hanya tombol Detail
        return '...btn-view...';
    }
}
```

### 3.2 Tambah Method `TicketController::confirm(Request $request, $id)`
- Validasi: `rating` required|integer|in:1,2,3,4,5
- `DB::transaction()`:
  - Update status → `CLOSED`
  - Set `closed_at` → `now()`
  - Set `rating` → `$request->rating`
  - Log history: `$this->historyService->closed($ticket)` (sudah ada)
- Return JSON success

---

## Tahap 4: Route

Tambahkan di `routes/web.php`:
```php
Route::put('tickets/confirm/{id}', [HelpDeskTicketController::class, 'confirm'])->name('tickets.confirm');
```

---

## Tahap 5: Frontend — HTML Modal Confirm + Rating

Tambahkan modal baru `#modal-confirm` di `index.blade.php` (setelah `#modal-ticket`):

### Struktur HTML:
```html
<div class="modal fade" id="modal-confirm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white">Konfirmasi Penyelesaian</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <i class="mdi mdi-check-circle-outline text-success" style="font-size: 48px;"></i>
                <h5 class="mt-2">Apakah tiket ini sudah sesuai dikerjakan?</h5>
                <p class="text-muted small">Beri rating untuk pengerjaan tiket ini</p>

                <!-- Star Rating -->
                <div id="star-rating" class="my-3">
                    <i class="mdi mdi-star-outline star-btn" data-value="1"></i>
                    <i class="mdi mdi-star-outline star-btn" data-value="2"></i>
                    <i class="mdi mdi-star-outline star-btn" data-value="3"></i>
                    <i class="mdi mdi-star-outline star-btn" data-value="4"></i>
                    <i class="mdi mdi-star-outline star-btn" data-value="5"></i>
                </div>
                <input type="hidden" id="rating-value" value="0">
                <p class="small text-muted" id="rating-text">Pilih rating</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btn-submit-confirm">
                    <i class="mdi mdi-check-all me-1"></i> Konfirmasi
                </button>
            </div>
        </div>
    </div>
</div>
```

---

## Tahap 6: Frontend — CSS Star Rating

```css
.star-btn {
    font-size: 2rem; cursor: pointer; color: #dee2e6;
    transition: color 0.2s;
}
.star-btn:hover,
.star-btn.active { color: #ffc107; }
```

---

## Tahap 7: Frontend — JavaScript

### 7.1 Click handler `.btn-confirm`
- Simpan `data-id` tombol ke variabel `confirmTicketId`
- Reset star rating ke 0
- Buka `#modal-confirm`

### 7.2 Star rating hover + click
- Hover: highlight bintang dari 1 sampai hovered
- Click: set `#rating-value` + ubah class `active` + update `#rating-text`

### 7.3 Submit `#btn-submit-confirm`
- Validasi `#rating-value` > 0
- AJAX PUT ke `helpdesk/tickets/confirm/{id}` dengan `{ rating: value }`
- Success → SweetAlert → tutup modal → reload DataTable

---

## Ringkasan File

| File | Perubahan |
|------|-----------|
| `database/migrations/xxxx_add_rating_to_tickets_table.php` | **Baru** — kolom `rating` tinyInteger nullable |
| `app/Models/Ticket.php` | **Diubah** — tambah `"rating"` ke `$fillable` |
| `app/Http/Controllers/HelpDesk/TicketController.php` | **Diubah** — modifikasi action column role 3 + tambah method `confirm()` |
| `routes/web.php` | **Diubah** — tambah route `PUT tickets/confirm/{id}` |
| `resources/views/helpdesk/tickets/index.blade.php` | **Diubah** — tambah modal `#modal-confirm`, CSS star rating, JS handlers |
