# Planning Implementasi Ekstraksi Uraian dari Hasil OCR

> Analisis codebase & struktur database untuk menambahkan ekstraksi **Uraian**
> dari hasil OCR aplikasi dokter, dengan regex yang dapat dikustomisasi per jenis
> dokumen (mengikuti pola ekstraksi nomor invoice / `number_regex` dan ekstraksi
> keterangan / `keterangan_regex`).

---

## 1. Tujuan

1. Mengambil data **Uraian** dari hasil OCR. Contoh pada
   `DD lagi KET URAIAN UPDATE.jpg.json` — data uraian berada di antara baris
   `URAIAN` dan `TOTAL`:

   ```
   URAIAN
   ACCOUNT PAYABLE
   HUTANG PAJAK PENGHASILAN

   TOTAL
   ```

   Hasil ekstraksi: `ACCOUNT PAYABLE | HUTANG PAJAK PENGHASILAN`
   (baris-baris uraian digabung dengan separator ` | `).

2. Regex **dapat dikustomisasi** per jenis dokumen lewat menu Jenis Dokumen
   (create/edit), sama seperti `number_regex` & `number_label` (nomor invoice)
   dan `keterangan_regex` & `keterangan_label` (keterangan).

3. Menyimpan hasil ekstraksi di: hasil OCR JSON (`scanner/ocr-results`),
   tabel `scan_logs`, dan ditampilkan di halaman **Log File** + **Export Excel**.

---

## 2. Analisis Codebase & Database (Kondisi Saat Ini)

### 2.1 Contoh hasil OCR yang dianalisis

File: `storage/app/private/scanner/ocr-results/DD lagi KET URAIAN UPDATE.jpg.json`

```json
{
    "filename": "DD lagi KET URAIAN UPDATE.jpg",
    "document_type": "DOKUMEN DUMMY",
    "dummy_number": "1772 A72",
    "vendor_name": "AITI MITRA UTAMA",
    "keterangan": null,
    "text": "# SBS\n...\nURAIAN\nACCOUNT PAYABLE\nHUTANG PAJAK PENGHASILAN\n\nTOTAL\n\nApproval Bralos; fully approved\n...",
    "processing_time_ms": "4543",
    "processed_at": "2026-08-03T07:18:50+07:00"
}
```

Dari teks OCR, bagian uraian berbentuk:

```
URAIAN
ACCOUNT PAYABLE
HUTANG PAJAK PENGHASILAN

TOTAL
```

Pola yang diinginkan: ambil semua baris di antara label `URAIAN` (satu baris)
sampai sebelum label `TOTAL`.

**Regex default yang terbukti berhasil** (diuji terhadap teks OCR asli):

```
/URAIAN\s*\n(.+?)\n\s*TOTAL/si
```

- `s` (dotall) → `.` ikut mencocokkan `\n`, sehingga uraian multi-baris
  (ACCOUNT PAYABLE + HUTANG PAJAK PENGHASILAN) tertangkap utuh.
- `i` → case-insensitive (OCR bisa menampilkan `uraian` / `Uraian`).
- `(.+?)` lazy + `\n\s*TOTAL` → berhenti tepat sebelum `TOTAL`, tahan terhadap
  baris kosong di antara data uraian dan `TOTAL`.

Hasil capture: `ACCOUNT PAYABLE\nHUTANG PAJAK PENGHASILAN`.

> **Catatan penting:** uraian bersifat **multi-baris**. Berbeda dengan
> `cleanKeterangan()` yang hanya trim + rangkap spasi, uraian perlu digabung
> barisnya. Metode `cleanUraian()` baru: pisah per baris → trim → buang baris
> kosong → gabung dengan ` | `.

### 2.2 Alur ekstraksi saat ini

```
[Job] ProcessScanFile (app/Jobs/ProcessScanFile.php)
   │ extractDocumentNumber()  → number_regex / number_label
   │ extractKeterangan()      → keterangan_regex / keterangan_label
   │ matchVendor()            → vendor_search_enabled
   ▼
$ocrData → scanner/ocr-results/{filename}.json
$logger->log('job_completed', ...) → scan_logs
```

### 2.3 Titik yang terkait

