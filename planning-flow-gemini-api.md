# Planning: Implementasi Alur Gemini API untuk OCR

## 1. Ringkasan

**Tujuan:** Mengimplementasikan alur di mana Gemini API menggantikan OCR Space secara penuh untuk mengekstrak data terstruktur dari dokumen, menghasilkan JSON langsung tanpa perlu regex extraction.

**Kondisi Saat Ini:**
- OCR Space: Menghasilkan teks mentah → DocumentTypeProcessor (regex) mengekstrak field
- Gemini API: Sudah ada implementasi di `GeminiEngine.php` namun belum optimal

**Kondisi Target:**
- OCR Space: Alur lama (teks mentah → regex extraction)
- Gemini API: Langsung menghasilkan JSON terstruktur (tidak perlu regex)

---

## 2. Analisis Alur Saat Ini

### 2.1 Alur OCR Space (Current)

```
┌─────────────────────────────────────────────────────────────┐
│                    ProcessScanFile Job                       │
├─────────────────────────────────────────────────────────────┤
│  1. Upload file ke storage                                   │
│  2. OCR Engine Selection (via OcrEngineFactory)              │
│     │                                                        │
│     ├─ OCR Space ──────────────────────────────────────┐     │
│     │   └─ OcrSpaceEngine::extractText()               │     │
│     │       └─ Return: string (teks mentah)             │     │
│     │                                                   │     │
│     │   ┌───────────────────────────────────────────────┘     │
│     │   │                                                     │
│     │   ▼                                                     │
│     │  DocumentTypeProcessor                                  │
│     │   ├─ extractDocumentNumber(ocrText, docType)            │
│     │   ├─ extractTanggal(ocrText, docType)                   │
│     │   ├─ extractKeterangan(ocrText, docType)                │
│     │   ├─ extractUraian(ocrText, docType)                    │
│     │   └─ matchVendor(ocrText, docType)                      │
│     │                                                          │
│     │   Return: array {                                        │
│     │     "document_number" => "...",                          │
│     │     "tanggal" => "...",                                  │
│     │     "keterangan" => "...",                               │
│     │     "uraian" => "...",                                   │
│     │     "vendor_name" => "..."                               │
│     │   }                                                      │
│     │                                                          │
│     └─ Gemini ────────────────────────────────────────┐       │
│         └─ GeminiEngine::extractText()                │       │
│             └─ Return: string (teks OCR) + JSON parse │       │
│                                                          │
│  3. Process hasil OCR                                     │
│  4. Generate filename & FTP path                          │
│  5. Upload ke FTP Final                                   │
│  6. Log ke scan_logs                                       │
│  7. Trigger MergeFlowService                              │
└─────────────────────────────────────────────────────────────┘
```

### 2.2 Struktur Data yang Dihasilkan

**OCR Space:**
```php
// Teks mentah dari OCR Space
$ocrText = "INVOICE NO: SP0001\nDATE: 01 Apr 26\nVENDOR: MADHANI...";

// Hasil DocumentTypeProcessor
$result = [
    "document_number" => "SP0001",
    "tanggal" => "01 Apr 26",
    "keterangan" => "P11646-JASA PENAMBANGAN...",
    "uraian" => "BIAYA YANG MASIH HARUS DIBAYAR...",
    "vendor_name" => "MADHANI TALATAH NUSANTARA"
];
```

**Gemini (Target):**
```json
{
  "document_type": "PEMBAYARAN",
  "document_number": "SP0001",
  "document_date": "01 Apr 26",
  "vendor_name": "MADHANI TALATAH NUSANTARA",
  "customer": "0023/,MADHANI TALATAH NUSANTARA, PT",
  "keterangan": "P11646-JASA PENAMBANGAN JAN'26 0400260006/298202",
  "uraian": [
    "BIAYA YANG MASIH HARUS DIBAYAR",
    "MIMS ADD. TAXES INVOICE PENDING",
    "HUTANG USAHA",
    "ACCOUNT PAYABLE"
  ]
}
```

---

## 3. Perubahan yang Diperlukan

### 3.1 GeminiEngine.php

**File:** `app/Services/Ocr/GeminiEngine.php`

**Perubahan:**
1. Modifikasi prompt Gemini untuk menghasilkan JSON dengan struktur yang konsisten
2. Tambahkan validasi response JSON
3. Tambahkan fallback jika JSON parsing gagal

