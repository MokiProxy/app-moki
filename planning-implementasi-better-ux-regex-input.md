# Planning Implementasi: Better UX Regex Input - CRUD Jenis Dokumen

## 1. Masalah Saat Ini

### Kondisi Eksisting
- User menginput regex secara manual dalam format `/pattern/flags` langsung ke `<input type="text">`
- Tidak ada validasi apakah regex yang diinput valid secara sintaks PHP
- Tidak ada live preview atau testing capability
- User harus memahami regex programming untuk mengisi field-field ini
- Format regex yang digunakan: `/pattern/flags` (contoh: `/No\s+Inv\s*\n?\s*:\s*(.+)/i`)

### Field Regex yang Ada
| Field | Fungsi | Contoh Default |
|-------|--------|----------------|
| `header_regex` | Primary identifier untuk deteksi jenis dokumen | `/^PEMBAYARAN$/mi` |
| `number_regex` | Ekstraksi nomor dokumen | `/No\s+Inv\s*\n?\s*:\s*(.+)/i` |
| `keterangan_regex` | Ekstraksi data keterangan | `/Keterangan\s*:\s*(.+)/i` |
| `uraian_regex` | Ekstraksi data uraian (multi-baris) | `/URAIAN\s*\n(.+?)\n\s*TOTAL/si` |
| `tanggal_regex` | Ekstraksi data tanggal | `/Tgl\s*\n?\s*:\s*(.+)/i` |

### File yang Terdampak
- `resources/views/dokter/document-type/create.blade.php`
- `resources/views/dokter/document-type/edit.blade.php`
- `app/Http/Requests/StoreDocumentTypeRequest.php`
- `app/Http/Requests/UpdateDocumentTypeRequest.php`
- `app/Http/Controllers/Dokter/DocumentTypeController.php` (opsional)

---

## 2. Solusi yang Diusulkan

### Konsep Utama
Membuat **"Visual Regex Builder"** yang mengubah input manual regex menjadi form berbasis pilihan (dropdown, toggle, input terstruktur) yang tetap menghasilkan output regex.

### Prinsip Desain
1. **Backward Compatible**: Output tetap berupa string regex, tidak mengubah struktur database atau backend processing
2. **User-Friendly**: User tidak perlu memahami regex untuk mengisi form
3. **Expert Mode**: Tetap menyediakan opsi manual untuk user yang mengerti regex
4. **Live Preview**: Menampilkan preview hasil regex terhadap sample text secara real-time
5. **Validation**: Melakukan validasi syntax regex sebelum disimpan

---

## 3. Arsitektur Solusi

### 3.1 Komponen: Visual Regex Builder