| # | File | Lokasi | Kondisi | Aksi |
|---|------|--------|---------|------|
| 1 | `app/Services/DocumentTypeProcessor.php` | :34-53 | punya `extractKeterangan()` | tambah `extractUraian()` + `cleanUraian()` |
| 2 | `app/Jobs/ProcessScanFile.php` | :72-91 | `extractKeterangan()` dipanggil | tambah `extractUraian()` + key `uraian` di `$ocrData` & log |
| 3 | `database/migrations/2026_08_02_000002_add_keterangan_config_to_document_types_table.php` | - | pola kolom config yang ditiru | tambah migrasi baru `uraian_regex`, `uraian_label` |
| 4 | `database/migrations/2026_08_02_000003_add_keterangan_to_scan_logs_table.php` | - | pola kolom snapshot yang ditiru | tambah migrasi baru kolom `uraian` |
| 5 | `database/migrations/2026_08_02_000004_add_keterangan_enabled_to_document_types_table.php` | - | pola toggle yang ditiru | tambah migrasi baru `uraian_enabled` |
| 6 | `app/Models/DocumentType.php` | :12-24 | `$fillable` | tambah `uraian_regex`, `uraian_label`, `uraian_enabled` |
| 7 | `app/Models/ScanLog.php` | :12-28 | `$fillable` | tambah `uraian` |
| 8 | `app/Http/Requests/StoreDocumentTypeRequest.php` & `UpdateDocumentTypeRequest.php` | :11-23 | validasi | tambah `uraian_regex`, `uraian_label`, `uraian_enabled` |
| 9 | `database/seeders/DocumentTypeSeeder.php` | :16-31 | data SLIP PEMBUKUAN AP | tambah `uraian_regex`, `uraian_label`, `uraian_enabled` |
| 10 | `resources/views/dokter/document-type/create.blade.php` & `edit.blade.php` | form | | tambah field Uraian Regex, Uraian Label, Uraian Enabled |
| 11 | `resources/views/dokter/document-type/index.blade.php` | :44-64 | tabel jenis dokumen | tambah kolom Uraian Regex |
| 12 | `resources/views/dokter/log-file/index.blade.php` | :71-121 | tabel log | tambah kolom Uraian |
| 13 | `app/Exports/ScanLogsExport.php` | :60-75 | export Excel | tambah kolom Uraian |
| 14 | `app/Http/Controllers/Dokter/LogFileController.php` | :38-68 | filter pencarian | tambah `uraian` di `orWhere` |
| 15 | `app/Services/OcrSearchService.php` | :87-114 | `listResults()` baca OCR JSON | tambah key `uraian` (opsional, konsisten) |

---

## 3. Desain Database (Migration Baru)

### Migration 1: `2026_08_03_000001_add_uraian_config_to_document_types_table.php`

| Kolom | Tipe | Default | Keterangan |
|-------|------|---------|------------|
| `uraian_regex` | string, nullable | null | Regex untuk menangkap uraian dari OCR text |
| `uraian_label` | string | `uraian` | Key label JSON (mis. `uraian`) |

```php
Schema::table('document_types', function (Blueprint $table) {
    $table->string('uraian_regex')->nullable()->after('keterangan_enabled');
    $table->string('uraian_label')->default('uraian')->after('uraian_regex');
});
```

### Migration 2: `2026_08_03_000002_add_uraian_to_scan_logs_table.php`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `uraian` | text, nullable | snapshot hasil ekstraksi uraian |

```php
Schema::table('scan_logs', function (Blueprint $table) {
    $table->text('uraian')->nullable()->after('keterangan');
});
```

### Migration 3: `2026_08_03_000003_add_uraian_enabled_to_document_types_table.php`

| Kolom | Tipe | Default | Keterangan |
|-------|------|---------|------------|
| `uraian_enabled` | boolean | true | toggle aktif/nonaktif ekstraksi uraian |

```php
Schema::table('document_types', function (Blueprint $table) {
    $table->boolean('uraian_enabled')->default(true)->after('uraian_label');
});
```

> Ketiga migrasi memakai operasi kolom baru (tanpa DBAL) → aman di PostgreSQL
> tanpa perlu `doctrine/dbal` (mengikuti pengalaman migrasi keterangan).

---

## 4. Desain Logika Ekstraksi

### `app/Services/DocumentTypeProcessor.php`

Tambah metode `extractUraian()` (mengikuti pola `extractKeterangan()`):

```php
public function extractUraian(DocumentType $docType, string $ocrText): ?string
{
    if (! ($docType->uraian_enabled ?? true)) {
        return null;
    }

    $pattern = $docType->uraian_regex
        ?? '/URAIAN\s*\n(.+?)\n\s*TOTAL/si';

    if (preg_match($pattern, $ocrText, $matches)) {
        $cleaned = $this->cleanUraian($matches[1]);

        if ($cleaned !== '') {
            return $cleaned;
        }
    }

    return null;
}
```

Tambah metode pembersihan khusus multi-baris (gabung baris dengan ` | `):

```php
protected function cleanUraian(string $value): string
{
    $lines = preg_split('/\r\n|\r|\n/', $value);

    $lines = array_map('trim', $lines);
    $lines = array_filter($lines, fn ($line) => $line !== '');

    return implode(' | ', $lines);
}
```

