# Planning: Perbaikan Merge Flow Sesuai Kondisi Codebase Sekarang

## Ringkasan

Analisis codebase menunjukkan bahwa fitur merge flow **sudah diimplementasikan** secara lengkap. Namun, ada masalah kritis pada konfigurasi `gemini_fields` yang menyebabkan field linking (`no_ba`, `no_inv`) tidak tersimpan di `ocr_data`, sehingga mekanisme `link_field` pada merge flow tidak berfungsi. Planning ini menjelaskan analisis, masalah, dan langkah perbaikan yang diperlukan.

---

## 1. Analisis Kondisi Codebase Sekarang

### 1.1 Komponen Merge Flow yang Sudah Ada

| Komponen | Status | Lokasi |
|----------|--------|--------|
| Model `MergeFlow` | ✅ Ada | `app/Models/MergeFlow.php` |
| Model `MergeFlowStep` | ✅ Ada | `app/Models/MergeFlowStep.php` (dengan `link_field`) |
| Model `DocumentMergeGroup` | ✅ Ada | `app/Models/DocumentMergeGroup.php` |
| Model `DocumentMergeGroupItem` | ✅ Ada | `app/Models/DocumentMergeGroupItem.php` |
| Service `MergeFlowService` | ✅ Ada | `app/Services/MergeFlowService.php` |
| Controller `MergeFlowController` | ✅ Ada | `app/Http/Controllers/Dokter/MergeFlowController.php` |
| Views (index, create, edit, groups) | ✅ Ada | `resources/views/dokter/merge-flow/` |
| Migrations (4 tabel + 2 kolom tambahan) | ✅ Ada | `database/migrations/` |
| Job `ProcessScanFile` (trigger merge flow) | ✅ Ada | `app/Jobs/ProcessScanFile.php:284` |

### 1.2 Database Schema yang Sudah Ada

```
merge_flows
├── id, name, slug, description, is_active, timestamps

merge_flow_steps
├── id, merge_flow_id (FK), document_type_id (FK), order
├── link_regex (nullable)    ← fallback untuk regex lama
├── link_label (nullable)    ← untuk linked_numbers JSON
├── link_field (nullable)    ← untuk Gemini ocr_data
└── timestamps

document_merge_groups
├── id, merge_flow_id (FK), vendor_name, root_document_number
├── status (0=Pending, 1=Lengkap, 2=Selesai)
├── final_pdf_path, merged_at, timestamps

document_merge_group_items
├── id, merge_group_id (FK), document_type_id (FK), scan_log_id (FK)
├── document_number, order, ftp_path, timestamps
```

### 1.3 Alur Merge Flow yang Sudah Diimplementasikan

```
ProcessScanFile::handle()
  │
  ├── 1. OCR via Gemini → ocr_data (structured JSON)
  ├── 2. Upload file ke FTP
  ├── 3. Log scan ke scan_logs (dengan metadata.ocr_data)
  └── 4. Trigger MergeFlowService::processAfterUpload($scanLog)
        │
        ├── Cari MergeFlowStep berdasarkan document_type_id
        │
        ├── Jika order=1 (ROOT):
        │   → handleRootDocument() → Buat/grup group, tambah item
        │
        └── Jika order>1 (CHILD):
            → handleChildDocument()
            → extractLinkedNumber() → Coba link_field → link_label → link_regex
            → Cari parent group, tambah item
            → checkAndTriggerMerge() → Jika lengkap → performFinalMerge()
```

### 1.4 Mekanisme `extractLinkedNumber()` yang Sudah Ada

