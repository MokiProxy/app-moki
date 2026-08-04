# Planning Implementasi: Final Merge File - Sistem Birokrasi Dokumen

## Overview

Sistem alur birokrasi terstruktur untuk menggabungkan beberapa jenis dokumen (BA → INV → SP) menjadi satu file PDF final dengan multiple halaman. Sistem ini dirancang **extensible** sehingga jenis dokumen lain dapat ditambahkan ke alur birokrasi tanpa perubahan kode.

---

## Analisis Codebase

### Arsitektur Sistem yang Sudah Ada

```
Physical Scanner
      │
      ▼
FTP Server (/incoming)           ← ftp_scanner disk
      │
      ▼
[app:monitor-scanner]            ← Artisan command (polled externally)
      │
      ├── processRootFiles()     ← auto-detect doc type via header_regex
      ├── processSubfolderFiles()← doc type from subfolder → slug
      │
      ▼
Local disk (scanner/incoming/)   ← temp storage
      │
      ▼
ProcessScanFile job (queued)     ← OCR, extract data, convert, merge
      │
      ▼
FTP Server (/root/{type}/...)    ← ftp_final disk (output)
```

### Komponen Utama yang Sudah Ada

| Komponen | Path | Fungsi |
|----------|------|--------|
| `MonitorScanner` | `app/Console/Commands/Dokter/MonitorScanner.php` | Poll FTP, dispatch jobs |
| `ProcessScanFile` | `app/Jobs/ProcessScanFile.php` | OCR, extract data, upload ke FTP |
| `PdfMergeService` | `app/Services/PdfMergeService.php` | Merge PDF (sudah ada, menggunakan FPDI) |
| `DocumentTypeProcessor` | `app/Services/DocumentTypeProcessor.php` | Extract nomor, vendor, tanggal dari OCR |
| `ScanLogger` | `app/Services/ScanLogger.php` | Log aktivitas ke scan_logs |
| `DocumentType` | `app/Models/DocumentType.php` | Model jenis dokumen |
| `ScanLog` | `app/Models/ScanLog.php` | Model log scan |
| `Vendor` | `app/Models/Vendor.php` | Model vendor/perusahaan |

### Database yang Sudah Ada

**`document_types`** - Konfigurasi jenis dokumen:
- `id`, `name`, `header_regex`, `description`
- `number_regex`, `number_label` (regex untuk extract nomor dokumen)
- `keterangan_regex`, `keterangan_label`, `keterangan_enabled`
- `uraian_regex`, `uraian_label`, `uraian_enabled`
- `tanggal_regex`, `tanggal_label`, `tanggal_enabled`
- `filename_template`, `ftp_folder_template`, `ftp_failed_folder`
- `vendor_search_enabled`

**`scan_logs`** - Log setiap file yang diproses:
- `id`, `source`, `event`, `status`, `merge_status`, `total_pages`
- `filename`, `extension`, `document_type_id`, `document_type_name`
- `document_number`, `tanggal`, `vendor_name`, `keterangan`, `uraian`
- `ftp_path`, `file_size`, `processing_time_ms`, `message`, `metadata`

**`vendors`** - Data vendor/perusahaan:
- `id`, `name`, `slug`, `description`

**`document_type_vendor`** - Pivot antara document_type dan vendor:
- `id`, `document_type_id`, `vendor_id`

### FTP Path Pattern yang Sudah Ada

```
ftp_final/
├── BERITA ACARA/
│   └── MADHANI TALATAH NUSANTARA/
│       └── BA_MADHANI TALATAH NUSANTARA_BA0001.pdf
├── INVOICE/
│   └── MADHANI TALATAH NUSANTARA/
│       └── INV_MADHANI TALATAH NUSANTARA_INV0001.pdf
├── PEMBAYARAN/
│   └── MADHANI TALATAH NUSANTARA/
│       └── SP_MADHANI TALATAH NUSANTARA_SP0001.pdf
└── FINAL/                              ← (akan ditambahkan)
    └── MADHANI TALATAH NUSANTARA/
        └── FINAL_MADHANI TALATAH_NUSANTARA_BA0001_INV0001_SP0001.pdf
```

### Merge yang Sudah Ada (Tidak Terpengaruh)

Saat ini sudah ada merge logic di `ProcessScanFile.php` (line 149-203):
- **Tujuan**: Menggabungkan scan **jenis dokumen yang SAMA** dengan **nomor yang SAMA** (misal: beberapa scan halaman Invoice yang sama)
- **Trigger**: Saat file baru di-upload dan file dengan path FTP yang sama sudah ada
- **Hasil**: File di path yang sama ditambah halamannya

**Merge baru** yang akan dibuat:
- **Tujuan**: Menggabungkan **jenis dokumen BERBEDA** (BA + INV + SP) yang saling terhubung
- **Trigger**: Ketika semua dokumen dalam satu alur birokrasi sudah lengkap
- **Hasil**: File PDF baru di folder `FINAL/` dengan nama gabungan

**Kedua merge ini BERBEDA dan TIDAK BERTABRAKAN.**

---

## Alur Birokrasi yang Diinginkan

```
Step 1: Upload BA
         │
         ▼
    ┌─────────────────┐
    │ BA_DOKUMEN.pdf   │ ──→ FTP: BERITA ACARA/VENDOR/BA_VENDOR_BA0001.pdf
    │ No BA: BA0001    │ ──→ Buat Merge Group (status: pending)
    └────────┬────────┘
             │
             ▼
Step 2: Upload INV (dengan referensi ke BA)
         │
         ▼
    ┌─────────────────┐
    │ INV_DOKUMEN.pdf  │ ──→ FTP: INVOICE/VENDOR/INV_VENDOR_INV0001.pdf
    │ No Inv: INV0001  │ ──→ Extract "No BA: BA0001" dari OCR
    │ No BA: BA0001    │ ──→ Tambahkan ke Merge Group BA0001
    └────────┬────────┘
             │
             ▼
Step 3: Upload SP (dengan referensi ke INV)
         │
         ▼
    ┌─────────────────┐
    │ SP_DOKUMEN.pdf   │ ──→ FTP: PEMBAYARAN/VENDOR/SP_VENDOR_SP0001.pdf
    │ No SP: SP0001    │ ──→ Extract "No Inv: INV0001" dari OCR
    │ No Inv: INV0001  │ ──→ Find Merge Group via INV → BA0001
    └────────┬────────┘
             │
             ▼
    ┌─────────────────────────────────────────┐
    │ Semua dokumen lengkap?                   │
    │ BA ✓ | INV ✓ | SP ✓                     │
    │                                          │
    │ YA → Trigger Final Merge                 │
    │      Download BA + INV + SP dari FTP     │
    │      Gabung jadi 1 PDF                   │
    │      Upload ke FINAL/VENDOR/             │
    │      FINAL_VENDOR_BA0001_INV0001_SP0001  │
    └─────────────────────────────────────────┘
```

