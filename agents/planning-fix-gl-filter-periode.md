# Planning: Fix GL Query Filter by Periode

## Masalah

Query GL di `EqualizationController::equalization()` dan `export()` **tidak filter by periode**. 

**Skenario bug:**
1. User upload SPT (berisi semua periode)
2. User upload GL Februari
3. Ekualisasi Februari → OK (GL hanya berisi data Feb)
4. User upload GL Maret → `eqtax_gl` sekarang berisi Feb + Maret
5. Ekualisasi Maret → **BUG**: GL query mengambil SEMUA data (Feb + Maret), tapi SPT hanya filter Maret
   - GL data Februari bocor ke hasil ekualisasi Maret
   - Invoice yang sama di GL Feb dan SPT Maret bisa salah match

## Root Cause

```php
// GL query TIDAK ada filter periode
$gl_agg = DB::table('eqtax_gl')
    ->select(...)
    ->when($entity, fn($q) => $q->where('entity', $entity))
    ->groupBy(...)
    ->get(); // ← Mengambil SEMUA data GL
```

## Solusi

### Approach: Filter GL by `jurnal_date` pattern

Format `jurnal_date` di database: string YYYYMMDD (e.g., "20260215")

Konversi `masa_pajak` + `tahun` → filter `jurnal_date`:
- Februari 2026 → `WHERE jurnal_date LIKE '202602%'`
- Maret 2026 → `WHERE jurnal_date LIKE '202603%'`

### File yang Perlu Diubah

1. `app/Http/Controllers/EQTax/EqualizationController.php`
   - Tambah helper `getJurnalDatePrefix($masaPajak, $tahun): string`
   - Filter GL query di `equalization()` method
   - Filter GL query di `export()` method

### Tidak Perlu Diubah

- Model, Migration, Routes, View — tidak berubah
- Import GL — tidak berubah (data sudah benar)
- `saveResults()` — tidak berubah

## Checklist

- [ ] Buat planning file
- [ ] Tambah method `getJurnalDatePrefix()`
- [ ] Fix GL query di `equalization()`
- [ ] Fix GL query di `export()`
- [ ] Validasi PHP syntax
