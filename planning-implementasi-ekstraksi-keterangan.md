# Planning Implementasi Ekstraksi Keterangan dari Hasil OCR

> Analisis codebase & struktur database untuk menambahkan ekstraksi **Keterangan**
> dari hasil OCR aplikasi dokter, dengan regex yang dapat dikustomisasi per jenis
> dokumen (mengikuti pola ekstraksi nomor invoice / `number_regex`).

---

## 1. Tujuan

1. Mengambil data **Keterangan** dari hasil OCR (contoh: `P11646-JASA PENANBANCAN JAN'26 0400260006/298202`).
2. Regex **dapat dikustomisasi** per jenis dokumen lewat menu Jenis Dokumen (create/edit),
   sama seperti `number_regex` & `number_label` untuk nomor invoice.
3. Menyimpan hasil ekstraksi di: hasil OCR JSON (`scanner/ocr-results`), tabel `scan_logs`,
   dan ditampilkan di halaman **Log File** + **Export Excel**.

---

## 2. Analisis Codebase & Database (Kondisi Saat Ini)

### 2.1 Contoh hasil OCR yang dianalisis

File: `storage/app/private/scanner/ocr-results/pbt r54 lagi.jpg.json`

```json
{
    "filename": "pbt r54 lagi.jpg",
    "document_type": "SLIP PEMBUKUAN AP",
    "invoice_number": "762D R54",
    "vendor_name": "PUSAKA BUMI TRANSPORTAS",
    "text": "...\nCustomer\n: 00023/,PUSAKA BUMI TRANSPORTASI\nPT\nKeterangan\n: P11646-JASA PENANBANCAN JAN'26 0400260006/298202\nIisa Grouд 101\n: TJMO2026040165856930000FODHT\n..."
}
```

Dari teks OCR, baris keterangan berbentuk:

```
Keterangan
: P11646-JASA PENANBANCAN JAN'26 0400260006/298202
```

Pola yang diinginkan: `LABEL` (baris baru) `:` `spasi` `VALUE` (sampai akhir baris).

**Regex default yang terbukti berhasil** (diuji terhadap teks OCR asli):

```
/Keterangan\s*:\s*(.+)/i
```

Hasil capture: `P11646-JASA PENANBANCAN JAN'26 0400260006/298202`

> **Catatan penting:** nilai keterangan mengandung karakter `/` (mis. `0400260006/298202`).
> Karena itu pembersihan noise untuk keterangan **tidak boleh** menghapus `/`, `\`, maupun `,`
> (berbeda dengan `cleanOcrNoise()` yang dipakai untuk nomor dokumen). Perlu metode
> pembersihan terpisah: hanya trim + rangkap spasi.

### 2.2 Alur ekstraksi saat ini

```
[Job] ProcessScanFile (app/Jobs/ProcessScanFile.php)
   │ extractDocumentNumber()  → number_regex / number_label
   │ matchVendor()            → vendor_search_enabled
   │ extractKeterangan()      → (STUB — masih memakai number_regex, belum benar)
   ▼