---

## Design Database

### 1. Tabel `merge_flows` (Definisi Alur Birokrasi)

Menyimpan konfigurasi alur birokrasi. Satu flow bisa memiliki banyak step.

```php
Schema::create('merge_flows', function (Blueprint $table) {
    $table->id();
    $table->string('name');           // "BA-INV-SP"
    $table->string('slug')->unique(); // "ba-inv-sp"
    $table->text('description')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

### 2. Tabel `merge_flow_steps` (Step dalam Alur)

Menyimpan urutan dokumen dalam satu alur. Setiap step adalah satu jenis dokumen.

```php
Schema::create('merge_flow_steps', function (Blueprint $table) {
    $table->id();
    $table->foreignId('merge_flow_id')->constrained()->cascadeOnDelete();
    $table->foreignId('document_type_id')->constrained()->cascadeOnDelete();
    $table->unsignedSmallInteger('order');       // Urutan: 1, 2, 3...
    $table->string('link_regex')->nullable();    // Regex untuk extract nomor induk
    $table->string('link_label')->nullable();    // Label: "No BA", "No Inv"
    $table->timestamps();

    $table->unique(['merge_flow_id', 'order']);
    $table->unique(['merge_flow_id', 'document_type_id']);
});
```

**Contoh Data:**

| merge_flow_id | document_type_id | order | link_regex | link_label |
|---------------|-----------------|-------|------------|------------|
| 1 | 1 (BA) | 1 | NULL | NULL |
| 1 | 2 (INV) | 2 | `/No\s*BA\s*\n?\s*:\s*(.+)/i` | No BA |
| 1 | 3 (SP) | 3 | `/No\s*Inv\s*\n?\s*:\s*(.+)/i` | No Inv |

**Penjelasan:**
- Step 1 (BA): `link_regex = NULL` karena BA adalah dokumen root (tidak link ke dokumen lain)
- Step 2 (INV): `link_regex` untuk extract nomor BA dari OCR text INV
- Step 3 (SP): `link_regex` untuk extract nomor INV dari OCR text SP

### 3. Tabel `document_merge_groups` (Grup Penggabungan)

Menyimpan satu grup yang siap/sedang digabung. Grup diidentifikasi oleh vendor + nomor root document.

```php
Schema::create('document_merge_groups', function (Blueprint $table) {
    $table->id();
    $table->foreignId('merge_flow_id')->constrained();
    $table->string('vendor_name');
    $table->string('root_document_number');       // Nomor dokumen root (BA0001)
    $table->unsignedSmallInteger('status')->default(0); // 0=pending, 1=complete, 2=merged
    $table->string('final_pdf_path')->nullable(); // Path file final di FTP
    $table->timestamp('merged_at')->nullable();
    $table->timestamps();

    $table->unique(['merge_flow_id', 'vendor_name', 'root_document_number']);
    $table->index('status');
});
```

**Status:**
- `0` = **pending** - Belum semua dokumen lengkap
- `1` = **complete** - Semua dokumen sudah ada, menunggu merge
- `2` = **merged** - File final sudah digabung dan diupload

### 4. Tabel `document_merge_group_items` (Item dalam Grup)

Menyimpan setiap dokumen yang sudah ter-upload dalam satu grup.

```php
Schema::create('document_merge_group_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('merge_group_id')->constrained()->cascadeOnDelete();
    $table->foreignId('document_type_id')->constrained();
    $table->foreignId('scan_log_id')->constrained();
    $table->string('document_number');       // "BA0001", "INV0001", "SP0001"
    $table->unsignedSmallInteger('order');   // Urutan dalam merge
    $table->string('ftp_path');              // Path file di FTP
    $table->timestamps();

    $table->unique(['merge_group_id', 'document_type_id']);
});
```

### 5. Kolom Baru di `scan_logs`

```php
Schema::table('scan_logs', function (Blueprint $table) {
    $table->json('linked_numbers')->nullable()->after('metadata');
    // Contoh untuk INV: {"no_ba": "BA0001"}
    // Contoh untuk SP: {"no_inv": "INV0001"}
    // Untuk BA: NULL (tidak ada linking)
});
```

### ER Diagram

```
┌──────────────────┐       ┌──────────────────────┐
│   merge_flows    │       │  document_types      │
├──────────────────┤       ├──────────────────────┤
│ id               │       │ id                   │
│ name             │       │ name                 │
│ slug (unique)    │       │ header_regex         │
│ description      │       │ number_regex         │
│ is_active        │       │ number_label         │
│ timestamps       │       │ filename_template    │
└────────┬─────────┘       │ ftp_folder_template  │
         │                 │ ...                  │
         │ 1:N             └──────────┬───────────┘
         ▼                            │
┌──────────────────────┐              │
│  merge_flow_steps    │              │
├──────────────────────┤              │
│ id                   │              │
│ merge_flow_id (FK)   │              │
│ document_type_id (FK)│──────────────┘
│ order                │
│ link_regex (nullable)│
│ link_label (nullable)│
│ timestamps           │
└──────────────────────┘

