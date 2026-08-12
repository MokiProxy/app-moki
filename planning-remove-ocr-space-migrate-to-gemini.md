# Planning: Hapus OCR Space & Migrasi Full ke Gemini API

## Ringkasan

Menghapus seluruh dependency OCR Space dari codebase, menjadikan Gemini API sebagai satu-satunya mesin OCR, menghapus field regex pada Jenis Dokumen, dan menggantinya dengan field custom prompt untuk ekstraksi yang lebih akurat.

---

## Analisis Arsitektur Saat Ini

### Alur OCR Saat Ini
```
Scanner → ProcessScanFile Job
  → OcrEngineFactory::create() → OcrSpaceEngine / GeminiEngine
  → extractText()
    → Jika Gemini + structured data → gunakan langsung
    → Jika OCR Space → regex extraction via DocumentTypeProcessor
  → Document Type Detection (header_regex)
  → Field Extraction (number_regex, keterangan_regex, uraian_regex, tanggal_regex)
  → Upload to FTP
  → Merge Flow processing
```

### File yang Terdampak

| Kategori | File | Aksi |
|----------|------|------|
| **OCR Engine** | `app/Services/Ocr/OcrSpaceEngine.php` | HAPUS |
| **OCR Engine** | `app/Services/OcrService.php` | HAPUS |
| **OCR Engine** | `app/Services/Ocr/OcrEngineFactory.php` | SIMPLIFY |
| **OCR Engine** | `app/Enums/OcrEngineType.php` | HAPUS |
| **OCR Engine** | `app/Services/Ocr/Contracts/OcrEngineInterface.php` | PERTAHANKAN |
| **OCR Engine** | `app/Services/Ocr/GeminiEngine.php` | MODIFIKASI |
| **Controller** | `app/Http/Controllers/Dokter/OcrSettingController.php` | HAPUS |
| **View** | `resources/views/dokter/ocr-setting/index.blade.php` | HAPUS |
| **Route** | `routes/routers/dokter.php` (OCR Setting routes) | HAPUS |
| **Sidebar** | `resources/views/layouts/partials/dokter/app-sidebar.blade.php` | HAPUS menu OCR Setting |
| **Model** | `app/Models/DocumentType.php` | MODIFIKASI |
| **Model** | `app/Models/OcrSetting.php` | HAPUS |
| **Service** | `app/Services/DocumentTypeProcessor.php` | SIMPLIFY |
| **Job** | `app/Jobs/ProcessScanFile.php` | MODIFIKASI |
| **Request** | `app/Http/Requests/StoreDocumentTypeRequest.php` | MODIFIKASI |
| **Request** | `app/Http/Requests/UpdateDocumentTypeRequest.php` | MODIFIKASI |
| **View** | `resources/views/dokter/document-type/create.blade.php` | REDESIGN |
| **View** | `resources/views/dokter/document-type/edit.blade.php` | REDESIGN |
| **Config** | `config/services.php` | HAPUS bagian OCR Space |
| **Env** | `.env.example` | HAPUS baris OCR Space |
| **Seeder** | `database/seeders/OcrSettingSeeder.php` | HAPUS |
| **Seeder** | `database/seeders/DatabaseSeeder.php` | HAPUS panggilan OcrSettingSeeder |
| **Migration** | `database/migrations/2026_08_10_000001_create_ocr_settings_table.php` | HAPUS |
| **Permission** | `database/seeders/RolePermissionSeeder.php` | HAPUS `dokter.settings.manage` |
| **Service** | `app/Services/OcrSearchService.php` | PERTAHANKAN (masih berguna) |

---

## Tahap Implementasi

### Tahap 1: Hapus OCR Space Engine & Service

#### 1.1 Hapus File
- `app/Services/Ocr/OcrSpaceEngine.php`
- `app/Services/OcrService.php`
- `app/Models/OcrSetting.php`
- `app/Http/Controllers/Dokter/OcrSettingController.php`
- `resources/views/dokter/ocr-setting/index.blade.php`
- `database/seeders/OcrSettingSeeder.php`
- `database/migrations/2026_08_10_000001_create_ocr_settings_table.php`

#### 1.2 Hapus Enum
- `app/Enums/OcrEngineType.php`