> `cleanKeterangan()` tetap untuk keterangan single-line.
> `cleanUraian()` baru untuk uraian multi-baris → hasil `ACCOUNT PAYABLE | HUTANG PAJAK PENGHASILAN`.

### `app/Jobs/ProcessScanFile.php`

Di `handle()` setelah `extractKeterangan()`:

```php
$keterangan = $processor->extractKeterangan($documentType, $ocrText);
$uraian = $processor->extractUraian($documentType, $ocrText);
```

Tambahkan label & key di `$ocrData`:

```php
$keteranganLabel = $documentType->keterangan_label ?? 'keterangan';
$uraianLabel = $documentType->uraian_label ?? 'uraian';

$ocrData = [
    'filename' => $this->filename,
    'document_type' => strtoupper($documentType->name),
    $numberLabel => $documentNumber,
    'vendor_name' => $vendorName,
    $keteranganLabel => $keterangan,
    $uraianLabel => $uraian,
    'text' => $ocrText,
    'processing_time_ms' => $result['processing_time_ms'] ?? null,
    'processed_at' => now()->toIso8601String(),
];
```

Tambahkan `'uraian' => $uraian` pada:
- array `$logger->log('job_completed', ...)`
- array `Log::info('OCR processed successfully', ...)`

---

## 5. Desain Model, Request, Seeder

### `app/Models/DocumentType.php`
- `$fillable` tambah: `uraian_regex`, `uraian_label`, `uraian_enabled`.

### `app/Models/ScanLog.php`
- `$fillable` tambah: `uraian`.

### `app/Http/Requests/StoreDocumentTypeRequest.php` & `UpdateDocumentTypeRequest.php`
Tambah aturan validasi:
```php
'uraian_regex' => ['nullable', 'string', 'max:255'],
'uraian_label' => ['nullable', 'string', 'max:255'],
'uraian_enabled' => ['nullable', 'boolean'],
```

### `database/seeders/DocumentTypeSeeder.php`
Pada SLIP PEMBUKUAN AP tambah:
```php
'uraian_regex' => '/URAIAN\s*\n(.+?)\n\s*TOTAL/si',
'uraian_label' => 'uraian',
'uraian_enabled' => true,
```

---

## 6. Desain Views

### `document-type/create.blade.php` & `edit.blade.php`
Tambah field di bawah Keterangan Enabled:

| Field | name | Default |
|-------|------|---------|
| Uraian Regex | `uraian_regex` | `/URAIAN\s*\n(.+?)\n\s*TOTAL/si` (create) / nilai DB (edit) |
| Uraian Label | `uraian_label` | `uraian` (create) / nilai DB (edit) |
| Uraian Regex Enabled | `uraian_enabled` | `true` (create) / nilai DB (edit) |

```blade
<div class="col-md-6">
    <label class="form-label fw-bold">Uraian Regex</label>
    <input type="text" name="uraian_regex" class="form-control @error('uraian_regex') is-invalid @enderror"
           value="{{ old('uraian_regex', '/URAIAN\\s*\\n(.+?)\\n\\s*TOTAL/si') }}" maxlength="255">
    @error('uraian_regex') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <small class="text-muted">Regex untuk menangkap data Uraian (multi-baris, antara URAIAN dan TOTAL).</small>
</div>

<div class="col-md-6">
    <label class="form-label fw-bold">Uraian Label</label>
    <input type="text" name="uraian_label" class="form-control @error('uraian_label') is-invalid @enderror"
           value="{{ old('uraian_label', 'uraian') }}" maxlength="255">
    @error('uraian_label') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="col-md-6">
    <div class="mt-4">
        <div class="form-check form-switch">
            <input type="hidden" name="uraian_enabled" value="0">
            <input type="checkbox" name="uraian_enabled" class="form-check-input" id="uraian_enabled" value="1" {{ old('uraian_enabled') ? 'checked' : '' }}>
            <label class="form-check-label fw-bold" for="uraian_enabled">Uraian Regex Enabled</label>
        </div>
    </div>
</div>
```

### `document-type/index.blade.php`
Tambah kolom **Uraian Regex** (setelah Keterangan Enabled) menampilkan `<code>{{ $dt->uraian_regex ?? '-' }}</code>` dan kolom **Uraian Enabled** (badge).

### `log-file/index.blade.php`
Tambah kolom **Uraian** setelah kolom Keterangan, dengan `title` penuh + `text-truncate` agar rapi.

### `app/Exports/ScanLogsExport.php`
- `headings()`: tambah `'Uraian'` setelah `'Keterangan'`.
- `map()`: tambah `$log->uraian ?? '-',` setelah `$log->keterangan ?? '-',`.

