# Planning Implementasi Remove AWS S3

> Analisis codebase untuk menghapus seluruh proses yang menyangkut AWS S3.
> Hasil akhir proses OCR dari aplikasi dokter kini disimpan ke FTP server
> (`ftp_final`) saja, tidak lagi ke AWS S3.

---

## 1. Tujuan

1. Menghapus seluruh proses upload/penyimpanan file ke **AWS S3** (`Storage::disk('s3')`).
2. Menghapus semua referensi S3 / AWS di kode aplikasi, konfigurasi, migration, seeder, dan views.
3. **Mempertahankan** fitur templating nama file untuk file final di FTP dengan mengubah
   nama konsep `s3_filename_template` → `filename_template` (keputusan user).
4. Tetap memakai `Storage::disk('ftp_final')` sebagai satu-satunya tujuan penyimpanan hasil akhir.

---

## 2. Analisis Codebase (Kondisi Saat Ini)

### 2.1 Alur OCR saat ini

```
Scanner FTP (ftp_scanner /incoming)
   ▼
[Command] MonitorScanner (app:monitor-scanner)
   ▼
[Job] ProcessScanFile (app/Jobs/ProcessScanFile.php)
   │ 1. resolve document type
   │ 2. OCR extract text
   │ 3. extract nomor + match vendor
   │ 4. generate S3 filename  ← akan diubah jadi filename biasa
   │ 5. simpan hasil OCR (json) di local storage
   │ 6. upload original ke S3 (scanner/originals)  ← HAPUS
   │ 7. gambar → PDF
   │ 8. upload ke FTP final (ftp_final)            ← TETAP
   │ 9. hapus file incoming lokal
   ▼
FTP Final (ftp_final /root)  [tanpa S3]
```

> **Poin penting:** nama file hasil templating (`s3_filename_template`) saat ini dipakai
> untuk **dua** hal: nama file di S3 (`scanner/originals/...`) **dan** nama file PDF di
> FTP final (via `$uploadFilename`). Karena itu fitur templating tetap dipertahankan,
> hanya diganti namanya menjadi `filename_template` (tetap dipakai untuk FTP final).

### 2.2 Titik yang mengandung AWS S3 / referensi S3

| # | File | Lokasi | Isi | Aksi |
|---|------|--------|-----|------|
| 1 | `app/Jobs/ProcessScanFile.php` | :76-77 | `generateS3Filename()` + `$uploadFilename` | rename variabel/metode |
| 2 | `app/Jobs/ProcessScanFile.php` | :85, :179, :190 | key `s3_filename` di array log/OCR | hapus key |
| 3 | `app/Jobs/ProcessScanFile.php` | :102 | `Storage::disk('s3')->put(...)` | **hapus baris upload S3** |
| 4 | `app/Services/DocumentTypeProcessor.php` | :77-80 | `generateS3Filename()` | rename → `generateFilename()` |
| 5 | `app/Services/InvoiceNumberExtractor.php` | :26-38 | `generateS3Filename()` (tidak terpakai / dead code) | hapus metode |
| 6 | `app/Models/DocumentType.php` | :17 | `s3_filename_template` di `$fillable` | rename → `filename_template` |
| 7 | `app/Models/DocumentType.php` | :49-65 | `resolveS3Filename()` | rename → `resolveFilename()` |
| 8 | `app/Models/ScanLog.php` | :22 | `s3_filename` di `$fillable` | hapus |
| 9 | `app/Models/ScanLog.php` | :72 | event label `s3_uploaded` | hapus (event tak dipakai lagi) |
| 10 | `app/Services/OcrSearchService.php` | :104 | key `s3_filename` di `listResults()` | hapus |
| 11 | `app/Exports/ScanLogsExport.php` | :37, :58 | heading & data `S3 Filename` | hapus |
| 12 | `app/Http/Requests/StoreDocumentTypeRequest.php` | :16 | validasi `s3_filename_template` | rename |
| 13 | `app/Http/Requests/UpdateDocumentTypeRequest.php` | :16 | validasi `s3_filename_template` | rename |
| 14 | `database/seeders/DocumentTypeSeeder.php` | :23 | key `s3_filename_template` | rename |
| 15 | `database/migrations/2026_07_26_000000_add_ocr_config_to_document_types_table.php` | :18-20 | kolom `s3_filename_template` | biarkan (riwayat), rename via migrasi baru |
| 16 | `database/migrations/2026_07_31_000000_create_scan_logs_table.php` | :22 | kolom `s3_filename` | biarkan (riwayat), drop via migrasi baru |
| 17 | `resources/views/dokter/document-type/create.blade.php` | :47-51 | field "S3 Filename Template" | rename |
| 18 | `resources/views/dokter/document-type/edit.blade.php` | :48-52 | field "S3 Filename Template" | rename |
| 19 | `config/filesystems.php` | :48-59 | disk `'s3'` | hapus |
| 20 | `config/filesystems.php` | :27 | komentar "Supported Drivers ... s3" | perbarui komentar |
| 21 | `composer.json` | :19 | `league/flysystem-aws-s3-v3` | hapus dependency |
| 22 | `.env` / `.env.example` | - | `AWS_*` (key, secret, region, bucket, endpoint, use_path_style) | hapus |
| 23 | `config/cache.php`, `config/queue.php`, `config/services.php` | - | referensi `AWS_*` (default SQS/cache/SES) | hapus |
| 24 | `planning-implementasi-log-file.md` | beberapa | dokumen menyebut S3 | perbarui (opsional) |

