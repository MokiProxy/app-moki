# Planning Implementasi CRUD Master Data Satuan Kerja

## 1. Analisis Status Quo

### Database
- Tabel `pegawai_satker` **sudah ada** (migrasi `2026_07_15_075951`)
- Kolom: `kode_satker` (string, PK), `nama_satker` (string), `timestamps`
- Model `PegawaiSatker` **sudah ada** di `app/Models/PegawaiSatker.php`
- Memiliki relasi `hasMany` ke `PegawaiHirarki`

### Sidebar
- Menu "Satuan Kerja" sudah ada di sidebar (`app-sidebar.blade.php:37`)
- **Bug**: Saat ini route masih mengarah ke `route('division')` (salah), perlu diganti ke route baru

### Pattern yang Digunakan
- Master data sederhana (Regional, Company) menjadi referensi utama
- Menggunakan Yajra DataTables (server-side), Modal Bootstrap, AJAX jQuery, SweetAlert2

---

## 2. File yang Perlu Dibuat/Diubah

| No | File | Aksi | Keterangan |
|----|------|------|------------|
| 1 | `app/Http/Controllers/SatuanKerjaController.php` | **Buat baru** | Controller CRUD |
| 2 | `resources/views/components/satuan-kerja.blade.php` | **Buat baru** | View (DataTable + Modal) |
| 3 | `routes/web.php` | **Edit** | Tambah 6 route |
| 4 | `resources/views/layouts/partials/app-sidebar.blade.php` | **Edit** | Fix route pada baris 37 |

> **Tidak perlu**: Migration (sudah ada), Model (sudah ada)

---

## 3. Detail Implementasi

### 3.1 Route (`routes/web.php`)

Tambahkan di blok MASTER DATA (setelah route `division`), mengikuti pattern yang sama:

```php
Route::get('/satuan-kerja', [SatuanKerjaController::class, 'index'])->name('satuan-kerja');
Route::post('/satuan-kerja', [SatuanKerjaController::class, 'store'])->name('satuan-kerja.store');
Route::get('/satuan-kerja/edit/{id}', [SatuanKerjaController::class, 'show'])->name('satuan-kerja.show');
Route::put('/satuan-kerja/edit/{id}', [SatuanKerjaController::class, 'update'])->name('satuan-kerja.update');
Route::delete('/satuan-kerja/delete/{id}', [SatuanKerjaController::class, 'destroy'])->name('satuan-kerja.delete');
Route::post('/satuan-kerja/datatable', [SatuanKerjaController::class, 'datatable'])->name('satuan-kerja.datatable');
```

**Note**: Route name prefix `satuan-kerja` menggunakan hyphen agar URL-friendly (`/satuan-kerja`).

### 3.2 Controller (`SatuanKerjaController.php`)

Mengikuti pattern `RegionalController` dengan penyesuaian:
- Model: `PegawaiSatker` (bukan Model baru)
- Primary key: `kode_satker` (string, bukan auto-increment `id`)
- Field: `kode_satker` (required, unique saat update), `nama_satker` (required)

**Methods:**
| Method | HTTP | Fungsi |
|--------|------|--------|
| `index()` | GET | Return view |
| `datatable(Request)` | POST | Yajra DataTables server-side |
| `store(Request)` | POST | Validasi + create JSON response |
| `show($id)` | GET | Cari by `kode_satker`, return JSON |
| `update(Request, $id)` | PUT | Validasi + update JSON response |
| `destroy($id)` | DELETE | Hapus by `kode_satker`, return JSON |

**Validation Rules:**
```php
// Store
'kode_satker' => 'required|unique:pegawai_satker,kode_satker',
'nama_satker' => 'required'

// Update (unique exception)
'kode_satker' => 'required|unique:pegawai_satker,kode_satker,' . $id . ',kode_satker'
```

### 3.3 View (`satuan-kerja.blade.php`)

Mengikuti pattern `regional.blade.php` dengan improvement dari `division.blade.php`:

**Sections:**
- `content` — Card dengan DataTable + 2 Modal (Tambah/Ubah) + Form Hapus
- `css` — DataTables CSS, Select2 CSS
- `title` — "Satuan Kerja"
- `plugin` — DataTables JS + Script AJAX

**DataTable Columns:**
| # | Data | Label |
|---|------|-------|
| 1 | DT_RowIndex | No |
| 2 | kode_satker | Kode Satuan Kerja |
| 3 | nama_satker | Nama Satuan Kerja |
| 4 | created_at | Created At |
| 5 | action | Action (Edit/Delete) |

**Modal Forms:**
- `modal-add-satuan-kerja` — FormTambah: kode_satker (text), nama_satker (text)
- `modal-update-satuan-kerja` — FormUbah: kode_satker (text), nama_satker (text)
- `form-delete-satuan-kerja` — FormHapus (hidden, submit via AJAX)

**JavaScript Pattern:**
- DataTables server-side via AJAX POST ke `satuan-kerja.datatable`
- Form submit via `$.ajax` (POST/PUT/DELETE)
- SweetAlert2 untuk konfirmasi hapus
- `notification()` helper untuk notifikasi sukses/error
- `drawTable()` helper untuk reset form + hide modal + redraw table

### 3.4 Sidebar (`app-sidebar.blade.php`)

**Baris 37** — Ubah:
```html
{{-- SEBELUM --}}
<li><a href="{{ route('division') }}"><i class='bx bx-home'></i> Satuan Kerja</a></li>

{{-- SESUDAH --}}
<li><a href="{{ route('satuan-kerja') }}"><i class='bx bx-home'></i> Satuan Kerja</a></li>
```

---

## 4. Urutan Pengerjaan

1. **Buat Controller** — `SatuanKerjaController.php`
2. **Tambah Routes** — Edit `routes/web.php`
3. **Buat View** — `satuan-kerja.blade.php`
4. **Fix Sidebar** — Edit `app-sidebar.blade.php` baris 37
5. **Test** — Jalankan aplikasi, navigasi ke menu Satuan Kerja, uji CRUD

---

## 5. Catatan Penting

- **Primary Key String**: Karena `kode_satker` adalah string PK (bukan auto-increment), semua operasi `find()`, `update()`, `delete()` menggunakan `kode_satker` sebagai identifier
- **Unique Validation**: Saat update, kode_satker yang sedang di-edit harus di-exclude dari pengecekan unique
- **Relasi**: `PegawaiSatker` memiliki relasi `hasMany` ke `PegawaiHirarki`. Pertimbangkan untuk menambahkan pengecekan sebelum delete (apakah masih digunakan di tabel lain)
- **Sidebar Access**: Menu hanya ditampilkan untuk role non-approver (`$role != 2`), sudah sesuai dengan blok `@if` yang ada
