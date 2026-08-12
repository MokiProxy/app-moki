# Planning Algoritma Custom Prompt

## Overview

Sistem custom prompt dengan **1 prompt template universal** yang diinstruksikan ke Gemini API, namun **field JSON yang diekstrak dicustom** berdasarkan jenis dokumen yang dikonfigurasi di database.

---

## 1. Konsep Algoritma

### Prinsip Dasar
```
Prompt Template (Universal) + Field JSON (Per Jenis Dokumen) = Prompt Akhir
```

### Flow Diagram
```
┌─────────────────────────────────────────────────────────────────┐
│                    PROMPT ASSEMBLY PROCESS                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  1. Ambil Prompt Template dari Config                           │
│     "Analisis dokumen ini dan ekstrak informasi berikut..."     │
│                              ↓                                   │
│  2. Ambil Field JSON dari DocumentType.gemini_fields            │
│     ["document_type", "document_number", "vendor_name"]         │
│                              ↓                                   │
│  3. Konversi Array ke JSON String                               │
│     {"document_type", "document_number", "vendor_name"}         │
│                              ↓                                   │
│  4. Gabungkan Template + Field JSON                             │
│     Prompt Lengkap = Template + "\n" + Field JSON               │
│                              ↓                                   │
│  5. Kirim ke Gemini API                                         │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 2. Struktur Database

### 2.1 Tabel `document_types` - Kolom Baru

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `gemini_fields` | json (nullable) | Array field JSON yang akan diekstrak |

**Migration:**
```php
$table->json('gemini_fields')->nullable()->after('gemini_prompt');
```

### 2.2 Contoh Data

| Document Type | gemini_fields |
|---------------|---------------|
| INVOICE | `["document_type", "document_number", "document_date", "vendor_name", "customer", "keterangan", "uraian"]` |
| BERITA ACARA | `["document_type", "document_number", "document_date", "vendor_name", "keterangan"]` |
| SLIP PEMBUKUAN AP | `["document_type", "document_number", "document_date", "vendor_name", "keterangan", "uraian"]` |
| PEMBAYARAN | `["document_type", "document_number", "document_date", "vendor_name", "keterangan"]` |

---

## 3. Prompt Template

### 3.1 Template Universal (di `config/services.php`)

```
Analisis dokumen ini dan ekstrak informasi berikut dalam format JSON:
Aturan:
1. Kembalikan HANYA JSON tanpa penjelasan tambahan
2. Jika field tidak ditemukan, gunakan string kosong ""
3. Pastikan format tanggal konsisten (DD Mon YY)
4. Uraian harus berupa array meskipun hanya ada 1 item
5. Field vendor name diambil dari nama dari perusahaan di customer diambil sampai sebelum tanda koma
Ekstrak data nya dengan format berikut:
```

### 3.2 Field JSON (Per Jenis Dokumen)

Contoh untuk INVOICE:
```json
{
  "document_type",
  "document_number",
  "document_date",
  "vendor_name",
  "customer",
  "keterangan",
  "uraian"
}
```

### 3.3 Prompt Akhir (Hasil Gabungan)

```
Analisis dokumen ini dan ekstrak informasi berikut dalam format JSON:
Aturan:
1. Kembalikan HANYA JSON tanpa penjelasan tambahan
2. Jika field tidak ditemukan, gunakan string kosong ""
3. Pastikan format tanggal konsisten (DD Mon YY)
4. Uraian harus berupa array meskipun hanya ada 1 item
5. Field vendor name diambil dari nama dari perusahaan di customer diambil sampai sebelum tanda koma
Ekstrak data nya dengan format berikut:
{
  "document_type",
  "document_number",
  "document_date",
  "vendor_name",
  "customer",
  "keterangan",
  "uraian"
}
```

---

## 4. Algoritma Implementasi

### 4.1 PromptBuilder Service

```php
class PromptBuilder
{
    /**
     * Build prompt lengkap berdasarkan document type
     */
    public static function buildPrompt(DocumentType $documentType): string
    {
        // 1. Ambil template dari config
        $template = config('services.gemini.prompt_template');
        
        // 2. Ambil fields dari document type
        $fields = $documentType->gemini_fields;
        
        // 3. Jika tidak ada custom fields, gunakan default
        if (empty($fields)) {
            $fields = config('services.gemini.default_fields');
        }
        
        // 4. Konversi fields ke JSON string
        $fieldsJson = self::formatFieldsAsJson($fields);
        
        // 5. Gabungkan template dengan fields
        return $template . "\n" . $fieldsJson;
    }
    