#### Untuk Setiap Field Regex:
```
┌─────────────────────────────────────────────────────────────────┐
│ [Header/Number/Keterangan/Uraian/Tanggal] Regex Builder         │
├─────────────────────────────────────────────────────────────────┤
│ Mode: [○ Builder (Recommended)] [○ Manual Expert]               │
├─────────────────────────────────────────────────────────────────┤
│ Builder Mode:                                                   │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ Template Pola:  [Dropdown: Kata kunci + nilai]              │ │
│ │                  [Dropdown: Nomor dokumen]                  │ │
│ │                  [Dropdown: Tanggal]                        │ │
│ │                  [Dropdown: Multi-baris antara ...]          │ │
│ │                  [Dropdown: Custom...]                      │ │
│ ├─────────────────────────────────────────────────────────────┤ │
│ │ Kata Kunci:     [Input: "Tgl", "No Inv", dll]              │ │
│ │ Pemisah:        [Dropdown: ":" / "=" / spasi / newline]    │ │
│ │ Ambil Sampai:   [Dropdown: newline / spasi / karakter khusus]│ │
│ │ Flag:           [☑] Case-insensitive  [☑] Multi-line       │ │
│ │                 [☐] Single-line  [☐] Unicode               │ │
│ └─────────────────────────────────────────────────────────────┘ │
├─────────────────────────────────────────────────────────────────┤
│ Preview:                                                        │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ Regex: /Tgl\s*:\s*(.+)/i                                   │ │
│ │ [Sample text area untuk testing]                            │ │
│ │ Hasil: "12/03/2026" (match pada group 1)                    │ │
│ └─────────────────────────────────────────────────────────────┘ │
├─────────────────────────────────────────────────────────────────┤
│ Manual Mode:                                                    │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ [input text: /Tgl\s*:\s*(.+)/i]                            │ │
│ │ ⚠️ Pastikan regex valid. Gunakan /pattern/flags             │ │
│ └─────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

### 3.2 Preset Template Regex

Menyediakan preset untuk pola-pola umum yang sering digunakan:

| Preset | Pattern | Flags | Contoh Penggunaan |
|--------|---------|-------|-------------------|
| **Kata kunci + nilai** | `{keyword}\s*[:=]\s*(.+)` | `i` | Mencari "Tgl: 12/03/2026" |
| **Nomor Dokumen** | `(?:No|Nomor)\s*[:=]?\s*(.+)` | `i` | Mencari "No: INV-001" |
| **Tanggal** | `(\d{1,2}[/\-]\d{1,2}[/\-]\d{2,4})` | `i` | Mencari "12/03/2026" |
| **Multi-baris** | `{start}\s*\n(.+?)\n\s*{end}` | `si` | Uraian antara 2 kata kunci |
| **Teks antara** | `(?<={start}).*?(?={end})` | `s` | Teks antara 2 anchor |
| **Currency/Amount** | `Rp\s*([\d.,]+)` | `i` | Mencari "Rp 1.000.000" |

### 3.3 File yang Perlu Dibuat/Diubah

#### A. Frontend (JavaScript)
**Baru:**
- `public/js/regex-builder.js` - Core logic Visual Regex Builder

**Diubah:**
- `resources/views/dokter/document-type/create.blade.php` - Integrasi builder
- `resources/views/dokter/document-type/edit.blade.php` - Integrasi builder

#### B. Backend (PHP) - Minimal Changes
**Diubah:**
- `app/Http/Requests/StoreDocumentTypeRequest.php` - Tambah validasi regex syntax
- `app/Http/Requests/UpdateDocumentTypeRequest.php` - Tambah validasi regex syntax

**Baru (Opsional):**
- `app/Http/Controllers/Dokter/DocumentTypeController.php` - Tambah method `validateRegex()` untuk AJAX validation

#### C. Database
**TIDAK ADA PERUBAHAN** - Semua regex tetap disimpan sebagai string di kolom yang sama.

---

## 4. Detail Implementasi

### 4.1 JavaScript: `public/js/regex-builder.js`

```
Struktur Module:
├── RegexBuilder (class utama)
│   ├── constructor(fieldId, options)
│   ├── initUI()                    - Render form builder UI
│   ├── loadPresets()               - Load preset templates
│   ├── buildRegex()                - Generate regex dari form inputs
│   ├── parseRegex()                - Parse regex existing ke form inputs
│   ├── validateRegex(pattern)      - Validasi syntax regex
│   ├── testRegex(pattern, text)    - Test regex terhadap sample text
│   ├── toggleMode(builder/manual)  - Toggle antara builder dan manual mode
│   └── getOutput()                 - Return regex string untuk form submission
│
├── RegexPresets
│   ├── getKeywordValuePattern()
│   ├── getNumberPattern()
│   ├── getDatePattern()
│   ├── getMultiLinePattern()
│   ├── getBetweenPattern()
│   └── getCurrencyPattern()
│
└── RegexValidator
    ├── validate(pattern)           - Validasi syntax PHP regex
    └── getErrorMessage(error)      - Format error message
```

### 4.2 Form UI Changes

#### Layout Setiap Regex Field (Builder Mode):
```
┌────────────────────────────────────────────────────────────────┐
│ Header Regex                                    [Builder|Manual]
├────────────────────────────────────────────────────────────────┤
│ Pola: [Dropdown: "Kata kunci + nilai ▼"]                       │
│                                                                │
│ Kata Kunci: [input: "PEMBAYARAN"]                              │
│ Pemisah:    [Dropdown: "Tidak ada (exact match) ▼"]            │
│ Flag:       [☑] Case-insensitive  [☑] Multi-line               │
│             [☐] Single-line  [☐] Unicode                       │
├────────────────────────────────────────────────────────────────┤
│ Generated Regex:                                               │
│ ┌────────────────────────────────────────────────────────────┐ │
│ │ /^PEMBAYARAN$/mi                               [Copy] [?] │ │
│ └────────────────────────────────────────────────────────────┘ │
├────────────────────────────────────────────────────────────────┤
│ Test Regex:                                                    │
│ ┌────────────────────────────────────────────────────────────┐ │
│ │ [Sample text area]                                         │ │
│ │                                                            │ │
│ │ Result: ✅ Match! Group 1: "PEMBAYARAN"                    │ │
│ └────────────────────────────────────────────────────────────┘ │
└────────────────────────────────────────────────────────────────┘
```

#### Layout Setiap Regex Field (Manual Mode):
```
┌────────────────────────────────────────────────────────────────┐
│ Header Regex                                    [Builder|Manual]
├────────────────────────────────────────────────────────────────┤
│ ┌────────────────────────────────────────────────────────────┐ │
│ │ /^PEMBAYARAN$/mi                                          │ │
│ └────────────────────────────────────────────────────────────┘ │
│ ℹ️ Format: /pattern/flags (contoh: /^PEMBAYARAN$/mi)           │
│ ⚠️ Validasi: ✅ Regex valid                                    │
├────────────────────────────────────────────────────────────────┤
│ Test Regex:                                                    │
│ [Same as builder mode]                                         │
└────────────────────────────────────────────────────────────────┘
```

### 4.3 Backend Validation

#### Custom Validation Rule: `ValidRegex`
```php
// app/Rules/ValidRegex.php (baru)
namespace App\Rules;

