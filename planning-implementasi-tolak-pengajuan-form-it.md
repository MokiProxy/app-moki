# Planning Implementasi: Fix Fitur Tolak Pengajuan Form IT

## Ringkasan Masalah

Fitur **Tolak (Reject)** pada menu approval semua jenis form di modul Form IT belum berfungsi. Selain itu, ketika pengajuan ditolak, fitur **Lihat PDF** masih bisa diakses oleh user.

---

## Hasil Analisis

### 1. Struktur Database

| Tabel | Fungsi |
|-------|--------|
| `formit_software_installations` | Data pengajuan install software. Kolom `status` enum: `pending`, `process`, `approved`, `rejected`. Kolom `rejected_by`, `rejection_reason` sudah ada. |
| `formit_approvals` | Data approval per-level (2-level: Superior 1 & Manager IT). Kolom `status` enum: `pending`, `approved`, `rejected`. |
| `formit_fixed_asset_borrowings` | Data pengajuan peminjaman fixed asset. Kolom `status` enum: `pending`, `approved`, `rejected`. Kolom `rejected_by`, `rejection_reason`, `rejected_at` sudah ada. |

**Kesimpulan:** Struktur database sudah mendukung fitur reject. Tidak perlu migrasi baru.

### 2. Backend (Controller) - Sudah Benar

**`ApprovalController::process()`** (Software Installation):
- Sudah handle `action === 'reject'` dengan update status ke `rejected` pada `FormitApproval` dan `SoftwareInstallation`
- Sudah menyimpan `rejected_by` dan `rejection_reason`

**`ApprovalController::fixedAssetProcess()`** (Fixed Asset):
- Sudah handle `action === 'reject'` dengan update status ke `rejected` pada `FixedAssetBorrowing`
- Sudah menyimpan `rejected_by`, `rejection_reason`, dan `rejected_at`

**Kesimpulan:** Logic backend sudah benar. Tidak ada perubahan di controller.

### 3. Frontend (View) - Ditemukan Bug

#### Bug Utama: Software Installation Approval Show

**File:** `resources/views/form-it/approval/show.blade.php`

| Item | Detail |
|------|--------|
| **Masalah** | Fungsi `showRejectModal()` didefinisikan di `@section('scripts')` (baris 272-278) |
| **Layout** | `layouts/FormIT.blade.php` **TIDAK** memiliki `@yield('scripts')` |
| **Akibat** | Fungsi JavaScript `showRejectModal()` **tidak pernah di-render** ke halaman. Ketika user klik tombol "Tolak", browser error: `showRejectModal is not defined` |
| **Lokasi** | `show.blade.php:214` (tombol) dan `show.blade.php:274` (fungsi) |

#### Fixed Asset Approval Show

**File:** `resources/views/form-it/approval/fixed-asset-show.blade.php`

| Item | Detail |
|------|--------|
| **Status** | Fungsi `showRejectModal()` didefinisikan di `@section('plugin')` (baris 246-344) |
| **Layout** | `app-plugin.blade.php` memiliki `@yield('plugin')` di baris 9 |
| **Kesimpulan** | Seharusnya sudah berfungsi. Perlu diverifikasi di browser. |

### 4. Akses PDF Saat Rejected

#### Backend - Tidak Ada Pengecekan Status

**File:** `app/Http/Controllers/FormIT/FormController.php`

| Route | Method | Status Check |
|-------|--------|-------------|
| `GET /form-it/forms/software-installation/{id}/pdf` | `showPdf()` (baris 167) | **TIDAK ADA** - bisa diakses meskipun status `rejected` |
| `GET /form-it/forms/fixed-asset/fixed-asset/{id}/pdf` | `fixedAssetShowPdf()` (baris 295) | **TIDAK ADA** - bisa diakses meskipun status `rejected` |

#### Frontend - Sudah Benar

| View | Kondisi Tampil PDF |
|------|-------------------|
| `my-submissions.blade.php:80` | `@if($item->status === 'approved')` ✓ |
| `fixed-asset-my-submissions.blade.php:75` | `@if($item->status === 'approved')` ✓ |
| `software-installation-show.blade.php:198` | `@if($softwareInstallation->status === 'approved')` ✓ |
| `approval/show.blade.php:223` | `@if($softwareInstallation->status === 'approved')` ✓ |

**Kesimpulan:** Sembunyi PDF di UI sudah benar, tapi backend PDF route tidak memblokir akses.

---

