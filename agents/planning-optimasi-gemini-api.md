# Planning: Optimasi Penggunaan Gemini API - Modul Dokter

## Status: DONE
## Date: 2026-08-12
## Author: opencode

---

## 1. Ringkasan Masalah

Sistem modul dokter sudah berjalan sesuai rencana, namun menggunakan **Gemini API free tier** yang memiliki keterbatasan:
- **Request per day**: ~1,500 requests/day
- **Tokens per minute (RPM)**: ~32,000 tokens/minute
- **Tokens per day**: ~1,000,000 tokens/day

**Temuan kritis**: Saat ini satu file bisa menghabiskan **3x API call** karena ada duplikasi logika deteksi document type.

---

## 2. Analisis Arsitektur Saat Ini

### Flow Saat Ini (Root Folder File)

```
MonitorScanner::processRootFiles()
  │
  ├── [1] detectDocumentType() → extractText(file, null) ──────── API CALL #1
  │       (Tujuan: deteksi jenis dokumen)
  │
  └── ProcessScanFile::dispatch(filename, docType->id)
        │
        ├── resolveDocumentType() → DocumentType::find(id) ────── SKIP (ada ID)
        │
        └── extractText(file, documentType) ───────────────────── API CALL #2
                (Tujuan: ekstraksi data lengkap)
```

### Flow Saat Ini (Subfolder File)

```
MonitorScanner::processSubfolderFiles()
  │
  └── ProcessScanFile::dispatch(filename, docType->id)
        │
        ├── resolveDocumentType() → DocumentType::find(id) ────── SKIP (ada ID)
        │
        └── extractText(file, documentType) ───────────────────── API CALL #1
```

### Flow Problem: File Tanpa documentTypeId

```
ProcessScanFile(filename, documentTypeId=null)
  │
  ├── resolveDocumentType() → extractText(file, null) ────────── API CALL #1
  │       (Duplikasi deteksi dari MonitorScanner!)
  │
  └── extractText(file, documentType) ────────────────────────── API CALL #2
```

**Masalah**: Jika `documentTypeId = null`, terjadi **2x API call** di ProcessScanFile (deteksi + ekstraksi), padahal MonitorScanner sudah melakukan deteksi sebelumnya.

---

## 3. Temuan Kritis

### 3.1 Duplikasi API Call untuk Deteksi Document Type

| Kondisi | API Calls | Detail |
|---------|-----------|--------|
| File di root, documentTypeId dikirim | 1 | Hanya ekstraksi |
| File di root, documentTypeId NULL | 2 | Deteksi + Ekstraksi |
| File di subfolder | 1 | Hanya ekstraksi |

**Impact**: Jika semua file masuk ke root (yang paling umum), setiap file membutuhkan **2x API call**.

### 3.2 Tidak Ada Kompresi Gambar

- Gambar dikirim full resolution ke Gemini API
- Ukuran file besar = lebih banyak token terkonsumsi
- Contoh: Foto 4000x3000px ≈ 12MP ≈ ~1000-2000 tokens
- Setelah kompresi ke 1024px ≈ ~200-400 tokens (hemat 60-80%)

### 3.3 Prompt Belum Dioptimasi

- Prompt template saat ini ~200 karakter
- Belum memanfaatkan `system_instruction` untuk model yang support
- Tidak ada prompt caching mechanism

### 3.4 Tidak Ada Rate Limiting

- Tidak ada mekanisme throttling
- Mudah hit RPM limit jika banyak file diproses bersamaan

### 3.5 Tidak Ada Caching Hasil Deteksi

- Hasil deteksi document type tidak di-cache
- File yang sama bisa dideteksi berulang kali

---

## 4. Rencana Optimasi

### Prioritas: TINGGI

#### 4.1 Eliminasi Duplikasi API Call (Estimatasi Penghematan: 50%)

**Masalah**: `resolveDocumentType()` di `ProcessScanFile` memanggil `extractText()` lagi padahal deteksi sudah dilakukan di `MonitorScanner`.

