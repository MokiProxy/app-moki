# Planning: Penyesuaian Merge Flow Setelah Implementasi Gemini API

## Ringkasan

Menyesuaikan fitur Merge Flow agar kompatibel dengan perubahan OCR engine dari regex-based extraction ke Gemini API structured data. Alur merge tidak berubah, namun mekanisme ekstraksi nomor induk (linking) dari dokumen anak perlu disesuaikan karena format output OCR sudah berubah.

---

## 1. Analisis Masalah

### 1.1 Kondisi Saat Ini

**Alur OCR (Sesudah Gemini API):**
```
Scanner → ProcessScanFile Job
  → GeminiEngine::extractText(file, documentType->gemini_prompt)
  → Return: {success, text (JSON response), ocr_data (parsed JSON), processing_time_ms}
  → ocr_data: {document_type, document_number, document_date, vendor_name, customer, keterangan, uraian}
  → Upload to FTP
  → MergeFlowService::processAfterUpload($scanLog)
```

**Alur Merge Flow (Saat Ini):**
```
MergeFlowService::processAfterUpload($scanLog)
  → Cari MergeFlowStep berdasarkan document_type_id
  → Jika order=1 (root): Buat/grup DocumentMergeGroup, tambah item
  → Jika order>1 (child):
      → extractLinkedNumber(): Apply link_regex ke $scanLog->ocr_text
      → Cari parent group berdasarkan linked number
      → Tambah item ke group
  → Jika group lengkap: performFinalMerge()
```

### 1.2 Masalah yang Ditemukan

| # | Masalah | Lokasi | Dampak |
|---|---------|--------|--------|
| 1 | `ocr_text` sekarang berisi JSON response Gemini (bukan teks mentah OCR) | `ProcessScanFile.php:73,277` | `link_regex` tidak bisa match |
| 2 | Format JSON Gemini tidak mengandung teks seperti "No BA: BA0001" | `GeminiEngine.php:82` | Regex pattern `/No\s*BA\s*\n?\s*:\s*(.+)/i` gagal |
| 3 | `MergeFlowStep.link_regex` dirancang untuk teks OCR lama (OCR Space) | `MergeFlowStep.php:10` | linking antar dokumen gagal |
| 4 | Data structured Gemini (`ocr_data`) tidak disimpan di scan_log untuk keperluan linking | `ProcessScanFile.php:263-278` | Tidak ada data alternatif untuk linking |

### 1.3 Bukti Teknis

**Contoh `ocr_text` dengan Gemini (GAGAL regex):**
```
```json
{
  "document_type": "INVOICE",
  "document_number": "INV001",
  "document_date": "01 Apr 26",
  "vendor_name": "MADHANI TALATAH NUSANTARA",
  "customer": "0023/,MADHANI TALATAH NUSANTARA, PT",
  "keterangan": "P11646-JASA PENAMBANGAN JAN'26",
  "uraian": ["BIAYA YANG MASIH HARUS DIBAYAR"]
}
```
```

**Regex lama:** `/No\s*BA\s*\n?\s*:\s*(.+)/i`
**Hasil:** Tidak ada match (JSON tidak mengandung teks "No BA: ...")

---

## 2. Solusi yang Direkomendasikan

### 2.1 Pendekatan: Hybrid (Structured Data + Regex Fallback)

```
handleChildDocument($step, $scanLog, $ocrText, $vendorName, $documentNumber)
  │
  ├── 1. Coba extract dari ocr_data (Gemini structured data)
  │     → Cek scanLog->metadata['ocr_data'][$step->link_field]
  │     → Jika link_field ada dan value tidak kosong → gunakan
  │
  ├── 2. Coba extract dari linked_numbers (sudah di-populate)
  │     → Cek scanLog->linked_numbers[$step->link_label]
  │     → Jika ada → gunakan
  │
  └── 3. Fallback ke link_regex (regex pada ocr_text)
        → Apply $step->link_regex ke $scanLog->ocr_text
        → Jika match → gunakan
        → Jika tidak match → return null (gagal linking)
```

### 2.2 Alasan Memilih Pendekatan Ini