**Contoh Prompt yang Disarankan:**
```
Analisis dokumen ini dan ekstrak informasi berikut dalam format JSON:

{
  "document_type": "Jenis dokumen (contoh: PEMBAYARAN, FAKTUR, dll)",
  "document_number": "Nomor dokumen",
  "document_date": "Tanggal dokumen (format: DD Mon YY)",
  "vendor_name": "Nama vendor/pemasok",
  "customer": "Nama pelanggan",
  "keterangan": "Keterangan/deskripsi transaksi",
  "uraian": ["Daftar item/uraian"]
}

Kembalikan HANYA JSON tanpa penjelasan tambahan.
```

**Return Type:**
```php
// Saat ini
public function extractText(UploadedFile $file): string

// Target
public function extractText(UploadedFile $file): array|string
// atau
public function extractStructuredData(UploadedFile $file): array
```

### 3.2 OcrEngineInterface.php

**File:** `app/Services/Ocr/Contracts/OcrEngineInterface.php`

**Perubahan:**
```php
interface OcrEngineInterface
{
    /**
     * Ekstrak teks dari file
     */
    public function extractText(UploadedFile $file): string|array;
    
    /**
     * Ekstrak data terstruktur dari file (opsional, untuk Gemini)
     */
    public function extractStructuredData(UploadedFile $file): ?array;
}
```

### 3.3 ProcessScanFile.php

**File:** `app/Jobs/ProcessScanFile.php`

**Perubahan:**
```php
// Saat ini
$ocrResult = $engine->extractText($file);
$extracted = app(DocumentTypeProcessor::class)->process($ocrResult, $documentType);

// Target
if ($engine instanceof GeminiEngine) {
    $extracted = $engine->extractStructuredData($file);
    
    // Validasi field wajib
    $requiredFields = ['document_number', 'vendor_name', 'keterangan', 'uraian'];
    foreach ($requiredFields as $field) {
        if (empty($extracted[$field])) {
            // Fallback ke regex extraction
            $extracted = app(DocumentTypeProcessor::class)->process(
                $engine->extractText($file), 
                $documentType
            );
            break;
        }
    }
} else {
    // OCR Space - gunakan regex seperti biasa
    $ocrResult = $engine->extractText($file);
    $extracted = app(DocumentTypeProcessor::class)->process($ocrResult, $documentType);
}
```

### 3.4 ScanLogger.php

**File:** `app/Services/ScanLogger.php`

**Perubahan:**
- Pastikan field `uraian` bisa menyimpan array (JSON encode sebelum save)
- Atau tetap gunakan string (implode array dengan delimiter)

**Rekomendasi:**
```php
// Simpan uraian sebagai JSON string
$scanLog->uraian = is_array($extracted['uraian']) 
    ? json_encode($extracted['uraian']) 
    : $extracted['uraian'];
```

### 3.5 LogFileController.php

**File:** `app/Http/Controllers/Dokter/LogFileController.php`

**Perubahan:**
- Decode JSON uraian saat menampilkan log
- Tampilkan array uraian dengan format yang rapi

### 3.6 View Templates

**File:** `resources/views/dokter/log-file/index.blade.php`

**Perubahan:**
- Decode JSON uraian sebelum ditampilkan
- Tampilkan sebagai list jika berupa array

---

## 4. Diagram Alur Baru

### 4.1 Alur Gemini API (Target)

