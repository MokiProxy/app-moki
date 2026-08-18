# Planning Implementasi EQTax - Ekualisasi Restitusi Pajak

## Overview

Dokumen ini berisi rencana implementasi modul EQTax untuk proses ekualisasi SPT Pajak dan General Labouruntuk restitusi pajak. **Belum diimplementasi** - hanya planning.

---

## 1. Bug Fixes (Prioritas Tinggi)

### 1.1 SPTCoretaxController - Pesan Error Salah
**File**: `app/Http/Controllers/EQTax/SPTCoretaxController.php:29`
- **Masalah**: Pesan error berisi "Import SPT Coretax Berhasil" seharusnya "Gagal"
- **Fix**: Ganti `"Import SPT Coretax Berhasil"` → `"Import SPT Coretax Gagal"`

### 1.2 GLController - Redirect & Pesan Salah
**File**: `app/Http/Controllers/EQTax/GLController.php:30,39`
- **Masalah**: 
  - Line 30: Redirect ke `eqtax.spt.coretax.index` seharusnya `eqtax.gl.index`
  - Line 39: Redirect ke `eqtax.spt.coretax.index` seharusnya `eqtax.gl.index`
  - Line 39: Pesan "Import SPT Coretax Berhasil" seharusnya "Import GL Berhasil"
- **Fix**: Ganti route redirect dan pesan

### 1.3 Migration down() Inconsistency
**File**: `database/migrations/2026_08_14_133832_create_e_q_t_a_x_coretax_s_p_t_s_table.php`
- **Masalah**: `down()` drop `e_q_t_a_x_coretax_s_p_t_s` seharusnya `eqtax_coretax_spt`
- **Fix**: Buat migration baru untuk fix table name di down()

**File**: `database/migrations/2026_08_15_143005_create_e_q_t_a_x_g_l_s_table.php`
- **Masalah**: `down()` drop `e_q_t_a_x_g_l_s` seharusnya `eqtax_gl`
- **Fix**: Buat migration baru untuk fix table name di down()

---

## 2. Database Schema Changes

### 2.1 Tambah Kolom `entity` ke Tabel `eqtax_gl`

**Alasan**: GL memiliki multi-sheet (PPNMO, PPNHO, PPNPLTR) yang mewakili entity berbeda. Saat ini kolom `sheet` menyimpan nama sheet, tapi perlu distandarisasi.

**Migration baru**:
```php
// database/migrations/xxxx_xx_xx_add_entity_to_eqtax_gl_table.php
Schema::table('eqtax_gl', function (Blueprint $table) {
    $table->string('entity')->nullable()->after('sheet');
    $table->index('no_faktur_pajak');
    $table->index('entity');
});
```

**Mapping sheet → entity**:
| Sheet Name | Entity Code | Keterangan |
|------------|-------------|------------|
| PPNMO | TJMO | Tanjung Enim Mining Operation |
| PPNHO | SBHO | Head Office |
| PPNPLTR | PLTR | Pulau Laut |

### 2.2 Tambah Kolom `entity` ke Tabel `eqtax_coretax_spt`

**Alasan**: SPT dari Coretax juga bisa memiliki data per entity (lihat kolom SBHO/TJMO/PLTR di Excel). Perlu disimpan untuk referensi.

**Migration baru**:
```php
Schema::table('eqtax_coretax_spt', function (Blueprint $table) {
    $table->string('entity')->nullable()->after('tahun');
    $table->index('no_faktur_pajak');
});
```

### 2.3 Tabel `eqtax_equalization_results` (Opsional - Hasil Ekualisasi)

**Alasan**: Menyimpan hasil ekualisasi agar tidak perlu hitung ulang setiap kali.

```php
Schema::create('eqtax_equalization_results', function (Blueprint $table) {
    $table->id();
    $table->string('period')->nullable(); // periode ekualisasi (contoh: "2026-02")
    $table->string('entity')->nullable(); // SBHO, TJMO, PLTR
    $table->string('no_faktur_pajak')->nullable();
    $table->string('nama_penjual')->nullable();
    $table->bigInteger('dpp_spt')->nullable();
    $table->float('dpp_gl')->nullable();
    $table->bigInteger('ppn_spt')->nullable();
    $table->float('ppn_gl')->nullable();
    $table->float('selisih_ppn')->nullable();
    $table->string('status')->nullable(); // MATCH, SPT_ONLY, GL_ONLY, SELISIH
    $table->text('keterangan')->nullable();
    $table->timestamps();
});
```