1. **Backward Compatible**: Merge flow yang sudah ada dengan `link_regex` tetap bisa digunakan
2. **Forward Compatible**: Mendukung Gemini structured data untuk linking yang lebih akurat
3. **Minimal Changes**: Tidak mengubah alur merge (hanya mekanisme linking)
4. **Graceful Degradation**: Jika Gemini tidak mengembalikan field linking, fallback ke regex

---

## 3. Perubahan yang Diperlukan

### Tahap 1: Simpan OCR Data di ScanLog Metadata

**File:** `app/Jobs/ProcessScanFile.php`

**Perubahan:** Simpan `ocr_data` ke field `metadata` scan_log agar bisa diakses oleh MergeFlowService.

```php
// Saat ini (line 263-278)
$scanLog = $logger->log('job_completed', 'success', [
    'filename' => $this->filename,
    // ... other fields ...
    'ocr_text' => $ocrText,
]);

// Baru: Tambahkan ocr_data ke metadata
$existingMetadata = $scanLog->metadata ?? [];
$existingMetadata['ocr_data'] = $ocrData;
$scanLog->update(['metadata' => $existingMetadata]);
```

**File:** `app/Models/ScanLog.php`

**Perubahan:** Pastikan `metadata` field bisa menyimpan ocr_data.

```php
// Tidak perlu perubahan, sudah ada cast ke 'array'
protected $casts = [
    'metadata' => 'array',
    // ...
];
```

---

### Tahap 2: Tambah Field `link_field` ke MergeFlowStep

**File:** `database/migrations/2026_08_11_000002_add_link_field_to_merge_flow_steps_table.php` (BARU)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merge_flow_steps', function (Blueprint $table) {
            $table->string('link_field')->nullable()->after('link_label');
        });
    }

    public function down(): void
    {
        Schema::table('merge_flow_steps', function (Blueprint $table) {
            $table->dropColumn('link_field');
        });
    }
};
```

**Keterangan `link_field`:**
- `null` atau kosong: Gunakan `link_regex` (behavior lama)
- `'document_number'`: Ambil dari `ocr_data['document_number']` (jika parent number ada di field ini)
- Custom field name: Ambil dari `ocr_data[$link_field]` (untuk field khusus seperti `referenced_ba_number`)

---

### Tahap 3: Update Model MergeFlowStep

**File:** `app/Models/MergeFlowStep.php`

```php
protected $fillable = ['merge_flow_id', 'document_type_id', 'order', 'link_regex', 'link_label', 'link_field'];
```

---

### Tahap 4: Modifikasi MergeFlowService

**File:** `app/Services/MergeFlowService.php`

**Perubahan pada `handleChildDocument()`:**

```php
protected function handleChildDocument(MergeFlowStep $step, ScanLog $scanLog, string $ocrText, string $vendorName, string $documentNumber): void
{
    $linkedNumber = $this->extractLinkedNumber($step, $scanLog, $ocrText);
    
    if (! $linkedNumber) {
        Log::warning('Could not extract linked number from child document', [
            'scan_log_id' => $scanLog->id,
            'link_regex' => $step->link_regex,
            'link_field' => $step->link_field,
        ]);
        return;
    }

    // ... rest of the method unchanged ...
}

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

### Tahap 5: Update Controller dan Views

**File:** `app/Http/Controllers/Dokter/MergeFlowController.php`

Tambahkan `link_field` ke validasi:

```php
// store() method
$validated = $request->validate([
    // ...
    'steps.*.link_field' => 'nullable|string|max:255',
]);

// update() method
$validated = $request->validate([
    // ...
    'steps.*.link_field' => 'nullable|string|max:255',
]);
```

**File:** `resources/views/dokter/merge-flow/create.blade.php`

Tambahkan input field untuk `link_field`:

```html
<div class="col-md-3">
    <label class="form-label fw-bold">Link Field (Gemini)</label>
    <input type="text" name="steps[{{ $index }}][link_field]" class="form-control" 
           value="{{ old("steps.{$index}.link_field") }}" 
           placeholder="document_number">
    <small class="text-muted">Field dari Gemini ocr_data untuk linking (opsional)</small>
</div>
```

**File:** `resources/views/dokter/merge-flow/edit.blade.php`

Sama seperti create view.