```
┌─────────────────────────────────────────────────────────────┐
│                    ProcessScanFile Job                       │
├─────────────────────────────────────────────────────────────┤
│  1. Upload file ke storage                                   │
│  2. OCR Engine Selection (via OcrEngineFactory)              │
│     │                                                        │
│     ├─ OCR Space ──────────────────────────────────────┐     │
│     │   └─ OcrSpaceEngine::extractText()               │     │
│     │       └─ Return: string (teks mentah)             │     │
│     │                                                   │     │
│     │   ┌───────────────────────────────────────────────┘     │
│     │   │                                                     │     │
│     │   ▼                                                     │     │
│     │  DocumentTypeProcessor (regex extraction)               │
│     │   └─ Return: array { document_number, tanggal, ... }    │
│     │                                                          │
│     └─ Gemini ────────────────────────────────────────┐       │
│         └─ GeminiEngine::extractStructuredData()      │       │
│             │                                          │       │
│             ├─ Kirim file ke Gemini API               │       │
│             ├─ Parse JSON response                    │       │
│             ├─ Validasi field wajib                   │       │
│             │                                          │       │
│             ├─ Jika valid ──────────────────────┐    │       │
│             │   └─ Return: array (JSON terstruktur)   │       │
│             │                                    │    │       │
│             └─ Jika invalid ──────────────────┐ │    │       │
│                 └─ Fallback ke regex ──────────┘ │    │       │
│                                                  │    │       │
│  3. Process hasil OCR                           │    │       │
│     ├─ Jika array (dari Gemini)                ◄┘    │       │
│     └─ Jika string (dari OCR Space) ◄────────────────┘       │
│                                                               │
│  4. Generate filename & FTP path                              │
│  5. Upload ke FTP Final                                       │
│  6. Log ke scan_logs                                           │
│     └─ Simpan uraian sebagai JSON string                      │
│  7. Trigger MergeFlowService                                  │
└─────────────────────────────────────────────────────────────┘
```

---

## 5. File yang Perlu Diubah

### 5.1 Core Files (Prioritas Tinggi)

| File | Path | Perubahan |
|---|---|---|
| `GeminiEngine.php` | `app/Services/Ocr/GeminiEngine.php` | Tambah `extractStructuredData()`, validasi JSON |
| `OcrEngineInterface.php` | `app/Services/Ocr/Contracts/OcrEngineInterface.php` | Tambah method `extractStructuredData()` |
| `ProcessScanFile.php` | `app/Jobs/ProcessScanFile.php` | Handle response array dari Gemini |
| `OcrEngineFactory.php` | `app/Services/Ocr/OcrEngineFactory.php` | Pastikan return type sesuai |

### 5.2 Supporting Files (Prioritas Sedang)

| File | Path | Perubahan |
|---|---|---|
| `ScanLogger.php` | `app/Services/ScanLogger.php` | Handle array uraian |
| `OcrSpaceEngine.php` | `app/Services/Ocr/OcrSpaceEngine.php` | Implementasi `extractStructuredData()` (return null) |
| `DocumentTypeProcessor.php` | `app/Services/DocumentTypeProcessor.php` | Tidak berubah (tetap untuk OCR Space) |

### 5.3 UI Files (Prioritas Rendah)

| File | Path | Perubahan |
|---|---|---|
| `LogFileController.php` | `app/Http/Controllers/Dokter/LogFileController.php` | Decode JSON uraian |
| `index.blade.php` | `resources/views/dokter/log-file/index.blade.php` | Tampilkan array uraian |

---

## 6. Database Changes

### 6.1 Tidak Perlu Migration Baru

Struktur database yang ada sudah mendukung:
- `scan_logs.uraian` (text) - bisa menyimpan JSON string
- `scan_logs.metadata` (json) - bisa menyimpan data tambahan dari Gemini

### 6.2 Opsional: Tambah Kolom untuk Raw Gemini Response

Jika ingin menyimpan raw response dari Gemini untuk debugging:

```php
// Migration baru (opsional)
Schema::table('scan_logs', function (Blueprint $table) {
    $table->json('gemini_raw_response')->nullable()->after('ocr_text');
});
```

---

## 7. Konfigurasi

### 7.1 Environment Variables (Sudah Ada)

```env
# GOOGLE GEMINI API
GOOGLE_GEMINI_API_KEY=your_api_key
GEMINI_MODEL=gemini-1.5-flash
GEMINI_OCR_PROMPT=...  # Update prompt sesuai struktur JSON baru
GEMINI_MAX_TOKENS=8192
```

### 7.2 Prompt yang Disarankan

**File:** `config/services.php` (bagian gemini.prompt)

```php
'prompt' => env('GEMINI_OCR_PROMPT', <<<'PROMPT'
Analisis dokumen ini dan ekstrak informasi berikut dalam format JSON:

{
  "document_type": "Jenis dokumen (contoh: PEMBAYARAN, FAKTUR, NOTA, dll)",
  "document_number": "Nomor dokumen",
  "document_date": "Tanggal dokumen (format: DD Mon YY, contoh: 01 Apr 26)",
  "vendor_name": "Nama vendor/pemasok",
  "customer": "Nama pelanggan (jika ada)",
  "keterangan": "Keterangan/deskripsi transaksi",
  "uraian": ["Daftar item/uraian dalam bentuk array"]
}

Aturan:
1. Kembalikan HANYA JSON tanpa penjelasan tambahan
2. Jika field tidak ditemukan, gunakan string kosong ""
3. Pastikan format tanggal konsisten (DD Mon YY)
4. Uraian harus berupa array meskipun hanya ada 1 item
PROMPT
),
```