---

## 3. Import Improvements

### 3.1 SPT Import - Handle Multi-Entity Columns

**File**: `app/Imports/EQTaxImport.php`

**Masalah**: Kolom SBHO (21), TJMO (22), PLTR (23) di SPT Excel adalah formula VLOOKUP yang tidak tersimpan saat import. Aplikasi perlu menghitung sendiri pencocokan entity.

**Rencana**: 
- Import tetap seperti sekarang (19 kolom utama)
- Kolom entity (SBHO/TJMO/PLTR) dihitung saat proses ekualisasi, bukan saat import
- Tambah validasi: pastikan periode SPT sesuai dengan periode GL

### 3.2 GL Import - Simpan Entity dari Sheet Name

**File**: `app/Imports/PPNSingleSheetImport.php`

**Perubahan**: Tambah field `entity` yang di-mapping dari `sheetName`:
```php
$entityMap = [
    'PPNMO' => 'TJMO',
    'PPNHO' => 'SBHO',
    'PPNPLTR' => 'PLTR',
];

$this->parent->result[] = [
    'sheet' => $this->sheetName,
    'entity' => $entityMap[$this->sheetName] ?? $this->sheetName,
    // ... field lainnya
];
```

### 3.3 GL Import - Handle Format Angka Indonesia

**Masalah**: Angka di GL menggunakan format Indonesia (titik sebagai pemisah ribuan, koma sebagai desimal): `57.889.181.825,45`

**Fix**: Tambah helper function untuk convert:
```php
function parseIndonesianNumber(string $value): float
{
    $cleaned = str_replace('.', '', $value);
    $cleaned = str_replace(',', '.', $cleaned);
    return (float) $cleaned;
}
```

---

## 4. Equalization Logic (Core Feature)

### 4.1 EqualizationController - Perbaikan Query

**File**: `app/Http/Controllers/EQTax/EqualizationController.php`

**Perubahan utama**:
1. Tambah filter periode (masa_pajak + tahun)
2. Tambah filter entity (opsional)
3. Pisahkan aggregate GL per entity
4. Hitung selisih per entity dan total

**Query baru**:
```sql
WITH gl_agg AS (
    SELECT
        TRIM(no_faktur_pajak) AS no_faktur_pajak,
        entity,
        SUM(dpp) AS dpp_gl,
        SUM(ppn) AS ppn_gl,
        COUNT(*) AS jumlah_item
    FROM eqtax_gl
    WHERE :period IS NULL OR jurnal_date LIKE :period || '%'
    GROUP BY TRIM(no_faktur_pajak), entity
),
gl_total AS (
    SELECT
        no_faktur_pajak,
        SUM(dpp_gl) AS dpp_gl_total,
        SUM(ppn_gl) AS ppn_gl_total
    FROM gl_agg
    GROUP BY no_faktur_pajak
),
spt_norm AS (
    SELECT
        TRIM(no_faktur_pajak) AS no_faktur_pajak,
        nama_penjual,
        dpp AS dpp_spt,
        ppn AS ppn_spt,
        masa_pajak,
        tahun
    FROM eqtax_coretax_spt
    WHERE :period IS NULL OR (masa_pajak = :month AND tahun = :year)
)
SELECT
    COALESCE(spt.no_faktur_pajak, gl.no_faktur_pajak) AS no_faktur_pajak,
    spt.nama_penjual,
    spt.masa_pajak,
    spt.tahun,
    spt.dpp_spt,
    gl.dpp_gl_total AS dpp_gl,
    spt.ppn_spt,
    gl.ppn_gl_total AS ppn_gl,
    COALESCE(spt.ppn_spt, 0) - COALESCE(gl.ppn_gl_total, 0) AS selisih_ppn,
    CASE
        WHEN spt.no_faktur_pajak IS NOT NULL AND gl.no_faktur_pajak IS NOT NULL THEN 'MATCH'
        WHEN spt.no_faktur_pajak IS NOT NULL AND gl.no_faktur_pajak IS NULL THEN 'SPT_ONLY'
        WHEN spt.no_faktur_pajak IS NULL AND gl.no_faktur_pajak IS NOT NULL THEN 'GL_ONLY'
    END AS status
FROM spt_norm AS spt
FULL OUTER JOIN gl_total AS gl ON spt.no_faktur_pajak = gl.no_faktur_pajak
ORDER BY selisih_ppn DESC
```

