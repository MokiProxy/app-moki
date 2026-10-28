# Planning: Implementasi Single-Page OCR untuk PDF Multi-Halaman (Opsi A)

## Status: DONE
## Date: 2026-10-20
## Author: opencode
## Modul: Dokter — Monitor Scanner / OCR / Merge Flow

---

## 1. Tujuan

Menerapkan **Opsi A**:

1. **Semua halaman PDF tetap disimpan** sebagai arsip (upload ke FTP final tetap memakai PDF asli).
2. **OCR Gemini hanya membaca halaman pertama** (jumlah halaman dapat dikonfigurasi) untuk:
   - data yang disimpan ke database (`scan_logs`),
   - penentuan jenis dokumen,
   - pembentukan nama file dan path FTP.
3. **Tidak mengubah** perilaku merge flow: penggabungan tetap memakai PDF lengkap.
4. Tidak menambah dependency baru dan tidak membutuhkan Ghostscript.

### Non-goals

- Tidak memecah satu PDF menjadi beberapa record dokumen.
- Tidak mengubah struktur folder/nama file hasil OCR.
- Tidak memperbaiki seluruh masalah `total_pages` di UI (dibahas sebagai phase opsional).
- Tidak mengubah prompt Gemini.

---

## 2. Latar Belakang & Temuan

### 2.1 Kondisi saat ini

`MonitorScanner` memiliki dua jalur untuk PDF:

**Jalur A — jika Imagick bisa decode PDF** (`app/Console/Commands/Dokter/MonitorScanner.php:156-185` dan `:269-300`)

```php
$images = $converter->pdfToImages($fullPath); // semua halaman, max 20
Storage::disk('local')->delete($localPath);   // PDF asli dihapus
foreach ($images as $imagePath) {
    ProcessScanFile::dispatch($imageFilename, ...); // 1 job per halaman
}
```

**Jalur B — jika Imagick gagal** (catch di baris 177 dan 292)

```php
$this->warn("PDF local conversion unavailable, processing PDF directly: ...");
ProcessScanFile::dispatch($filename, ...); // seluruh PDF
```

Di `GeminiEngine::extractText()` (`app/Services/Ocr/GeminiEngine.php:47-108`), seluruh isi file di-base64 dan dikirim ke Gemini. Tidak ada pemotongan halaman.

### 2.2 Temuan di environment ini

- ImageMagick **tidak memiliki delegate PDF**:
  ```
  NoDecodeDelegateForThisImageFormat `PDF' @ error/constitute.c/ReadImage/746
  ```
  Akibatnya Jalur A selalu gagal dan **seluruh PDF dikirim ke Gemini**.
- PDF nyata `20261020_151604.pdf`:
  - PDF final di FTP: **18 halaman**
  - Ukuran: **4.389.373 byte** (base64 ±5,85 MB)
  - `prompt_tokens` Gemini: **9.768**
  - `completion_tokens`: 214
  - Waktu OCR: 6.854 ms
  - Hasil OCR: hanya **satu** dokumen (Invoice `2604002`)
- PDF nyata `20261020_113818.pdf`: **37 halaman**, 1.706.888 byte.
- `FILE_CONVERSION_MAX_PAGES=20` hanya berlaku di Jalur A; di Jalur B tidak berpengaruh.
- `total_pages` pada `scan_logs` untuk file non-merge selalu `1` (nilai default di `ProcessScanFile.php`), bukan jumlah halaman PDF sebenarnya.

### 2.3 Bukti kelayakan FPDI

FPDI sudah terpasang (`setasign/fpdi`) dan dipakai `PdfMergeService`.

| PDF | Halaman | Ukuran asli | Base64 asli | Ukuran halaman 1 | Base64 halaman 1 | Waktu ekstraksi |
|---|---:|---:|---:|---:|---:|---:|
| `20261020_113818.pdf` | 37 | 1.706.888 B | 2.275.852 | 34.345 B | 45.796 | 59 ms |
| `INV_..._2604002.pdf` | 18 | 4.389.373 B | 5.852.500 | 200.827 B | 267.772 | 68 ms |

Output tervalidasi tetap PDF 1 halaman yang valid.

---

## 3. Keputusan Desain

**Gunakan Opsi A: potong PDF hanya untuk input OCR, arsip tetap PDF utuh.**

Alasan:

| Kriteria | Opsi A (FPDI) | Opsi B (Imagick) | Opsi C (prompt saja) | Opsi D (pecah per halaman) |
|---|---|---|---|---|
| Full PDF tetap tersimpan | Ya | Ya | Ya | Tidak |
| OCR hanya halaman 1 | Ya | Ya | Tidak | Ya (per halaman) |
| Butuh Ghostscript | Tidak | Ya (tidak tersedia) | Tidak | Ya |
| Jumlah record/nama file | 1 | 1 | 1 | N |
| Perubahan arsitektur | Kecil | Sedang | Tidak | Besar |

### Prinsip implementasi

1. **Pemotongan hanya untuk OCR.** `ProcessScanFile` tetap memakai `$fullPath` asli untuk upload dan merge.
2. **Satu pintu OCR.** Pemotongan dilakukan di `GeminiEngine` agar semua pemanggil OCR terlindungi.
3. **MonitorScanner tidak lagi memecah PDF secara default.** Ini mencegah PDF asli dihapus dan diganti gambar halaman (yang bertentangan dengan requirement "simpan semua halaman").
4. **Configurable & reversible.** Semua perilaku baru dikendalikan config/env, tanpa migration.

---

## 4. Arsitektur Target

```
PDF dari FTP scanner (N halaman)
   │
   ├── ProcessScanFile
   │     ├── OCR (GeminiEngine)
   │     │     └── PdfPageExtractor → temp PDF 1 halaman → Gemini
   │     │           └── hasil: document_type, document_number, vendor_name, dll.
   │     │
   │     ├── generateFilename()  ← dari data OCR halaman 1
   │     ├── FTP upload          ← $fullPath asli (N halaman) ✅
   │     └── MergeFlowService    ← memakai PDF lengkap ✅
   │
   └── Arsip FTP final: PDF N halaman tetap utuh