**File:** `resources/views/dokter/merge-flow/index.blade.php`

Tampilkan `link_field` di tabel alur:

```blade
@if($step->link_field)
    <small class="text-muted d-block ms-2">Field: {{ $step->link_field }}</small>
@endif
```

---

### Tahap 6: Update Seeder

**File:** `database/seeders/MergeFlowSeeder.php`

```php
MergeFlowStep::create([
    'merge_flow_id' => $flow->id,
    'document_type_id' => $inv->id,
    'order' => 2,
    'link_regex' => '/No\s*BA\s*\n?\s*:\s*(.+)/i',
    'link_label' => 'No BA',
    'link_field' => null, // Atau 'referenced_ba_number' jika Gemini prompt dikonfigurasi
]);

MergeFlowStep::create([
    'merge_flow_id' => $flow->id,
    'document_type_id' => $sp->id,
    'order' => 3,
    'link_regex' => '/No\s*Inv\s*\n?\s*:\s*(.+)/i',
    'link_label' => 'No Inv',
    'link_field' => null, // Atau 'referenced_invoice_number'
]);
```

---

### Tahap 7 (Opsional): Update Gemini Prompt untuk Child Documents

Jika ingin menggunakan `link_field` secara optimal, update prompt Gemini pada document type child untuk mengekstrak nomor induk.

**Contoh prompt untuk INVOICE:**
```
Analisis dokumen ini dan ekstrak informasi berikut dalam format JSON:
{
  "document_type",
  "document_number",
  "document_date",
  "vendor_name",
  "customer",
  "keterangan",
  "uraian",
  "referenced_ba_number": "Nomor Berita Acara yang direferensikan (jika ada)"
}
Aturan:
1. Kembalikan HANYA JSON tanpa penjelasan tambahan
2. Jika field tidak ditemukan, gunakan string kosong ""
```

**Contoh prompt untuk PEMBAYARAN/SP:**
```
Analisis dokumen ini dan ekstrak informasi berikut dalam format JSON:
{
  "document_type",
  "document_number",
  "document_date",
  "vendor_name",
  "customer",
  "keterangan",
  "uraian",
  "referenced_invoice_number": "Nomor Invoice yang direferensikan (jika ada)"
}
```

---

## 4. Diagram Alur Setelah Perubahan