$ocrData → scanner/ocr-results/{filename}.json
$logger->log('job_completed', ...) → scan_logs
```

### 2.3 Titik yang terkait

| # | File | Lokasi | Kondisi | Aksi |
|---|------|--------|---------|------|
| 1 | `app/Services/DocumentTypeProcessor.php` | :34-49 | `extractKeterangan()` masih STUB (salinan `extractDocumentNumber`) | implementasi dengan `keterangan_regex` |
| 2 | `app/Jobs/ProcessScanFile.php` | :72, :79-88 | belum ekstrak keterangan | tambah pemanggilan + key di `$ocrData`, log, `Log::info` |
| 3 | `database/migrations/2026_07_26_000000_add_ocr_config_to_document_types_table.php` | - | kolom `number_regex`, `number_label` (pola yang ditiru) | biarkan; tambah kolom baru via migrasi baru |
| 4 | `app/Models/DocumentType.php` | :16-17 | `$fillable` | tambah `keterangan_regex`, `keterangan_label` |
| 5 | `app/Models/ScanLog.php` | :12-27 | `$fillable` | tambah `keterangan` |
| 6 | `app/Http/Requests/StoreDocumentTypeRequest.php` & `UpdateDocumentTypeRequest.php` | :11-20 | validasi | tambah `keterangan_regex`, `keterangan_label` |
| 7 | `database/seeders/DocumentTypeSeeder.php` | :21-26 | data awal SLIP PEMBUKUAN AP | tambah `keterangan_regex`, `keterangan_label` |
| 8 | `resources/views/dokter/document-type/create.blade.php` & `edit.blade.php` | form | | tambah field Keterangan Regex & Label |
| 9 | `resources/views/dokter/document-type/index.blade.php` | :44-61 | tabel jenis dokumen | tambah kolom Keterangan Regex |
| 10 | `resources/views/dokter/log-file/index.blade.php` | :71-117 | tabel log | tambah kolom Keterangan |
| 11 | `app/Exports/ScanLogsExport.php` | :26-63 | export Excel | tambah kolom Keterangan |
| 12 | `app/Http/Controllers/Dokter/LogFileController.php` | :56-64 | filter pencarian | tambah `keterangan` di `orWhere` |
| 13 | `app/Services/OcrSearchService.php` | :87-113 | `listResults()` baca OCR JSON | tambah key `keterangan` (opsional, konsisten) |

---

## 3. Desain Database (Migration Baru)

### Migration 1: `2026_08_02_000002_add_keterangan_config_to_document_types_table.php`

| Kolom | Tipe | Default | Keterangan |
|-------|------|---------|------------|
| `keterangan_regex` | string, nullable | null | Regex untuk menangkap keterangan dari OCR text |
| `keterangan_label` | string | `keterangan` | Key label JSON (mis. `keterangan`) |

```php
Schema::table('document_types', function (Blueprint $table) {
    $table->string('keterangan_regex')->nullable()->after('number_label');
    $table->string('keterangan_label')->default('keterangan')->after('keterangan_regex');
});
```

### Migration 2: `2026_08_02_000003_add_keterangan_to_scan_logs_table.php`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `keterangan` | text, nullable | snapshot hasil ekstraksi keterangan |

```php
Schema::table('scan_logs', function (Blueprint $table) {
    $table->text('keterangan')->nullable()->after('vendor_name');
});
```

> Kedua migrasi memakai operasi kolom baru (tanpa DBAL) → aman di PostgreSQL
> tanpa perlu `doctrine/dbal` (pengalaman migrasi sebelumnya).

---

## 4. Desain Logika Ekstraksi

### `app/Services/DocumentTypeProcessor.php`

Perbaiki stub `extractKeterangan()` (baris 34-49) menjadi:

```php
public function extractKeterangan(DocumentType $docType, string $ocrText): ?string
{
    $pattern = $docType->keterangan_regex
        ?? '/Keterangan\s*:\s*(.+)/i';

    if (preg_match($pattern, $ocrText, $matches)) {
        $raw = trim($matches[1]);
        $cleaned = $this->cleanKeterangan($raw);

        if ($cleaned !== '') {
            return $cleaned;
        }
    }

    return null;
}
```

Tambahkan metode pembersihan khusus (tidak menghapus `/`, `\`, `,`):

```php
protected function cleanKeterangan(string $value): string
{
    return Str::of($value)
        ->replaceMatches('/\s{2,}/', ' ')
        ->trim()
        ->__toString();
}
```

> `cleanOcrNoise()` (untuk nomor dokumen) tetap — menghapus `\ / ,`.
> Keterangan memakai `cleanKeterangan()` yang lebih konservatif.

### `app/Jobs/ProcessScanFile.php`

Di `handle()` setelah `extractDocumentNumber()`:

```php
$documentNumber = $processor->extractDocumentNumber($documentType, $ocrText);
$keterangan = $processor->extractKeterangan($documentType, $ocrText);
$vendorName = $processor->matchVendor($documentType, $ocrText);
```

Tambahkan label & key di `$ocrData`:

```php
$numberLabel = $documentType->number_label ?? 'document_number';
$keteranganLabel = $documentType->keterangan_label ?? 'keterangan';

$ocrData = [
    'filename' => $this->filename,
    'document_type' => strtoupper($documentType->name),
    $numberLabel => $documentNumber,
    'vendor_name' => $vendorName,
    $keteranganLabel => $keterangan,
    'text' => $ocrText,
    'processing_time_ms' => $result['processing_time_ms'] ?? null,
    'processed_at' => now()->toIso8601String(),
];
```

Tambahkan `'keterangan' => $keterangan` pada:
- array `$logger->log('job_completed', ...)`
- array `Log::info('OCR processed successfully', ...)`

---

## 5. Desain Model, Request, Seeder

### `app/Models/DocumentType.php`
- `$fillable` tambah: `keterangan_regex`, `keterangan_label`.

### `app/Models/ScanLog.php`
- `$fillable` tambah: `keterangan`.

### `app/Http/Requests/StoreDocumentTypeRequest.php` & `UpdateDocumentTypeRequest.php`
Tambah aturan validasi:
```php
'keterangan_regex' => ['nullable', 'string', 'max:255'],
'keterangan_label' => ['nullable', 'string', 'max:255'],
```

### `database/seeders/DocumentTypeSeeder.php`
Pada SLIP PEMBUKUAN AP tambah:
```php
'keterangan_regex' => '/Keterangan\s*:\s*(.+)/i',
'keterangan_label' => 'keterangan',
```

---

## 6. Desain Views

### `document-type/create.blade.php` & `edit.blade.php`
Tambah field di bawah Number Label:

| Field | name | Default |
|-------|------|---------|
| Keterangan Regex | `keterangan_regex` | `/Keterangan\s*:\s*(.+)/i` (create) / nilai DB (edit) |
| Keterangan Label | `keterangan_label` | `keterangan` (create) / nilai DB (edit) |

```blade
<div class="col-md-6">
    <label class="form-label fw-bold">Keterangan Regex</label>
    <input type="text" name="keterangan_regex" class="form-control @error('keterangan_regex') is-invalid @enderror"
           value="{{ old('keterangan_regex', '/Keterangan\s*:\s*(.+)/i') }}" maxlength="255">
    @error('keterangan_regex') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="col-md-6">
    <label class="form-label fw-bold">Keterangan Label</label>
    <input type="text" name="keterangan_label" class="form-control @error('keterangan_label') is-invalid @enderror"
           value="{{ old('keterangan_label', 'keterangan') }}" maxlength="255">
    @error('keterangan_label') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
