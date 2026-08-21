# Planning: Implementasi Relasi Antar Table & Model EQTax

## 1. Ikhtisar

### Tujuan
Menambahkan relasi antar table (foreign key constraints) dan model relationships pada modul EQTax untuk memastikan integritas data dan memudahkan akses data relasional.

### Kondisi Saat Ini
- **3 tabel utama**: `eqtax_coretax_spt`, `eqtax_gl`, `eqtax_equalization_results`
- **Tidak ada foreign key constraints** di migration
- **Tidak ada model relationships** (hasOne, hasMany, belongsTo)
- **Matching dilakukan via `TRIM(no_faktur_pajak)`** (natural key, bukan primary key)
- **Tabel `eqtax_equalization_results`** adalah hasil denormalisasi dari proses ekualisasi

---

## 2. Struktur Database Aktual

### 2.1 Tabel: `eqtax_coretax_spt`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint (PK) | Auto-increment |
| `npwp_penjual` | string | NPWP Penjual |
| `nama_penjual` | string | Nama Penjual |
| `no_faktur_pajak` | string | Nomor Faktur Pajak |
| `tgl_faktur_pajak` | timestamp | Tanggal Faktur Pajak |
| `masa_pajak` | string | Masa Pajak (Januari, Februari, dst) |
| `tahun` | string | Tahun Pajak |
| `entity` | string | Kode Entity (TJMO, SBHO, PLTR) |
| `status_faktur` | string | Status Faktur |
| `dpp` | bigInteger | Dasar Pengenaan Pajak |
| `ppn` | bigInteger | Pajak Pertambahan Nilai |
| `...` | ... | Kolom lainnya |

**Index**: Tidak ada index tambahan (hanya PK)

### 2.2 Tabel: `eqtax_gl`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint (PK) | Auto-increment |
| `sheet` | string | Nama Sheet |
| `entity` | string | Kode Entity |
| `no_supplier` | string | Nomor Supplier |
| `nama_supplier` | string | Nama Supplier |
| `jurnal_date` | string | Tanggal Jurnal |
| `jurnal_no` | string | Nomor Jurnal |
| `no_faktur_pajak` | string | Nomor Faktur Pajak |
| `dpp` | float | Dasar Pengenaan Pajak |
| `ppn` | float | Pajak Pertambahan Nilai |
| `keterangan` | string | Keterangan |

**Index**: `no_faktur_pajak` (index), `entity` (index)

### 2.3 Tabel: `eqtax_equalization_results`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint (PK) | Auto-increment |
| `period` | string | Format: YYYY-MM |
| `entity` | string | Kode Entity |
| `no_faktur_pajak` | string | Nomor Faktur Pajak |
| `nama_penjual` | string | Nama Penjual |
| `dpp_spt` | bigInteger | DPP dari SPT |
| `dpp_gl` | double | DPP dari GL |
| `ppn_spt` | bigInteger | PPN dari SPT |
| `ppn_gl` | double | PPN dari GL |
| `selisih_ppn` | double | Selisih PPN |
| `status` | string | MATCH, TO_BE_CHECK, SPT_ONLY, GL_ONLY |
| `keterangan` | text | Keterangan |

**Index**: `period` (index), `entity` (index), `no_faktur_pajak` (index), `status` (index)

---

## 3. Analisis Relasi

### 3.1 Pola Matching di EqualizationController

Berdasarkan analisis `EqualizationController::equalization()`:

```
1. GL Data di-aggregate:
   - GROUP BY TRIM(no_faktur_pajak), entity, nama_supplier
   - SUM(dpp), SUM(ppn), COUNT(*)

2. SPT Data di-filter:
   - WHERE masa_pajak = ? AND tahun = ?

3. Matching dilakukan via:
   - key = TRIM(no_faktur_pajak)
   - Gabungkan semua unique no_faktur_pajak dari SPT dan GL

4. Status ditentukan:
   - MATCH: Ada di SPT DAN GL, selisih_ppn = 0
   - TO_BE_CHECK: Ada di SPT DAN GL, selisih_ppn != 0
   - SPT_ONLY: Hanya ada di SPT
   - GL_ONLY: Hanya ada di GL
```