> `TrustProxies.php:27` (`HEADER_X_FORWARDED_AWS_ELB`) **bukan** bagian dari
> penyimpanan S3 → dibiarkan (konfigurasi proxy header, tidak terkait storage).

---

## 3. Desain Perubahan per File

### 3.1 `config/filesystems.php`

Hapus blok disk `s3` (baris 48-59) dan perbarui komentar driver:

```php
'disks' => [
    'local'      => [...],
    'public'     => [...],
    'ftp_scanner'=> [...],
    'ftp_final'  => [...],   // satu-satunya tujuan hasil akhir
],
```

### 3.2 `composer.json` + `.env` + `.env.example`

- Hapus `"league/flysystem-aws-s3-v3": "^1.0"` lalu jalankan:
  `composer remove league/flysystem-aws-s3-v3`
- Hapus semua baris `AWS_*` di `.env` dan `.env.example`:
  - `AWS_ACCESS_KEY_ID`
  - `AWS_SECRET_ACCESS_KEY`
  - `AWS_DEFAULT_REGION`
  - `AWS_BUCKET`
  - `AWS_URL`
  - `AWS_ENDPOINT`
  - `AWS_USE_PATH_STYLE_ENDPOINT`

### 3.3 `config/cache.php`, `config/queue.php`, `config/services.php`

Hapus blok/referensi `AWS_*` (default Laravel untuk ElastiCache, SQS, SES) yang
tidak terpakai, agar tidak tersisa referensi AWS di config.

### 3.4 `app/Models/DocumentType.php`

- `$fillable`: ganti `'s3_filename_template'` → `'filename_template'`.
- `resolveS3Filename()` → `resolveFilename()`:

```php
public function resolveFilename(?string $vendorName, ?string $number, string $ext): string
{
    $template = $this->attributes['filename_template'] ?? '{vendor}_{number}.{ext}';
    // ... logika sama, tidak ada perubahan selain nama & referensi kolom
}
```

### 3.5 `app/Services/DocumentTypeProcessor.php`

Rename metode dan delegasi:

```php
public function generateFilename(DocumentType $docType, ?string $vendorName, ?string $number, string $originalExtension): string
{
    return $docType->resolveFilename($vendorName, $number, $originalExtension);
}
```

### 3.6 `app/Services/InvoiceNumberExtractor.php`

Hapus metode `generateS3Filename()` (dead code — tidak pernah dipanggil di codebase).

### 3.7 `app/Jobs/ProcessScanFile.php`

- Ganti pemanggilan & variabel:

```php
$originalExtension = pathinfo($this->filename, PATHINFO_EXTENSION);
$targetFilename = $processor->generateFilename($documentType, $vendorName, $documentNumber, $originalExtension);
$targetFilename = $targetFilename ?: $this->filename;
```

- Hapus baris: `Storage::disk('s3')->put("scanner/originals/{$uploadFilename}", $content);`
  (variabel `$content` tetap dipakai untuk konversi PDF & upload FTP).
- Hapus key `'s3_filename' => $uploadFilename` dari `$ocrData` (baris 85) dan dari
  array log `job_completed` (baris 179) serta `Log::info` (baris 190).