    /**
     * Format fields array menjadi JSON string
     */
    private static function formatFieldsAsJson(array $fields): string
    {
        $formatted = [];
        foreach ($fields as $field) {
            $formatted[$field] = '';  // Empty string placeholder
        }
        
        return json_encode($formatted, JSON_PRETTY_PRINT);
    }
}
```

### 4.2 Updated GeminiEngine

```php
public function extractText(UploadedFile $file, ?DocumentType $documentType = null): array
{
    // 1. Build prompt berdasarkan document type
    $prompt = PromptBuilder::buildPrompt($documentType);
    
    // 2. Kirim ke Gemini API
    // ... (existing code)
    
    // 3. Parse response
    $ocrData = $this->parseJsonResponse($text);
    
    // 4. Validasi fields sesuai konfigurasi document type
    if ($documentType && $documentType->gemini_fields) {
        $ocrData = $this->validateFields($ocrData, $documentType->gemini_fields);
    }
    
    return [
        'success' => true,
        'text' => $text,
        'ocr_data' => $ocrData,
        'processing_time_ms' => $elapsed,
    ];
}

/**
 * Validasi dan filter fields sesuai konfigurasi
 */
private function validateFields(?array $data, array $allowedFields): ?array
{
    if ($data === null) return null;
    
    $validated = [];
    foreach ($allowedFields as $field) {
        $validated[$field] = $data[$field] ?? '';
    }
    
    return $validated;
}
```

### 4.3 Updated ProcessScanFile

```php
$result = $ocr->extractText($uploadedFile, $documentType);

// Response akan memiliki field sesuai gemini_fields document type
// Contoh untuk INVOICE:
// $result['ocr_data'] = [
//     'document_type' => 'INVOICE',
//     'document_number' => 'INV001',
//     'document_date' => '01 Apr 26',
//     'vendor_name' => 'PT MAJU JAYA',
//     'customer' => '0001/,PT MAJU JAYA',
//     'keterangan' => 'INV/2026/001',
//     'uraian' => ['Barang A', 'Barang B']
// ]

// Contoh untuk BERITA ACARA (hanya 5 fields):
// $result['ocr_data'] = [
//     'document_type' => 'BERITA ACARA',
//     'document_number' => 'BA001',
//     'document_date' => '01 Apr 26',
//     'vendor_name' => 'PT MAJU JAYA',
//     'keterangan' => 'BA untuk invoice INV001'
// ]
```

---

## 5. Flow Lengkap

```
┌─────────────────────────────────────────────────────────────────┐
│                    ALUR PROSES CUSTOM PROMPT                     │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  1. File Scan Masuk                                             │
│     ↓                                                           │
│  2. MonitorScanner detect file                                  │
│     ↓                                                           │
│  3. Dispatch ProcessScanFile job                                │
│     ↓                                                           │
│  4. ProcessScanFile: resolve DocumentType                       │
│     ↓                                                           │
│  5. PromptBuilder::buildPrompt($documentType)                  │
│     - Ambil template dari config                                │
│     - Ambil gemini_fields dari $documentType                   │
│     - Gabungkan menjadi prompt lengkap                          │
│     ↓                                                           │
│  6. GeminiEngine::extractText($file, $documentType)            │
│     - Kirim prompt lengkap ke Gemini API                        │
│     - Terima JSON response                                     │
│     ↓                                                           │
│  7. Parse JSON response                                        │
│     ↓                                                           │
│  8. Validasi fields sesuai gemini_fields                        │
│     ↓                                                           │
│  9. Return ocr_data dengan fields sesuai konfigurasi           │
│     ↓                                                           │
│ 10. ProcessScanFile gunakan ocr_data langsung                  │
│     - document_number = ocr_data['document_number']            │
│     - vendor_name = ocr_data['vendor_name']                    │
│     - dst.                                                      │
│     ↓                                                           │
│ 11. Generate filename, upload FTP, log, dll                    │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 6. File yang Perlu Diubah

### 6.1 Database
- **Migration**: Tambah kolom `gemini_fields` ke `document_types`
- **Model**: Tambah `gemini_fields` ke fillable

