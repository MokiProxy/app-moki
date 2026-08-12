# Planning Implementasi Custom Gemini Prompt

## Overview

Mengubah prompt, response, dan alur penggunaan Gemini API agar mengembalikan JSON terstruktur langsung dari Gemini, sehingga tidak perlu lagi melakukan regex extraction menggunakan `DocumentTypeProcessor`.

---

## 1. Perubahan Prompt

### Saat Ini
```
Baca dan ekstrak seluruh teks dari dokumen ini secara akurat. Pertahankan format asli dokumen.
```

### Baru
```
Kembalikan JSON: {document_type,document_number,document_date,vendor_name,customer,keterangan,uraian}
vendor_name = ambil nama customer nya saja sampai tanda koma
```

### Lokasi Perubahan
- **File**: `config/services.php` (baris 42)
- **Alternatif**: `.env` variable `GEMINI_OCR_PROMPT`

---

## 2. Perubahan Response Format

### Response Gemini Saat Ini
```json
{
  "success": true,
  "text": "teks mentah OCR...",
  "processing_time_ms": 1234
}
```

### Response Gemini Baru (JSON terstruktur)
```json
{
  "document_type": "BERITA ACARA",
  "document_number": "BA0001",
  "document_date": "01 Apr 26",
  "vendor_name": "MADHANI TALATAH NUSANTARA",
  "customer": "0023/,MADHANI TALATAH NUSANTARA, PT",
  "keterangan": "P11646-JASA PENAMBANGAN JAN'26 0400260006/298202",
  "uraian": "BIAYA YANG MASIH HARUS DIBAYAR | MIMS ADD. TAXES INVOICE PENDING | HUTANG USAHA | ACCOUNT PAYABLE"
}
```

---

## 3. File yang Perlu Diubah

### 3.1 `app/Services/Ocr/GeminiEngine.php`
**Perubahan:**
- Ubah prompt default ke prompt baru
- Parse response JSON dari Gemini
- Kembalikan data terstruktur bukan teks mentah

**Detail:**
```php
// Prompt baru
$this->prompt = config('services.gemini.prompt',
    'Kembalikan JSON: {document_type,document_number,document_date,vendor_name,customer,keterangan,uraian}
vendor_name = ambil nama customer nya saja sampai tanda koma'
);

// Response handling baru
$text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

// Parse JSON dari response (handle markdown code block)
$jsonString = preg_replace('/```json\s*|\s*```/', '', $text);
$ocrData = json_decode($jsonString, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    throw new Exception('Failed to parse Gemini JSON response');
}

return [
    'success' => true,
    'text' => $text, // Tetap simpan teks mentah untuk logging
    'ocr_data' => $ocrData, // Data terstruktur
    'processing_time_ms' => (int) round($elapsed),
];
```

### 3.2 `app/Services/Ocr/Contracts/OcrEngineInterface.php`
**Perubahan:**
- Update return type annotation untuk include `ocr_data`

```php
/**
 * @return array{success: bool, text: string, ocr_data: ?array, processing_time_ms: ?int}
 */
public function extractText(UploadedFile $file): array;
```

### 3.3 `app/Services/Ocr/OcrSpaceEngine.php`
**Perubahan:**
- Return `ocr_data` sebagai `null` (OCR Space tidak mengembalikan JSON terstruktur)

```php
public function extractText(UploadedFile $file): array
{
    $result = $this->service->extractText($file);
    $result['ocr_data'] = null; // OCR Space tidak punya structured data
    return $result;
}
```

### 3.4 `app/Jobs/ProcessScanFile.php`
**Perubahan:**
- Cek apakah `ocr_data` tersedia dari Gemini
- Jika ya, gunakan langsung tanpa regex extraction
- Jika tidak (OCR Space), gunakan `DocumentTypeProcessor` seperti saat ini

**Alur Baru:**
```
1. OCR extraction (Gemini atau OCR Space)
2. Cek $result['ocr_data']
   - Jika ada (Gemini): gunakan langsung
     - document_number = ocr_data['document_number']
     - vendor_name = ocr_data['vendor_name']
     - tanggal = ocr_data['document_date']
     - keterangan = ocr_data['keterangan']
     - uraian = ocr_data['uraian']
     - document_type = resolve dari ocr_data['document_type']
   - Jika null (OCR Space): gunakan DocumentTypeProcessor seperti saat ini
3. Lanjut ke proses selanjutnya (filename, FTP upload, merge, dll)
```

**Detail Implementasi:**
```php
$result = $ocr->extractText($uploadedFile);

if (empty($result['success'])) {
    throw new Exception('OCR failed: '.json_encode($result));
}

$ocrText = $result['text'] ?? '';
$ocrData = $result['ocr_data'] ?? null;

if ($ocrData !== null) {
    // Gemini: gunakan data terstruktur langsung
    $documentNumber = $ocrData['document_number'] ?? null;
    $vendorName = $ocrData['vendor_name'] ?? null;
    $tanggal = $ocrData['document_date'] ?? null;
    $keterangan = $ocrData['keterangan'] ?? null;
    $uraian = $ocrData['uraian'] ?? null;
    
    // Resolve document_type dari ocr_data
    $documentTypeName = strtoupper($ocrData['document_type'] ?? '');
    $documentType = DocumentType::whereRaw('UPPER(name) = ?', [$documentTypeName])->first();
    
    if ($documentType === null) {
        // Fallback: coba deteksi via header regex
        $documentType = $this->resolveDocumentType($uploadedFile, $ocr);
    }
} else {
    // OCR Space: gunakan regex extraction seperti saat ini
    $documentType = $this->resolveDocumentType($uploadedFile, $ocr);
    
    if ($documentType === null) {
        throw new Exception("Could not detect document type for: {$this->filename}");
    }
    
    $documentNumber = $processor->extractDocumentNumber($documentType, $ocrText);
    $vendorName = $processor->matchVendor($documentType, $ocrText);
    $tanggal = $processor->extractTanggal($documentType, $ocrText);
    $keterangan = $processor->extractKeterangan($documentType, $ocrText, $vendorName);
    $uraian = $processor->extractUraian($documentType, $ocrText);
}
```

