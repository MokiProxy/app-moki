# Planning Implementasi: Merge Dokumen ke Multiple Page PDF

## Overview

Sistem akan menggabungkan dokumen PDF dengan kombinasi **jenis dokumen + vendor + nomor dokumen** yang sama menjadi satu file PDF dengan multiple pages. Penggabungan dilakukan menggunakan library **FPDI** (Free PDF Document Importer).

---

## Alur Konsep

```
User Upload File
       │
       ▼
┌─────────────────────────────────────────────────┐
│ STEP 1: OCR & Ekstraksi Data                    │
│ - DocumentType (jenis dokumen)                  │
│ - Vendor                                       │
│ - Document Number (nomor dokumen)               │
└───────────────────────┬─────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────┐
│ STEP 2: Generate Target Filename                │
│ Template: {vendor}_{number}.{ext}              │
│ Contoh: MADHANI TALATAH NUSANTARA_INV_MADHANI  │
│         TALATAH NUSANTARA_D0001.pdf             │
└───────────────────────┬─────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────┐
│ STEP 3: Cek Apakah File Sudah Ada di FTP Final │
│ Path: {document_type}/{vendor}/{filename}       │
│                                                 │
│ IF EXISTS:                                      │
│   ├── Download file existing dari FTP           │
│   ├── Merge file existing + file baru (fpdi)    │
│   ├── Upload merged file ke FTP (overwrite)     │
│   └── Log: "File merged, total N pages"        │
│                                                 │
│ IF NOT EXISTS:                                  │
│   ├── Upload file baru langsung ke FTP          │
│   └── Log: "File baru diupload"                 │
└───────────────────────┬─────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────┐
│ STEP 4: Cleanup & Logging                       │
│ - Hapus file temporary lokal                    │
│ - Catat ke scan_logs                            │
└─────────────────────────────────────────────────┘
```

---

## Ekspektasi Hasil

### Contoh Kasus 1: File Pertama (Single Page)
```
Input:  scan file INV_MADHANI.pdf
OCR:    Document Type = "SLIP PEMBUKUAN AP"
        Vendor = "MADHANI TALATAH NUSANTARA"
        Number = "INV_MADHANI TALATAH NUSANTARA_D0001"

Proses: Cek FTP → File TIDAK ADA
Output: Upload baru ke FTP
        Path: SLIP PEMBUKUAN AP/MADHANI TALATAH NUSANTARA/INV_MADHANI TALATAH NUSANTARA_D0001.pdf
        Pages: 1

Log: [MERGE_NEW] File baru diupload (1 page)
```

### Contoh Kasus 2: File Kedua (Merge → 2 Pages)
```
Input:  scan file INV_MADHANI_2.pdf (user berbeda/dokumen berbeda)
OCR:    Document Type = "SLIP PEMBUKUAN AP"
        Vendor = "MADHANI TALATAH NUSANTARA"
        Number = "INV_MADHANI TALATAH NUSANTARA_D0001"

Proses: Cek FTP → File SUDAH ADA (1 page)
        Download existing file
        Merge: existing (1 page) + baru (1 page) = 2 pages
        Upload merged file ke FTP (overwrite)

Output: Path: SLIP PEMBUKUAN AP/MADHANI TALATAH NUSANTARA/INV_MADHANI TALATAH NUSANTARA_D0001.pdf
        Pages: 2

Log: [MERGE_SUCCESS] File digabung, total 2 pages
```

### Contoh Kasus 3: File Ketiga (Merge → 3 Pages)
```
Input:  scan file INV_MADHANI_3.pdf
OCR:    Document Type = "SLIP PEMBUKUAN AP"
        Vendor = "MADHANI TALATAH NUSANTARA"
        Number = "INV_MADHANI TALATAH NUSANTARA_D0001"

Proses: Cek FTP → File SUDAH ADA (2 pages)
        Download existing file
        Merge: existing (2 pages) + baru (1 page) = 3 pages
        Upload merged file ke FTP (overwrite)

Output: Path: SLIP PEMBUKUAN AP/MADHANI TALATAH NUSANTARA/INV_MADHANI TALATAH NUSANTARA_D0001.pdf
        Pages: 3

Log: [MERGE_SUCCESS] File digabung, total 3 pages
```