```php
// app/Services/MergeFlowService.php:141-172

protected function extractLinkedNumber(MergeFlowStep $step, ScanLog $scanLog, string $ocrText): ?string
{
    // Priority 1: Extract dari Gemini structured data (ocr_data)
    if ($step->link_field) {
        $ocrData = $scanLog->metadata['ocr_data'] ?? null;
        if ($ocrData && isset($ocrData[$step->link_field]) && $ocrData[$step->link_field] !== '') {
            $value = trim($ocrData[$step->link_field]);
            if ($value !== '') {
                return $value;
            }
        }
    }

    // Priority 2: Extract dari linked_numbers JSON field
    if ($step->link_label) {
        $linkedNumbers = $scanLog->linked_numbers ?? [];
        if (isset($linkedNumbers[$step->link_label]) && $linkedNumbers[$step->link_label] !== '') {
            return trim($linkedNumbers[$step->link_label]);
        }
    }

    // Priority 3: Fallback ke regex extraction (behavior lama)
    if ($step->link_regex) {
        if (preg_match($step->link_regex, $ocrText, $matches)) {
            $raw = trim($matches[1]);
            $cleaned = preg_replace('/[\x00-\x1F\x7F]/', ' ', $raw);
            $cleaned = preg_replace('/\s{2,}/', ' ', $cleaned);
            $cleaned = trim($cleaned);
            return $cleaned !== '' ? $cleaned : null;
        }
    }

    return null;
}
```

---

## 2. Analisis Masalah

### 2.1 Masalah Kritis: `gemini_fields` Tidak Include Field Linking

**Root Cause:**

Konfigurasi `gemini_fields` pada setiap document type menentukan field apa saja yang akan:
1. Dimasukkan ke prompt Gemini (agar Gemini tahu harus extract field apa)
2. Diparse dan disimpan di `ocr_data` (via `GeminiEngine::parseJsonResponse()`)

**Default fields di config:**
```php
// config/services.php:47-55
'default_fields' => [
    'document_type',
    'document_number',
    'document_date',
    'vendor_name',
    'customer',
    'keterangan',
    'uraian',
],
```

**Field linking yang dibutuhkan:**
- INV membutuhkan `no_ba` (untuk link ke BA)
- SP membutuhkan `no_inv` (untuk link ke INV)

**Masalah:** Field `no_ba` dan `no_inv` **TIDAK** ada di default fields, sehingga:
1. Gemini tidak diminta untuk extract field ini (tidak ada di prompt)
2. Meskipun Gemini mengembalikan field ini, `parseJsonResponse()` akan **membuangnya** karena validasi `allowedFields`

### 2.2 dampak Masalah

| Dampak | Penjelasan |
|--------|------------|
| `no_ba` tidak tersimpan di `ocr_data` | `GeminiEngine::parseJsonResponse()` hanya keep fields di `allowedFields` |
| `no_inv` tidak tersimpan di `ocr_data` | Sama seperti di atas |
| `extractLinkedNumber()` gagal di Priority 1 | `ocrData[$step->link_field]` selalu kosong/tidak ada |
| Fallback ke `link_label` juga gagal | `linked_numbers` tidak di-populate untuk INV/SP |
| Fallback ke `link_regex` tidak ada | Regex tidak dikonfigurasi (kosong) |
| **Merge flow tidak berfungsi** | Child document tidak bisa link ke parent |

### 2.3 Bukti Teknis dari OCR Output

**INV OCR Output (yang diberikan user):**
```json
{
    "filename": "INV.jpeg",
    "text": "{\n    \"document_type\": \"INVOICE\",\n    \"document_number\": \"INV0001\",\n    \"no_ba\": \"BA0001\",\n    ...}",
    "document_type": "INVOICE",
    "document_number": "INV0001",
    "no_ba": "BA0001",           // ← FIELD INI ADA DI RAW RESPONSE
    ...
}
```

**SP OCR Output (yang diberikan user):**
```json
{
    "filename": "SP.jpeg",
    "text": "{\n    \"document_type\": \"PEMBAYARAN\",\n    \"document_number\": \"SP0001\",\n    \"no_inv\": \"INV0001\",\n    ...}",
    "document_type": "PEMBAYARAN",
    "document_number": "SP0001",
    "no_inv": "INV0001",         // ← FIELD INI ADA DI RAW RESPONSE
    ...
}
```