### 3.2 Tantangan Implementasi Relasi

| Tantangan | Penjelasan |
|-----------|------------|
| `no_faktur_pajak` tidak unique | Satu faktur pajak bisa muncul beberapa kali (terutama di GL) |
| Matching pakai TRIM() | Ada spasi/whitespace di data, harus di-trim dulu |
| GL di-aggregate | Satu faktur pajak bisa punya beberapa baris di GL |
| Entity tidak konsisten | Entity di SPT dan GL bisa berbeda |
| Data denormalisasi | `eqtax_equalization_results` menyimpan hasil merge, bukan referensi |

---

## 4. Pilihan Pendekatan

### Pendekatan A: Foreign Key Formal (spt_id, gl_id)
**Deskripsi**: Tambah kolom `spt_id` dan `gl_id` di `eqtax_equalization_results` sebagai foreign key.

| Kelebihan | Kekurangan |
|-----------|------------|
| Relasi Eloquent proper | Migration sangat kompleks |
| Data integrity terjamin | Harus update logika matching di controller |
| Mudah query relasi | GL aggregation membuat FK ganda (satu faktur → banyak GL rows) |
| | Existing data harus di-migrate |

**Verdict**: ❌ **TIDAK DISARANKAN** - Terlalu kompleks dan tidak sesuai dengan logika bisnis

---

### Pendekatan B: Composite Index + Accessor Methods (REKOMENDASI)
**Deskripsi**: Tambah composite index untuk performa, dan accessor methods di model untuk kemudahan akses.

| Kelebihan | Kekurangan |
|-----------|------------|
| Tidak ubah logika matching | Tidak ada Eloquent relationship formal |
| Performa terjaga dengan index | Harus query manual untuk relasi |
| Data existing aman | |
| Implementasi sederhana | |

**Verdict**: ✅ **DISARANKAN** - Praktis dan tidak mengganggu logika bisnis

---

### Pendekatan C: Pertahankan Denormalisasi (Status Quo)
**Deskripsi**: Tidak ada perubahan, hanya tambah index jika diperlukan.

| Kelebihan | Kekurangan |
|-----------|------------|
| Zero risk | Tidak ada kemudahan akses relasi |
| Tidak perlu ubah kode | Performa bisa lambat tanpa index |

**Verdict**: ⚠️ **PARTIAL** - Hanya tambah index, tanpa accessor

---

## 5. Rekomendasi: Pendekatan B (Composite Index + Accessor Methods)

### 5.1 Rencana Implementasi

#### Tahap 1: Migration Baru (Index Tambahan)
Buat migration baru untuk menambah index yang diperlukan:

```php
// File: database/migrations/2026_08_21_000001_add_indexes_to_eqtax_tables.php

Schema::table('eqtax_coretax_spt', function (Blueprint $table) {
    $table->index('no_faktur_pajak');
    $table->index('masa_pajak');
    $table->index('tahun');
    $table->index(['masa_pajak', 'tahun']); // composite index
    $table->index(['no_faktur_pajak', 'entity']); // composite index
});

Schema::table('eqtax_gl', function (Blueprint $table) {
    $table->index('jurnal_date');
    $table->index(['no_faktur_pajak', 'entity']); // composite index
});

Schema::table('eqtax_equalization_results', function (Blueprint $table) {
    $table->index(['period', 'entity']); // composite index
    $table->index(['no_faktur_pajak', 'entity']); // composite index
});
```

#### Tahap 2: Model Relationships (Accessor Methods)
Tambahkan methods di model untuk akses data relasional:

**EQTAXCoretaxSPT.php**
```php
/**
 * Mendapatkan data equalization results untuk faktur pajak ini
 */
public function equalizationResults()
{
    return $this->hasMany(EQTAXEqualizationResult::class, 'no_faktur_pajak', 'no_faktur_pajak')
        ->where('entity', $this->entity);
}

/**
 * Mendapatkan data GL untuk faktur pajak ini
 */
public function glData()
{
    return $this->hasMany(EQTAXGL::class, 'no_faktur_pajak', 'no_faktur_pajak')
        ->where('entity', $this->entity);
}
```

**EQTAXGL.php**
```php
/**
 * Mendapatkan data SPT untuk faktur pajak ini
 */
public function sptData()
{
    return $this->belongsTo(EQTAXCoretaxSPT::class, 'no_faktur_pajak', 'no_faktur_pajak')
        ->where('entity', $this->entity);
}

/**
 * Mendapatkan data equalization results untuk faktur pajak ini
 */
public function equalizationResults()
{
    return $this->hasMany(EQTAXEqualizationResult::class, 'no_faktur_pajak', 'no_faktur_pajak')
        ->where('entity', $this->entity);
}
```

**EQTAXEqualizationResult.php**
```php
/**
 * Mendapatkan data SPT untuk faktur pajak ini
 */
public function sptData()
{
    return $this->belongsTo(EQTAXCoretaxSPT::class, 'no_faktur_pajak', 'no_faktur_pajak')
        ->where('entity', $this->entity)
        ->where('masa_pajak', $this->getMasaPajakFromPeriod())
        ->where('tahun', $this->getTahunFromPeriod());
}

/**
 * Mendapatkan data GL untuk faktur pajak ini
 */
public function glData()
{
    return $this->hasMany(EQTAXGL::class, 'no_faktur_pajak', 'no_faktur_pajak')
        ->where('entity', $this->entity);
}

/**
 * Helper: Extract masa_pajak dari period (YYYY-MM)
 */
private function getMasaPajakFromPeriod(): string
{
    $monthMap = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
        '04' => 'April', '05' => 'Mei', '06' => 'Juni',
        '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
        '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
    ];
    $month = substr($this->period, 5, 2);
    return $monthMap[$month] ?? 'Januari';
}

/**
 * Helper: Extract tahun dari period (YYYY-MM)
 */
private function getTahunFromPeriod(): string
{
    return substr($this->period, 0, 4);
}
```

#### Tahap 3: Update Existing Migration (Fix Bug)
Fix bug di migration lama yang salah di `down()`:

```php
// File: database/migrations/2026_08_14_133832_create_e_q_t_a_x_coretax_s_p_t_s_table.php
public function down()
{
    Schema::dropIfExists('eqtax_coretax_spt'); // BUKAN 'e_q_t_a_x_coretax_s_p_t_s'
}

// File: database/migrations/2026_08_15_143005_create_e_q_t_a_x_g_l_s_table.php
public function down()
{
    Schema::dropIfExists('eqtax_gl'); // BUKAN 'e_q_t_a_x_g_l_s'
}
```

---

## 6. File yang Akan Dimodifikasi

| # | File | Perubahan |
|---|------|-----------|
| 1 | `database/migrations/2026_08_21_000001_add_indexes_to_eqtax_tables.php` | **BARU** - Tambah index |
| 2 | `app/Models/EQTAXCoretaxSPT.php` | Tambah `equalizationResults()`, `glData()` |
| 3 | `app/Models/EQTAXGL.php` | Tambah `sptData()`, `equalizationResults()` |
| 4 | `app/Models/EQTAXEqualizationResult.php` | Tambah `sptData()`, `glData()`, helpers |
| 5 | `database/migrations/2026_08_14_133832_...php` | Fix bug `down()` |
| 6 | `database/migrations/2026_08_15_143005_...php` | Fix bug `down()` |

---

## 7. Contoh Penggunaan