### Contoh Kasus 4: Nomor Dokumen Berbeda (Tidak Di-merge)
```
Input:  scan file INV_MADHANI_D0002.pdf
OCR:    Document Type = "SLIP PEMBUKUAN AP"
        Vendor = "MADHANI TALATAH NUSANTARA"
        Number = "INV_MADHANI TALATAH NUSANTARA_D0002"

Proses: Cek FTP → File TIDAK ADA (nomor berbeda)
Output: Upload baru
        Path: SLIP PEMBUKUAN AP/MADHANI TALATAH NUSANTARA/INV_MADHANI TALATAH NUSANTARA_D0002.pdf
        Pages: 1

Log: [MERGE_NEW] File baru diupload (1 page)
```

---

## Komponen yang Perlu Diubah/Ditambah

### 1. Install Library FPDI
```bash
composer require setasign/fpdi
```

### 2. Service Baru: `PdfMergeService.php`
**Path:** `app/Services/PdfMergeService.php`

**Responsibility:**
- Merge dua file PDF menjadi satu dengan multiple pages
- Menggunakan fpdi untuk import PDF existing
- Menggunakan TCPDF atau FPDF untuk generate PDF baru

**Metode:**
```php
class PdfMergeService
{
    /**
     * Merge multiple PDF files into one
     * @param array $pdfPaths Array of file paths to merge
     * @param string $outputPath Path untuk output merged file
     * @return string Path ke merged file
     */
    public function mergePdfs(array $pdfPaths, string $outputPath): string

    /**
     * Add pages from existing PDF to current PDF
     * @param string $pdfPath Path ke PDF yang akan di-import
     */
    public function addPagesFromPdf(string $pdfPath): void
}
```

### 3. Modifikasi Job: `ProcessScanFile.php`
**Path:** `app/Jobs/ProcessScanFile.php`

**Perubahan:**
- Setelah generate `$targetFilename` dan `$ftpPath`, tambahkan pengecekan file existing di FTP
- Jika ada, download → merge → upload overwrite
- Jika tidak ada, upload langsung seperti biasa

**Alur baru (di dalam `handle()`):**
```php
// ... setelah OCR dan generate filename ...

$ftpDisk = Storage::disk('ftp_final');

// Cek apakah file sudah ada di FTP
$existingContent = null;
if ($ftpDisk->exists($ftpPath)) {
    $existingContent = $ftpDisk->get($ftpPath);
    // Simpan ke temporary file
    $tempExisting = storage_path("app/private/scanner/temp/existing_{$pdfFilename}");
    file_put_contents($tempExisting, $existingContent);
}

if ($existingContent !== null) {
    // MERGE: existing + baru
    $merger = app(PdfMergeService::class);
    
    $tempNew = storage_path("app/private/scanner/temp/new_{$pdfFilename}");
    file_put_contents($tempNew, $ftpContent);
    
    $mergedPath = $merger->mergePdfs([
        $tempExisting,
        $tempNew
    ], storage_path("app/private/scanner/merged/{$pdfFilename}"));
    
    $ftpContent = file_get_contents($mergedPath);
    
    // Cleanup temporary files
    unlink($tempExisting);
    unlink($tempNew);
    unlink($mergedPath);
    
    $mergeStatus = 'merged';
    $totalPages = $merger->getPageCount($mergedPath);
} else {
    $mergeStatus = 'new';
    $totalPages = 1;
}

// Upload ke FTP (baik merge atau baru)
$ftpDisk->put($ftpPath, $ftpContent);

// Log
$logger->log('job_completed', 'success', [
    // ... existing fields ...
    'merge_status' => $mergeStatus,
    'total_pages' => $totalPages,
    'message' => $mergeStatus === 'merged'
        ? "File digabung, total {$totalPages} pages"
        : "File baru diupload (1 page)",
]);
```

### 4. Cleanup Temporary Files
Tambahkan cleanup di akhir job atau gunakan scheduled task:
```php
// Di akhir handle()
Storage::disk('local')->delete([
    "scanner/temp/existing_{$pdfFilename}",
    "scanner/temp/new_{$pdfFilename}",
    "scanner/merged/{$pdfFilename}",
]);
```

### 5. Tabel `scan_logs` (Opsional - Kolom Tambahan)
Jika ingin tracking detail merge, tambahkan kolom:
```php
// Migration baru
Schema::table('scan_logs', function (Blueprint $table) {
    $table->string('merge_status')->nullable()->after('status'); // 'new', 'merged'
    $table->integer('total_pages')->nullable()->after('merge_status');
});
```

---

## File yang Perlu Dibuat/Dimodifikasi

| No | File | Aksi | Keterangan |
|----|------|------|------------|
| 1 | `composer.json` | Modifikasi | Install fpdi |
| 2 | `app/Services/PdfMergeService.php` | **BARU** | Service untuk merge PDF |
| 3 | `app/Jobs/ProcessScanFile.php` | Modifikasi | Tambah logika merge |
| 4 | `database/migrations/xxxx_add_merge_columns_to_scan_logs.php` | **BARU** | Kolom tambahan (opsional) |