┌─────────────────────────┐     ┌──────────────────────┐
│ document_merge_groups   │     │     scan_logs        │
├─────────────────────────┤     ├──────────────────────┤
│ id                      │     │ id                   │
│ merge_flow_id (FK)      │     │ document_number      │
│ vendor_name             │     │ vendor_name          │
│ root_document_number    │     │ ftp_path             │
│ status (0/1/2)          │     │ linked_numbers (JSON)│
│ final_pdf_path          │     │ ...                  │
│ merged_at               │     └──────────┬───────────┘
│ timestamps              │                │
└────────────┬────────────┘                │
             │                             │
             │ 1:N                         │ 1:1
             ▼                             │
┌──────────────────────────┐               │
│ document_merge_group_items│              │
├──────────────────────────┤               │
│ id                       │               │
│ merge_group_id (FK)      │               │
│ document_type_id (FK)    │───────────────┘
│ scan_log_id (FK)         │───────────────┘
│ document_number          │
│ order                    │
│ ftp_path                 │
│ timestamps               │
└──────────────────────────┘
```

---

## Alur Proses Detail

### FASE 1: Upload & OCR (Sistem yang Sudah Ada - Tidak Diubah)

Proses ini sudah berjalan dan **tidak perlu diubah**:

```
1. File masuk ke FTP scanner (/incoming)
2. MonitorScanner command mendeteksi file baru
3. File didownload ke local disk
4. Document type dideteksi (via header_regex)
5. OCR diekstrak teksnya
6. Document number, vendor, tanggal, keterangan, uraian di-extract
7. File dikonversi ke PDF (jika bukan PDF)
8. File di-upload ke ftp_final
9. Jika file sudah ada di path yang sama → merge (existing merge)
10. Log dicatat ke scan_logs
```

### FASE 2: Merge Flow Processing (BARU - Ditambahkan)

Setelah FASE 1 selesai (`job_completed`), jalankan:

```
┌────────────────────────────────────────────────────────────────────┐
│ STEP 1: Cek apakah document_type ini participates dalam merge flow │
│                                                                    │
│ Query: merge_flow_steps WHERE document_type_id = {current_type}    │
│                                                                    │
│ IF NOT FOUND → return (tidak ikut merge flow)                      │
│ IF FOUND → lanjut ke STEP 2                                       │
└────────────────────────────────┬───────────────────────────────────┘
                                 │
                                 ▼
┌────────────────────────────────────────────────────────────────────┐
│ STEP 2: Tentukan apakah ini root document atau child document      │
│                                                                    │
│ IF order = 1 (ROOT):                                               │
│   → Buat merge group baru (status: pending)                        │
│   → Tambahkan item ini ke group                                    │
│   → return                                                         │
│                                                                    │
│ IF order > 1 (CHILD):                                              │
│   → Extract linked number menggunakan link_regex dari OCR text     │
│   → Contoh: INV extract "No BA: BA0001" → linked_number = BA0001  │
│   → Lanjut ke STEP 3                                               │
└────────────────────────────────┬───────────────────────────────────┘
                                 │
                                 ▼
┌────────────────────────────────────────────────────────────────────┐
│ STEP 3: Cari Merge Group induk                                     │
│                                                                    │
│ Cari document_merge_group_items di mana:                           │
│   - merge_group.vendor_name = vendor_name dokumen saat ini         │
│   - item.document_number = linked_number                           │
│   - item.order = order - 1 (parent document)                       │
│                                                                    │
│ IF FOUND → dapatkan merge_group_id                                 │
│ IF NOT FOUND → buat merge group baru dengan status pending         │
│               (menunggu root document di-upload)                    │
│                                                                    │
│ Tambahkan item ini ke group                                        │
└────────────────────────────────┬───────────────────────────────────┘
                                 │
                                 ▼
┌────────────────────────────────────────────────────────────────────┐
│ STEP 4: Cek apakah group sudah lengkap                             │
│                                                                    │
│ Hitung: total steps dalam merge_flow                               │
│ Hitung: total items dalam merge_group                              │
│                                                                    │
│ IF total_items >= total_steps:                                     │
│   → Update status group ke 'complete' (1)                          │
│   → Trigger STEP 5 (Final Merge)                                   │
│ ELSE:                                                              │
│   → Update status tetap 'pending' (0)                              │
│   → return                                                         │
└────────────────────────────────┬───────────────────────────────────┘
                                 │
                                 ▼
┌────────────────────────────────────────────────────────────────────┐
│ STEP 5: Final Merge - Gabungkan semua PDF                          │
│                                                                    │
│ 5a. Download semua PDF dari FTP berdasarkan items (order 1,2,3...)  │
│     - BA: BERITA ACARA/VENDOR/BA_VENDOR_BA0001.pdf                 │
│     - INV: INVOICE/VENDOR/INV_VENDOR_INV0001.pdf                   │
│     - SP: PEMBAYARAN/VENDOR/SP_VENDOR_SP0001.pdf                   │
│                                                                    │
│ 5b. Generate nama file final:                                      │
│     FINAL_{VENDOR}_{BA_NO}_{INV_NO}_{SP_NO}.pdf                    │
│     Contoh: FINAL_MADHANI TALATAH_NUSANTARA_BA0001_INV0001_SP0001  │
│                                                                    │
│ 5c. Merge menggunakan PdfMergeService::mergePdfs()                  │
│                                                                    │
│ 5d. Upload hasil merge ke:                                         │
│     FINAL/{VENDOR}/{final_filename}.pdf                            │
│                                                                    │
│ 5e. Update merge group:                                            │
│     - status = 2 (merged)                                          │
│     - final_pdf_path = FINAL/VENDOR/final_filename.pdf             │
│     - merged_at = now()                                            │
│                                                                    │
│ 5f. Log hasil merge ke scan_logs                                   │
└────────────────────────────────────────────────────────────────────┘
```

### Contoh Kasus Lengkap

#### Kasus 1: Upload BA → INV → SP (Normal)

```
1. User scan BA → OCR extract "No BA: BA0001"
   → Upload ke FTP: BERITA ACARA/MADHANI TALATAH NUSANTARA/BA_MADHANI TALATAH NUSANTARA_BA0001.pdf
   → Merge Flow Check: BA = root (order 1)
   → Buat merge_group: {vendor: "MADHANI TALATAH NUSANTARA", root: "BA0001", status: 0}
   → Tambah item: {doc_type: BA, order: 1, number: BA0001}
   → Status: pending (1/3 documents)