**Solusi**: 
- `MonitorScanner` sudah menyimpan `documentTypeId` saat dispatch job
- `ProcessScanFile::resolveDocumentType()` sudah handle case `documentTypeId != null` dengan return early
- **Yang perlu diperbaiki**: Pastikan `MonitorScanner` SELALU mengirim `documentTypeId` ke job, atau gunakan pendekatan berbeda

**Implementasi**:
```php
// MonitorScanner::detectDocumentType() sudah mengembalikan DocumentType
// ProcessScanFile::dispatch() sudah menerima $docType->id

// HAPUS resolveDocumentType() dari ProcessScanFile
// Gunakan $this->documentTypeId langsung

// Di ProcessScanFile::handle():
$documentType = DocumentType::find($this->documentTypeId);
if ($documentType === null) {
    throw new Exception("Document type not found for ID: {$this->documentTypeId}");
}
```

**File yang diubah**:
- `app/Jobs/ProcessScanFile.php` - Hapus method `resolveDocumentType()`, gunakan `documentTypeId` langsung

---

#### 4.2 Kompresi Gambar Sebelum API Call (Estimasi Penghematan: 60-80% tokens)

**Solusi**: Tambahkan image compression sebelum mengirim ke Gemini API.

**Implementasi**:
```php
// app/Services/Ocr/GeminiEngine.php
protected function compressImage(string $filePath, int $maxWidth = 1024): string
{
    $imageInfo = @getimagesize($filePath);
    if ($imageInfo === false) {
        return $filePath; // Bukan gambar, return as-is
    }
    
    [$width, $height] = $imageInfo;
    if ($width <= $maxWidth) {
        return $filePath; // Sudah kecil, tidak perlu kompres
    }
    
    $ratio = $maxWidth / $width;
    $newWidth = $maxWidth;
    $newHeight = (int)($height * $ratio);
    
    // Gunakan Imagick atau GD untuk kompresi
    $imagick = new \Imagick();
    $imagick->readImage($filePath);
    $imagick->thumbnailImage($newWidth, $newHeight);
    $imagick->setImageFormat('jpeg');
    $imagick->setImageCompressionQuality(85);
    
    $compressedPath = $filePath . '.compressed.jpg';
    $imagick->writeImage($compressedPath);
    $imagick->clear();
    $imagick->destroy();
    
    return $compressedPath;
}
```

**Config tambahan di `.env`**:
```
GEMINI_IMAGE_MAX_WIDTH=1024
GEMINI_IMAGE_QUALITY=85
```

**File yang diubah**:
- `app/Services/Ocr/GeminiEngine.php` - Tambah method `compressImage()`
- `config/services.php` - Tambah config image optimization
- `.env.example` - Tambah config options

---

#### 4.3 Optimasi Prompt (Estimasi Penghematan: 20-30% tokens)

**Solusi**: Buat prompt lebih ringkas dan efisien.

**Prompt Saat Ini** (~200 karakter):
```
Analisis dokumen ini dan ekstrak informasi berikut dalam format JSON:
Aturan:
1. Kembalikan HANYA JSON tanpa penjelasan tambahan
2. Jika field tidak ditemukan, gunakan string kosong ""
3. Pastikan format tanggal konsisten (DD Mon YY)
4. Uraian harus berupa array meskipun hanya ada 1 item
5. Field vendor name diambil dari nama dari perusahaan di customer diambil di antara 2 tanda koma...
6. field document_number diambil dari nomor dokumen paling atas
Ekstrak data nya dengan format berikut:
```

**Prompt Optimasi** (~120 karakter):
```
Extract from document as JSON only. Rules: empty string for missing fields, date format DD Mon YY, uraian as array. Vendor name between commas. Return ONLY JSON.
```

**Implementasi**:
```php
// config/services.php
'gemini' => [
    'prompt_template' => <<<'PROMPT'
Extract from document as JSON only. Rules: empty string for missing fields, date format DD Mon YY, uraian as array. Vendor name between commas. Return ONLY JSON.
PROMPT,
    // ...
],
```

