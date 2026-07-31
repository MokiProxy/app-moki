# Planning Implementasi Fitur Log File

> Analisis codebase & struktur database untuk fitur pencatatan aktivitas scan dan
> masuknya file ke sistem, dengan kemampuan download laporan ke format Excel.

---

## 1. Tujuan

Mencatat setiap aktivitas pada alur scan & masuknya file ke sistem (dari scanner
FTP sampai berhasil tersimpan di FTP final / S3), kemudian menampilkannya di
modul **Dokter** dan dapat di-download menjadi **Excel**.

---

## 2. Analisis Codebase (Alur Scan Saat Ini)

```
Scanner FTP (ftp_scanner /incoming)
   │  file .png/.jpg/.jpeg/.webp/.pdf
   ▼
[Command] MonitorScanner  → app:monitor-scanner  (app/Console/Commands/Dokter/MonitorScanner.php)
   │ 1. list file root & subfolder
   │ 2. skip file ekstensi tidak didukung (hapus dari FTP)
   │ 3. download ke storage/app/private/scanner/incoming
   │ 4. auto-detect document type via OCR
   │ 5. PDF → dipecah jadi gambar, lalu dispatch job
   ▼
[Job] ProcessScanFile  → App\Jobs\ProcessScanFile  (app/Jobs/ProcessScanFile.php)
   │ 1. resolve document type (manual id / auto via OCR)
   │ 2. OCR extract text
   │ 3. extract nomor dokumen + match vendor
   │ 4. generate S3 filename
   │ 5. simpan hasil OCR (json) di local storage
   │ 6. upload original ke S3 (scanner/originals)
   │ 7. gambar → PDF (konversi, fallback ke file asli)
   │ 8. upload ke FTP final (retry 3x)
   │ 9. hapus file incoming lokal
   ▼
FTP Final (ftp_final /root) + S3
```

**Kondisi saat ini:** semua aktivitas di atas hanya ditulis ke
`Log::info/warning/error` (file log laravel). Tidak ada data terstruktur yang
bisa dilihat user atau di-export.

### Titik-titik penting yang harus dicatat (hook point)

| File | Lokasi | Event yang terjadi |
|------|--------|--------------------|
| `MonitorScanner.php` | `processRootFiles()` | file ditemukan, file di-skip (ekstensi), deteksi gagal, tipe terdeteksi, job di-dispatch |
| `MonitorScanner.php` | `processFile()` | file subfolder di-skip / di-proses |
| `ProcessScanFile.php` | `handle()` | OCR sukses/gagal, nomor dokumen & vendor, S3 ter-upload, konversi PDF, FTP upload sukses/gagal, job selesai |
| `ProcessScanFile.php` | `failed()` | job gagal permanen |

---

## 3. Analisis Database (Tabel Terkait)

| Tabel | Kolom kunci | Keterangan |
|-------|-------------|------------|
| `document_types` | `id`, `name`, `slug`, `number_regex`, `number_label`, `s3_filename_template`, `ftp_folder_template`, `ftp_failed_folder`, `vendor_search_enabled` | Master jenis dokumen; dijadikan referensi FK + snapshot name di log |
| `vendors` | `id`, `name`, `slug` | Master vendor (pivot `document_type_vendor`) |
| `document_type_vendor` | `document_type_id`, `vendor_id` | Pivot relasi |
| `users` | `id`, `name` | Untuk kolom `user_id` (opsional, siapa yang trigger manual) |

> Tidak ada tabel log/audit yang sudah ada → perlu tabel baru `scan_logs`.

---

## 4. Desain Database (Tabel Baru)

### Migration: `2026_07_31_000000_create_scan_logs_table.php`

| Kolom | Tipe | Nullable | Keterangan |
|-------|------|----------|------------|
| `id` | bigint auto | - | PK |
| `source` | string | default `scanner` | `scanner` \| `manual` |
| `event` | string | - | kode event (lihat tabel di bawah) |
| `status` | string | default `info` | `success` \| `failed` \| `warning` \| `skipped` \| `info` |
| `filename` | string | ✓ | nama file asli |
| `extension` | string | ✓ | ekstensi file |
| `document_type_id` | FK unsignedBigInt | ✓ | FK ke `document_types` (nullOnDelete) |
| `document_type_name` | string | ✓ | snapshot nama jenis dokumen (aman walau dihapus) |
| `document_number` | string | ✓ | nomor dokumen hasil extract OCR |
| `vendor_name` | string | ✓ | nama vendor hasil match OCR |
| `s3_filename` | string | ✓ | nama file saat di-upload ke S3 |
| `ftp_path` | string | ✓ | path di FTP final |
| `file_size` | unsignedBigInt | ✓ | ukuran file (bytes) |
| `processing_time_ms` | unsignedInt | ✓ | durasi OCR (ms) |
| `message` | text | ✓ | pesan detail / error |
| `metadata` | json | ✓ | konteks tambahan (retry ke-n, deteksi score, dll) |
| `created_at` / `updated_at` | timestamp | - | timestamps |

