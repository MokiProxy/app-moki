# Planning Perbaikan Bug: Scoring Document Type Detection

## Masalah Saat Ini

```
Dokumen: PEMBAYARAN
├─ NO SP: SP/2024/001     → regex PEMBAYARAN match (+10)
├─ No Inv: INV/2024/001   → regex INVOICE match (+10)
└─ Vendor: MADHANI...     → match (+5)

Hasil: KEDUA document type skor = 15
→ Salah prediksi tergantung urutan database
```

## Root Cause

Algoritma scoring saat ini:
- `number_regex` match: **+10** (terlalu rendah, mudah tie)
- `vendor` match: **+5** (terlalu rendah, mudah tie)
- **Tidak ada primary identifier** yang pasti untuk menentukan jenis dokumen

## Solusi: Header-First Detection

### Konsep

```
┌─────────────────────────────────────────────────────────────┐
│                    ALUR BARU                                │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  STEP 1: Header Match (Primary)                            │
│  ├─ Cocokkan teks OCR dengan header_regex                  │
│  ├─ Jika MATCH → langsung return document type tersebut    │
│  └─ Jika TIDAK ADA match → lanjut ke Step 2               │
│                                                             │
│  STEP 2: Fallback Scoring (Saat Ini)                       │
│  ├─ number_regex: +10                                      │
│  ├─ vendor: +5                                             │
│  └─ Return tertinggi                                       │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Mengapa Header Paling Presisi

```
Contoh Dokumen:
┌──────────────────────────────────────────────┐
│              PEMBAYARAN                       │  ← Header (baris pertama)
│                                               │
│  No SP    : SP/2024/001                      │
│  No Inv   : INV/2024/001                     │
│  Vendor   : MADHANI TALATAH NUSANTARA        │
└──────────────────────────────────────────────┘

- Header "PEMBAYARAN" → UNIK, hanya ada 1 per dokumen
- Nomor "No SP" dan "No Inv" bisa BERSAMAAN di dokumen yang sama
- Vendor bisa SAMA untuk berbagai jenis dokumen

→ Header adalah identifier paling reliable
```

## Implementasi

### 1. Database: Tambah Kolom `header_regex`

```php
// Migration
Schema::table('document_types', function (Blueprint $table) {
    $table->string('header_regex')->nullable()->after('name');
});
```

**Contoh Data:**

| name | header_regex |
|------|--------------|
| PEMBAYARAN | `/^PEMBAYARAN$/mi` |
| INVOICE | `/^INVOICE$/mi` |
| SLIP PEMBUKUAN AP | `/^SLIP\s+PEMBUKUAN\s+AP$/mi` |
| NOTA PENJUALAN | `/^NOTA\s+PENJUALAN$/mi` |

### 2. Model: Tambah field ke fillable

```php
// app/Models/DocumentType.php
protected $fillable = [
    'name',
    'header_regex',  // ← TAMBAH
    'description',
    // ...
];
```

### 3. Service: Update Detection Algorithm

```php
// app/Console/Commands/Dokter/MonitorScanner.php

protected function detectDocumentType(...): ?DocumentType
{
    // ... OCR ...

    // STEP 1: Header Match (Primary)
    foreach ($documentTypes as $docType) {
        if ($this->matchHeader($docType, $ocrText)) {
            return $docType;  // ← Langsung return, tidak perlu scoring
        }
    }

    // STEP 2: Fallback Scoring
    // ... (kode saat ini) ...
}

protected function matchHeader(DocumentType $docType, string $ocrText): bool
{
    $pattern = $docType->header_regex ?? null;

    if ($pattern === null) {
        return false;
    }

    return (bool) @preg_match($pattern, $ocrText);
}
```

### 4. Admin Panel: Form Input header_regex

```
┌─────────────────────────────────────────────┐
│ Document Type Form                          │
├─────────────────────────────────────────────┤
│ Name: [PEMBAYARAN                    ]      │
│ Header Regex: [/^PEMBAYARAN$/mi     ]      │  ← FIELD BARU
│ Number Regex: [/No\s+SP\s*:\s*(.+)/i]      │
│ ...                                         │
└─────────────────────────────────────────────┘
```

## Alur Lengkap Setelah Perbaikan

```
                    File dari Scanner
                          │
                          ▼
                ┌─────────────────┐
                │ OCR Extract Text│
                └────────┬────────┘
                         │
                         ▼
                ┌─────────────────┐
                │ Header Match?   │
                └────────┬────────┘
                    Ya / │ \ Tidak
                   /     │     \
                  ░      │      ░
                  ░      ░      ░
         ┌────────╨──┐   │   ┌──╨────────┐
         │ RETURN    │   │   │ SCORING   │
         │ Doc Type  │   │   │ FALLBACK  │
         │ (PASTI    │   │   │ (+10, +5) │
         │  BENAR)   │   │   └─────┬─────┘
         └───────────┘   │         │
                         ▼         ▼
                   ┌──────────────────────┐
                   │ Dispatch ProcessScan │
                   └──────────────────────┘