- Rename semua `$uploadFilename` → `$targetFilename` (nama file FTP tetap konsisten).
- Upload FTP `Storage::disk('ftp_final')->put(...)` **tidak berubah**.

### 3.8 `app/Models/ScanLog.php`

- Hapus `'s3_filename'` dari `$fillable`.
- Hapus entry `'s3_uploaded' => 'Upload ke S3'` di `getEventLabelAttribute()`.
- `getEventLabelAttribute()` lainnya tetap.

### 3.9 `app/Services/OcrSearchService.php`

Di `listResults()`, hapus baris:
```php
's3_filename' => $data['s3_filename'] ?? null,
```

### 3.10 `app/Exports/ScanLogsExport.php`

- Hapus heading `'S3 Filename'` dari `headings()`.
- Hapus `$log->s3_filename ?? '-',` dari `map()`.

### 3.11 `app/Http/Requests/StoreDocumentTypeRequest.php` & `UpdateDocumentTypeRequest.php`

Ganti aturan validasi:
```php
's3_filename_template' => ['nullable', 'string', 'max:255'],
// menjadi
'filename_template' => ['nullable', 'string', 'max:255'],
```

### 3.12 Views `document-type/create.blade.php` & `edit.blade.php`

Rename field form:
- Label: `S3 Filename Template` → `Filename Template`
- `name="s3_filename_template"` → `name="filename_template"`
- Default create: `old('s3_filename_template', '{vendor}_{number}.{ext}')` → `old('filename_template', '{vendor}_{number}.{ext}')`
- Edit: `old('s3_filename_template', $documentType->s3_filename_template)` → `old('filename_template', $documentType->filename_template)`
- Keterangan variabel tetap (`{vendor}`, `{number}`, `{ext}`, `{filename}`).

> `resources/views/dokter/log-file/index.blade.php` **tidak** menampilkan kolom S3 → tidak perlu diubah.

### 3.13 `database/seeders/DocumentTypeSeeder.php`

Ganti key array:
```php
's3_filename_template' => 'INV_{vendor}_{number}.{ext}',
// menjadi
'filename_template' => 'INV_{vendor}_{number}.{ext}',
```

---

## 4. Desain Database (Migration Baru)

### Migration 1: `2026_08_02_000000_rename_s3_filename_template_to_filename_template_on_document_types.php`

| Aksi | Tabel | Kolom | Menjadi |
|------|-------|-------|---------|
| rename | `document_types` | `s3_filename_template` | `filename_template` |

```php
public function up(): void
{
    Schema::table('document_types', function (Blueprint $table) {
        $table->renameColumn('s3_filename_template', 'filename_template');
    });
}

public function down(): void
{
    Schema::table('document_types', function (Blueprint $table) {
        $table->renameColumn('filename_template', 's3_filename_template');
    });
}
```

> Catatan: PostgreSQL mendukung `renameColumn` di Laravel 8 (memakai `ALTER TABLE ... RENAME COLUMN`).

### Migration 2: `2026_08_02_000001_drop_s3_filename_from_scan_logs_table.php`

| Aksi | Tabel | Kolom |
|------|-------|-------|
| drop | `scan_logs` | `s3_filename` |

```php
public function up(): void
{
    Schema::table('scan_logs', function (Blueprint $table) {
        $table->dropColumn('s3_filename');
    });
}

public function down(): void
{
    Schema::table('scan_logs', function (Blueprint $table) {
        $table->string('s3_filename')->nullable()->after('vendor_name');
    });
}
```

> Informasi nama file final tetap terekam di kolom `ftp_path` (basename path FTP).

---

## 5. Struktur File Baru / Berubah

```
baru:
database/migrations/
├── 2026_08_02_000000_rename_s3_filename_template_to_filename_template_on_document_types.php
└── 2026_08_02_000001_drop_s3_filename_from_scan_logs_table.php

berubah:
app/Jobs/ProcessScanFile.php
app/Models/DocumentType.php
app/Models/ScanLog.php
app/Services/DocumentTypeProcessor.php
app/Services/InvoiceNumberExtractor.php   (hapus dead code)
app/Services/OcrSearchService.php
app/Exports/ScanLogsExport.php
app/Http/Requests/StoreDocumentTypeRequest.php
app/Http/Requests/UpdateDocumentTypeRequest.php
database/seeders/DocumentTypeSeeder.php
resources/views/dokter/document-type/create.blade.php
resources/views/dokter/document-type/edit.blade.php
config/filesystems.php
config/cache.php
config/queue.php
config/services.php
composer.json
.env.example
(manual) .env
(opsional) planning-implementasi-log-file.md
```