2. User scan INV → OCR extract "No Inv: INV0001" + "No BA: BA0001"
   → Upload ke FTP: INVOICE/MADHANI TALATAH NUSANTARA/INV_MADHANI TALATAH NUSANTARA_INV0001.pdf
   → Merge Flow Check: INV = child (order 2), link = "BA0001"
   → Cari merge_group where root_number = "BA0001" AND vendor = "MADHANI TALATAH NUSANTARA"
   → DITEMUKAN → Tambah item: {doc_type: INV, order: 2, number: INV0001}
   → Status: pending (2/3 documents)

3. User scan SP → OCR extract "No SP: SP0001" + "No Inv: INV0001"
   → Upload ke FTP: PEMBAYARAN/MADHANI TALATAH NUSANTARA/SP_MADHANI TALATAH NUSANTARA_SP0001.pdf
   → Merge Flow Check: SP = child (order 3), link = "INV0001"
   → Cari merge_group via INV0001 → BA0001
   → DITEMUKAN → Tambah item: {doc_type: SP, order: 3, number: SP0001}
   → Status: complete (3/3 documents) → TRIGGER FINAL MERGE
   → Download BA + INV + SP dari FTP
   → Merge → FINAL/MADHANI TALATAH NUSANTARA/FINAL_MADHANI TALATAH_NUSANTARA_BA0001_INV0001_SP0001.pdf
   → Status: merged
```

#### Kasus 2: Upload INV Sebelum BA

```
1. User scan INV → OCR extract "No Inv: INV0001" + "No BA: BA0001"
   → Upload ke FTP: INVOICE/MADHANI TALATAH NUSANTARA/INV_MADHANI TALATAH NUSANTARA_INV0001.pdf
   → Merge Flow Check: INV = child (order 2), link = "BA0001"
   → Cari merge_group where root_number = "BA0001" → TIDAK DITEMUKAN
   → Buat merge_group baru: {root: "BA0001", status: 0}
   → Tambah item: {doc_type: INV, order: 2, number: INV0001}
   → Status: pending (1/3 documents) — menunggu BA

2. User scan BA → OCR extract "No BA: BA0001"
   → Upload ke FTP: BERITA ACARA/MADHANI TALATAH NUSANTARA/BA_MADHANI TALATAH NUSANTARA_BA0001.pdf
   → Merge Flow Check: BA = root (order 1)
   → Cari merge_group where root_number = "BA0001" → DITEMUKAN (sudah ada dari step 1)
   → Tambah item: {doc_type: BA, order: 1, number: BA0001}
   → Status: pending (2/3 documents) — menunggu SP

3. User scan SP → ... → complete → merge
```

#### Kasus 3: Vendor Berbeda

```
1. BA untuk MADHANI TALATAH NUSANTARA → group terpisah
2. BA untuk PT. LAINNYA → group terpisah lainnya
→ Vendor adalah bagian dari kunci unik group
```

#### Kasus 4: Flow yang Bisa Dikustomisasi

```
User bisa membuat flow baru melalui UI:

Flow: "SPK-PO-GR"
Steps:
  1. SPK (order 1, root)
  2. PO (order 2, link ke SPK via regex)
  3. GR (order 3, link ke PO via regex)

Flow: "QUOTATION-CONTRACT-SO"
Steps:
  1. QUOTATION (order 1, root)
  2. CONTRACT (order 2, link ke QUOTATION)
  3. SO (order 3, link ke CONTRACT)
```

---

## Komponen yang Perlu Dibuat/Dimodifikasi

### 1. Migration (4 file baru)

| No | File | Keterangan |
|----|------|------------|
| 1 | `database/migrations/xxxx_create_merge_flows_table.php` | Tabel definisi alur |
| 2 | `database/migrations/xxxx_create_merge_flow_steps_table.php` | Tabel step alur |
| 3 | `database/migrations/xxxx_create_document_merge_groups_table.php` | Tabel grup merge |
| 4 | `database/migrations/xxxx_create_document_merge_group_items_table.php` | Tabel item grup merge |
| 5 | `database/migrations/xxxx_add_linked_numbers_to_scan_logs_table.php` | Kolom tambahan scan_logs |

### 2. Model (4 file baru)

| No | File | Keterangan |
|----|------|------------|
| 1 | `app/Models/MergeFlow.php` | Model merge flow |
| 2 | `app/Models/MergeFlowStep.php` | Model merge flow step |
| 3 | `app/Models/DocumentMergeGroup.php` | Model merge group |
| 4 | `app/Models/DocumentMergeGroupItem.php` | Model merge group item |

### 3. Service (1 file baru, 1 file modifikasi)

| No | File | Aksi | Keterangan |
|----|------|------|------------|
| 1 | `app/Services/MergeFlowService.php` | **BARU** | Core logic untuk merge flow processing |
| 2 | `app/Jobs/ProcessScanFile.php` | Modifikasi | Tambahkan trigger ke MergeFlowService setelah upload berhasil |

### 4. Controller (1 file baru)

| No | File | Aksi | Keterangan |
|----|------|------|------------|
| 1 | `app/Http/Controllers/Dokter/MergeFlowController.php` | **BARU** | CRUD merge flow + view merge groups |

### 5. Routes (1 file modifikasi)

| No | File | Aksi | Keterangan |
|----|------|------|------------|
| 1 | `routes/routers/dokter.php` | Modifikasi | Tambah routes untuk merge flow management |

### 6. Views (4 file baru)

| No | File | Keterangan |
|----|------|------------|
| 1 | `resources/views/dokter/merge-flow/index.blade.php` | Daftar merge flows + merge groups |
| 2 | `resources/views/dokter/merge-flow/create.blade.php` | Form buat merge flow baru |
| 3 | `resources/views/dokter/merge-flow/edit.blade.php` | Form edit merge flow |
| 4 | `resources/views/dokter/merge-flow/groups.blade.php` | Daftar merge groups + status |

### 7. Seeder (1 file baru)

| No | File | Keterangan |
|----|------|------------|
| 1 | `database/seeders/MergeFlowSeeder.php` | Seed flow BA-INV-SP |

---

## Detail Implementasi

### 1. `MergeFlowService.php` (Core Logic)

```php
<?php