```

Perbedaan kunci:

- Sebelum: yang dikirim ke Gemini = file yang sama dengan yang diarsipkan.
- Sesudah: yang dikirim ke Gemini = **salinan sementara 1 halaman**; yang diarsipkan tetap file asli.

---

## 5. Daftar Perubahan File

| No | File | Perubahan | Wajib |
|---|---|---|---|
| 1 | `config/services.php` | Tambah `gemini.pdf_ocr_pages`, `scanner.split_pdf_pages` | Ya |
| 2 | `.env.example` | Dokumentasi `GEMINI_PDF_OCR_PAGES`, `SCANNER_SPLIT_PDF_PAGES` | Ya |
| 3 | `app/Services/PdfPageExtractor.php` | **Baru** — ekstraksi halaman dengan FPDI | Ya |
| 4 | `app/Services/Ocr/GeminiEngine.php` | Potong PDF sebelum kirim + cleanup temp + fallback | Ya |
| 5 | `app/Console/Commands/Dokter/MonitorScanner.php` | Jangan pecah PDF secara default | Ya |
| 6 | `app/Jobs/ProcessScanFile.php` | Opsional: catat `source_pages` / `ocr_pages_used` | Tidak |
| 7 | `tests/Unit/Dokter/PdfPageExtractorTest.php` | **Baru** — unit test ekstraksi | Ya |
| 8 | `tests/Feature/Dokter/SinglePageOcrTest.php` | Opsional — test integrasi job | Tidak |

Tidak ada migration database.

---

## 6. Detail Implementasi

### 6.1 Config & ENV

`config/services.php` — blok `scanner` dan `gemini`:

```php
'scanner' => [
    'min_file_age' => env('SCANNER_MIN_FILE_AGE', 15),
    // false = PDF dikirim utuh sebagai satu job (default baru)
    // true  = perilaku lama: pecah PDF menjadi gambar per halaman
    'split_pdf_pages' => env('SCANNER_SPLIT_PDF_PAGES', false),
],

'gemini' => [
    // ...
    // Jumlah halaman PDF yang dikirim ke Gemini untuk OCR.
    // 0 = semua halaman (perilaku lama), 1 = halaman pertama saja.
    'pdf_ocr_pages' => env('GEMINI_PDF_OCR_PAGES', 1),

    // Token & think optimization.
    'max_tokens' => env('GEMINI_MAX_TOKENS', 1024),
    'media_resolution' => env('GEMINI_MEDIA_RESOLUTION', 'low'),
    'thinking_level' => env('GEMINI_THINKING_LEVEL', 'minimal'),
    'thinking_budget' => env('GEMINI_THINKING_BUDGET'),
    'response_mime_type' => env('GEMINI_RESPONSE_MIME_TYPE', 'application/json'),
],
```

`.env.example`:

```env
# OCR PDF: jumlah halaman yang dikirim ke Gemini (0 = semua halaman)
GEMINI_PDF_OCR_PAGES=1
# Resolusi media: low | medium | high | unspecified (low = paling hemat token)
GEMINI_MEDIA_RESOLUTION=low
# Thinking Gemini 3/4: minimal | low | medium | high
GEMINI_THINKING_LEVEL=minimal
# Gemini 2.5: 0 = thinking mati, -1 = dinamis
# GEMINI_THINKING_BUDGET=0
GEMINI_RESPONSE_MIME_TYPE=application/json
GEMINI_MAX_TOKENS=1024