### `app/Http/Controllers/Dokter/LogFileController.php`
`applyFilters()` pencarian tambah:
```php
->orWhere('uraian', 'like', "%{$search}%")
```

### `app/Services/OcrSearchService.php` (opsional, konsisten)
Di `listResults()` tambah `'uraian' => $data['uraian'] ?? null`, dan helper
`resolveUraianLabel()` (mirror `resolveKeteranganLabel()`).

---

## 7. Struktur File Baru / Berubah

```
baru:
database/migrations/
├── 2026_08_03_000001_add_uraian_config_to_document_types_table.php
├── 2026_08_03_000002_add_uraian_to_scan_logs_table.php
└── 2026_08_03_000003_add_uraian_enabled_to_document_types_table.php

berubah:
app/Services/DocumentTypeProcessor.php   (tambah extractUraian + cleanUraian)
app/Jobs/ProcessScanFile.php
app/Models/DocumentType.php
app/Models/ScanLog.php
app/Http/Requests/StoreDocumentTypeRequest.php
app/Http/Requests/UpdateDocumentTypeRequest.php
database/seeders/DocumentTypeSeeder.php
resources/views/dokter/document-type/create.blade.php
resources/views/dokter/document-type/edit.blade.php
resources/views/dokter/document-type/index.blade.php
resources/views/dokter/log-file/index.blade.php
app/Exports/ScanLogsExport.php
app/Http/Controllers/Dokter/LogFileController.php
app/Services/OcrSearchService.php        (opsional)
```

---

## 8. Urutan Pekerjaan

| Step | Task | Dependency |
|------|------|------------|
| 1 | Migration `document_types` (+ `uraian_regex`, `uraian_label`) | - |
| 2 | Migration `scan_logs` (+ `uraian`) | - |
| 3 | Migration `document_types` (+ `uraian_enabled`) | - |
| 4 | `DocumentTypeProcessor`: implementasi `extractUraian()` + `cleanUraian()` | - |
| 5 | `ProcessScanFile`: panggil ekstraksi + tambah key di ocrData/log | Step 4 |
| 6 | Model `DocumentType` & `ScanLog` ($fillable) | Step 1, 2, 3 |
| 7 | Requests validasi | Step 1 |
| 8 | Seeder | Step 1 |
| 9 | Views document-type (create/edit/index) | Step 1 |
| 10 | View log-file + export + filter controller | Step 2 |
| 11 | `php artisan migrate` | Step 1, 2, 3 |
| 12 | Verifikasi: regex diuji terhadap OCR file; halaman log menampilkan uraian | Semua |

---

## 9. Verifikasi

1. `php artisan migrate` sukses (3 migrasi baru).
2. Unit-test kecil regex default terhadap teks OCR asli:
   `preg_match('/URAIAN\s*\n(.+?)\n\s*TOTAL/si', $text)` menghasilkan
   `ACCOUNT PAYABLE\nHUTANG PAJAK PENGHASILAN`.
3. Jalankan pipeline OCR → hasil JSON memuat key `uraian`;
   `scan_logs.uraian` terisi `ACCOUNT PAYABLE | HUTANG PAJAK PENGHASILAN`.
4. Halaman **Jenis Dokumen**: field Uraian Regex, Uraian Label & Uraian Enabled
   tampil & tersimpan.
5. Halaman **Log File** & **Export Excel**: kolom Uraian tampil.
6. `php artisan route:list` & config cache bersih → aplikasi boot normal.

---

## 10. Catatan Penting / Open Questions

- **Uraian multi-baris:** hasil capture regex bisa berisi beberapa baris.
  Digabung dengan separator ` | ` via `cleanUraian()` agar tampil rapi di satu sel.
- **Pembatas `TOTAL`:** regex default berhenti pada `\nTOTAL`. Jika suatu dokumen
  memakai kata pembatas lain, cukup ubah `uraian_regex` di menu Jenis Dokumen.
- **Backward compatibility:** file OCR JSON lama tidak memuat key `uraian` —
  hanya file baru yang diproses sesudah implementasi yang akan memilikinya.
- **Label dinamis:** `uraian_label` bisa diubah; key JSON mengikuti label.
  `listResults()` memakai default `uraian`.
- **Regex bisa berubah per jenis dokumen:** setiap dokumen dapat punya pola
  uraian berbeda dengan mengubah `uraian_regex` di menu Jenis Dokumen.
- **OCR noise pada baris uraian:** jika OCR menempelkan kolom lain (mis. angka
  DEBET/KREDIT) pada baris yang sama, `cleanUraian()` tidak memisahkannya —
  bisa ditangani lanjutan dengan regex khusus per jenis dokumen.