### 4.2 Method `equalization()` - Proses Ekualisasi & Simpan

Implementasi method `equalization()` yang saat ini kosong:

```php
public function equalization(Request $request)
{
    $request->validate([
        'masa_pajak' => 'required|string',
        'tahun' => 'required|string',
    ]);

    // 1. Jalankan query ekualisasi
    // 2. Simpan hasil ke tabel eqtax_equalization_results (jika ada)
    // 3. Return ke view dengan data hasil ekualisasi
}
```

---

## 5. Route Changes

**File**: `routes/routers/eqtax.php`

**Penambahan route**:
```php
Route::prefix('equalization')->name('equalization.')->group(function () {
    Route::get("/", [EqualizationController::class, "index"])->name("index");
    Route::post("/process", [EqualizationController::class, "equalization"])->name("process");
    Route::get("/export", [EqualizationController::class, "export"])->name("export");
});
```

---

## 6. View Improvements

### 6.1 SPT Coretax Index - Tambah Tabel Data

**File**: `resources/views/eqtax/spt/coretax/index.blade.php`

**Perubahan**: 
- Tambah tabel untuk menampilkan data SPT yang sudah di-import
- Tambah info periode (masa pajak & tahun)
- Tambah tombol "Hapus Data" untuk reset

### 6.2 GL Index - Tambah Tabel Data

**File**: `resources/views/eqtax/gl/index.blade.php`

**Perubahan**:
- Tambah tabel untuk menampilkan data GL yang sudah di-import
- Group by entity (PPNMO, PPNHO, PPNPLTR)
- Tambah info total DPP dan PPN per entity

### 6.3 Equalization Index - UI Lengkap

**File**: `resources/views/eqtax/equalization/index.blade.php`

**Perubahan**:
- Tambah filter: periode (masa pajak + tahun), entity
- Tambah tombol "Proses Ekualisasi"
- Tambah summary cards: Total SPT, Total GL, Total Selisih, Jumlah Match/SPT Only/GL Only
- Tambah warna pada tabel: hijau untuk match, kuning untuk selisih, merah untuk GL Only
- Tambah tombol "Export to Excel"
- Fix: Hapus JavaScript upload file yang tidak perlu

---

## 7. New Features

### 7.1 Filter Periode

User dapat memilih periode (masa pajak + tahun) untuk ekualisasi:
- Default: periode terakhir yang ada di data SPT
- Dropdown masa pajak: Januari - Desember
- Input tahun

### 7.2 Export to Excel

Export hasil ekualisasi ke Excel dengan format:
- Sheet 1: Summary (total SPT, GL, selisih)
- Sheet 2: Detail per Faktur Pajak
- Sheet 3: SPT Only (FP belum ada di GL)
- Sheet 4: GL Only (FP tidak ada di SPT)

### 7.3 Dashboard Statistics

Update dashboard untuk menampilkan:
- Total Faktur Pajak (SPT vs GL)
- Total PPN SPT vs Total PPN GL
- Total Selisih (kandidat restitusi)
- Grafik perbandingan SPT vs GL per entity

### 7.4 Detail View per Faktur Pajak

Klik pada baris faktur pajak untuk melihat detail:
- Info SPT (nama penjual, NPWP, tanggal, status)
- Detail GL (semua item/barang dari GL)
- Perhitungan selisih detail

---

## 8. File yang Perlu Dibuat/Diubah