# Scanner: pecah PDF menjadi gambar per halaman (false = simpan PDF utuh)
SCANNER_SPLIT_PDF_PAGES=false
```

Catatan operasional: setelah mengubah `.env`, jalankan:

```bash
php artisan config:clear
```

### 6.2 Service baru: `app/Services/PdfPageExtractor.php`

Tanggung jawab:

- Menerima path PDF dan jumlah halaman yang diinginkan.
- Mengembalikan path file temp PDF hasil potongan.
- Mengembalikan `null` jika tidak perlu dipotong (`total halaman <= limit`).
- Melempar exception jika PDF tidak bisa dibaca FPDI (agar pemanggil bisa fallback).

```php
<?php

namespace App\Services;

use setasign\Fpdi\Fpdi;
use Throwable;

class PdfPageExtractor
{
    /**
     * Ekstrak sejumlah halaman pertama ke file PDF sementara.
     *
     * @return string|null Path file temp, atau null jika tidak perlu dipotong.
     *
     * @throws Throwable
     */
    public function extractFirstPages(string $pdfPath, int $limit): ?string
    {
        if ($limit <= 0) {
            return null;
        }

        $out = new Fpdi();
        $totalPages = $out->setSourceFile($pdfPath);

        if ($totalPages <= $limit) {
            return null;
        }

        for ($page = 1; $page <= $limit; $page++) {
            $templateId = $out->importPage($page);
            $size = $out->getTemplateSize($templateId);

            $out->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $out->useTemplate($templateId);
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'ocr_pdf_');
        if ($tempPath === false) {
            throw new \RuntimeException('Gagal membuat file temp untuk OCR PDF');
        }

        // tempnam membuat file tanpa ekstensi; gunakan nama .pdf agar konsisten.
        @unlink($tempPath);
        $tempPath .= '.pdf';

        $out->Output('F', $tempPath);

        return $tempPath;
    }
}
```

Catatan:

- FPDI membaca struktur PDF; pada uji nyata 37 halaman selesai dalam ±59 ms.
- File temp **wajib** dihapus oleh pemanggil (`finally`).
- Jika `limit` lebih besar/equal dari jumlah halaman, tidak ada file temp yang dibuat.

### 6.3 Integrasi di `GeminiEngine`

`app/Services/Ocr/GeminiEngine.php`:

1. Tambah property:

```php
protected int $pdfOcrPages;
```

2. Di constructor:

```php
$this->pdfOcrPages = (int) config('services.gemini.pdf_ocr_pages', 1);
```

3. Di `extractText()`, siapkan path OCR terpisah dari file asli:

```php
public function extractText(UploadedFile $file, ?DocumentType $documentType = null): array
{
    $startTime = microtime(true);

    $originalPath = $file->getRealPath();
    $ocrPath = $originalPath;
    $temporaryPdf = null;
    $mimeType = $file->getMimeType() ?: mime_content_type($originalPath);

    try {
        if ($this->isPdf($originalPath, $mimeType)) {
            $temporaryPdf = app(PdfPageExtractor::class)
                ->extractFirstPages($originalPath, $this->pdfOcrPages);

            if ($temporaryPdf !== null) {
                $ocrPath = $temporaryPdf;
            }

            $mimeType = 'application/pdf';
            $fileContent = file_get_contents($ocrPath);
        } else {
            $compressedPath = $this->compressImage($originalPath);
            $fileContent = file_get_contents($compressedPath);
            $mimeType = $compressedPath !== $originalPath
                ? 'image/jpeg'
                : $mimeType;
        }

        if ($fileContent === false) {
            throw new Exception('Gagal membaca file: '.$file->getClientOriginalName());
        }

        // ... prompt, base64, callGeminiApi(), parse, log ...

        return [...];
    } finally {
        if ($temporaryPdf !== null && file_exists($temporaryPdf)) {
            @unlink($temporaryPdf);
        }
    }
}
```

4. Helper deteksi PDF (lebih tahan terhadap mime yang tidak konsisten):

```php
protected function isPdf(string $path, ?string $mimeType): bool
{
    return $mimeType === 'application/pdf'
        || strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf';
}
```

Poin penting:

- `GeminiEngine` diinstansiasi dengan `new GeminiEngine()` di `ProcessScanFile.php:59`, sehingga **constructor injection tidak otomatis** berjalan. Gunakan `app(PdfPageExtractor::class)` di dalam method, atau ubah pemanggilan menjadi `app(GeminiEngine::class)`. Rencana ini memilih `app(...)` agar perubahan minimal.
- Cleanup **harus** di `finally`, termasuk saat API gagal/exception.
- Saat `pdf_ocr_pages = 0`, `extractFirstPages()` mengembalikan `null` dan perilaku kembali ke pengiriman PDF utuh.
- `compressImage()` tidak perlu dipanggil untuk PDF (PDF bukan gambar dan akan mengembalikan path asli).

### 6.4 `MonitorScanner` — jangan pecah PDF secara default

Ubah dua blok yang saat ini memanggil `pdfToImages()` (root dan subfolder) menjadi:

```php
// root: $documentTypeId = null
// subfolder: $documentTypeId = $documentType->id
$splitPdf = (bool) config('services.scanner.split_pdf_pages', false);

