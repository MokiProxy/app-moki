# Planning: Implementasi Inline Edit Data Master SPT & GL

## Overview

User ingin mengubah data master **SPT** dan **GL** langsung dari tabel yang ditampilkan dengan cara **double-click** pada cell yang akan diubah. Setelah edit data master, user kembali ke halaman ekualisasi dan proses ulang.

**Pendekatan**: Edit langsung di halaman SPT (`/eqtax/spt/coretax`) dan GL (`/eqtax/gl`) via inline edit, bukan dari tabel ekualisasi.

---

## 1. Analisis Data Flow

### Arsitektur Saat Ini

```
eqtax_coretax_spt (SPT) ──┐
                           ├──→ EqualizationController ──→ eqtax_equalization_results (cache)
eqtax_gl (GL) ────────────┘
```

- SPT dan GL adalah **data master** yang diimport dari Excel
- `eqtax_equalization_results` adalah **cache** hasil komputasi
- User harus edit data master (SPT/GL), lalu re-run equalization

### Apa yang Bisa Diedit?

**SPT (`eqtax_coretax_spt`)**:
- `dpp` — harga dasar pengenaan pajak
- `ppn` — pajak pertambahan nilai

**GL (`eqtax_gl`)**:
- `dpp` — per baris item
- `ppn` — per baris item

**Tidak bisa diedit**: `no_faktur_pajak` (kunci pencocokan), `nama_penjual`, `entity`, field lainnya.

---

## 2. Strategi Inline Edit

### 2.1 UX Flow

1. User melihat tabel SPT atau GL
2. User **double-click** pada cell DPP atau PPN yang akan diubah
3. Cell berubah menjadi **input field** (inline edit mode)
4. User mengubah nilai → tekan **Enter** untuk simpan atau **Escape** untuk batal
5. AJAX POST update ke server
6. Cell kembali ke mode tampilan dengan nilai baru

### 2.2 Cell yang Bisa Di-Edit

| Halaman | Kolom | Editable |
|---------|-------|----------|
| SPT Coretax | DPP | ✅ Double-click → input |
| SPT Coretax | PPN | ✅ Double-click → input |
| General Ledger | DPP | ✅ Double-click → input |
| General Ledger | PPN | ✅ Double-click → input |
| SPT Coretax | No FP, Nama, NPWP, Tgl, dll | ❌ Read-only |
| General Ledger | Entity, Supplier, Jurnal, dll | ❌ Read-only |

### 2.3 Behavior

- **Double-click** → cell text diganti dengan `<input type="number">` yang sudah terisi nilai saat ini
- **Enter** → simpan via AJAX, tampilkan nilai baru
- **Escape** → batal, kembali ke tampilan semula
- **Click di luar cell** → juga batal (blur event)
- **Loading indicator** kecil saat AJAX berjalan
- **Toast notification** untuk sukses/gagal

---

## 3. Implementasi

### 3.1 Database Changes

**Tidak perlu migration baru** — edit langsung ke kolom yang sudah ada.

### 3.2 Routes Baru

```php
// routes/routers/eqtax.php

// SPT update
Route::prefix('spt')->name('spt.')->group(function () {
    Route::prefix('coretax')->name('coretax.')->group(function () {
        Route::get("/", [SPTCoretaxController::class, "index"])->name("index");
        Route::post("import", [SPTCoretaxController::class, "import"])->name("import");
        // BARU: Update field SPT via AJAX
        Route::post("update-field", [SPTCoretaxController::class, "updateField"])->name("update-field");
    });
});

// GL update
Route::prefix('gl')->name('gl.')->group(function () {
    Route::get("/", [GLController::class, "index"])->name("index");
    Route::post("import", [GLController::class, "import"])->name("import");
    // BARU: Update field GL via AJAX
    Route::post("update-field", [GLController::class, "updateField"])->name("update-field");
});
```

### 3.3 Controller Methods Baru

#### `SPTCoretaxController::updateField(Request $request)`

```
POST /eqtax/spt/coretax/update-field
Body JSON: { "id": 123, "field": "dpp", "value": 150000 }

Response JSON: { "success": true, "message": "DPP berhasil diupdate", "formatted_value": "Rp 150.000" }
```

**Logic**:
1. Validasi: `id` harus exists di `eqtax_coretax_spt`
2. Validasi: `field` harus salah satu dari `['dpp', 'ppn']`
3. Validasi: `value` harus numeric, >= 0
4. Update: `EQTAXCoretaxSPT::where('id', $id)->update([$field => $value])`
5. Return JSON sukses dengan value yang sudah diformat

#### `GLController::updateField(Request $request)`

```
POST /eqtax/gl/update-field
Body JSON: { "id": 456, "field": "ppn", "value": 25000 }

Response JSON: { "success": true, "message": "PPN berhasil diupdate", "formatted_value": "Rp 25.000" }
```