**File yang diubah**:
- `config/services.php` - Update prompt template

---

### Prioritas: SEDANG

#### 4.4 Tambahkan Delay antar Request (Rate Limiting)

**Solusi**: Tambahkan delay untuk menghindari RPM limit.

**Implementasi**:
```php
// app/Services/Ocr/GeminiEngine.php
protected int $requestDelayMs = 100; // 100ms delay antar request

public function extractText(UploadedFile $file, ?DocumentType $documentType = null): array
{
    // Delay sebelum request
    usleep($this->requestDelayMs * 1000);
    
    // ... existing code
}
```

**Config tambahan di `.env`**:
```
GEMINI_REQUEST_DELAY_MS=100
```

**File yang diubah**:
- `app/Services/Ocr/GeminiEngine.php` - Tambah delay
- `config/services.php` - Tambah config delay
- `.env.example` - Tambah config option

---

#### 4.5 Tambahkan Retry dengan Backoff

**Solusi**: Implementasi exponential backoff untuk retry.

**Implementasi**:
```php
// app/Services/Ocr/GeminiEngine.php
protected function callGeminiApi(array $payload, int $attempt = 1): array
{
    $maxRetries = 3;
    $baseDelay = 1000; // 1 detik
    
    try {
        $response = Http::timeout(120)->post(...);
        
        if ($response->status() === 429) { // Rate limited
            $delay = $baseDelay * pow(2, $attempt - 1);
            Log::warning("Rate limited, retrying in {$delay}ms", ['attempt' => $attempt]);
            usleep($delay * 1000);
            return $this->callGeminiApi($payload, $attempt + 1);
        }
        
        // ... handle response
    } catch (Exception $e) {
        if ($attempt < $maxRetries) {
            $delay = $baseDelay * pow(2, $attempt - 1);
            usleep($delay * 1000);
            return $this->callGeminiApi($payload, $attempt + 1);
        }
        throw $e;
    }
}
```

**File yang diubah**:
- `app/Services/Ocr/GeminiEngine.php` - Refactor API call dengan retry logic

---

#### 4.6 Tambahkan Monitoring & Logging Token Usage

**Solusi**: Log penggunaan token untuk monitoring.

**Implementasi**:
```php
// app/Services/Ocr/GeminiEngine.php
protected function logTokenUsage(array $response): void
{
    $usageMetadata = $response['usageMetadata'] ?? null;
    if ($usageMetadata) {
        Log::info('Gemini token usage', [
            'prompt_tokens' => $usageMetadata['promptTokenCount'] ?? 0,
            'completion_tokens' => $usageMetadata['candidatesTokenCount'] ?? 0,
            'total_tokens' => $usageMetadata['totalTokenCount'] ?? 0,
        ]);
    }
}
```

**File yang diubah**:
- `app/Services/Ocr/GeminiEngine.php` - Tambah logging token usage

---

### Prioritas: RENDAH

#### 4.7 Caching Hasil Deteksi Document Type

**Solusi**: Cache hasil deteksi berdasarkan hash file.

**Implementasi**:
```php
// app/Services/Ocr/GeminiEngine.php
public function detectDocumentType(UploadedFile $file): ?string
{
    $fileHash = md5_file($file->getRealPath());
    $cacheKey = "gemini_detection_{$fileHash}";
    
    return cache()->remember($cacheKey, 3600, function () use ($file) {
        $result = $this->extractText($file, null);
        return $result['ocr_data']['document_type'] ?? null;
    });
}
```

**File yang diubah**:
- `app/Services/Ocr/GeminiEngine.php` - Tambah method `detectDocumentType()` dengan caching

---

#### 4.8 Batch Processing dengan Delay

**Solusi**: Proses file secara batch dengan delay antar batch.