**Index:** `created_at`, `status`, `event`, `document_type_id`, `filename`.

### Kode `event`

| event | status | Penjelasan |
|-------|--------|------------|
| `file_detected` | info | file baru terlihat di FTP scanner |
| `file_skipped` | skipped | ekstensi tidak didukung → dihapus |
| `detection_failed` | failed | auto-detect tipe dokumen gagal |
| `doc_type_detected` | info | tipe dokumen terdeteksi |
| `ocr_success` | success | OCR berhasil (isi nomor dokumen, vendor, waktu proses) |
| `ocr_failed` | failed | OCR gagal |
| `s3_uploaded` | success | file asli tersimpan ke S3 |
| `pdf_conversion_failed` | warning | konversi gambar→PDF gagal, fallback file asli |
| `ftp_upload_success` | success | file masuk ke FTP final |
| `ftp_upload_failed` | failed | FTP gagal (retry 3x) |
| `job_completed` | success | seluruh pipeline selesai |
| `job_failed` | failed | job gagal permanen (method `failed()`) |

---

## 5. Desain Model

### `app/Models/ScanLog.php`
- `$table = 'scan_logs'`
- `$fillable` = semua kolom kecuali id & timestamps
- `$casts = ['metadata' => 'array', 'processing_time_ms' => 'integer', 'file_size' => 'integer']`
- Relasi:
  - `documentType()`: `belongsTo(DocumentType::class, 'document_type_id')`
- (Opsional) accessor untuk label status/event ber-Bahasa Indonesia.

---

## 6. Desain Service Logger

### `app/Services/ScanLogger.php`
Service kecil yang membungkus insert log agar kode hook tetap bersih.

```php
class ScanLogger
{
    public function log(string $event, string $status = 'info', array $data = []): ScanLog;
    // data[] dipetakan ke kolom tabel, message default dari event.
}
```

Contoh pemakaian di titik hook:

```php
ScanLogger::log('file_detected', 'info', ['filename' => $filename]);

ScanLogger::log('ocr_success', 'success', [
    'filename' => $this->filename,
    'document_type_name' => $documentType->name,
    'document_number' => $documentNumber,
    'vendor_name' => $vendorName,
    'processing_time_ms' => $result['processing_time_ms'] ?? null,
]);

ScanLogger::log('ftp_upload_success', 'success', [
    'filename' => $pdfFilename,
    'ftp_path' => $ftpPath,
    's3_filename' => $uploadFilename,
]);
```

---

## 7. Desain Controller, Routes, Views, Permission

### Controller: `app/Http/Controllers/Dokter/LogFileController.php`

| Method | Fungsi |
|--------|--------|
| `index(Request $request)` | Tampilkan halaman log + filter (tanggal, status, jenis dokumen, keyword) |
| `datatable(Request $request)` | Server-side DataTables (optional, mengikuti pola `file-managements`) |
| `export(Request $request)` | Download laporan Excel sesuai filter (via Maatwebsite) |

### Routes (`routes/routers/dokter.php`)

```php
Route::prefix('log-file')->name('log-file.')->group(function () {
    Route::middleware('permission:dokter.log-file.view')->group(function () {
        Route::get('/', [LogFileController::class, 'index'])->name('index');
    });
    Route::middleware('permission:dokter.log-file.export')->group(function () {
        Route::get('/export', [LogFileController::class, 'export'])->name('export');
    });
});
```

> Hapus route dummy yang ada saat ini:
> `Route::get("log", [FileManagementController::class, "log"]);`
> (method `log()` tidak pernah dibuat di controller).

### Permission (`database/seeders/RolePermissionSeeder.php`)

- `dokter.log-file.view` — lihat log
- `dokter.log-file.export` — download Excel

Tambahkan ke array `$permissions` dan berikan ke role `admin` (dan role lain
sesuai kebutuhan).

### Sidebar (`resources/views/layouts/partials/dokter/app-sidebar.blade.php`)

Perbaiki menu "Log File" yang saat ini salah:

```blade
@can('dokter.log-file.view')
<li>
    <a href="{{ route('dokter.log-file.index') }}" class="waves-effect">
        <i class='bx bx-history'></i>
        <span key="t-log-file">Log File</span>
    </a>
</li>
@endcan
```

### View: `resources/views/dokter/log-file/index.blade.php`
- Layout `layouts.Dokter` (mengikuti `dokter/file-managements/index.blade.php`)
- Filter bar: rentang tanggal, status, jenis dokumen, pencarian keyword
- Tabel kolom: No, Waktu, Nama File, Jenis Dokumen, Nomor Dokumen, Vendor, Status (badge warna), FTP Path, Waktu Proses, Pesan
- Tombol **Export Excel** (`route('dokter.log-file.export', [...filter])`)