```
┌─────────────────────────────────────────────────────────────────┐
│                  MergeFlowService::handleChildDocument           │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  1. extractLinkedNumber($step, $scanLog, $ocrText)               │
│     │                                                            │
│     ├── [Priority 1] Cek link_field + ocr_data dari metadata    │
│     │   └─ Jika $step->link_field ada                           │
│     │       └─ Ambil dari $scanLog->metadata['ocr_data'][$field]│
│     │                                                            │
│     ├── [Priority 2] Cek linked_numbers JSON field              │
│     │   └─ Jika $step->link_label ada                           │
│     │       └─ Ambil dari $scanLog->linked_numbers[$label]      │
│     │                                                            │
│     └── [Priority 3] Fallback ke link_regex                     │
│         └─ Apply $step->link_regex ke $ocrText                  │
│                                                                  │
│  2. Jika linkedNumber ditemukan:                                 │
│     → Lanjut ke pencarian group (tidak berubah)                  │
│                                                                  │
│  3. Jika linkedNumber null:                                      │
│     → Log warning, return (tidak ada perubahan)                  │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 5. File yang Perlu Diubah

| # | File | Aksi | Prioritas |
|---|------|------|-----------|
| 1 | `database/migrations/2026_08_11_000002_add_link_field_to_merge_flow_steps_table.php` | BUAT | Tinggi |
| 2 | `app/Models/MergeFlowStep.php` | MODIFIKASI | Tinggi |
| 3 | `app/Services/MergeFlowService.php` | MODIFIKASI | Tinggi |
| 4 | `app/Jobs/ProcessScanFile.php` | MODIFIKASI | Tinggi |
| 5 | `app/Http/Controllers/Dokter/MergeFlowController.php` | MODIFIKASI | Sedang |
| 6 | `resources/views/dokter/merge-flow/create.blade.php` | MODIFIKASI | Sedang |
| 7 | `resources/views/dokter/merge-flow/edit.blade.php` | MODIFIKASI | Sedang |
| 8 | `resources/views/dokter/merge-flow/index.blade.php` | MODIFIKASI | Rendah |
| 9 | `database/seeders/MergeFlowSeeder.php` | MODIFIKASI | Rendah |
| 10 | Custom Gemini prompts per document type (opsional) | OPSIONAL | Rendah |

---

## 6. Urutan Eksekusi

| # | Tahap | Estimasi | Keterangan |
|---|-------|----------|------------|
| 1 | Buat migration `link_field` | 10 menit | Tambah kolom ke merge_flow_steps |
| 2 | Update Model `MergeFlowStep` | 5 menit | Tambah ke fillable |
| 3 | Update `ProcessScanFile` | 15 menit | Simpan ocr_data ke metadata |
| 4 | Update `MergeFlowService` | 30 menit | Modifikasi extractLinkedNumber() |
| 5 | Update `MergeFlowController` | 10 menit | Tambah validasi link_field |
| 6 | Update Views (create/edit/index) | 20 menit | Tambah input field link_field |
| 7 | Update Seeder | 5 menit | Set default link_field |
| 8 | Testing | 30 menit | Test dengan data existing + baru |
| **Total** | | **~2 jam** | |

---

## 7. Testing Checklist

### 7.1 Backward Compatibility (Data Existing)
- [ ] Merge flow dengan `link_regex` saja (tanpa `link_field`) masih berfungsi
- [ ] Data seeder BA-INV-SP masih berfungsi dengan regex fallback
- [ ] Group pending yang sudah ada tetap bisa di-complete

### 7.2 Forward Compatibility (Gemini Structured Data)
- [ ] Upload Invoice → ocr_data tersimpan di metadata scan_log
- [ ] Upload Invoice → linking ke BA berhasil via `link_field` atau regex fallback
- [ ] Upload SP → linking ke Invoice berhasil
- [ ] Group lengkap → final merge berhasil

### 7.3 UI Testing
- [ ] Form create merge flow menampilkan field `link_field`
- [ ] Form edit merge flow menampilkan field `link_field`
- [ ] Index merge flow menampilkan `link_field` info
- [ ] Validasi form berfungsi dengan benar

### 7.4 Edge Cases
- [ ] `link_field` kosong tapi `link_regex` ada → fallback ke regex
- [ ] `link_field` ada tapi `ocr_data` tidak ada → fallback ke regex
- [ ] Keduanya kosong → return null, log warning
- [ ] `ocr_data` field kosong → fallback ke regex

---

## 8. Rollback Plan

Jika ada masalah, langkah rollback:

1. **Migration rollback:** `php artisan migrate:rollback --step=1`
2. **Model rollback:** Hapus `link_field` dari `$fillable` di `MergeFlowStep.php`
3. **Service rollback:** Kembalikan `extractLinkedNumber()` ke versi lama (hanya regex)
4. **Controller rollback:** Hapus validasi `link_field` dari controller
5. **View rollback:** Hapus input field `link_field` dari views

---

## 9. Risk & Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| ocr_data tidak tersimpan di metadata | Tinggi | Pastikan ProcessScanFile mengupdate metadata setelah log |
| link_field tidak match dengan field Gemini | Sedang | Fallback ke regex; pastikan prompt Gemini sesuai |
| Performance (extra DB query untuk metadata) | Rendah | Metadata sudah di-load bersama scan_log |
| Data existing tidak punya ocr_data di metadata | Rendah | Fallback ke regex otomatis |

---

## 10. Kesimpulan

Perubahan ini memastikan Merge Flow tetap kompatibel dengan Gemini API tanpa mengubah alur merge secara fundamental. Pendekatan hybrid memberikan fleksibilitas untuk:

1. **Data lama**: Tetap menggunakan `link_regex` (backward compatible)
2. **Data baru**: Bisa menggunakan `link_field` untuk Gemini structured data (forward compatible)
3. **Masa depan**: Memudahkan migrasi penuh ke Gemini-only linking jika diperlukan

Alur merge (root → child → group → final merge) **tidak berubah sama sekali**.

---

*Document created: 2026-08-11*
*Author: opencode*