class ValidRegex implements Rule
{
    public function validate($attribute, $value, $fail)
    {
        if ($value === null || $value === '') {
            return true; // nullable
        }

        $result = @preg_match($value, '');
        if ($result === false) {
            $fail('The :attribute must be a valid regular expression.');
        }
    }
}
```

#### Update Form Request:
```php
// StoreDocumentTypeRequest.php
'header_regex' => ['nullable', 'string', 'max:255', new ValidRegex],
'number_regex' => ['nullable', 'string', 'max:255', new ValidRegex],
'keterangan_regex' => ['nullable', 'string', 'max:255', new ValidRegex],
'uraian_regex' => ['nullable', 'string', 'max:255', new ValidRegex],
'tanggal_regex' => ['nullable', 'string', 'max:255', new ValidRegex],
```

### 4.4 AJAX Validation Endpoint (Opsional)

```php
// routes/dokter.php
Route::post('/document-types/validate-regex', [DocumentTypeController::class, 'validateRegex']);

// DocumentTypeController.php
public function validateRegex(Request $request)
{
    $pattern = $request->input('pattern');
    $result = @preg_match($pattern, $request->input('test_text', ''));

    return response()->json([
        'valid' => $result !== false,
        'matches' => $result === 1 ? preg_capture_groups($pattern, $request->input('test_text', '')) : null,
    ]);
}
```

---

## 5. Alur Kerja User

### Skenario 1: User Baru (Tidak Mengerti Regex)
1. User membuka form Create/Edit Jenis Dokumen
2. Pada field regex, user memilih mode **"Builder (Recommended)"**
3. User memilih preset dari dropdown (misal: "Kata kunci + nilai")
4. User mengisi parameter:
   - Kata Kunci: "Tgl"
   - Pemisah: ":"
   - Flag: Case-insensitive ✓
5. User melihat generated regex: `/Tgl\s*:\s*(.+)/i`
6. User menguji dengan sample text: "Tgl: 12/03/2026"
7. User melihat hasil: Match! Group 1: "12/03/2026"
8. User klik Simpan

### Skenario 2: User Ahli (Mengerti Regex)
1. User membuka form Create/Edit Jenis Dokumen
2. Pada field regex, user memilih mode **"Manual Expert"**
3. User mengetik regex langsung: `/Tgl\s*:\s*(.+)/i`
4. Sistem menampilkan validasi: "✅ Regex valid"
5. User menguji dengan sample text (opsional)
6. User klik Simpan

---

## 6. Preset Templates Detail

### 6.1 Kata Kunci + Nilai (Keyword-Value)
**Kegunaan**: Mencari nilai setelah kata kunci tertentu
**Parameter**:
- `keyword`: Kata kunci yang dicari (misal: "Tgl", "No", "Keterangan")
- `separator`: Pemisah (":", "=", spasi, atau custom)
- `capture_after`: Apakah mengambil nilai setelah pemisah

**Generated Regex**:
```
Pattern: /{keyword}\s*{separator}\s*(.+)/i
Contoh:  /Tgl\s*:\s*(.+)/i
```

### 6.2 Nomor Dokumen
**Kegunaan**: Mencari nomor dokumen dengan format umum
**Parameter**:
- `prefix`: Prefix nomor (misal: "No", "Nomor", "DOC")
- `separator`: Pemisah setelah prefix
- `number_format`: Format nomor (angka saja, alphanumeric, custom)

**Generated Regex**:
```
Pattern: /(?:{prefix})\s*{separator}?\s*({number_format})/i
Contoh:  /(?:No|Nomor)\s*:\s*([A-Z0-9\-]+)/i
```

### 6.3 Tanggal
**Kegunaan**: Mencari format tanggal umum
**Parameter**:
- `separator`: Pemisah tanggal (/ atau - atau .)
- `format`: Urutan tanggal (DD/MM/YYYY, MM/DD/YYYY, YYYY-MM-DD)

**Generated Regex**:
```
Pattern: /(\d{1,2}{separator}\d{1,2}{separator}\d{2,4})/i
Contoh:  /(\d{1,2}\/\d{1,2}\/\d{4})/i
```

### 6.4 Multi-baris (Between Keywords)
**Kegunaan**: Mencari teks antara dua kata kunci
**Parameter**:
- `start_keyword`: Kata kunci awal
- `end_keyword`: Kata kunci akhir
- `include_newlines`: Apakah span beberapa baris

**Generated Regex**:
```
Pattern: /{start_keyword}\s*\n(.+?)\n\s*{end_keyword}/si
Contoh:  /URAIAN\s*\n(.+?)\n\s*TOTAL/si
```

### 6.5 Custom Pattern
**Kegunaan**: User dapat membangun regex dari komponen-komponen
**Parameter**:
- `components[]`: Array komponen regex
  - `type`: "literal", "digit", "word", "space", "any", "group"
  - `value`: Nilai untuk literal/group
  - `quantifier`: "?", "*", "+", "{n,m}"

---

## 7. Estimasi effort

### Phase 1: Core Implementation (3-4 hari)
- [ ] Buat `public/js/regex-builder.js` dengan class RegexBuilder
- [ ] Implement 4 preset templates (keyword-value, number, date, multi-line)
- [ ] Implement builder UI untuk setiap field regex
- [ ] Implement mode toggle (builder/manual)
- [ ] Implement live regex preview

### Phase 2: Validation & Testing (1-2 hari)
- [ ] Buat `app/Rules/ValidRegex.php`
- [ ] Update `StoreDocumentTypeRequest` dan `UpdateDocumentTypeRequest`
- [ ] Implement regex syntax validation di frontend (JavaScript)
- [ ] Implement regex testing dengan sample text

### Phase 3: Integration (1-2 hari)
- [ ] Update `create.blade.php` dengan regex builder component
- [ ] Update `edit.blade.php` dengan regex builder component
- [ ] Pastikan backward compatibility (regex tetap tersimpan sebagai string)
- [ ] Testing CRUD lengkap

### Phase 4: Enhancement (Opsional, 1-2 hari)
- [ ] AJAX validation endpoint
- [ ] Additional presets (currency, email, phone)
- [ ] Save/load custom templates
- [ ] Batch regex testing dengan multiple sample texts

**Total Estimasi: 5-8 hari kerja**

---

## 8. Testing Plan

### 8.1 Unit Tests
- Test validasi regex syntax (valid dan invalid)
- Test regex builder generate output yang benar
- Test parsing regex existing ke form inputs

### 8.2 Integration Tests
- Test CRUD jenis dokumen dengan regex builder
- Test regex execution di DocumentTypeProcessor
- Test backward compatibility dengan data existing

### 8.3 Manual Testing
- Test semua preset templates
- Test mode builder dan manual
- Test live preview dengan sample text berbagai format
- Test responsive design (mobile/tablet/desktop)

---

## 9. Risks & Mitigations

| Risiko | Dampak | Mitigasi |
|--------|--------|----------|
| Regex generated tidak sama dengan manual | Data existing tidak cocok | Unit testing komparasi regex output |
| Browser compatibility | Feature tidak jalan di browser lama | Progressive enhancement, fallback ke manual mode |
| Performance | Form menjadi lambat | Debounce preview, lazy loading |
| User confusion | User tidak paham cara pakai | Tooltip, helper text, contoh preset |

---

## 10. Kesimpulan

Implementasi ini akan mengubah experience user dari:
> "Saya harus mengetik regex manual yang kompleks"

Menjadi:
> "Saya tinggal pilih preset, isi parameter, dan preview hasilnya"

**Yang TIDAK Berubah:**
- Struktur database (kolom tetap string)
- Backend processing (DocumentTypeProcessor tetap menggunakan preg_match)
- Format penyimpanan regex (tetap `/pattern/flags`)
- Semua existing functionality

**Yang BERUBAH:**
- User experience menjadi lebih user-friendly
- Adanya validasi regex syntax sebelum disimpan
- Adanya live preview dan testing capability
- User tidak perlu memahami regex programming

---

*Document created: 2026-08-04*
*Author: opencode*