**Catatan:** OCR output yang ditampilkan user sudah include `no_ba` dan `no_inv`. Ini menunjukkan Gemini sudah mengembalikan field tersebut. Namun, apakah field ini tersimpan di `ocr_data` scan_log? Itu tergantung apakah `gemini_fields` document type sudah dikonfigurasi.

---

## 3. Solusi yang Direkomendasikan

### 3.1 Pendekatan: Konfigurasi `gemini_fields` + `link_field`

```
┌─────────────────────────────────────────────────────────────────┐
│                    Alur Perbaikan                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  1. Konfigurasi gemini_fields pada document type                │
│     → INV: tambahkan "no_ba" ke gemini_fields                   │
│     → SP: tambahkan "no_inv" ke gemini_fields                   │
│                                                                  │
│  2. Konfigurasi link_field pada merge flow step                 │
│     → Step 2 (INV): link_field = "no_ba"                        │
│     → Step 3 (SP): link_field = "no_inv"                        │
│                                                                  │
│  3. Test end-to-end                                              │
│     → Upload BA → Buat merge group                              │
│     → Upload INV → Link ke BA via no_ba                         │
│     → Upload SP → Link ke INV via no_inv                        │
│     → Group lengkap → Final merge                               │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 3.2 Alasan Memilih Pendekatan Ini

1. **Tidak perlu ubah kode** — Semua mekanisme sudah diimplementasikan
2. **Hanya perlu konfigurasi** — Update `gemini_fields` di DB + `link_field` di merge flow steps
3. **Backward compatible** — `link_regex` tetap bisa sebagai fallback
4. **Sesuai dengan OCR output** — Gemini sudah mengembalikan `no_ba` dan `no_inv`

---

## 4. Detail Perubahan yang Diperlukan

### Tahap 1: Konfigurasi `gemini_fields` pada Document Type

**Tujuan:** Agar Gemini tahu harus extract field `no_ba` dan `no_inv`, dan field tersimpan di `ocr_data`.

**Cara:** Update kolom `gemini_fields` di tabel `document_types` untuk setiap document type yang participate di merge flow.

#### SQL untuk INV (Invoice):

```sql
UPDATE document_types
SET gemini_fields = '["document_type", "document_number", "document_date", "vendor_name", "customer", "keterangan", "uraian", "no_ba"]'
WHERE UPPER(name) = 'INVOICE';
```

#### SQL untuk SP (Pembayaran):

```sql
UPDATE document_types
SET gemini_fields = '["document_type", "document_number", "document_date", "vendor_name", "customer", "keterangan", "uraian", "no_inv"]'
WHERE UPPER(name) = 'PEMBAYARAN';
```

#### SQL untuk BA (Berita Acara):

```sql
-- BA tidak perlu field linking karena adalah root document
-- Tapi bisa tambahkan field lain jika diperlukan
UPDATE document_types
SET gemini_fields = '["document_type", "document_number", "document_date", "vendor_name", "customer", "keterangan", "uraian"]'
WHERE UPPER(name) = 'BERITA ACARA';
```

**Atau melalui UI:** Edit Document Type → Isi `gemini_fields` JSON array.

**Atau melalui PHP (Seeder/Artisan):**

```php
use App\Models\DocumentType;

// INV
$inv = DocumentType::whereRaw('UPPER(name) = ?', ['INVOICE'])->first();
if ($inv) {
    $inv->update([
        'gemini_fields' => [
            'document_type',
            'document_number',
            'document_date',
            'vendor_name',
            'customer',
            'keterangan',
            'uraian',
            'no_ba',  // ← TAMBAHKAN INI
        ],
    ]);
}