---

## 8. Desain Export Excel

### `app/Exports/ScanLogsExport.php` (Maatwebsite/Excel)

Implement:
- `FromCollection` (atau `FromQuery` untuk data besar) + `WithHeadings` + `WithMapping` + `ShouldAutoSize`

Kolom Excel:

| No | Kolom |
|----|-------|
| 1 | Tanggal / Waktu (Y-m-d H:i:s) |
| 2 | Nama File |
| 3 | Jenis Dokumen |
| 4 | Nomor Dokumen |
| 5 | Vendor |
| 6 | Status |
| 7 | S3 Filename |
| 8 | FTP Path |
| 9 | Ukuran (bytes / readable) |
| 10 | Waktu Proses (ms) |
| 11 | Pesan |

Query memakai filter yang sama dengan halaman index (`date_from`, `date_to`,
`status`, `document_type_id`, `search`).

---

## 9. Integrasi (Penambahan Hook ke Alur Scan)

### A. `MonitorScanner.php`
- `processRootFiles()`:
  - setelah file terlihat → `file_detected`
  - ekstensi tidak didukung → `file_skipped`
  - `detectDocumentType()` null → `detection_failed`
  - terdeteksi → `doc_type_detected`
- `processFile()` (subfolder): sama — `file_skipped`, `doc_type_detected`

### B. `ProcessScanFile.php`
- `handle()`:
  - file tidak ditemukan di local → `job_failed` (warning) + return
  - `documentType === null` → `detection_failed` + throw
  - OCR sukses → `ocr_success`; OCR gagal → `ocr_failed` + throw
  - upload S3 → `s3_uploaded`
  - konversi PDF gagal → `pdf_conversion_failed`
  - upload FTP sukses → `ftp_upload_success`; gagal 3x → `ftp_upload_failed` + throw
  - selesai → `job_completed`
- `failed()`: → `job_failed`

> `ScanLogger` di-inject via parameter method `handle()` (Laravel auto-resolve),
> sesuai pola yang sudah dipakai service lain.

---

## 10. Struktur File Baru

```
database/migrations/
└── 2026_07_31_000000_create_scan_logs_table.php

app/
├── Models/ScanLog.php
├── Services/ScanLogger.php
├── Exports/ScanLogsExport.php
├── Http/Controllers/Dokter/LogFileController.php
└── (updated) Console/Commands/Dokter/MonitorScanner.php
└── (updated) Jobs/ProcessScanFile.php

resources/views/dokter/log-file/
└── index.blade.php

routes/routers/dokter.php          (updated)
database/seeders/RolePermissionSeeder.php  (updated)
resources/views/layouts/partials/dokter/app-sidebar.blade.php  (updated)
```

---

## 11. Urutan Pekerjaan

| Step | Task | Dependency |
|------|------|------------|
| 1 | Migration `scan_logs` | - |
| 2 | Model `ScanLog` | Step 1 |
| 3 | Service `ScanLogger` | Step 2 |
| 4 | Tambah hook di `MonitorScanner` | Step 3 |
| 5 | Tambah hook di `ProcessScanFile` | Step 3 |
| 6 | Controller `LogFileController` (+ datatable/filter) | Step 2 |
| 7 | Export `ScanLogsExport` (Maatwebsite) | Step 2 |
| 8 | Route dokter (hapus dummy `log`, tambah `log-file`) | Step 6, 7 |
| 9 | Permission `dokter.log-file.*` + assign ke role | Step 8 |
| 10 | View `dokter/log-file/index.blade.php` | Step 6 |
| 11 | Update sidebar (route & permission benar) | Step 8, 9 |
| 12 | Migrate + seed ulang permission | - |
| 13 | Uji alur scan → log muncul → export Excel | Semua |

---

## 12. Catatan Penting / Open Questions

- **Maatwebsite/Excel** sudah ada di `composer.json` (`^3.1`) → tidak perlu install baru.
- **`maatwebsite/excel`** perlu dipublish config (`config/excel.php`) bila belum.
- **Deduplikasi event:** satu file melewati banyak event → bisa >1 baris log per file.
  Jika ingin "satu file = satu baris" perlu desain status agregat; untuk sekarang
  log per-event lebih informatif untuk audit.
- **Retensi data:** pertimbangkan penghapusan log lama (cron) bila data membesar.
- **Manual upload:** belum ada flow upload manual di codebase (hanya via scanner FTP).
  Kolom `source` & `user_id` sudah disiapkan sebagai titik perluasan.
- **Format waktu:** kolom `created_at` dipakai sebagai waktu event (dan waktu proses
  OCR terpisah di `processing_time_ms`).
- **Sidebar saat ini** punya duplikasi menu "File Management" & "Log File" yang
  sama-sama menunjuk `dokter.file-managements.index` → sekaligus diperbaiki.