### 3.5 `config/services.php`
**Perubahan:**
- Update prompt default

```php
'gemini' => [
    'api_key' => env('GOOGLE_GEMINI_API_KEY'),
    'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
    'prompt' => env('GEMINI_OCR_PROMPT', 'Kembalikan JSON: {document_type,document_number,document_date,vendor_name,customer,keterangan,uraian}
vendor_name = ambil nama customer nya saja sampai tanda koma'),
    'max_tokens' => env('GEMINI_MAX_TOKENS', 8192),
],
```

### 3.6 `.env`
**Perubahan:**
- Set prompt baru di environment variable

```
GEMINI_OCR_PROMPT="Kembalikan JSON: {document_type,document_number,document_date,vendor_name,customer,keterangan,uraian}
vendor_name = ambil nama customer nya saja sampai tanda koma"
```

---

## 4. Flow Diagram

### Saat Ini
```
[File] → [Gemini API] → [Teks Mentah] → [DocumentTypeProcessor] → [Data Terstruktur]
                                    ↓
                              [Regex Extraction]
                              - number_regex
                              - tanggal_regex
                              - keterangan_regex
                              - uraian_regex
                              - vendor_match
```

### Baru (dengan Gemini)
```
[File] → [Gemini API] → [JSON Response] → [Parse JSON] → [Data Terstruktur]
                                    ↓
                              Langsung dapat:
                              - document_type
                              - document_number
                              - document_date
                              - vendor_name
                              - customer
                              - keterangan
                              - uraian
```

### Baru (dengan OCR Space - fallback)
```
[File] → [OCR Space API] → [Teks Mentah] → [DocumentTypeProcessor] → [Data Terstruktur]
                                    ↓
                              [Regex Extraction]
                              (tetap seperti saat ini)
```

---

## 5. Keuntungan

1. **Lebih Akurat**: Gemini AI memahami konteks dokumen, bukan hanya pattern matching
2. **Lebih Cepat**: Tidak perlu regex extraction berulang
3. **Fleksibel**: Bisa handle berbagai format dokumen tanpa konfigurasi regex
4. **Backward Compatible**: OCR Space tetap bisa digunakan sebagai fallback
5. **Vendor Name Parsing**: Gemini langsung mengambil nama vendor sampai tanda koma

---

## 6. Risiko & Mitigasi

| Risiko | Mitigasi |
|--------|----------|
| Gemini return JSON invalid | Fallback ke regex extraction jika parse gagal |
| Gemini return field kosong | Gunakan null value, log warning |
| Response time lebih lama | Timeout 120s sudah di-set, bisa diatur |
| Biaya API lebih tinggi | Monitor usage, pertimbangkan model flash |

---

## 7. Testing Checklist

- [ ] Test dengan dokumen BERITA ACARA
- [ ] Test dengan dokumen INVOICE
- [ ] Test dengan dokumen FAKTUR
- [ ] Test fallback ke OCR Space
- [ ] Test validasi JSON response
- [ ] Test field kosong/null
- [ ] Test vendor name parsing (nama sampai koma)
- [ ] Test uraian dengan multiple lines
- [ ] Test log scanning hasil Gemini
- [ ] Test merge flow dengan data Gemini
- [ ] Test FTP upload dengan data Gemini

---

## 8. Implementation Steps

1. **Update config/services.php** - Set prompt baru
2. **Update .env** - Set GEMINI_OCR_PROMPT
3. **Update GeminiEngine.php** - Parse JSON response
4. **Update OcrEngineInterface.php** - Update return type
5. **Update OcrSpaceEngine.php** - Tambah ocr_data = null
6. **Update ProcessScanFile.php** - Conditional logic Gemini vs OCR Space
7. **Testing** - Test dengan berbagai jenis dokumen
8. **Deploy** - Deploy ke production

---

## 9. Rollback Plan

Jika ada masalah, kembalikan ke prompt lama:
```
Baca dan ekstrak seluruh teks dari dokumen ini secara akurat. Pertahankan format asli dokumen.
```

Dan nonaktifkan logic `ocr_data` di ProcessScanFile.php.

---

## 10. Timeline

| Task | Estimasi | Dependencies |
|------|----------|--------------|
| Update config & env | 5 menit | - |
| Update GeminiEngine.php | 30 menit | - |
| Update OcrEngineInterface.php | 5 menit | - |
| Update OcrSpaceEngine.php | 5 menit | - |
| Update ProcessScanFile.php | 45 menit | GeminiEngine selesai |
| Testing | 60 menit | Semua update selesai |
| **Total** | **~2.5 jam** | - |

---

*Document created: 2026-08-11*
*Author: opencode*