// SP
$sp = DocumentType::whereRaw('UPPER(name) = ?', ['PEMBAYARAN'])->first();
if ($sp) {
    $sp->update([
        'gemini_fields' => [
            'document_type',
            'document_number',
            'document_date',
            'vendor_name',
            'customer',
            'keterangan',
            'uraian',
            'no_inv',  // ← TAMBAHKAN INI
        ],
    ]);
}
```

### Tahap 2: Konfigurasi `link_field` pada Merge Flow Steps

**Tujuan:** Memberitahu MergeFlowService field mana di `ocr_data` yang berisi nomor induk untuk linking.

**Cara:** Update kolom `link_field` di tabel `merge_flow_steps` untuk step yang bukan root.

#### SQL:

```sql
-- Untuk step INV (order=2) yang link ke BA
UPDATE merge_flow_steps
SET link_field = 'no_ba'
WHERE document_type_id = (SELECT id FROM document_types WHERE UPPER(name) = 'INVOICE')
  AND order = 2;

-- Untuk step SP (order=3) yang link ke INV
UPDATE merge_flow_steps
SET link_field = 'no_inv'
WHERE document_type_id = (SELECT id FROM document_types WHERE UPPER(name) = 'PEMBAYARAN')
  AND order = 3;
```

**Atau melalui UI:** Edit Merge Flow → Isi "Link Field (Gemini)" untuk setiap step.

**Atau melalui PHP:**

```php
use App\Models\MergeFlow;
use App\Models\DocumentType;

$flow = MergeFlow::where('slug', 'ba-inv-sp')->first();
if ($flow) {
    $inv = DocumentType::whereRaw('UPPER(name) = ?', ['INVOICE'])->first();
    $sp = DocumentType::whereRaw('UPPER(name) = ?', ['PEMBAYARAN'])->first();

    // Update step INV
    $flow->steps()
        ->where('document_type_id', $inv->id)
        ->update(['link_field' => 'no_ba']);

    // Update step SP
    $flow->steps()
        ->where('document_type_id', $sp->id)
        ->update(['link_field' => 'no_inv']);
}
```

### Tahap 3: Verifikasi Konfigurasi

**Cara mengecek apakah konfigurasi sudah benar:**

#### 1. Cek `gemini_fields` di document type:

```php
use App\Models\DocumentType;

$inv = DocumentType::whereRaw('UPPER(name) = ?', ['INVOICE'])->first();
$sp = DocumentType::whereRaw('UPPER(name) = ?', ['PEMBAYARAN'])->first();

echo "INV gemini_fields: " . json_encode($inv->gemini_fields);
// Expected: ["document_type", "document_number", "document_date", "vendor_name", "customer", "keterangan", "uraian", "no_ba"]

echo "SP gemini_fields: " . json_encode($sp->gemini_fields);
// Expected: ["document_type", "document_number", "document_date", "vendor_name", "customer", "keterangan", "uraian", "no_inv"]
```

#### 2. Cek `link_field` di merge flow step:

```php
use App\Models\MergeFlow;

$flow = MergeFlow::with('steps.documentType')->where('slug', 'ba-inv-sp')->first();

foreach ($flow->steps as $step) {
    echo "Step {$step->order}: {$step->documentType->name} | link_field: {$step->link_field}\n";
}

// Expected:
// Step 1: BERITA ACARA | link_field: (kosong)
// Step 2: INVOICE | link_field: no_ba
// Step 3: PEMBAYARAN | link_field: no_inv
```

#### 3. Cek `ocr_data` di scan_log setelah upload:

```php
use App\Models\ScanLog;

$scanLog = ScanLog::latest()->first();
$ocrData = $scanLog->metadata['ocr_data'] ?? null;