#### 1.3 Simplify OcrEngineFactory
```php
// app/Services/Ocr/OcrEngineFactory.php
<?php

namespace App\Services\Ocr;

use App\Services\Ocr\Contracts\OcrEngineInterface;

class OcrEngineFactory
{
    public static function create(): OcrEngineInterface
    {
        return new GeminiEngine();
    }
}
```

#### 1.4 Hapus Route OCR Setting
File: `routes/routers/dokter.php`
- Hapus baris 90-93 (route prefix `ocr-setting`)
- Hapus import `OcrSettingController`

#### 1.5 Hapus Menu Sidebar
File: `resources/views/layouts/partials/dokter/app-sidebar.blade.php`
- Hapus baris 73-80 (menu "Pengaturan OCR")

#### 1.6 Hapus Permission
File: `database/seeders/RolePermissionSeeder.php`
- Hapus permission `dokter.settings.manage`

---

### Tahap 2: Modifikasi GeminiEngine untuk Custom Prompt

#### 2.1 Update GeminiEngine
File: `app/Services/Ocr/GeminiEngine.php`

Modifikasi method `extractText` untuk menerima custom prompt:
```php
public function extractText(UploadedFile $file, ?string $customPrompt = null): array
{
    $prompt = $customPrompt ?? $this->prompt;
    // ... gunakan $prompt instead of $this->prompt
}
```

#### 2.2 Update OcrEngineInterface
File: `app/Services/Ocr/Contracts/OcrEngineInterface.php`
```php
public function extractText(UploadedFile $file, ?string $customPrompt = null): array;
```

---

### Tahap 3: Redesign Model DocumentType

#### 3.1 Database Migration (Create New)
Migration baru untuk mengubah struktur `document_types`:

```php
// Hapus kolom regex:
// - header_regex
// - number_regex
// - number_label
// - keterangan_regex
// - keterangan_label
// - keterangan_enabled
// - uraian_regex
// - uraian_label
// - uraian_enabled
// - tanggal_regex
// - tanggal_label
// - tanggal_enabled

// Tambah kolom:
// - gemini_prompt (text, nullable) - custom prompt untuk ekstraksi
```

**Catatan:** Karena kolom `header_regex` masih dibutuhkan untuk document type detection (primary identifier), maka kolom ini TIDAK dihapus. Yang dihapus adalah regex untuk field extraction (number, keterangan, uraian, tanggal).

#### 3.2 Updated Schema
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | bigint | PK |
| name | string | Nama jenis dokumen |
| header_regex | string | **PERTAHANKAN** - untuk deteksi jenis dokumen |
| description | text | Deskripsi |
| gemini_prompt | text | **BARU** - custom prompt untuk ekstraksi field |
| filename_template | string | Template nama file |
| ftp_folder_template | string | Template folder FTP |
| ftp_failed_folder | string | Folder FAILED |
| vendor_search_enabled | boolean | Aktifkan pencarian vendor |
| created_at | timestamp | - |
| updated_at | timestamp | - |

#### 3.3 Update Model
File: `app/Models/DocumentType.php`
```php
protected $fillable = [
    'name',
    'header_regex',
    'description',
    'gemini_prompt',  // BARU
    'filename_template',
    'ftp_folder_template',
    'ftp_failed_folder',
    'vendor_search_enabled',
];
```

---

### Tahap 4: Update Form Requests

#### 4.1 StoreDocumentTypeRequest
File: `app/Http/Requests/StoreDocumentTypeRequest.php`
```php
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:255'],
        'header_regex' => ['nullable', 'string', 'max:255', new ValidRegex],
        'description' => ['nullable', 'string', 'max:1000'],
        'gemini_prompt' => ['nullable', 'string', 'max:5000'],  // BARU
        'filename_template' => ['nullable', 'string', 'max:255'],
        'ftp_folder_template' => ['nullable', 'string', 'max:255'],
        'ftp_failed_folder' => ['nullable', 'string', 'max:255'],
        'vendor_search_enabled' => ['nullable', 'boolean'],
    ];
}
```

#### 4.2 UpdateDocumentTypeRequest
Sama seperti StoreDocumentTypeRequest.

---

### Tahap 5: Redesign Views Document Type

#### 5.1 Create View
File: `resources/views/dokter/document-type/create.blade.php`