```

### `document-type/index.blade.php`
Tambah kolom **Keterangan Regex** (setelah Number Label) menampilkan `<code>{{ $dt->keterangan_regex ?? '-' }}</code>`.

### `log-file/index.blade.php`
Tambah kolom **Keterangan** setelah kolom Vendor, dengan `title` penuh + `text-truncate` agar rapi.

### `app/Exports/ScanLogsExport.php`
- `headings()`: tambah `'Keterangan'` setelah `'Vendor'`.
- `map()`: tambah `$log->keterangan ?? '-',` setelah `$log->vendor_name ?? '-',`.

### `app/Http/Controllers/Dokter/LogFileController.php`
`applyFilters()` pencarian tambah:
```php
->orWhere('keterangan', 'like', "%{$search}%")
```

### `app/Services/OcrSearchService.php` (opsional, konsisten)
Di `listResults()` tambah `'keterangan' => $data['keterangan'] ?? null`, dan helper
`resolveKeteranganLabel()` (mirror `resolveNumberLabel()`).

---

## 7. Struktur File Baru / Berubah

```
baru:
database/migrations/
├── 2026_08_02_000002_add_keterangan_config_to_document_types_table.php
└── 2026_08_02_000003_add_keterangan_to_scan_logs_table.php

berubah:
app/Services/DocumentTypeProcessor.php   (implementasi extractKeterangan + cleanKeterangan)
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
| 1 | Migration `document_types` (+ `keterangan_regex`, `keterangan_label`) | - |
| 2 | Migration `scan_logs` (+ `keterangan`) | - |
| 3 | `DocumentTypeProcessor`: implementasi `extractKeterangan()` + `cleanKeterangan()` | - |
| 4 | `ProcessScanFile`: panggil ekstraksi + tambah key di ocrData/log | Step 3 |
| 5 | Model `DocumentType` & `ScanLog` ($fillable) | Step 1, 2 |
| 6 | Requests validasi | Step 1 |
| 7 | Seeder | Step 1 |
| 8 | Views document-type (create/edit/index) | Step 1 |
| 9 | View log-file + export + filter controller | Step 2 |
| 10 | `php artisan migrate` | Step 1, 2 |
| 11 | Verifikasi: regex diuji terhadap OCR file; halaman log menampilkan keterangan | Semua |

---

## 9. Verifikasi

1. `php artisan migrate` sukses (2 migrasi baru).
2. Unit-test kecil regex default terhadap teks OCR asli:
   `preg_match('/Keterangan\s*:\s*(.+)/i', $text)` menghasilkan
   `P11646-JASA PENANBANCAN JAN'26 0400260006/298202`.
3. Jalankan pipeline OCR → hasil JSON memuat key `keterangan`; `scan_logs.keterangan` terisi.
4. Halaman **Jenis Dokumen**: field Keterangan Regex & Label tampil & tersimpan.
5. Halaman **Log File** & **Export Excel**: kolom Keterangan tampil.
6. `php artisan route:list` & config cache bersih → aplikasi boot normal.

---

## 10. Catatan Penting / Open Questions

- **Pembersihan data:** keterangan mengandung `/` yang bermakna → jangan pakai
  `cleanOcrNoise()` (yang menghapus `/ \ ,`). Dipakai `cleanKeterangan()` baru.
- **Backward compatibility:** file OCR JSON lama tidak memuat key `keterangan` —
  hanya file baru yang diproses sesudah implementasi yang akan memilikinya.
- **Label dinamis:** `keterangan_label` bisa diubah; key JSON mengikuti label.
  `listResults()` memakai default `keterangan` (fitur tersebut belum dipakai controller).
- **Regex bisa berubah per jenis dokumen:** setiap dokumen dapat punya pola
  keterangan berbeda (mis. `Deskripsi`, `Uraian`, dll.) dengan mengubah
  `keterangan_regex` di menu Jenis Dokumen.
- **Hapus stub lama:** `extractKeterangan()` sebelumnya adalah salinan
  `extractDocumentNumber()` dan tidak pernah dipanggil → diimplementasikan ulang.