echo "ocr_data: " . json_encode($ocrData, JSON_PRETTY_PRINT);
// Expected untuk INV: include "no_ba": "BA0001"
// Expected untuk SP: include "no_inv": "INV0001"
```

---

## 5. Diagram Alur Setelah Perbaikan

```
┌─────────────────────────────────────────────────────────────────┐
│                    Upload INV Document                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  1. ProcessScanFile::handle()                                   │
│     ├── GeminiEngine::extractText(file, INV_type)               │
│     │   └── Prompt: default fields + "no_ba" (dari gemini_fields)│
│     │   └── Response: {document_type, document_number, no_ba, ..}│
│     │                                                            │
│     ├── parseJsonResponse() → keep only allowedFields           │
│     │   └── Result: {document_type, document_number, no_ba, ..} │
│     │                                                            │
│     ├── Save ocr_data ke scan_log.metadata['ocr_data']          │
│     │   └── metadata.ocr_data = {document_number: INV0001,      │
│     │                              no_ba: BA0001, ...}          │
│     │                                                            │
│     └── Trigger MergeFlowService::processAfterUpload()          │
│                                                                  │
│  2. MergeFlowService::processAfterUpload($scanLog)              │
│     ├── Find MergeFlowStep for INV (order=2, link_field="no_ba")│
│     │                                                            │
│     └── handleChildDocument()                                   │
│         ├── extractLinkedNumber()                                │
│         │   ├── Priority 1: link_field="no_ba"                  │
│         │   │   └── ocrData['no_ba'] = "BA0001" ✅              │
│         │   └── Return "BA0001"                                  │
│         │                                                        │
│         ├── Find parent group (root_number="BA0001")            │
│         │   └── Found! (group sudah dibuat saat BA upload)      │
│         │                                                        │
│         ├── Add item to group                                    │
│         │   └── {doc_type: INV, order: 2, number: INV0001}      │
│         │                                                        │
│         └── checkAndTriggerMerge()                               │
│             └── 2/3 items → status tetap pending                │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                    Upload SP Document                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  1. ProcessScanFile::handle()                                   │
│     ├── GeminiEngine::extractText(file, SP_type)                │
│     │   └── Prompt: default fields + "no_inv" (dari gemini_fields)│
│     │   └── Response: {document_type, document_number, no_inv, ..}│
│     │                                                            │
│     ├── parseJsonResponse() → keep only allowedFields           │
│     │   └── Result: {document_type, document_number, no_inv, ..}│
│     │                                                            │
│     └── Trigger MergeFlowService::processAfterUpload()          │
│                                                                  │
│  2. MergeFlowService::processAfterUpload($scanLog)              │
│     ├── Find MergeFlowStep for SP (order=3, link_field="no_inv")│
│     │                                                            │
│     └── handleChildDocument()                                   │
│         ├── extractLinkedNumber()                                │
│         │   ├── Priority 1: link_field="no_inv"                 │
│         │   │   └── ocrData['no_inv'] = "INV0001" ✅            │
│         │   └── Return "INV0001"                                 │
│         │                                                        │
│         ├── Find parent group via INV0001 → BA0001              │
│         │   └── Found! (ada item INV dengan number=INV0001)     │
│         │                                                        │
│         ├── Add item to group                                    │
│         │   └── {doc_type: SP, order: 3, number: SP0001}        │
│         │                                                        │
│         └── checkAndTriggerMerge()                               │
│             └── 3/3 items → status = 1 (Lengkap)                │
│             └── performFinalMerge()                              │
│                 ├── Download BA + INV + SP dari FTP              │
│                 ├── Merge → FINAL_VENDOR_BA0001_INV0001_SP0001  │
│                 ├── Upload ke FINAL/VENDOR/                      │
│                 └── status = 2 (Selesai)                         │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 6. File yang Perlu Diubah

| # | File | Aksi | Keterangan |
|---|------|------|------------|
| 1 | Database `document_types` | UPDATE | Tambah `no_ba` ke `gemini_fields` INV, `no_inv` ke `gemini_fields` SP |
| 2 | Database `merge_flow_steps` | UPDATE | Set `link_field` pada step INV dan SP |

**Tidak ada perubahan kode yang diperlukan!** Semua mekanisme sudah diimplementasikan.

---

## 7. Urutan Eksekusi

| # | Tahap | Estimasi | Keterangan |
|---|-------|----------|------------|
| 1 | Update `gemini_fields` INV | 5 menit | Tambah `no_ba` ke JSON array |
| 2 | Update `gemini_fields` SP | 5 menit | Tambah `no_inv` ke JSON array |
| 3 | Update `link_field` step INV | 5 menit | Set ke `no_ba` |
| 4 | Update `link_field` step SP | 5 menit | Set ke `no_inv` |
| 5 | Verifikasi konfigurasi | 10 menit | Cek DB + test upload |
| 6 | Test end-to-end | 30 menit | Upload BA → INV → SP → cek merge |
| **Total** | | **~1 jam** | |