### File yang Diubah:
| File | Perubahan |
|------|-----------|
| `app/Http/Controllers/EQTax/SPTCoretaxController.php` | Fix pesan error |
| `app/Http/Controllers/EQTax/GLController.php` | Fix redirect & pesan |
| `app/Http/Controllers/EQTax/EqualizationController.php` | Rewrite query, tambah method equalization & export |
| `app/Imports/PPNSingleSheetImport.php` | Tambah entity mapping |
| `routes/routers/eqtax.php` | Tambah route process & export |
| `resources/views/eqtax/spt/coretax/index.blade.php` | Tambah tabel data |
| `resources/views/eqtax/gl/index.blade.php` | Tambah tabel data |
| `resources/views/eqtax/equalization/index.blade.php` | Rewrite UI lengkap |
| `resources/views/eqtax/dashboard/index.blade.php` | Tambah statistik |
| `app/Models/EQTAXGL.php` | Tambah entity ke fillable |
| `app/Models/EQTAXCoretaxSPT.php` | Tambah entity ke fillable |

### File yang Dibuat Baru:
| File | Keterangan |
|------|------------|
| `database/migrations/xxxx_add_entity_to_eqtax_gl.php` | Tambah kolom entity + index |
| `database/migrations/xxxx_add_entity_to_eqtax_coretax_spt.php` | Tambah kolom entity + index |
| `database/migrations/xxxx_create_eqtax_equalization_results.php` | Tabel hasil ekualisasi |
| `app/Exports/EqualizationExport.php` | Export ke Excel |
| `app/Http/Requests/EqualizationRequest.php` | Validasi request ekualisasi |

---

## 9. Implementation Order (Urutan Pengerjaan)

### Phase 1: Bug Fixes & Foundation (Hari 1)
1. Fix bug SPTCoretaxController (pesan error)
2. Fix bug GLController (redirect & pesan)
3. Fix migration down() inconsistency
4. Buat migration tambah kolom entity ke eqtax_gl
5. Update model EQTAXGL tambah entity ke fillable
6. Update PPNSingleSheetImport tambah entity mapping

### Phase 2: Database & Import (Hari 2)
7. Buat migration tambah kolom entity ke eqtax_coretax_spt
8. Update model EQTAXCoretaxSPT tambah entity ke fillable
9. Buat migration eqtax_equalization_results
10. Update GL import untuk simpan entity

### Phase 3: Equalization Logic (Hari 3-4)
11. Rewrite EqualizationController::index() dengan query baru
12. Implementasi EqualizationController::equalization() 
13. Tambah route untuk process & export
14. Buat EqualizationRequest untuk validasi

### Phase 4: Views & UI (Hari 5-6)
15. Update SPT Coretax index view (tambah tabel data)
16. Update GL index view (tambah tabel data)
17. Rewrite Equalization index view (filter, summary, tabel)
18. Update dashboard view (statistik)

### Phase 5: Export & Polish (Hari 7)
19. Buat EqualizationExport class
20. Implementasi export ke Excel
21. Testing end-to-end
22. Bug fixes & polish

---

## 10. Testing Plan

### 10.1 Unit Testing
- Test normalisasi nomor faktur pajak (TRIM, leading zeros)
- Test aggregate GL per no_faktur_pajak
- Test perhitungan selisih
- Test entity mapping (sheet → entity code)

### 10.2 Integration Testing
- Test import SPT Pajak
- Test import GL (multi-sheet)
- Test proses ekualisasi dengan data sample
- Test export ke Excel

### 10.3 Manual Testing dengan Data Sample
- Upload file `Coretax SPT PPN Tahun 2026_Update.xlsx`
- Upload file `GL-0326.xlsx`
- Jalankan ekualisasi
- Verifikasi hasil: pastikan pencocokan benar
- **Note**: Pastikan periode SPT dan GL sama untuk testing yang valid

---

## 11. Risks & Mitigasi

| Risik | Mitigasi |
|-------|----------|
| Format nomor FP berbeda antara SPT dan GL | Normalisasi dengan TRIM() dan optional leading zero removal |
| GL memiliki banyak baris per FP | Agregasi (SUM) sebelum pencocokan |
| Data GL memiliki spasi trailing dari Excel | TRIM() saat import |
| Angka format Indonesia (titik/koma) | Parser khusus untuk konversi |
| Periode SPT dan GL berbeda | Validasi periode sebelum ekualisasi |
| performa query dengan data besar | Index pada no_faktur_pajak dan entity |

---

## 12. Referensi

- File SPT: `agents/Coretax SPT PPN Tahun 2026_Update.xlsx`
- File GL: `agents/GL-0326.xlsx`
- Analisis proses: `analisis-proses-ekualisasi-restitusi-pajak.md`