## Daftar Perubahan yang Diperlukan

### Perubahan 1: Fix Tombol Tolak Software Installation Approval

**File:** `resources/views/form-it/approval/show.blade.php`

**Masalah:** Fungsi `showRejectModal()` di `@section('scripts')` tidak di-yield oleh layout.

**Solusi:** Pindahkan fungsi `showRejectModal()` dari `@section('scripts')` ke `@section('plugin')`.

**Sebelum:**
```php
@section('scripts')
<script>
function showRejectModal() {
    var rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
    rejectModal.show();
}
</script>
@endsection
```

**Sesudah:**
```php
@section('plugin')
<script>
function showRejectModal() {
    var rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
    rejectModal.show();
}
</script>
@endsection
```

**Dampak:** Tombol "Tolak" di approval software installation akan berfungsi, modal akan muncul.

---

### Perubahan 2: Blokir Akses PDF Saat Status Rejected (Software Installation)

**File:** `app/Http/Controllers/FormIT/FormController.php`

**Masalah:** Method `showPdf()` tidak mengecek status pengajuan.

**Solusi:** Tambahkan pengecekan status sebelum generate PDF.

**Tambahkan di awal method `showPdf()` (setelah baris 178):**
```php
if ($softwareInstallation->status === 'rejected') {
    abort(404, 'Pengajuan ini telah ditolak.');
}
```

---

### Perubahan 3: Blokir Akses PDF Saat Status Rejected (Fixed Asset)

**File:** `app/Http/Controllers/FormIT/FormController.php`

**Masalah:** Method `fixedAssetShowPdf()` tidak mengecek status pengajuan.

**Solusi:** Tambahkan pengecekan status sebelum generate PDF.

**Tambahkan di awal method `fixedAssetShowPdf()` (setelah baris 303):**
```php
if ($borrowing->status === 'rejected') {
    abort(404, 'Pengajuan ini telah ditolak.');
}
```

---

### Perubahan 4: Bersihkan Dead Code di `fixedAssetShowPdf()`

**File:** `app/Http/Controllers/FormIT/FormController.php`

**Masalah:** Setelah `return` statement di baris 308, ada sisa kode copy-paste dari software installation PDF yang tidak terjangkau (baris 310-342).

**Solusi:** Hapus baris 310-342 yang merupakan dead code.

---

## Ringkasan File yang Diubah

| # | File | Perubahan | Prioritas |
|---|------|-----------|-----------|
| 1 | `resources/views/form-it/approval/show.blade.php` | Pindahkan `@section('scripts')` ke `@section('plugin')` | **Tinggi** |
| 2 | `app/Http/Controllers/FormIT/FormController.php` | Tambah status check di `showPdf()` | **Tinggi** |
| 3 | `app/Http/Controllers/FormIT/FormController.php` | Tambah status check di `fixedAssetShowPdf()` | **Tinggi** |
| 4 | `app/Http/Controllers/FormIT/FormController.php` | Hapus dead code baris 310-342 | **Rendah** |

---

## Alur Setelah Perubahan

### Alur Tolak Software Installation
```
User klik "Tolak" → showRejectModal() dipanggil → Modal muncul
→ User isi alasan penolakan → Klik tombol "Tolak" di modal
→ Form submit POST /form-it/approval/{id}/process (action=reject)
→ ApprovalController::process() update status ke 'rejected'
→ Redirect back dengan pesan sukses
```

### Alur PDF Setelah Reject
```
User coba akses PDF (klik link atau URL manual)
→ FormController::showPdf() / fixedAssetShowPdf()
→ Cek status == 'rejected' → abort(404)
→ User lihat halaman 404 "Pengajuan ini telah ditolak"
```

---

## Testing Checklist

- [ ] Klik tombol "Tolak" di approval software installation → Modal muncul
- [ ] Isi alasan → Submit → Status berubah jadi "Ditolak"
- [ ] Klik tombol "Tolak" di approval fixed asset → Modal muncul
- [ ] Isi alasan → Submit → Status berubah jadi "Ditolak"
- [ ] Coba akses PDF software installation yang ditolak → 404
- [ ] Coba akses PDF fixed asset yang ditolak → 404
- [ ] PDF tetap bisa diakses untuk pengajuan yang approved
- [ ] Halaman "Pengajuan Saya" tidak menampilkan tombol PDF untuk pengajuan ditolak
- [ ] Halaman detail pengajuan menampilkan "Alasan Penolakan" card untuk yang ditolak