---

## 8. Testing Checklist

### 8.1 Verifikasi Konfigurasi
- [ ] `gemini_fields` INV include `no_ba`
- [ ] `gemini_fields` SP include `no_inv`
- [ ] Merge flow step INV punya `link_field = 'no_ba'`
- [ ] Merge flow step SP punya `link_field = 'no_inv'`

### 8.2 Test Upload & OCR
- [ ] Upload BA → `ocr_data` tersimpan di metadata (termasuk `document_number: BA0001`)
- [ ] Upload INV → `ocr_data` tersimpan dengan `no_ba: BA0001`
- [ ] Upload SP → `ocr_data` tersimpan dengan `no_inv: INV0001`

### 8.3 Test Merge Flow
- [ ] Upload BA → Merge group dibuat (status: Pending, 1/3 items)
- [ ] Upload INV → Item INV ditambahkan ke group (status: Pending, 2/3 items)
- [ ] Upload SP → Item SP ditambahkan, group lengkap (status: Lengkap, 3/3)
- [ ] Final merge → PDF gabungan diupload ke `FINAL/VENDOR/`
- [ ] Group status berubah menjadi Selesai

### 8.4 Test Edge Cases
- [ ] Upload INV sebelum BA → Group dibuat dengan root_number = BA0001 (dari no_ba)
- [ ] Upload BA setelah INV → Group ter-update, cek apakah lengkap
- [ ] Vendor berbeda → Group terpisah
- [ ] `link_field` kosong → Fallback ke `link_regex` (jika ada)

### 8.5 Test Backward Compatibility
- [ ] Merge flow dengan `link_regex` saja (tanpa `link_field`) masih berfungsi
- [ ] Data existing (scan_log tanpa `ocr_data` di metadata) tidak menyebabkan error

---

## 9. Rollback Plan

Jika ada masalah, langkah rollback:

1. **Kembalikan `gemini_fields`** ke default (hapus `no_ba`/`no_inv`)
2. **Kosongkan `link_field`** di merge flow steps
3. **Gunakan `link_regex`** sebagai fallback (regex lama)

```php
// Rollback gemini_fields
$inv->update(['gemini_fields' => null]); // Akan gunakan default_fields
$sp->update(['gemini_fields' => null]);

// Rollback link_field
$flow->steps()->where('document_type_id', $inv->id)->update(['link_field' => null]);
$flow->steps()->where('document_type_id', $sp->id)->update(['link_field' => null]);
```

---

## 10. Risk & Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| `gemini_fields` tidak diupdate | Tinggi | Pastikan update DB sebelum test |
| `link_field` tidak match dengan field Gemini | Sedang | Cek nama field di OCR output vs gemini_fields |
| Gemini tidak mengembalikan `no_ba`/`no_inv` | Sedang | Cek prompt Gemini, pastikan field ada di gemini_fields |
| Data existing tidak punya `no_ba`/`no_inv` di metadata | Rendah | Fallback ke `link_regex` atau `link_label` |
| Performance (extra query untuk metadata) | Rendah | Metadata sudah di-load bersama scan_log |

---

## 11. Kesimpulan

Fitur merge flow **sudah diimplementasikan secara lengkap** di codebase. Masalah utama adalah **konfigurasi** (`gemini_fields` dan `link_field`), bukan kode. Perbaikan yang diperlukan:

1. **Update `gemini_fields`** pada document type INV dan SP agar include field linking
2. **Update `link_field`** pada merge flow steps agar指向 field yang benar di `ocr_data`

Setelah konfigurasi ini diupdate, merge flow akan berfungsi sesuai yang diharapkan tanpa perlu perubahan kode.

---

*Document created: 2026-08-12*
*Author: opencode*