**Logic**:
1. Validasi: `id` harus exists di `eqtax_gl`
2. Validasi: `field` harus salah satu dari `['dpp', 'ppn']`
3. Validasi: `value` harus numeric, >= 0
4. Update: `EQTAXGL::where('id', $id)->update([$field => $value])`
5. Return JSON sukses dengan value yang sudah diformat

### 3.4 Frontend — Inline Edit JS

**File**: `resources/views/eqtax/spt/coretax/index.blade.php` dan `resources/views/eqtax/gl/index.blade.php`

#### JavaScript Logic (shared pattern)

```javascript
// Inline edit: double-click pada cell editable
$(document).on('dblclick', '.editable', function() {
    const $cell = $(this);
    if ($cell.find('input').length > 0) return; // sudah dalam mode edit

    const currentValue = $cell.data('value'); // raw value (tanpa format)
    const field = $cell.data('field');
    const id = $cell.closest('tr').data('id');

    // Simpan teks asli untuk restore jika batal
    const originalHtml = $cell.html();

    // Ganti isi cell dengan input
    $cell.html(`<input type="number" class="inline-edit-input" value="${currentValue}" min="0">`);
    $cell.find('input').focus().select();

    // Enter → simpan
    $cell.find('input').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            saveInlineEdit($cell, id, field, $(this).val(), originalHtml);
        }
        // Escape → batal
        if (e.key === 'Escape') {
            $cell.html(originalHtml);
        }
    });

    // Blur → batal (tapi delay agar tidak terjadi saat user klik tombol save)
    $cell.find('input').on('blur', function() {
        setTimeout(() => {
            if ($cell.find('input').length > 0) {
                $cell.html(originalHtml);
            }
        }, 150);
    });
});

function saveInlineEdit($cell, id, field, newValue, originalHtml) {
    const url = window.location.pathname.includes('spt')
        ? '{{ route("eqtax.spt.coretax.update-field") }}'
        : '{{ route("eqtax.gl.update-field") }}';

    // Validasi client-side
    if (newValue === '' || isNaN(newValue) || parseFloat(newValue) < 0) {
        showToast('error', 'Nilai tidak valid');
        $cell.html(originalHtml);
        return;
    }

    // Tampilkan loading
    $cell.html('<i class="fas fa-spinner fa-spin"></i>');

    $.ajax({
        url: url,
        type: 'POST',
        data: JSON.stringify({ id: id, field: field, value: parseFloat(newValue) }),
        contentType: 'application/json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(response) {
            if (response.success) {
                // Update cell dengan nilai baru (formatted)
                $cell.html(response.formatted_value);
                $cell.data('value', newValue);
                showToast('success', response.message);
            } else {
                $cell.html(originalHtml);
                showToast('error', response.message);
            }
        },
        error: function(xhr) {
            $cell.html(originalHtml);
            showToast('error', xhr.responseJSON?.message || 'Gagal menyimpan');
        }
    });
}
```

### 3.5 Blade Template Changes

#### SPT View (`resources/views/eqtax/spt/coretax/index.blade.php`)

Tambahkan `data-*` attributes pada cell DPP dan PPN:

```html
<!-- Sebelum (read-only) -->
<td class="text-end">Rp {{ number_format($dt->dpp, 0, ',', '.') }}</td>
<td class="text-end">Rp {{ number_format($dt->ppn, 0, ',', '.') }}</td>

<!-- Sesudah (editable) -->
<td class="text-end editable" data-id="{{ $dt->id }}" data-field="dpp" data-value="{{ $dt->dpp }}">
    Rp {{ number_format($dt->dpp, 0, ',', '.') }}
</td>
<td class="text-end editable" data-id="{{ $dt->id }}" data-field="ppn" data-value="{{ $dt->ppn }}">
    Rp {{ number_format($dt->ppn, 0, ',', '.') }}
</td>
```

Juga tambahkan `data-id` pada `<tr>`:

```html
<tr data-id="{{ $dt->id }}">
```

#### GL View (`resources/views/eqtax/gl/index.blade.php`)

Sama seperti SPT — tambahkan `data-*` attributes pada cell DPP dan PPN:

```html
<td class="text-end editable" data-id="{{ $dt->id }}" data-field="dpp" data-value="{{ $dt->dpp }}">
    Rp {{ number_format($dt->dpp, 0, ',', '.') }}
</td>
<td class="text-end editable" data-id="{{ $dt->id }}" data-field="ppn" data-value="{{ $dt->ppn }}">
    Rp {{ number_format($dt->ppn, 0, ',', '.') }}
</td>
```

### 3.6 CSS Tambahan