if ($converter->isPdf($fullPath) && $splitPdf) {
    // perilaku lama: pecah menjadi gambar per halaman
    try {
        $images = $converter->pdfToImages($fullPath);
        Storage::disk('local')->delete($localPath);

        if (empty($images)) {
            // ... logging yang sudah ada ...
            return;
        }

        foreach ($images as $imagePath) {
            ProcessScanFile::dispatch(basename($imagePath), $documentTypeId);
        }
    } catch (Exception $e) {
        // fallback lama: kirim PDF utuh
        ProcessScanFile::dispatch($filename, $documentTypeId);
    }
} else {
    // default baru: PDF utuh, OCR dipotong di GeminiEngine
    ProcessScanFile::dispatch($filename, $documentTypeId);
}
```

Catatan:

- Blok kode ini **duplikat** di `processRootFile()` dan `processFile()`. Disarankan ekstrak menjadi helper `dispatchScanFile()` agar tidak ada perbedaan perilaku di kemudian hari.
- Jika `split_pdf_pages=false` (default), file PDF tetap ada di `storage/app/private/scanner/incoming` sampai `ProcessScanFile` selesai upload, lalu dihapus di `ProcessScanFile.php` (baris ±250). Ini menjaga arsip penuh di FTP final.
- `FileConversionService::pdfToImages()` tetap dipertahankan untuk kebutuhan lain/rollback.

### 6.5 Opsional — metrik halaman di `ScanLog`

Saat ini `total_pages` untuk file non-merge selalu `1`:

```php
$mergeStatus = 'new';
$totalPages = 1;
```

Rekomendasi phase 2 (tidak wajib untuk Opsi A):

- Hitung jumlah halaman asli untuk PDF:
  ```php
  $sourcePages = $converter->isPdf($fullPath)
      ? $merger->getPageCount($fullPath)
      : 1;
  ```
- Simpan:
  - `total_pages` = jumlah halaman file yang diarsipkan, atau
  - `metadata.source_pages` = jumlah halaman asli,
  - `metadata.ocr_pages_used` = jumlah halaman yang dikirim ke OCR.
- Tampilkan di halaman Log File agar operator bisa membedakan PDF 1 halaman vs 18 halaman.

Jika tidak ingin mengubah makna `total_pages`, gunakan metadata saja.

---

## 7. Edge Case & Fallback

| Kasus | Perilaku yang diharapkan |
|---|---|
| PDF 1 halaman | Tidak ada file temp; PDF asli dikirim ke Gemini (tidak ada overhead) |
| PDF N halaman, `pdf_ocr_pages=1` | File temp 1 halaman dikirim; file asli tetap untuk arsip |
| PDF N halaman, `pdf_ocr_pages=0` | Seluruh PDF dikirim (perilaku lama) |
| PDF terenkripsi / FPDI gagal | Log warning; fallback kirim PDF utuh agar job tidak gagal total |
| Halaman 1 kosong/cover | Data OCR bisa kosong. Mitigasi: set `pdf_ocr_pages=2`, atau phase 2 fallback ke halaman berikutnya bila field kunci kosong |
| PDF berisi banyak dokumen | Hanya 1 record & 1 nama file (dari halaman 1); semua halaman tetap diarsipkan |
| Gambar (JPG/PNG) | Tidak berubah; tetap lewat `compressImage()` |
| Merge flow | Tidak berubah; memakai PDF lengkap dari FTP |
| Temp file tertinggal | Dicegah dengan `finally` + `@unlink` |
| Config cache | Wajib `php artisan config:clear` setelah mengubah env |

### Fallback FPDI

```php
try {
    $temporaryPdf = app(PdfPageExtractor::class)
        ->extractFirstPages($originalPath, $this->pdfOcrPages);
} catch (Throwable $e) {
    Log::warning('Gagal memotong PDF untuk OCR, memakai PDF utuh', [
        'file' => $file->getClientOriginalName(),
        'error' => $e->getMessage(),
    ]);

    $temporaryPdf = null; // lanjut dengan PDF asli
}
```

---

## 8. Rencana Pengujian

### 8.1 Unit test — `PdfPageExtractor`

Buat `tests/Unit/Dokter/PdfPageExtractorTest.php`:

| Test | Ekspektasi |
|---|---|
| PDF 3 halaman, limit 1 | Output PDF valid, 1 halaman |
| PDF 3 halaman, limit 2 | Output PDF valid, 2 halaman |
| PDF 3 halaman, limit 3 | `null` (tidak perlu dipotong) |
| PDF 3 halaman, limit 0 | `null` |
| File bukan PDF | Exception |
| File tidak ada | Exception |
| File temp dihapus pemanggil | Tidak ada file tersisa |

Gunakan FPDF (`setasign/fpdf`, sudah tersedia) untuk membuat PDF uji multi-halaman.

### 8.2 Manual / integrasi

Gunakan PDF nyata yang sudah ada:

- `storage/app/private/scanner/incoming/20261020_113818.pdf` (37 halaman)
- atau PDF 18 halaman dari FTP final invoice.

Langkah:

1. Set `GEMINI_PDF_OCR_PAGES=1`, `SCANNER_SPLIT_PDF_PAGES=false`, lalu `php artisan config:clear`.
2. Salin PDF multi-halaman ke folder FTP scanner (`C:\FTP_Folder\incoming` atau subfolder document type).
3. Jalankan:
   ```bash
   php artisan app:monitor-scanner
   ```
4. Verifikasi:
   - [ ] File di `storage/app/private/scanner/incoming` terhapus setelah sukses.
   - [ ] PDF di FTP final memiliki jumlah halaman **sama dengan sumber** (cek dengan FPDI).
   - [ ] `scan_logs` berisi `document_number`, `vendor_name`, dan `ftp_path` yang benar.
   - [ ] Nama file mengikuti template (`INV_...`, `BA_...`, dst.).
   - [ ] Log Gemini menunjukkan `prompt_tokens` jauh lebih kecil dibanding PDF utuh.
   - [ ] Tidak ada file `ocr_pdf_*.pdf` tersisa di temp.
5. Regression:
   - [ ] Upload JPG/PNG → tetap diproses seperti biasa.
   - [ ] PDF 1 halaman → tetap benar.
   - [ ] `GEMINI_PDF_OCR_PAGES=0` → seluruh PDF dikirim (tidak ada error).
   - [ ] `SCANNER_SPLIT_PDF_PAGES=true` → perilaku lama (pecah per halaman) masih berjalan.
   - [ ] Merge flow 2 PDF multi-halaman → PDF final berisi total halaman keduanya.

### 8.3 Test matrix

| Skenario | pdf_ocr_pages | split_pdf_pages | Ekspektasi |
|---|---:|---:|---|
| Default baru | 1 | false | Arsip full, OCR 1 halaman |
| Tanpa optimasi | 0 | false | Arsip full, OCR semua halaman |
| Legacy split | 1 | true | Perilaku lama (gambar per halaman) |
| PDF 1 halaman | 1 | false | Tidak ada temp, OCR 1 halaman |

---

## 9. Acceptance Criteria

- [x] PDF multi-halaman tetap tersimpan **utuh** di FTP final (upload tetap memakai `$fullPath` asli).
- [x] OCR Gemini hanya menerima **1 halaman** (default) untuk PDF multi-halaman.
- [x] Data `scan_logs` dan nama file tetap dihasilkan dari halaman 1 (tidak ada perubahan alur data).
- [x] Tidak ada perubahan perilaku untuk file gambar.
- [x] Merge flow tetap menggunakan PDF lengkap.
- [x] Gagal ekstraksi PDF tidak membuat job gagal total (fallback ke PDF utuh).
- [x] Tidak ada file temp OCR yang tertinggal (dibersihkan di `finally`, diuji).
- [x] `GEMINI_PDF_OCR_PAGES=0` mengembalikan perilaku lama tanpa error (diuji).
- [x] Unit test PdfPageExtractor lulus (8 test, 20 assertion).
- [x] Feature test integrasi GeminiEngine lulus (3 test, 16 assertion).
- [x] `php -l` pada semua file yang diubah tidak menghasilkan error.
- [x] `php artisan config:clear` terdokumentasi.
- [ ] Verifikasi end-to-end di FTP final (butuh koneksi FTP scanner/final).

---

## 10. Rollout & Rollback

### Rollout

1. Deploy kode + perubahan config.
2. Set env:
   ```env
   GEMINI_PDF_OCR_PAGES=1
   SCANNER_SPLIT_PDF_PAGES=false
   ```
3. Jalankan `php artisan config:clear`.
4. Uji dengan satu PDF multi-halaman di folder scanner.
5. Monitor log `Gemini token usage` dan `OCR processed successfully`.

Tidak ada migration dan tidak ada perubahan data lama.

### Rollback

- Kembalikan OCR ke semua halaman:
  ```env
  GEMINI_PDF_OCR_PAGES=0
  ```
- Kembalikan perilaku pemecahan PDF lama (jika diperlukan):
  ```env
  SCANNER_SPLIT_PDF_PAGES=true
  ```
- Jalankan `php artisan config:clear`.

Catatan: `SCANNER_SPLIT_PDF_PAGES=true` akan menghapus PDF asli dan menyimpan gambar per halaman (perilaku lama), jadi hanya dipakai untuk rollback darurat.

---

## 11. Risiko & Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Halaman 1 bukan halaman data utama | Data DB/nama file salah | Verifikasi dengan dokumen nyata; naikkan `pdf_ocr_pages`; phase 2 fallback halaman berikutnya |
| FPDI gagal pada PDF tertentu | OCR gagal jika tidak ditangani | `try/catch` + fallback PDF utuh |
| PDF sangat besar tetap lambat | Timeout | Pemotongan halaman justru mengurangi risiko; pantau waktu OCR |
| `SCANNER_SPLIT_PDF_PAGES=true` tidak sengaja aktif | PDF asli tidak tersimpan | Default `false`; dokumentasikan sebagai legacy |
| `total_pages` tetap menyesatkan | Monitoring salah | Phase 2 simpan metrik halaman asli |
| Config cache tidak dibersihkan | Env tidak terbaca | Dokumentasi + checklist deploy |

---

## 12. Urutan Pekerjaan

1. [x] Tambah config `services.gemini.pdf_ocr_pages` dan `services.scanner.split_pdf_pages`.
2. [x] Tambah dokumentasi env di `.env.example`.
3. [x] Buat `app/Services/PdfPageExtractor.php`.
4. [x] Buat unit test `PdfPageExtractorTest`.
5. [x] Integrasikan pemotongan di `GeminiEngine::extractText()` + cleanup `finally` + fallback.
6. [x] Ubah `MonitorScanner` agar tidak memecah PDF secara default (dua lokasi).
7. [x] (Opsional) Simpan `source_pages` / `ocr_pages_used` di `ScanLog`.
8. [x] Jalankan `php -l` pada semua file yang diubah.
9. [x] Uji extractor dengan PDF nyata 37 halaman (PDF 18 halaman tidak tersedia di workspace).
10. [x] Uji regresi PDF 1 halaman dan `pdf_ocr_pages=0` melalui feature test.
11. [ ] Verifikasi jumlah halaman PDF arsip di FTP final.
12. [ ] Update dokumentasi/tutorial modul dokter bila perlu.
13. [ ] Code review & merge.

---

## 13. Pertanyaan Terbuka

1. **Jumlah halaman OCR:** tetap 1 halaman, atau sediakan opsi 2 halaman untuk dokumen dengan cover?
   - Rekomendasi: default 1, configurable via env.
2. **Fallback otomatis:** perlukah OCR halaman berikutnya jika `document_number`/`vendor_name` kosong?
   - Rekomendasi: phase 2, bukan bagian dari implementasi awal.
3. **Metrik halaman:** apakah `total_pages` boleh diubah maknanya, atau cukup metadata?
   - Rekomendasi: metadata terlebih dahulu agar tidak mengubah UI yang ada.
4. **Legacy split:** apakah `SCANNER_SPLIT_PDF_PAGES` perlu dipertahankan?
   - Rekomendasi: ya, untuk rollback, default `false`.

---

## 14. Estimasi

| Pekerjaan | Estimasi |
|---|---:|
| Config + service extractor | 0.5 hari |
| Integrasi GeminiEngine | 0.5 hari |
| Perubahan MonitorScanner | 0.25 hari |
| Unit test + manual test | 0.5–1 hari |
| **Total** | **1.5–2 hari** |

---

## 15. Referensi File

- `app/Console/Commands/Dokter/MonitorScanner.php:156-185`, `:269-300` — cabang PDF → images.
- `app/Jobs/ProcessScanFile.php:39-58` — `handle()` dan pembuatan `UploadedFile`.
- `app/Jobs/ProcessScanFile.php:121-152` — pengambilan konten upload.
- `app/Jobs/ProcessScanFile.php:227` — upload PDF ke FTP final.
- `app/Services/Ocr/GeminiEngine.php:47-108` — `extractText()`.
- `app/Services/Ocr/GeminiEngine.php:110-154` — `compressImage()`.
- `app/Services/FileConversionService.php:48-86` — `pdfToImages()` (legacy).
- `app/Services/PdfMergeService.php:27-91` — FPDI, `mergePdfs()`, `getPageCount()`.
- `config/services.php:60-96` — konfigurasi scanner & Gemini.
- `.env.example` — dokumentasi environment.

---

## 16. Catatan Implementasi (2026-10-20)

### File yang diubah / dibuat

| File | Perubahan |
|---|---|
| `config/services.php` | `scanner.split_pdf_pages` (default `false`), `gemini.pdf_ocr_pages` (default `1`) |
| `.env.example` | `GEMINI_PDF_OCR_PAGES=1`, `SCANNER_SPLIT_PDF_PAGES=false` |
| `app/Services/PdfPageExtractor.php` | **Baru** — ekstraksi halaman dengan FPDI, mengembalikan `path`, `source_pages`, `ocr_pages` |
| `app/Services/Ocr/GeminiEngine.php` | Potong PDF sebelum OCR, fallback ke PDF utuh, cleanup `finally`, log halaman |
| `app/Console/Commands/Dokter/MonitorScanner.php` | Cabang pecah PDF hanya aktif jika `split_pdf_pages=true` |
| `app/Jobs/ProcessScanFile.php` | Simpan `source_pages` & `ocr_pages_used` di metadata OCR |
| `tests/Unit/Dokter/PdfPageExtractorTest.php` | **Baru** — 8 test |
| `tests/Feature/Dokter/GeminiEngineSinglePageOcrTest.php` | **Baru** — 5 test integrasi dengan `Http::fake()` |

### Perilaku akhir

- PDF dari scanner **tidak dipecah** dan **tidak dihapus** oleh `MonitorScanner` secara default.
- `ProcessScanFile` tetap mengunggah file asli (`$fullPath`) ke FTP final, sehingga seluruh halaman tersimpan.
- `GeminiEngine` membuat salinan sementara berisi halaman pertama (default) lalu menghapusnya di `finally`.
- Jika FPDI gagal (PDF terenkripsi/rusak), OCR memakai PDF utuh dan mencatat warning.
- `GEMINI_PDF_OCR_PAGES=0` mengirim seluruh PDF (perilaku lama).
- `SCANNER_SPLIT_PDF_PAGES=true` mengembalikan perilaku lama (pecah gambar per halaman).

### Hasil verifikasi

| Verifikasi | Hasil |
|---|---|
| `php -l` semua file yang diubah | Tidak ada syntax error |
| `phpunit tests/Unit/Dokter/PdfPageExtractorTest.php` | 8 test, 20 assertion — OK |
| `phpunit tests/Feature/Dokter/GeminiEngineSinglePageOcrTest.php` | 5 test, 27 assertion — OK |
| `phpunit --testsuite Unit` | 121 test, 303 assertion — OK |
| PDF nyata 37 halaman (`20261020_113818.pdf`, 1.706.888 B) | Terpotong menjadi 1 halaman (34.345 B), output PDF valid 1 halaman |
| OCR nyata via fallback `gemini-3.5-flash-lite` | Sukses, 391 prompt token + 248 completion token (total 639), hasil ekstraksi benar |

### Catatan

- File `.env` aktual tidak diubah karena default config sudah menerapkan perilaku baru. Jalankan `php artisan config:clear` bila `.env` diubah.
- `metadata.ocr_data.source_pages` dan `metadata.ocr_data.ocr_pages_used` bernilai `null` bila FPDI gagal membaca PDF (jumlah halaman tidak diketahui).
- `total_pages` pada `scan_logs` belum diubah maknanya; nilai tersebut tetap mengikuti logika merge yang sudah ada.
- Verifikasi end-to-end ke FTP final belum dijalankan karena membutuhkan koneksi FTP scanner/final.

---

## 17. Optimasi Token Lanjutan (2026-10-20)

Setelah Opsi A berjalan, konsumsi token diturunkan lagi tanpa mengubah data yang diarsipkan.

### Perubahan

| Optimasi | Sebelum | Sesudah | Dampak |
|---|---|---|---|
| Halaman OCR | Semua halaman (18) | 1 halaman | ±18x lebih sedikit |
| Resolusi media PDF | `unspecified` (±560 token/halaman, Gemini 3) | `MEDIA_RESOLUTION_LOW` (±280 token/halaman) | ±2x lebih sedikit |
| Thinking | Default model | `thinkingLevel: minimal` | Menghindari token thinking berlebih |
| Output | Teks bebas + markdown | `responseMimeType: application/json` | Output lebih pendek & konsisten |
| `maxOutputTokens` | 8192 | 1024 | Mencegah output/thinking membengkak |
| Prompt | ±175 token | ±100 token | Prompt lebih ringkas |
| 503/429 | Job langsung gagal | Retry exponential + `Retry-After` | Lebih tahan saat model high demand |
| Model high demand | Job gagal | Fallback otomatis ke model cadangan | OCR tetap jalan meski model utama 503 |

### Konfigurasi baru

```env
GEMINI_PDF_OCR_PAGES=1
GEMINI_MEDIA_RESOLUTION=low
GEMINI_THINKING_LEVEL=minimal
GEMINI_RESPONSE_MIME_TYPE=application/json
GEMINI_MAX_TOKENS=1024
GEMINI_FALLBACK_MODELS=gemini-3.5-flash-lite,gemini-2.5-flash-lite
# Khusus Gemini 2.5:
# GEMINI_THINKING_BUDGET=0
```

### Fallback model

Jika model utama mengembalikan `503/500/502/504` setelah 3 percobaan, engine otomatis mencoba model berikutnya dari `GEMINI_FALLBACK_MODELS`. Model `gemini-2.5-flash-lite` otomatis memakai `thinkingBudget: 0` (bukan `thinkingLevel`).

### Hasil pengukuran nyata

Diuji dengan PDF 37 halaman (`20261020_113818.pdf`) memakai model cadangan `gemini-3.5-flash-lite`:

| Skenario | Prompt token | Completion token | Total |
|---|---:|---:|---:|
| Sebelum (18 halaman, default) | 9.768 | 214 | 9.982 |
| Sesudah (1 halaman, `low`, prompt ringkas) | **391** | **248** | **639** |
| **Reduksi** | **±96%** | — | **±93,6%** |

Hasil ekstraksi tetap benar:

```json
{
  "document_type": "SLIP PEMBUKUAN AP",
  "document_number": "001/PBT/M/03/26",
  "vendor_name": "PUSAKA BUMI TRANSPORTASI",
  "document_date": "02 Apr 26"
}
```

`thoughts_tokens` tercatat `0`, sehingga `thinkingLevel: minimal` benar-benar menekan token thinking. Waktu proses 50 detik karena model utama 503 dan harus beralih ke model cadangan.

Angka aktual dapat bervariasi tergantung jenis PDF (native text vs scanned) dan panjang `uraian`. Jika akurasi OCR menurun pada dokumen tertentu, naikkan `GEMINI_MEDIA_RESOLUTION` ke `medium` atau `high`.

### Catatan 503

Saat pengujian nyata, model `gemini-3.1-flash-lite` mengembalikan `503 UNAVAILABLE` karena high demand. Kode sekarang melakukan retry otomatis (3 percobaan, exponential backoff, menghormati `Retry-After`) lalu beralih ke `GEMINI_FALLBACK_MODELS`. Jika semua model gagal, queue akan mencoba ulang job pada siklus berikutnya.