---

## 6. Urutan Pekerjaan

| Step | Task | Dependency |
|------|------|------------|
| 1 | Migration rename `s3_filename_template` → `filename_template` | - |
| 2 | Migration drop `scan_logs.s3_filename` | - |
| 3 | Update `app/Models/DocumentType.php` (fillable + `resolveFilename()`) | Step 1 |
| 4 | Update `app/Services/DocumentTypeProcessor.php` (`generateFilename()`) | Step 3 |
| 5 | Update `app/Jobs/ProcessScanFile.php` (hapus upload S3, rename var/key) | Step 3, 4 |
| 6 | Update `app/Models/ScanLog.php` (hapus `s3_filename`, label `s3_uploaded`) | Step 2 |
| 7 | Update `app/Services/OcrSearchService.php` & `app/Exports/ScanLogsExport.php` | Step 6 |
| 8 | Update `app/Http/Requests/StoreDocumentTypeRequest.php` & `UpdateDocumentTypeRequest.php` | Step 1 |
| 9 | Update `database/seeders/DocumentTypeSeeder.php` | Step 1 |
| 10 | Update views `document-type/create.blade.php` & `edit.blade.php` | Step 1 |
| 11 | Hapus disk `s3` di `config/filesystems.php` + bersihkan `config/cache.php`, `config/queue.php`, `config/services.php` | - |
| 12 | `composer remove league/flysystem-aws-s3-v3` + hapus `AWS_*` di `.env` & `.env.example` | Step 11 |
| 13 | `php artisan migrate` | Step 1, 2 |
| 14 | Verifikasi: `grep -iE "s3|aws|bucket"` di `app/`, `config/`, `resources/`, `database/` → 0 match | Semua |

---

## 7. Verifikasi

1. `composer remove league/flysystem-aws-s3-v3` sukses tanpa error.
2. `php artisan migrate` sukses (rename + drop kolom).
3. Jalankan `app:monitor-scanner` → file tetap terproses, PDF ter-upload ke
   `ftp_final`, tidak ada error referensi disk `s3`.
4. Halaman **Log File** & **Export Excel** tampil tanpa kolom S3.
5. Form **Jenis Dokumen** (create/edit) menampilkan field "Filename Template".
6. Grep seluruh codebase tidak menemukan lagi `s3`, `aws`, `bucket`,
   `Storage::disk('s3')`, `AWS_*` (kecuali `TrustProxies` & seeder asset yang
   bukan bagian dari storage S3).
7. `php artisan route:list` & test pipeline OCR (jika ada) tetap lulus.

---

## 8. Catatan Penting / Open Questions

- **Perilaku nama file FTP:** setelah rename, templating nama tetap berfungsi
  untuk file PDF di FTP final (contoh `INV_{vendor}_{number}.{ext}`). Jika ingin
  memakai nama asli scanner, cukup kosongkan `filename_template` (fallback ke
  `$this->filename`).
- **Data lama:** kolom `s3_filename` di `scan_logs` lama akan hilang saat migrasi
  drop. Jika data historis masih diperlukan, alternatifnya kolom dipertahankan
  namun tidak diisi lagi — perlu keputusan tambahan.
- **Dead code:** `InvoiceNumberExtractor::generateS3Filename()` tidak dipakai
  di mana pun → dihapus. Jika ternyata ada pemakaian eksternal, ganti rename
  daripada hapus.
- **`config/cache.php`, `config/queue.php`, `config/services.php`:** hanya
  berisi default Laravel untuk driver SQS/ElastiCache/SES yang tidak dipakai;
  pembersihan bersifat preventif agar tidak ada referensi AWS tersisa.
- **`.env`:** berisi kredensial AWS asli — pastikan penghapusan juga dilakukan
  di lingkungan produksi/staging, bukan hanya lokal.
- **Dokumentasi:** `planning-implementasi-log-file.md` masih menyebut S3;
  bersifat historis, diperbarui agar konsisten (opsional).