Hapus semua field regex (number_regex, keterangan_regex, uraian_regex, tanggal_regex) dan label/enabled fields. Tambahkan textarea untuk `gemini_prompt`.

**Layout baru:**
```
Row 1: [Nama] [Header Regex]
Row 2: [Deskripsi] (full width)
Row 3: [Gemini Custom Prompt] (full width, textarea)
Row 4: [Filename Template] [FTP Folder Template]
Row 5: [FTP Failed Folder] [Vendor Search Enabled]
```

**Field Gemini Prompt:**
```html
<div class="col-md-12">
    <label class="form-label fw-bold">Custom Gemini Prompt</label>
    <textarea name="gemini_prompt" class="form-control" rows="6"
        placeholder="Contoh: Ekstrak nomor invoice, tanggal, nama vendor, keterangan, dan uraian barang dari dokumen ini. Pastikan format tanggal DD/MM/YYYY.">{{ old('gemini_prompt') }}</textarea>
    <small class="text-muted">
        Custom prompt untuk Gemini API agar lebih akurat dalam mengekstrak field-field spesifik dari dokumen jenis ini.
        Jika dikosongkan, akan menggunakan prompt default.
    </small>
</div>
```

**Hapus field berikut:**
- Number Regex + Number Label
- Keterangan Regex + Keterangan Label + Keterangan Regex Enabled
- Uraian Regex + Uraian Label + Uraian Regex Enabled
- Tanggal Regex + Tanggal Label + Tanggal Regex Enabled

**Hapus JavaScript regex validation** (tidak diperlukan lagi).

#### 5.2 Edit View
Sama seperti Create View.

---

### Tahap 6: Simplify DocumentTypeProcessor

#### 6.1 Hapus Method Regex
File: `app/Services/DocumentTypeProcessor.php`

Hapus method berikut:
- `extractDocumentNumber()` (menggunakan number_regex)
- `extractNumberFromTaxCode()`
- `extractTanggal()` (menggunakan tanggal_regex)
- `extractTanggalFromTaxCode()`
- `extractKeterangan()` (menggunakan keterangan_regex)
- `extractKeteranganFromVendor()`
- `extractUraian()` (menggunakan uraian_regex)
- `cleanTanggal()`
- `cleanKeterangan()`
- `cleanUraian()`

Pertahankan method:
- `matchVendor()` - masih berguna untuk pencarian vendor
- `generateFilename()`
- `resolveFtpPath()`
- `resolveFailedPath()`
- `cleanOcrNoise()` - masih berguna

---

### Tahap 7: Update ProcessScanFile Job

#### 7.1 Alur Baru
```
Scanner → ProcessScanFile Job
  → GeminiEngine::extractText(file, documentType->gemini_prompt)
  → Gunakan ocr_data langsung (structured data)
  → Document Type Detection (header_regex) - PERTAHANKAN
  → Upload to FTP
  → Merge Flow processing
```

#### 7.2 Update Code
File: `app/Jobs/ProcessScanFile.php`

```php
public function handle(...): void
{
    // ... existing code ...

    $ocr = OcrEngineFactory::create();

    // Deteksi jenis dokumen dulu
    $documentType = $this->resolveDocumentType($uploadedFile, $ocr);

    if ($documentType === null) {
        throw new Exception("Could not detect document type for: {$this->filename}");
    }

    // Extract dengan custom prompt dari document type
    $result = $ocr->extractText($uploadedFile, $documentType->gemini_prompt);

    if (empty($result['success'])) {
        throw new Exception('OCR failed: '.json_encode($result));
    }

    $ocrText = $result['text'] ?? '';
    $ocrData = $result['ocr_data'] ?? null;

    // Gunakan structured data dari Gemini
    if ($ocrData !== null) {
        $documentNumber = $ocrData['document_number'] ?? null;
        $vendorName = $ocrData['vendor_name'] ?? null;
        $tanggal = $ocrData['document_date'] ?? null;
        $keterangan = $ocrData['keterangan'] ?? null;
        $uraian = $ocrData['uraian'] ?? null;
    } else {
        // Fallback: gunakan vendor search saja
        $vendorName = $processor->matchVendor($documentType, $ocrText);
        $documentNumber = null;
        $tanggal = null;
        $keterangan = null;
        $uraian = null;
    }

    // ... rest of the flow (filename generation, FTP upload, merge) ...
}
```