**Implementasi**:
```php
// app/Console/Commands/Dokter/MonitorScanner.php
protected function processRootFiles(...): void
{
    $files = Storage::disk('ftp_scanner')->files();
    
    // Batch processing
    $batches = array_chunk($files, 5); // 5 file per batch
    
    foreach ($batches as $batch) {
        foreach ($batch as $file) {
            // Process file
        }
        
        // Delay antar batch
        usleep(500000); // 500ms
    }
}
```

**File yang diubah**:
- `app/Console/Commands/Dokter/MonitorScanner.php` - Implementasi batch processing

---

## 5. Ringkasan Estimasi Penghematan

| Optimasi | Estimasi Penghematan | Kompleksitas | Prioritas |
|----------|---------------------|--------------|-----------|
| Eliminasi Duplikasi API Call | 50% (dari 2 → 1 call/file) | Rendah | TINGGI |
| Kompresi Gambar | 60-80% tokens | Sedang | TINGGI |
| Optimasi Prompt | 20-30% tokens | Rendah | TINGGI |
| Rate Limiting | Menghindari 429 error | Rendah | SEDANG |
| Retry with Backoff | Meningkatkan reliability | Sedang | SEDANG |
| Token Usage Logging | Monitoring | Rendah | SEDANG |
| Caching Deteksi | Mengurangi repeat calls | Sedang | RENDAH |
| Batch Processing | Mengurangi RPM pressure | Sedang | RENDAH |

**Total Estimasi Penghematan**: ~70-80% pengurangan penggunaan API

---

## 6. Implementation Plan

### Phase 1: Quick Wins (1-2 hari)
1. [x] Eliminasi duplikasi API call di `ProcessScanFile`
2. [x] Optimasi prompt template
3. [x] Tambahkan logging token usage

### Phase 2: Core Optimization (2-3 hari)
1. [x] Implementasi image compression
2. [x] Tambahkan rate limiting
3. [x] Implementasi retry with backoff

### Phase 3: Advanced (3-5 hari)
1. [x] Caching hasil deteksi
2. [x] Batch processing
3. [ ] Dashboard monitoring penggunaan API

---

## 7. File yang Perlu Diubah

| File | Perubahan |
|------|-----------|
| `app/Jobs/ProcessScanFile.php` | Hapus `resolveDocumentType()`, gunakan `documentTypeId` langsung |
| `app/Services/Ocr/GeminiEngine.php` | Tambah image compression, rate limiting, retry, logging |
| `config/services.php` | Update prompt, tambah config image optimization |
| `.env.example` | Tambah config options baru |
| `app/Console/Commands/Dokter/MonitorScanner.php` | Optimasi flow, batch processing |

---

## 8. Config Baru yang Ditambahkan

```env
# Image Optimization
GEMINI_IMAGE_MAX_WIDTH=1024
GEMINI_IMAGE_QUALITY=85

# Rate Limiting
GEMINI_REQUEST_DELAY_MS=100

# Retry
GEMINI_MAX_RETRIES=3
GEMINI_RETRY_BASE_DELAY_MS=1000
```

---

## 9. Testing Plan

1. **Unit Test**: Test image compression, prompt building, retry logic
2. **Integration Test**: Test full flow dengan mock Gemini API
3. **Load Test**: Simulasi banyak file untuk testing rate limiting
4. **Monitoring**: Pantau penggunaan token selama 1 minggu

---

## 10. Risks & Mitigasi

| Risiko | Mitigasi |
|--------|----------|
| Image compression mengurangi akurasi OCR | Test dengan berbagai resolusi, gunakan quality 85 |
| Prompt optimization mengurangi akurasi | Bandingkan hasil sebelum/sesudah, test dengan sample data |
| Rate limiting memperlambat proses | Balance antara speed dan reliability |
| Cache invalidation | Gunakan TTL singkat (1 jam) |

---

## 11. Success Metrics

- **API Calls per file**: 2 → 1 (50% reduction)
- **Tokens per file**: ~2000 → ~500 (75% reduction)
- **Daily API usage**: Monitor dan pastikan di bawah 1,500 requests
- **Error rate (429)**: < 1%
- **Processing time**: Tidak bertambah signifikan