namespace App\Services;

use App\Models\DocumentMergeGroup;
use App\Models\DocumentMergeGroupItem;
use App\Models\MergeFlow;
use App\Models\MergeFlowStep;
use App\Models\ScanLog;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MergeFlowService
{
    /**
     * Dipanggil setelah ProcessScanFile berhasil upload file ke FTP.
     * Memeriksa apakah dokumen ini bagian dari merge flow dan memprosesnya.
     */
    public function processAfterUpload(ScanLog $scanLog, string $ocrText): void
    {
        $documentType = $scanLog->documentType;
        if (!$documentType) return;

        // Cari step di mana document_type ini berpartisipasi
        $step = MergeFlowStep::where('document_type_id', $documentType->id)
            ->with('mergeFlow')
            ->first();

        if (!$step || !$step->mergeFlow->is_active) return;

        $vendorName = $scanLog->vendor_name;
        $documentNumber = $scanLog->document_number;

        if ($step->order === 1) {
            // ROOT document
            $this->handleRootDocument($step, $scanLog, $vendorName, $documentNumber);
        } else {
            // CHILD document
            $this->handleChildDocument($step, $scanLog, $ocrText, $vendorName, $documentNumber);
        }
    }

    protected function handleRootDocument(MergeFlowStep $step, ScanLog $scanLog, string $vendorName, string $documentNumber): void
    {
        $group = DocumentMergeGroup::firstOrCreate(
            [
                'merge_flow_id' => $step->merge_flow_id,
                'vendor_name' => $vendorName,
                'root_document_number' => $documentNumber,
            ],
            ['status' => 0]
        );

        // Tambahkan item jika belum ada
        DocumentMergeGroupItem::updateOrCreate(
            ['merge_group_id' => $group->id, 'document_type_id' => $scanLog->document_type_id],
            [
                'scan_log_id' => $scanLog->id,
                'document_number' => $documentNumber,
                'order' => $step->order,
                'ftp_path' => $scanLog->ftp_path,
            ]
        );

        $this->checkAndTriggerMerge($group, $step->mergeFlow);
    }

    protected function handleChildDocument(MergeFlowStep $step, ScanLog $scanLog, string $ocrText, string $vendorName, string $documentNumber): void
    {
        // Extract linked number dari OCR text
        $linkedNumber = $this->extractLinkedNumber($step, $ocrText);
        if (!$linkedNumber) {
            Log::warning('Could not extract linked number from child document', [
                'scan_log_id' => $scanLog->id,
                'link_regex' => $step->link_regex,
            ]);
            return;
        }

        // Simpan linked_numbers ke scan_log
        $linkedNumbers = $scanLog->linked_numbers ?? [];
        $linkedNumbers[$step->link_label ?? 'linked_number'] = $linkedNumber;
        $scanLog->update(['linked_numbers' => $linkedNumbers]);

        // Cari parent step (order - 1)
        $parentStep = MergeFlowStep::where('merge_flow_id', $step->merge_flow_id)
            ->where('order', $step->order - 1)
            ->first();

        if (!$parentStep) {
            Log::warning('Parent step not found', ['step_id' => $step->id]);
            return;
        }

        // Cari merge group berdasarkan parent document number + vendor
        $group = DocumentMergeGroup::where('merge_flow_id', $step->merge_flow_id)
            ->where('vendor_name', $vendorName)
            ->whereHas('items', function ($q) use ($linkedNumber, $parentStep) {
                $q->where('document_number', $linkedNumber)
                  ->where('order', $parentStep->order);
            })
            ->first();

        if (!$group) {
            // Buat group baru (root belum di-upload)
            $group = DocumentMergeGroup::create([
                'merge_flow_id' => $step->merge_flow_id,
                'vendor_name' => $vendorName,
                'root_document_number' => $linkedNumber, // sementara, akan diupdate jika root upload
                'status' => 0,
            ]);
        }

        // Tambahkan item
        DocumentMergeGroupItem::updateOrCreate(
            ['merge_group_id' => $group->id, 'document_type_id' => $scanLog->document_type_id],
            [
                'scan_log_id' => $scanLog->id,
                'document_number' => $documentNumber,
                'order' => $step->order,
                'ftp_path' => $scanLog->ftp_path,
            ]
        );

        $this->checkAndTriggerMerge($group, $step->mergeFlow);
    }

    protected function extractLinkedNumber(MergeFlowStep $step, string $ocrText): ?string
    {
        if (!$step->link_regex) return null;

        if (preg_match($step->link_regex, $ocrText, $matches)) {
            $raw = trim($matches[1]);
            // Bersihkan OCR noise
            $cleaned = preg_replace('/[\x00-\x1F\x7F]/', ' ', $raw);
            $cleaned = preg_replace('/\s{2,}/', ' ', $cleaned);
            $cleaned = trim($cleaned);
            return $cleaned !== '' ? $cleaned : null;
        }

        return null;
    }

    protected function checkAndTriggerMerge(DocumentMergeGroup $group, MergeFlow $flow): void
    {
        $totalSteps = $flow->steps()->count();
        $totalItems = $group->items()->count();

        if ($totalItems >= $totalSteps) {
            $group->update(['status' => 1]); // complete
            $this->performFinalMerge($group, $flow);
        }
    }

    protected function performFinalMerge(DocumentMergeGroup $group, MergeFlow $flow): void
    {
        $ftpDisk = Storage::disk('ftp_final');
        $items = $group->items()->orderBy('order')->get();
        $tempFiles = [];

        try {
            // 1. Download semua PDF dari FTP
            foreach ($items as $item) {
                if (!$ftpDisk->exists($item->ftp_path)) {
                    throw new Exception("File not found on FTP: {$item->ftp_path}");
                }
                $content = $ftpDisk->get($item->ftp_path);
                $tempFile = storage_path("app/private/scanner/temp/merge_flow_{$group->id}_{$item->order}.pdf");
                $this->ensureDirectoryExists(dirname($tempFile));
                file_put_contents($tempFile, $content);
                $tempFiles[] = $tempFile;
            }

            // 2. Generate nama file final
            $vendorSlug = strtoupper($group->vendor_name);
            $numbers = $items->pluck('document_number')->implode('_');
            $finalFilename = "FINAL_{$vendorSlug}_{$numbers}.pdf";

            // 3. Merge PDFs
            $mergedDir = storage_path("app/private/scanner/merged_flow");
            $this->ensureDirectoryExists($mergedDir);
            $mergedPath = $mergedDir . '/' . $finalFilename;

            $merger = app(PdfMergeService::class);
            $merger->mergePdfs($tempFiles, $mergedPath);

            // 4. Upload ke FTP
            $finalPath = "FINAL/{$group->vendor_name}/{$finalFilename}";
            $mergedContent = file_get_contents($mergedPath);
            $ftpDisk->put($finalPath, $mergedContent);

            // 5. Update group
            $group->update([
                'status' => 2,
                'final_pdf_path' => $finalPath,
                'merged_at' => now(),
            ]);

            // 6. Log
            $totalPages = $merger->getPageCount($mergedPath);
            app(ScanLogger::class)->log('final_merge_completed', 'success', [
                'merge_group_id' => $group->id,
                'vendor_name' => $group->vendor_name,
                'root_document_number' => $group->root_document_number,
                'final_pdf_path' => $finalPath,
                'total_documents' => $items->count(),
                'total_pages' => $totalPages,
                'message' => "Final merge selesai: {$items->count()} dokumen, {$totalPages} halaman",
            ]);

            Log::info('Final merge completed', [
                'group_id' => $group->id,
                'final_path' => $finalPath,
            ]);

        } catch (Exception $e) {
            Log::error('Final merge failed', [
                'group_id' => $group->id,
                'error' => $e->getMessage(),
            ]);

            app(ScanLogger::class)->log('final_merge_failed', 'failed', [
                'merge_group_id' => $group->id,
                'vendor_name' => $group->vendor_name,
                'root_document_number' => $group->root_document_number,
                'message' => "Final merge gagal: {$e->getMessage()}",
            ]);

        } finally {
            // Cleanup temp files
            foreach ($tempFiles as $file) {
                @unlink($file);
            }
            @unlink($mergedPath ?? '');
        }
    }

    protected function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }
}
```

### 2. Modifikasi `ProcessScanFile.php`

Tambahkan 2 baris setelah log `job_completed` (sekitar line 255):

```php
// === BARU: Trigger merge flow processing ===
if ($scanLog = ScanLog::where('filename', $this->filename)->latest()->first()) {
    app(MergeFlowService::class)->processAfterUpload($scanLog, $ocrText);
}
```

**Catatan penting:** `ocrText` perlu di-pass ke logging agar tersedia untuk `processAfterUpload`. Alternatif: simpan `ocrText` di `metadata` scan_log, atau simpan di temporary file.

**Pilihan lebih baik:** Simpan OCR text di metadata scan_log agar tersedia untuk merge flow processing:

Di ProcessScanFile.php, tambahkan `ocr_text` ke data logging (sekitar line 241):
```php
$logger->log('job_completed', 'success', [
    // ... existing fields ...
    'ocr_text' => $ocrText,  // ← TAMBAHKAN INI
]);
```

Dan tambahkan `ocr_text` ke `ScanLog` fillable.

### 3. `MergeFlowController.php` (CRUD)

```php
<?php