---

## Dependency

### Library PHP
```json
{
    "setasign/fpdi": "^2.5",
    "setasign/fpdf": "^1.8"
}
```

### System Requirements
- PHP ext-gd atau ext-imagick (sudah ada untuk konversi gambar)
- PHP ext-zlib (untuk kompresi PDF)
- PHP ext-mbstring (untuk handling string)

---

## Edge Cases & Error Handling

### 1. File Existing Bukan PDF Valid
```php
if (!$merger->isValidPdf($existingContent)) {
    // Upload file baru saja, jangan merge
    Log::warning('Existing file is not valid PDF, uploading new file only');
}
```

### 2. FTP Download Gagal
```php
try {
    $existingContent = $ftpDisk->get($ftpPath);
} catch (Exception $e) {
    // File mungkin tidak ada atau FTP error
    // Upload file baru saja
    Log::warning('Failed to download existing file from FTP', [
        'error' => $e->getMessage()
    ]);
    $existingContent = null;
}
```

### 3. File Terlalu Besar (Memory Limit)
```php
// Gunakan chunked processing untuk file besar
// atau limit max pages per file
define('MAX_PAGES_PER_FILE', 100);

if ($totalPages >= MAX_PAGES_PER_FILE) {
    // Buat file baru dengan nomor suffix
    // Contoh: filename_part2.pdf
}
```

### 4. Race Condition (Concurrent Upload)
```php
// Menggunakan file lock atau atomic operation
$lockPath = storage_path("app/private/scanner/locks/{$pdfFilename}.lock");
$lock = fopen($lockPath, 'c');
if (flock($lock, LOCK_EX)) {
    // Proses merge
    flock($lock, LOCK_UN);
}
fclose($lock);
```

---

## Testing Strategy

### Unit Test
```php
// tests/Unit/Services/PdfMergeServiceTest.php

class PdfMergeServiceTest extends TestCase
{
    public function test_merge_two_pdfs_creates_two_pages()
    {
        // Given: 2 PDF files (masing-masing 1 halaman)
        // When: merge
        // Then: hasilnya 2 halaman
    }
    
    public function test_merge_three_pdfs_creates_three_pages()
    {
        // Given: 3 PDF files
        // When: merge
        // Then: hasilnya 3 halaman
    }
    
    public function test_merge_preserves_content()
    {
        // Given: PDF dengan teks tertentu
        // When: merge
        // Then: semua teks masih ada
    }
}
```

### Integration Test
```php
// tests/Feature/Jobs/ProcessScanFileMergeTest.php

class ProcessScanFileMergeTest extends TestCase
{
    public function test_new_file_uploaded_when_no_existing()
    {
        // Given: FTP kosong
        // When: proses file
        // Then: file baru terupload
    }
    
    public function test_file_merged_when_existing()
    {
        // Given: FTP sudah ada file
        // When: proses file baru dengan kombinasi sama
        // Then: file ter-merge (2 pages)
    }
}
```

---

## Rollback Plan

Jika ada masalah setelah deploy:
1. **Nonaktifkan fitur merge** dengan env variable:
   ```env
   PDF_MERGE_ENABLED=false
   ```
2. **Kode di ProcessScanFile.php:**
   ```php
   if (env('PDF_MERGE_ENABLED', true)) {
       // Logika merge
   } else {
       // Logika lama (upload langsung)
   }
   ```
3. **Rollback migration** jika ada kolom baru:
   ```bash
   php artisan migrate:rollback
   ```

---

## Timeline Estimasi

| No | Task | Estimasi |
|----|------|----------|
| 1 | Install FPDI | 0.5 jam |
| 2 | Buat PdfMergeService | 2 jam |
| 3 | Modifikasi ProcessScanFile | 2 jam |
| 4 | Unit Test | 1 jam |
| 5 | Integration Test | 1 jam |
| 6 | Deploy & UAT | 1 jam |
| **Total** | | **7.5 jam** |

---

## Catatan Penting

1. **Semua dokumen** akan mengikuti alur merge ini (bukan hanya tipe tertentu)
2. **Kunci unik merge:** Kombinasi `document_type_id` + `vendor_name` + `document_number`
3. **File existing akan di-overwrite** dengan versi merged (backup tersedia di FTP jika diperlukan)
4. **Logging lengkap** untuk audit trail (status merge, jumlah halaman)
5. **Race condition handling** untuk menghindari corrupt data saat concurrent upload