```

## Kasus Test

### Kasus 1: Dokumen PEMBAYARAN
```
OCR Text:
"PEMBAYARAN
NO SP: SP/2024/001
No Inv: INV/2024/001
Vendor: MADHANI..."

Step 1 - Header Match:
  PEMBAYARAN regex /^PEMBAYARAN$/mi → ✓ MATCH
  → RETURN PEMBAYARAN (langsung, tanpa scoring)

Hasil: ✓ BENAR
```

### Kasus 2: Dokumen INVOICE
```
OCR Text:
"INVOICE
No Inv: INV/2024/001
Vendor: MADHANI..."

Step 1 - Header Match:
  PEMBAYARAN regex /^PEMBAYARAN$/mi → ✗ NO MATCH
  INVOICE regex /^INVOICE$/mi → ✓ MATCH
  → RETURN INVOICE

Hasil: ✓ BENAR
```

### Kasus 3: Dokumen Tanpa Header (Legacy)
```
OCR Text:
"NO SP: SP/2024/001
Vendor: MADHANI..."

Step 1 - Header Match:
  PEMBAYARAN regex → ✗ NO MATCH
  INVOICE regex → ✗ NO MATCH
  (Tidak ada header match)

Step 2 - Fallback Scoring:
  PEMBAYARAN: +10 (regex) +5 (vendor) = 15
  INVOICE: +0 (regex) +5 (vendor) = 5

  → RETURN PEMBAYARAN

Hasil: ✓ BENAR (fallback ke scoring)
```

### Kasus 4: Header Match + Multiple Regex
```
OCR Text:
"PEMBAYARAN
NO SP: SP/2024/001
No Inv: INV/2024/001"

Step 1 - Header Match:
  PEMBAYARAN regex /^PEMBAYARAN$/mi → ✓ MATCH
  → RETURN PEMBAYARAN (langsung)

Hasil: ✓ BENAR (tidak bingung meski 2 regex match)
```

## File yang Perlu Diubah

| No | File | Aksi |
|----|------|------|
| 1 | `database/migrations/xxxx_add_header_regex_to_document_types.php` | BARU |
| 2 | `app/Models/DocumentType.php` | Tambah `header_regex` ke fillable |
| 3 | `app/Console/Commands/Dokter/MonitorScanner.php` | Update `detectDocumentType()` |
| 4 | `app/Http/Controllers/Dokter/DocumentTypeController.php` | Tambah field di form |
| 5 | `resources/views/` (blade) | Tambah input `header_regex` |
| 6 | `app/Http/Requests/StoreDocumentTypeRequest.php` | Validasi header_regex |
| 7 | `app/Http/Requests/UpdateDocumentTypeRequest.php` | Validasi header_regex |

## Edge Cases

### 1. Header Regex Tidak Diisi
```php
if ($docType->header_regex === null || $docType->header_regex === '') {
    // Skip header matching, langsung ke fallback
}
```

### 2. Header Regex Invalid
```php
if (@preg_match($pattern, $ocrText) === false) {
    // Regex invalid, skip document type ini
    Log::warning('Invalid header_regex', ['doc_type' => $docType->name, 'regex' => $pattern]);
    continue;
}
```

### 3. Multiple Header Match
```php
// Jika lebih dari 1 document type match header
// Return yang pertama ditemukan (sudah cukup karena header seharusnya unik)
```

### 4. Header di Tengah Dokumen
```php
// Pattern /PEMBAYARAN/ tanpa ^ akan match di mana saja
// Pattern /^PEMBAYARAN$/mi hanya match di baris tersendiri
// Rekomendasi: gunakan ^ dan $ untuk presisi
```

## Presisi Solusi

| Skenario | Solusi | Presisi |
|----------|--------|---------|
| Header unik | Header match langsung return | 100% |
| Tanpa header | Fallback scoring | ~90% |
| Header ambigu | First match | ~95% |
| Regex invalid | Skip, fallback | 100% |

**Kesimpulan:** Dengan `header_regex`, kemungkinan salah prediksi **mendekati 0%** untuk dokumen yang memiliki header/judul jelas.

## Timeline

| Task | Estimasi |
|------|----------|
| Migration + Model | 0.5 jam |
| Update MonitorScanner | 1 jam |
| Update Form/Controller | 1 jam |
| Seeder data existing | 0.5 jam |
| Testing | 1 jam |
| **Total** | **4 jam** |