namespace App\Http\Controllers\Dokter;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Models\DocumentMergeGroup;
use App\Models\MergeFlow;
use Illuminate\Http\Request;

class MergeFlowController extends Controller
{
    public function index()
    {
        $pageName = 'Alur Birokrasi';
        $flows = MergeFlow::with('steps.documentType')->get();
        $pendingGroups = DocumentMergeGroup::where('status', 0)->with(['mergeFlow', 'items.documentType'])->latest()->paginate(20);
        $completeGroups = DocumentMergeGroup::where('status', 1)->with(['mergeFlow', 'items.documentType'])->latest()->paginate(20);

        return view('dokter.merge-flow.index', compact('pageName', 'flows', 'pendingGroups', 'completeGroups'));
    }

    public function create()
    {
        $pageName = 'Buat Alur Birokrasi';
        $documentTypes = DocumentType::orderBy('name')->get();

        return view('dokter.merge-flow.create', compact('pageName', 'documentTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'steps' => 'required|array|min:2',
            'steps.*.document_type_id' => 'required|exists:document_types,id',
            'steps.*.link_regex' => 'nullable|string',
            'steps.*.link_label' => 'nullable|string',
        ]);

        $flow = MergeFlow::create([
            'name' => $validated['name'],
            'slug' => \Illuminate\Support\Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
        ]);

        foreach ($validated['steps'] as $index => $step) {
            $flow->steps()->create([
                'document_type_id' => $step['document_type_id'],
                'order' => $index + 1,
                'link_regex' => $step['link_regex'] ?? null,
                'link_label' => $step['link_label'] ?? null,
            ]);
        }

        return redirect()->route('dokter.merge-flows.index')->with('success', 'Alur birokrasi berhasil dibuat!');
    }

    public function edit(MergeFlow $mergeFlow)
    {
        $pageName = 'Edit Alur Birokrasi';
        $mergeFlow->load('steps.documentType');
        $documentTypes = DocumentType::orderBy('name')->get();

        return view('dokter.merge-flow.edit', compact('pageName', 'mergeFlow', 'documentTypes'));
    }