### 7.1 Query dengan Relasi
```php
// Ambil SPT beserta equalization results
$spt = EQTAXCoretaxSPT::with('equalizationResults')->find(1);
echo $spt->equalizationResults->count(); // Jumlah faktur yang sudah diekualisasi

// Ambil Equalization Result beserta data SPT
$result = EQTAXEqualizationResult::with('sptData')->find(1);
echo $result->sptData->npwp_penjual; // NPWP penjual dari SPT

// Ambil GL beserta data SPT
$gl = EQTAXGL::with('sptData')->find(1);
echo $gl->sptData->nama_penjual; // Nama penjual dari SPT
```

### 7.2 Query tanpa Relasi (Saat Ini)
```php
// Harus query manual
$spt = EQTAXCoretaxSPT::find(1);
$results = EQTAXEqualizationResult::where('no_faktur_pajak', $spt->no_faktur_pajak)
    ->where('entity', $spt->entity)
    ->get();
```

---

## 8. Flowchart Implementasi

```
┌─────────────────────────────────────────────────────────────┐
│                    TAHAP 1: MIGRATION                        │
│  Buat migration baru untuk tambah index                     │
│  - eqtax_coretax_spt: no_faktur_pajak, masa_pajak, tahun   │
│  - eqtax_gl: jurnal_date, composite no_faktur_pajak+entity │
│  - eqtax_equalization_results: composite period+entity      │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                    TAHAP 2: MODEL                            │
│  Tambah accessor methods di 3 model:                        │
│  - EQTAXCoretaxSPT: equalizationResults(), glData()        │
│  - EQTAXGL: sptData(), equalizationResults()               │
│  - EQTAXEqualizationResult: sptData(), glData(), helpers   │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                    TAHAP 3: FIX BUG                          │
│  Fix bug di migration lama:                                 │
│  - down() salah hapus table name                            │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                    SELESAI                                   │
│  Jalankan: php artisan migrate                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 9. Testing Checklist

- [ ] Jalankan `php artisan migrate` tanpa error
- [ ] Jalankan `php artisan migrate:rollback` tanpa error
- [ ] Test `EQTAXCoretaxSPT::with('equalizationResults')` berfungsi
- [ ] Test `EQTAXGL::with('sptData')` berfungsi
- [ ] Test `EQTAXEqualizationResult::with('sptData')` berfungsi
- [ ] Test `EQTAXEqualizationResult::with('glData')` berfungsi
- [ ] Pastikan tidak ada performance regression
- [ ] Pastikan logika ekualisasi masih berfungsi

---

## 10. Estimasi Waktu

| Task | Estimasi |
|------|----------|
| Buat migration baru (index) | 15 menit |
| Update 3 model files | 30 menit |
| Fix bug migration lama | 5 menit |
| Testing | 20 menit |
| **Total** | **~70 menit (~1 jam)** |

---

## 11. Risk & Mitigasi

| Risk | Mitigasi |
|------|------------|
| Migration gagal karena index sudah ada | Cek dulu apakah index sudah ada sebelum tambah |
| Performance regression | Benchmark query sebelum dan sesudah |
| Breaking change di existing code | Tidak ada perubahan API, hanya penambahan methods |
| Data integrity issue | Foreign key tidak ditambahkan (hanya index + methods) |

---

## 12. Catatan Penting

1. **Tidak ada Foreign Key Constraint**: Karena matching dilakukan via `TRIM(no_faktur_pajak)` dan bukan primary key, foreign key constraint tidak ditambahkan. Relasi dilakukan di level aplikasi (Eloquent relationships).

2. **Performance**: Composite index akan membantu performa query, terutama untuk tabel `eqtax_gl` yang bisa memiliki banyak baris.

3. **Backward Compatible**: Semua perubahan bersifat tambahan (additive), tidak mengubah API yang sudah ada.

4. **Data Existing**: Tidak perlu migrasi data karena tidak ada kolom baru di tabel.