---

## 8. Testing Plan

### 8.1 Unit Tests

```php
// tests/Unit/Services/Ocr/GeminiEngineTest.php

it('extracts structured data from document', function () {
    $engine = new GeminiEngine();
    $file = UploadedFile::fake()->create('document.pdf');
    
    $result = $engine->extractStructuredData($file);
    
    expect($result)->toBeArray()
        ->toHaveKeys(['document_type', 'document_number', 'document_date', 
                      'vendor_name', 'customer', 'keterangan', 'uraian']);
});

it('validates required fields', function () {
    $engine = new GeminiEngine();
    // Test dengan response yang tidak lengkap
    // ...
});
```

### 8.2 Integration Tests

```php
// tests/Feature/Jobs/ProcessScanFileTest.php

it('processes document with Gemini engine', function () {
    // Mock Gemini API response
    // Dispatch ProcessScanFile job
    // Assert scan_logs record created
    // Assert uraian saved as JSON
});
```

### 8.3 Manual Testing Checklist

- [ ] Upload PDF dengan engine Gemini
- [ ] Upload gambar (JPG/PNG) dengan engine Gemini
- [ ] Verifikasi JSON response dari Gemini
- [ ] Cek scan_logs记录 (uraian tersimpan sebagai JSON)
- [ ] Cek tampilan log di UI (uraian terdecode dengan benar)
- [ ] Test fallback ke regex jika Gemini response invalid
- [ ] Test dengan berbagai jenis dokumen

---

## 9. Risk Assessment

| Risk | Impact | Mitigation |
|---|---|---|
| Gemini API timeout | High | Timeout 120 detik, retry mechanism |
| Response JSON invalid | High | Fallback ke regex extraction |
| Biaya API meningkat | Medium | Monitor penggunaan, set budget alert |
| Rate limiting | Medium | Implementasi queue, delay between requests |
| Format JSON berubah | Medium | Validasi struktur, logging raw response |

---

## 10. Estimasi Waktu

| Task | Estimasi | Keterangan |
|---|---|---|
| Modifikasi GeminiEngine | 2-3 jam | Tambah extractStructuredData() |
| Update ProcessScanFile | 2-3 jam | Handle array response |
| Update ScanLogger | 1 jam | Handle array uraian |
| Update UI | 1-2 jam | Decode JSON uraian |
| Testing | 2-3 jam | Unit + Integration tests |
| **Total** | **8-12 jam** | |

---

## 11. Implementation Steps

### Phase 1: Core Implementation (4-6 jam)
1. [ ] Modifikasi `OcrEngineInterface.php` - tambah method `extractStructuredData()`
2. [ ] Implementasi `extractStructuredData()` di `GeminiEngine.php`
3. [ ] Implementasi `extractStructuredData()` di `OcrSpaceEngine.php` (return null)
4. [ ] Update `ProcessScanFile.php` - handle array response

### Phase 2: Supporting Changes (2-3 jam)
5. [ ] Update `ScanLogger.php` - handle array uraian
6. [ ] Update prompt di `config/services.php`
7. [ ] Update `LogFileController.php` - decode JSON uraian

### Phase 3: UI & Testing (2-3 jam)
8. [ ] Update Blade template log-file
9. [ ] Buat unit tests
10. [ ] Jalankan manual testing

---

## 12. Conclusion

Implementasi alur Gemini API akan menghasilkan:
- **Akurasi lebih tinggi** untuk dokumen kompleks
- **Ekstraksi field lebih lengkap** (termasuk customer)
- **Uraian sebagai array** (lebih fleksibel untuk ditampilkan)
- **Fallback mechanism** jika Gemini gagal

Alur OCR Space tetap dipertahankan sebagai backup untuk:
- Dokumen sederhana
- Ketika API Gemini bermasalah
- Penghematan biaya untuk dokumen tertentu