    public function update(Request $request, MergeFlow $mergeFlow)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'steps' => 'required|array|min:2',
            'steps.*.document_type_id' => 'required|exists:document_types,id',
            'steps.*.link_regex' => 'nullable|string',
            'steps.*.link_label' => 'nullable|string',
        ]);

        $mergeFlow->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // Hapus steps lama, buat baru
        $mergeFlow->steps()->delete();
        foreach ($validated['steps'] as $index => $step) {
            $mergeFlow->steps()->create([
                'document_type_id' => $step['document_type_id'],
                'order' => $index + 1,
                'link_regex' => $step['link_regex'] ?? null,
                'link_label' => $step['link_label'] ?? null,
            ]);
        }

        return redirect()->route('dokter.merge-flows.index')->with('success', 'Alur birokrasi berhasil diperbarui!');
    }

    public function destroy(MergeFlow $mergeFlow)
    {
        $mergeFlow->delete();
        return redirect()->route('dokter.merge-flows.index')->with('success', 'Alur birokrasi berhasil dihapus!');
    }

    public function groups(Request $request)
    {
        $pageName = 'Grup Penggabungan';
        $query = DocumentMergeGroup::with(['mergeFlow', 'items.documentType', 'items.scanLog']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('vendor_name')) {
            $query->where('vendor_name', 'like', "%{$request->vendor_name}%");
        }

        $groups = $query->latest()->paginate(20);

        return view('dokter.merge-flow.groups', compact('pageName', 'groups'));
    }
}
```

### 4. Model-Model Baru

#### `MergeFlow.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MergeFlow extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(MergeFlowStep::class)->orderBy('order');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(DocumentMergeGroup::class);
    }
}
```

#### `MergeFlowStep.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MergeFlowStep extends Model
{
    protected $fillable = ['merge_flow_id', 'document_type_id', 'order', 'link_regex', 'link_label'];

    public function mergeFlow(): BelongsTo
    {
        return $this->belongsTo(MergeFlow::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }
}
```

#### `DocumentMergeGroup.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentMergeGroup extends Model
{
    protected $fillable = [
        'merge_flow_id', 'vendor_name', 'root_document_number',
        'status', 'final_pdf_path', 'merged_at',
    ];

    protected $casts = [
        'merged_at' => 'datetime',
    ];

    public function mergeFlow(): BelongsTo
    {
        return $this->belongsTo(MergeFlow::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DocumentMergeGroupItem::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            0 => 'Pending',
            1 => 'Lengkap',
            2 => 'Selesai',
            default => 'Unknown',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            0 => 'bg-warning',
            1 => 'bg-info',
            2 => 'bg-success',
            default => 'bg-secondary',
        };
    }
}
```

#### `DocumentMergeGroupItem.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentMergeGroupItem extends Model
{
    protected $fillable = [
        'merge_group_id', 'document_type_id', 'scan_log_id',
        'document_number', 'order', 'ftp_path',
    ];

    public function mergeGroup(): BelongsTo
    {
        return $this->belongsTo(DocumentMergeGroup::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function scanLog(): BelongsTo
    {
        return $this->belongsTo(ScanLog::class);
    }
}
```

### 5. Routes

```php
// routes/routers/dokter.php - tambahkan di dalam group dokter

Route::middleware('permission:dokter.merge-flows.view')->group(function () {
    Route::get('merge-flows', [MergeFlowController::class, 'index'])->name('merge-flows.index');
    Route::get('merge-flows/groups', [MergeFlowController::class, 'groups'])->name('merge-flows.groups');
});
Route::middleware('permission:dokter.merge-flows.create')->group(function () {
    Route::get('merge-flows/create', [MergeFlowController::class, 'create'])->name('merge-flows.create');
    Route::post('merge-flows', [MergeFlowController::class, 'store'])->name('merge-flows.store');
});
Route::middleware('permission:dokter.merge-flows.edit')->group(function () {
    Route::get('merge-flows/{mergeFlow}/edit', [MergeFlowController::class, 'edit'])->name('merge-flows.edit');
    Route::put('merge-flows/{mergeFlow}', [MergeFlowController::class, 'update'])->name('merge-flows.update');
});
Route::middleware('permission:dokter.merge-flows.delete')->group(function () {
    Route::delete('merge-flows/{mergeFlow}', [MergeFlowController::class, 'destroy'])->name('merge-flows.destroy');
});
```

### 6. Seeder

```php
<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use App\Models\MergeFlow;
use App\Models\MergeFlowStep;
use Illuminate\Database\Seeder;

class MergeFlowSeeder extends Seeder
{
    public function run(): void
    {
        $ba = DocumentType::where('slug', 'berita-acara')->first();
        $inv = DocumentType::where('slug', 'invoice')->first();
        $sp = DocumentType::where('slug', 'slip-pembayaran')->first();

        if (!$ba || !$inv || !$sp) {
            $this->command->warn('Document types BA, INV, or SP not found. Skipping merge flow seed.');

            return;
        }

        $flow = MergeFlow::create([
            'name' => 'BA-INV-SP',
            'slug' => 'ba-inv-sp',
            'description' => 'Alur birokrasi: Berita Acara → Invoice → Slip Pembayaran',
            'is_active' => true,
        ]);

        MergeFlowStep::create([
            'merge_flow_id' => $flow->id,
            'document_type_id' => $ba->id,
            'order' => 1,
            'link_regex' => null,
            'link_label' => null,
        ]);

        MergeFlowStep::create([
            'merge_flow_id' => $flow->id,
            'document_type_id' => $inv->id,
            'order' => 2,
            'link_regex' => '/No\s*BA\s*\n?\s*:\s*(.+)/i',
            'link_label' => 'No BA',
        ]);

        MergeFlowStep::create([
            'merge_flow_id' => $flow->id,
            'document_type_id' => $sp->id,
            'order' => 3,
            'link_regex' => '/No\s*Inv\s*\n?\s*:\s*(.+)/i',
            'link_label' => 'No Inv',
        ]);
    }
}
```

---

## Daftar File yang Perlu Dibuat/Dimodifikasi

| No | File | Aksi | Keterangan |
|----|------|------|------------|
| 1 | `database/migrations/xxxx_create_merge_flows_table.php` | **BARU** | Tabel definisi alur |
| 2 | `database/migrations/xxxx_create_merge_flow_steps_table.php` | **BARU** | Tabel step alur |
| 3 | `database/migrations/xxxx_create_document_merge_groups_table.php` | **BARU** | Tabel grup merge |
| 4 | `database/migrations/xxxx_create_document_merge_group_items_table.php` | **BARU** | Tabel item grup |
| 5 | `database/migrations/xxxx_add_linked_numbers_and_ocr_text_to_scan_logs.php` | **BARU** | Kolom tambahan |
| 6 | `app/Models/MergeFlow.php` | **BARU** | Model |
| 7 | `app/Models/MergeFlowStep.php` | **BARU** | Model |
| 8 | `app/Models/DocumentMergeGroup.php` | **BARU** | Model |
| 9 | `app/Models/DocumentMergeGroupItem.php` | **BARU** | Model |
| 10 | `app/Services/MergeFlowService.php` | **BARU** | Core logic |
| 11 | `app/Http/Controllers/Dokter/MergeFlowController.php` | **BARU** | CRUD + views |
| 12 | `app/Jobs/ProcessScanFile.php` | **Modifikasi** | Tambahkan trigger ke MergeFlowService + simpan ocr_text di scan_log |
| 13 | `app/Models/ScanLog.php` | **Modifikasi** | Tambahkan `linked_numbers`, `ocr_text` ke fillable |
| 14 | `routes/routers/dokter.php` | **Modifikasi** | Tambah routes |
| 15 | `resources/views/dokter/merge-flow/index.blade.php` | **BARU** | View index |
| 16 | `resources/views/dokter/merge-flow/create.blade.php` | **BARU** | View create |
| 17 | `resources/views/dokter/merge-flow/edit.blade.php` | **BARU** | View edit |
| 18 | `resources/views/dokter/merge-flow/groups.blade.php` | **BARU** | View groups |
| 19 | `database/seeders/MergeFlowSeeder.php` | **BARU** | Seeder BA-INV-SP |
| 20 | `resources/views/layouts/partials/sidebar.blade.php` | **Modifikasi** | Tambah menu "Alur Birokrasi" |

---

## Permissions

Tambahkan permission baru ke database (via seeder atau manual):

```
dokter.merge-flows.view
dokter.merge-flows.create
dokter.merge-flows.edit
dokter.merge-flows.delete
```

---

## Edge Cases & Error Handling

### 1. OCR Tidak Bisa Extract Linked Number
```
INV di-upload tapi regex "No BA" tidak match → linked_number = null
→ Log warning, tidak tambahkan ke group
→ User perlu upload ulang atau edit manual
```

### 2. Vendor Tidak Cocok
```
INV untuk "MADHANI" tidak akan tergabung dengan BA untuk "PT. LAIN"
→ Vendor adalah bagian dari kunci unik group
```

### 3. Root Document Belum Ada
```
INV di-upload sebelum BA → group dibuat tapi status pending
→ Ketika BA di-upload → group ter-update, cek apakah lengkap
```

### 4. File Tidak Ada di FTP
```
Semua PDF harus sudah ada di FTP final sebelum merge
→ Jika salah satu missing → throw exception, log error, group tetap complete tapi merge gagal
→ User bisa upload ulang dokumen yang missing
```

### 5. Race Condition (Concurrent Upload)
```
Gunakan database transaction + lock untuk prevent double merge
→ DB::transaction(function() { ... })
```

### 6. Re-Scan Document
```
Jika dokumen di-scan ulang (nomor sama, vendor sama)
→ updateOrCreate akan update item yang sudah ada
→ Jika sudah merged, buat group baru dengan status pending
```

---

## Testing Strategy

### Unit Test

```php
// tests/Unit/Services/MergeFlowServiceTest.php

class MergeFlowServiceTest extends TestCase
{
    public function test_root_document_creates_merge_group() { ... }
    public function test_child_document_links_to_parent_group() { ... }
    public function test_complete_group_triggers_final_merge() { ... }
    public function test_incomplete_group_stays_pending() { ... }
    public function test_different_vendors_create_separate_groups() { ... }
    public function test_extract_linked_number_from_ocr_text() { ... }
}
```

### Integration Test

```php
// tests/Feature/Jobs/MergeFlowIntegrationTest.php

class MergeFlowIntegrationTest extends TestCase
{
    public function test_full_ba_inv_sp_flow() { ... }
    public function test_inv_before_ba_flow() { ... }
    public function test_final_merge_creates_pdf_on_ftp() { ... }
}
```

---

## Rollback Plan

### 1. Environment Variable Toggle
```env
MERGE_FLOW_ENABLED=true
```

Di `MergeFlowService::processAfterUpload()`:
```php
if (!config('services.merge_flow_enabled', true)) {
    return;
}
```

### 2. Rollback Migration
```bash
php artisan migrate:rollback
```

### 3. Disable Flow via Database
Set `is_active = false` pada `merge_flows` table.

---

## Timeline Estimasi

| No | Task | Estimasi |
|----|------|----------|
| 1 | Buat 5 migration | 1.5 jam |
| 2 | Buat 4 model | 1 jam |
| 3 | Buat MergeFlowService | 4 jam |
| 4 | Modifikasi ProcessScanFile | 1 jam |
| 5 | Buat MergeFlowController | 2 jam |
| 6 | Buat 4 views (Blade) | 3 jam |
| 7 | Tambah routes + permissions | 0.5 jam |
| 8 | Buat seeder | 0.5 jam |
| 9 | Unit Test | 2 jam |
| 10 | Integration Test | 1.5 jam |
| 11 | UAT & Bug Fix | 2 jam |
| **Total** | | **19 jam** |

---

## Catatan Penting

1. **Tidak mengganggu sistem yang sudah ada**: Existing merge (same-type multi-page) tetap berjalan. Merge flow baru adalah layer tambahan.

2. **Extensible**: Flow baru dapat ditambahkan melalui UI tanpa perubahan kode. Cukup buat flow baru dengan steps-nya.

3. **OCR text disimpan**: OCR text perlu disimpan di scan_log (kolom `ocr_text` atau di `metadata`) agar bisa digunakan oleh `MergeFlowService` untuk extract linked numbers.

4. **FTP structure tetap sama**: Setiap dokumen tetap di-upload ke path FTP masing-masing. Final merge menghasilkan file baru di folder `FINAL/`.

5. **Order dokumen**: Urutan merge ditentukan oleh `order` di `merge_flow_steps`. Document type yang sama bisa digunakan di multiple flows.

6. **Performance**: Merge hanya terjadi ketika semua dokumen lengkap. Tidak ada polling atau scheduled task tambahan — everything triggered by `ProcessScanFile` job completion.