---

### Tahap 8: Cleanup Config & Environment

#### 8.1 Config
File: `config/services.php`

Hapus seluruh bagian `ocr`:
```php
// HAPUS:
'ocr' => [
    'api_endpoint' => env('OCR_SPACE_API_ENDPOINT'),
    'api_key' => env('OCR_SPACE_API_KEY'),
    'engine' => env('OCR_SPACE_ENGINE'),
    'active_engine' => env('OCR_ACTIVE_ENGINE', 'ocr_space'),
],
```

#### 8.2 Environment
File: `.env.example`

Hapus baris:
```
OCR_SPACE_API_ENDPOINT=https://api.ocr.space/parse/image
OCR_SPACE_API_KEY=
OCR_SPACE_ENGINE=3
OCR_ACTIVE_ENGINE=ocr_space
```

---

### Tahap 9: Merge Flow - Pertahankan link_regex

**Catatan Penting:** Field `link_regex` pada `merge_flow_steps` TIDAK dihapus.

Alasan:
- `link_regex` digunakan untuk mengekstrak nomor induk dari teks OCR (bukan regex extraction seperti pada document type)
- Berguna untuk menghubungkan dokumen anak ke dokumen induk dalam alur birokrasi
- Masih kompatibel dengan output Gemini (berupa teks)
-Ini adalah mekanisme linking, bukan field extraction

Jika ingin meningkatkan akurasi, bisa ditambahkan opsi `gemini_link_prompt` di masa depan, tapi untuk saat ini `link_regex` sudah cukup.

---

## Urutan Eksekusi

| # | Tahap | Estimasi | Keterangan |
|---|-------|----------|------------|
| 1 | Hapus OCR Space files | 30 menit | File deletion + factory simplification |
| 2 | Hapus route, sidebar, permission | 15 menit | UI cleanup |
| 3 | Modifikasi GeminiEngine | 20 menit | Tambah custom prompt support |
| 4 | Update OcrEngineInterface | 5 menit | Update signature |
| 5 | Create migration document_types | 30 menit | Schema change |
| 6 | Update DocumentType model | 10 menit | Fillable update |
| 7 | Update Form Requests | 15 menit | Validation rules |
| 8 | Redesign DocumentType views | 45 menit | Create + Edit views |
| 9 | Simplify DocumentTypeProcessor | 20 menit | Hapus regex methods |
| 10 | Update ProcessScanFile job | 30 menit | Integration |
| 11 | Cleanup config & env | 10 menit | Config cleanup |
| 12 | Testing | 60 menit | Manual testing |
| **Total** | | **~5.5 jam** | |

---

## Risk & Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| Data existing regex hilang | Tinggi | Backup data sebelum migration; jalankan data migration script |
| Gemini API error handling | Sedang | Pertahankan fallback text extraction |
| Custom prompt kurang akurat | Sedang | Test dengan berbagai jenis dokumen |
| Merge flow terpengaruh | Rendah | link_regex dipertahankan |

---

## Data Migration Strategy

Sebelum menjalankan migration, buat script untuk:
1. Backup seluruh data `document_types` existing
2. Migrate existing regex ke `gemini_prompt` format baru (opsional)
3. Set default `gemini_prompt` berdasarkan regex yang ada

```php
// Contoh migrasi data
DocumentType::all()->each(function ($dt) {
    $promptParts = [];
    if ($dt->number_regex) {
        $promptParts[] = "Ekstrak nomor dokumen dengan format regex: {$dt->number_regex}";
    }
    // ... tambah field lainnya

    $dt->update([
        'gemini_prompt' => implode("\n", $promptParts) ?: null,
    ]);
});
```

---

## Testing Checklist

- [ ] Buat jenis dokumen baru dengan custom prompt
- [ ] Edit jenis dokumen, ubah custom prompt
- [ ] Upload dokumen → proses OCR dengan Gemini
- [ ] Verifikasi extracted fields sesuai custom prompt
- [ ] Merge flow masih berfungsi (link_regex)
- [ ] Vendor search masih berfungsi
- [ ] Filename template masih berfungsi
- [ ] FTP upload masih berfungsi
- [ ] Log file masih mencatat dengan benar
- [ ] Menu "Pengaturan OCR" tidak muncul di sidebar