### 6.2 Services
- **Baru**: `app/Services/PromptBuilder.php`
- **Update**: `app/Services/Ocr/GeminiEngine.php`
  - Terima parameter `$documentType`
  - Gunakan `PromptBuilder` untuk build prompt
  - Validasi fields sesuai konfigurasi

### 6.3 Config
- **Update**: `config/services.php`
  - Tambah `prompt_template` (prompt universal)
  - Tambah `default_fields` (default fields jika tidak ada custom)

### 6.4 Views
- **Update**: `resources/views/dokter/document-type/create.blade.php`
  - Tambah input untuk `gemini_fields` (JSON array)
- **Update**: `resources/views/dokter/document-type/edit.blade.php`
  - Sama dengan create

### 6.5 Form Requests
- **Update**: `app\Http\Requests/StoreDocumentTypeRequest.php`
  - Tambah validasi `gemini_fields`
- **Update**: `app\Http\Requests/UpdateDocumentTypeRequest.php`
  - Sama dengan store

---

## 7. Contoh Konfigurasi

### 7.1 Config Services Baru

```php
'gemini' => [
    'api_key' => env('GOOGLE_GEMINI_API_KEY'),
    'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
    'prompt_template' => <<<'PROMPT'
Analisis dokumen ini dan ekstrak informasi berikut dalam format JSON:
Aturan:
1. Kembalikan HANYA JSON tanpa penjelasan tambahan
2. Jika field tidak ditemukan, gunakan string kosong ""
3. Pastikan format tanggal konsisten (DD Mon YY)
4. Uraian harus berupa array meskipun hanya ada 1 item
5. Field vendor name diambil dari nama dari perusahaan di customer diambil sampai sebelum tanda koma
Ekstrak data nya dengan format berikut:
PROMPT,
    'default_fields' => [
        'document_type',
        'document_number',
        'document_date',
        'vendor_name',
        'customer',
        'keterangan',
        'uraian'
    ],
    'max_tokens' => env('GEMINI_MAX_TOKENS', 8192),
],
```

### 7.2 Database Seeder

```php
// INVOICE
DocumentType::create([
    'name' => 'INVOICE',
    'gemini_fields' => ['document_type', 'document_number', 'document_date', 'vendor_name', 'customer', 'keterangan', 'uraian'],
    // ... other fields
]);

// BERITA ACARA
DocumentType::create([
    'name' => 'BERITA ACARA',
    'gemini_fields' => ['document_type', 'document_number', 'document_date', 'vendor_name', 'keterangan'],
    // ... other fields
]);
```

---

## 8. Keuntungan

1. **Fleksibel**: Setiap jenis dokumen bisa mengekstrak field berbeda
2. **Maintainable**: Prompt template hanya 1, mudah diupdate
3. **Efficient**: Field yang tidak diperlukan tidak diekstrak (hemat token)
4. **Consistent**: Aturan parsing konsisten untuk semua jenis dokumen
5. **Scalable**: Mudah menambah jenis dokumen baru dengan field berbeda

---

## 9. Risiko & Mitigasi

| Risiko | Mitigasi |
|--------|----------|
| Gemini tidak mengikuti format fields | Validasi response, fallback ke default fields |
| Field tidak ditemukan di response | Gunakan string kosong "" sesuai aturan |
| gemini_fields kosong/null | Gunakan default_fields dari config |
| Response time lebih lama | Field lebih sedikit = response lebih cepat |

---

## 10. Testing Checklist

- [ ] Test prompt assembly dengan berbagai document type
- [ ] Test INVOICE dengan 7 fields
- [ ] Test BERITA ACARA dengan 5 fields
- [ ] Test validasi fields response
- [ ] Test fallback ke default fields
- [ ] Test form create/edit dengan gemini_fields input
- [ ] Test save/load gemini_fields dari database
- [ ] Test dengan dokumen aktual

---

## 11. Implementation Steps

1. **Buat migration** tambah kolom `gemini_fields`
2. **Update Model** DocumentType
3. **Buat PromptBuilder** service
4. **Update GeminiEngine** terima parameter documentType
5. **Update config/services.php** dengan prompt_template
6. **Update views** form create/edit
7. **Update form requests** validasi
8. **Update seeder** dengan gemini_fields
9. **Testing** dengan berbagai jenis dokumen
10. **Deploy**

---

*Document created: 2026-08-12*
*Author: opencode*