```css
/* Cell editable indicator */
.editable {
    cursor: pointer;
    position: relative;
    transition: background-color 0.2s;
}

.editable:hover {
    background-color: #e0e7ff !important;
}

.editable:hover::after {
    content: '\f044'; /* FontAwesome edit icon */
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    position: absolute;
    right: 4px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.65rem;
    color: #6366f1;
    opacity: 0.7;
}

/* Inline edit input */
.inline-edit-input {
    width: 100%;
    padding: 2px 6px;
    border: 2px solid #6366f1;
    border-radius: 4px;
    font-size: inherit;
    text-align: right;
    background: white;
    outline: none;
}

.inline-edit-input:focus {
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
}

/* Toast notification */
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
}
```

### 3.7 Toast Notification (shared)

Tambahkan di layout atau di masing-masing blade:

```html
<!-- Toast container -->
<div class="toast-container" id="toastContainer"></div>

<script>
function showToast(type, message) {
    const icon = type === 'success' ? 'fa-check-circle text-success' : 'fa-exclamation-circle text-danger';
    const html = `
        <div class="toast show" role="alert">
            <div class="toast-body d-flex align-items-center">
                <i class="fas ${icon} me-2"></i>
                ${message}
            </div>
        </div>
    `;
    const $toast = $(html);
    $('#toastContainer').append($toast);
    setTimeout(() => $toast.fadeOut(300, () => $toast.remove()), 3000);
}
</script>
```

---

## 4. File yang Perlu Diubah

| No | File | Perubahan |
|----|------|-----------|
| 1 | `routes/routers/eqtax.php` | Tambah 2 route: `spt.coretax.update-field`, `gl.update-field` |
| 2 | `app/Http/Controllers/EQTax/SPTCoretaxController.php` | Tambah method `updateField()` |
| 3 | `app/Http/Controllers/EQTax/GLController.php` | Tambah method `updateField()` |
| 4 | `resources/views/eqtax/spt/coretax/index.blade.php` | Tambah `data-*` attrs, inline edit JS, CSS |
| 5 | `resources/views/eqtax/gl/index.blade.php` | Tambah `data-*` attrs, inline edit JS, CSS |

### Tidak Perlu Diubah

- Model — tidak perlu ubahan (pakai `$fillable` yang sudah ada)
- Migration — tidak perlu table baru
- Equalization page — tidak berubah
- Import/Export — tidak berubah

---

## 5. Flow User

```
1. User buka halaman SPT Coretax (/eqtax/spt/coretax)
2. User melihat tabel data SPT
3. User DOUBLE-CLICK pada cell DPP atau PPN yang salah
4. Cell berubah menjadi input field dengan nilai saat ini
5. User mengubah nilai → tekan Enter
6. AJAX POST update ke server → cell menampilkan nilai baru
7. User kembali ke halaman Ekualisasi Pajak
8. User pilih periode → Klik "Proses Ekualisasi" / "Proses Ulang"
9. Hasil ekualisasi terbaru ditampilkan dengan data yang sudah diperbaiki
```

---

## 6. Edge Cases

| Skenario | Penanganan |
|----------|------------|
| User input nilai negatif | Validasi `min="0"` + server-side check |
| User input kosong | Kembalikan ke nilai semula |
| User input non-numeric | Validasi `is_numeric()` di JS + server |
| AJAX gagal (network error) | Kembalikan cell ke nilai semula, tampilkan error |
| User double-click cell non-editable | Tidak terjadi apa-apa (hanya cell `.editable` yang aktif) |
| User edit lalu reload halaman | Perubahan sudah tersimpan di database |

---

## 7. Validasi & Keamanan

1. **Hanya field tertentu yang bisa diedit**: Controller validasi `field` ∈ `['dpp', 'ppn']`
2. **Server-side validation**: `numeric`, `min:0` via Laravel Validator
3. **CSRF Token**: AJAX header `X-CSRF-TOKEN`
4. **Authorization**: Bisa ditambahkan middleware edit jika diperlukan
5. **Audit trail**: Opsional — bisa log perubahan ke table log

---

## 8. Checklist

- [ ] Buat planning file ini
- [ ] Tambah route `spt.coretax.update-field` dan `gl.update-field`
- [ ] Implement `SPTCoretaxController::updateField()`
- [ ] Implement `GLController::updateField()`
- [ ] Tambah `data-*` attributes di SPT view (cell DPP, PPN)
- [ ] Tambah `data-*` attributes di GL view (cell DPP, PPN)
- [ ] Tambah JavaScript inline edit handler (shared)
- [ ] Tambah CSS styling (editable cell, input, toast)
- [ ] Tambah toast notification HTML + JS
- [ ] Test double-click → input muncul dengan nilai benar
- [ ] Test Enter → AJAX save → cell update
- [ ] Test Escape → batal edit
- [ ] Test re-run equalization → hasil sesuai perubahan
- [ ] Validasi error handling (input invalid, network error)
