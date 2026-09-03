# Planning: Implementasi Tabs Berdasarkan Entity di Menu SPT Coretax

## Overview

Fitur ini bertujuan untuk **mengelompokkan data di menu SPT Coretax menjadi tab-tab berdasarkan entity**,
dimana setiap tab menampilkan table dengan kolom-kolom yang sesuai dengan format kolom saat file SPT diimport.

Selain itu, diperbaiki **bug pada entity PK** dimana data kolom **"Penandatanganan"** tidak masuk ke
database saat import. Sebagai tambahan, dipastikan **semua kolom yang diimport masuk ke kolom masing-masing**
untuk ketiga format.

---
## 1. Pemetaan Entity ke Format SPT (Konfirmasi)

Berdasarkan analisis codebase dan konfirmasi user:

| Tab / Entity | Format Import | Deteksi Header | Keterangan |
|---|---|---|---|
| **PK** (Pajak Keluaran) | **Format 1** | mengandung `NPWP Penjual` | Faktur keluaran (penjual). **Punya kolom Penandatanganan**, **TIDAK punya Perekam** |
| **PM** (Pajak Masukan) | **Format 2** | mengandung `NPWP Pembeli` | Faktur masukan (pembeli). **Punya kolom Perekam**, **TIDAK punya Penandatanganan** |
| **PMS** | **Format 3** | mengandung `Nomor Dokumen` / `Jenis Transaksi` / `Nilai Tagihan` / `Dibuat Oleh` | Dokumen. **Punya kolom Perekam**, **TIDAK punya Penandatanganan** |

> Inconsistency yang DITEMUKAN pada kode saat ini (`app/Imports/SPTSingleSheetImport.php`):
> - `mapFormat1()` (PK) memetakan `perekam` tetapi **TIDAK memetakan `penandatanganan`** → **BUG**: data Penandatanganan PK tidak masuk DB.
> - `mapFormat2()` (PM) memetakan `penandatanganan` tetapi **TIDAK memetakan `perekam`**.
> - `mapFormat3()` (PMS) memetakan `perekam` (benar).

---

## 2. Analisis Kondisi Saat Ini

### 2.1 Alur Import SPT Saat Ini

```
resources/views/eqtax/spt/coretax/index.blade.php
        │  POST /eqtax/spt/coretax/import (file)
        ▼
app/Http/Controllers/EQTax/SPTCoretaxController.php::import()
        │  Excel::import(new SPTSheetImport($file), $file)
        ▼
app/Imports/SPTSheetImport.php            (WithMultipleSheets → iterate semua sheet)
        ▼  tiap sheet → SPTSingleSheetImport
app/Imports/SPTSingleSheetImport.php      (deteksi format, mapping kolom, build record)
        ▼
eqtax_coretax_spt table
```

- `entity` di-set **dari nama sheet Excel** (`'entity' => $this->sheetName` di `buildRecord()`).
- Seluruh record (PK/PM/PMS) di-insert ke **satu tabel** `eqtax_coretax_spt`, dibedakan oleh kolom `entity`.

### 2.2 Halaman SPT Coretax Saat Ini

- View: `resources/views/eqtax/spt/coretax/index.blade.php`
- Controller: `app/Http/Controllers/EQTax/SPTCoretaxController.php::index()`
- Saat ini: **1 tabel flat tunggal** dengan filter dropdown (Cari, Entity, Masa Pajak, Tahun).
- **Belum ada tab grouping**.
- Tabel saat ini menampilkan subset kolom (18 kolom) dan **belum menampilkan** kolom format-spesifik
  (`kode_transaksi`, `esign_status`, `penandatanganan`, `metode_input`, `uraian`, `is_show_clear_name`,
  `jenis_transaksi`, `keterangan`, `dibuat_oleh`).
- Inline edit (double-click) aktif untuk semua cell `.editable` → AJAX ke `update-field`.

### 2.3 Form Deteksi & Pemetaan Kolom di Import (Saat Ini)

`SPTSingleSheetImport`:
- `detectFormat($header)` → `format2` / `format3` / `format1` / `null`.
- `mapFormat1`, `mapFormat2`, `mapFormat3` → array `db_field => index_kolom`.
- `buildRecord()` → set `entity` dari sheet name + cast value.
- `castValue()` → `tgl_faktur_pajak` = date; `valid/dilaporkan/dilaporkan_oleh_penjual/is_show_clear_name` = bool;
  `harga_jual/dpp/ppn/ppnbm` = number; else text.

### 2.4 Skema DB `eqtax_coretax_spt` (Setelah Semua Migration)

```
id
npwp_penjual             string
nama_penjual             string
no_faktur_pajak          string   (index)
tgl_faktur_pajak         timestamp
masa_pajak               string
tahun                    string
entity                   string
kode_transaksi           string   (Format 2)
masa_pajak_pengkreditan  string
tahun_pajak_pengkreditan string
status_faktur            string
esign_status             string   (Format 2)
harga_jual               bigint
dpp                      bigint
ppn                      bigint
ppnbm                    bigint
penandatanganan          string   (Format 1 → PK)
perekam                  string   (tidak dipetakan di Format 2 saat ini → BUG)
referensi                string
metode_input             string   (Format 2)
uraian                   string   (Format 2)
is_show_clear_name       boolean  (Format 2 & 3)
no_sp2d                  string
jenis_transaksi          string   (Format 3)
keterangan               string   (Format 3)
dibuat_oleh              string   (Format 3)
valid                    boolean
dilaporkan               boolean
dilaporkan_oleh_penjual  boolean
created_at / updated_at
```

---

## 3. Masalah yang Ditemukan

1. **BUG — Penandatanganan di PK (Format 1)**:
   - `mapFormat1()` TIDAK memetakan kolom `Penandatanganan` → nilai kolom ini di file Excel PK selalu
     `null` di DB. Perlu ditambahkan pemetaan `'penandatanganan' => $col(['Penandatanganan'])`.

2. **BUG — Perekam di PM (Format 2)**:
   - `mapFormat2()` TIDAK memetakan kolom `Perekam`, padahal file PM (Format 2) punya kolom Perekam.
     Perlu ditambahkan `'perekam' => $col(['Perekam'])`.

3. **Kolom yang diimport belum semuanya ditampilkan**:
   - View tidak menampilkan kolom format-spesifik (kode_transaksi, esign_status, penandatanganan,
     metode_input, uraian, is_show_clear_name, jenis_transaksi, keterangan, dibuat_oleh, dsb).
   - Dengan tab grouping per entity, setiap tab harus menampilkan kolom **sesuai format kolom saat file
     diimport** untuk entity tsb.

---

## 4. Desain Solusi

### 4.1 Tabs Berdasarkan Entity

Di view `index.blade.php`, buat **3 tab** di atas tabel:

```
[ PK ]  [ PM ]  [ PMS ]
```

- Tab aktif default: **PK** (atau tab pertama yang datanya ada — usulan: default **PK**).
- Setiap tab menampilkan data dengan filter `entity` == tab tsb.
- Klik tab → memuat data tab tsb (bisa via: (a) render semua sekaligus lalu toggle dengan JS, atau
  (b) AJAX load per tab).

**Rekomendasi pendekatan:** Karena controller sudah memfilter per entity, pendekatan yang paling sederhana
& konsisten dengan kode saat ini adalah **menggunakan query param `tab`**:
- Route tetap `GET /eqtax/spt/coretax` dengan query param `tab=PK|PM|PMS` (default `PK`).
- Controller `index()` memfilter `where('entity', $tab)` jika `tab` diberikan.
- View menerima `$activeTab`, `$masaPajakList`, `$tahunList` sesuai tab aktif.
- Tab dirender sebagai link dengan query string (`?tab=PK&masa_pajak=...`).

Alternatif (lebih baik UX): **3 tabel di-render terpisah** (satu per tab), dan JS tinggal show/hide.
Namun karena perbedaannya hanya filter entity, pendekatan query param lebih ringan dan tidak perlu refactor
controller signifikan. **Diusulkan: pendekatan query param `tab`.**

### 4.2 Kolom Tabel per Tab (Sesuai Format Import)

Setiap tab menampilkan kolom sesuai format kolom saat file SPT diimport untuk entity tsb.

#### Tab PK (Format 1)
Kolom yang ditampilkan (menambahkan `Penandatanganan` — tidak menampilkan `Perekam` karena PK tidak punya):

```
No | No Faktur Pajak | Nama Penjual | NPWP | Tanggal FP | Masa Pajak | Tahun
| Masa Pengkreditan | Tahun Pengkreditan | Status Faktur | Harga Jual | DPP
| PPN | PPNBM | Penandatanganan | Referensi | No SP2D
```

#### Tab PM (Format 2)
Kolom yang ditampilkan (menambahkan `Perekam`; tidak menampilkan `Penandatanganan` karena PM tidak punya):

```
No | No Faktur Pajak | Nama Pembeli | NPWP Pembeli | Tanggal FP | Masa Pajak | Tahun
| Kode Transaksi | Status Faktur | ESign Status | Harga Jual | DPP | PPN | PPNBM
| Perekam | Referensi | Metode Input | Uraian | Dilaporkan oleh Penjual | IsShowClearName
```

> Catatan: label kolom untuk PM yang memakai field `npwp_penjual`/`nama_penjual` disesuaikan tampilannya
> menjadi "NPWP Pembeli"/"Nama Pembeli" (karena di Format 2 data tsb adalah identitas pembeli).

#### Tab PMS (Format 3)
Kolom yang ditampilkan:

```
No | Nomor Dokumen | NPWP Penjual | Nama Penjual | Tanggal Dokumen | Masa Pajak | Tahun
| Masa Pengkreditan | Tahun Pengkreditan | Nilai Tagihan | DPP | PPN | PPNBM
| Status | Perekam | Keterangan | Jenis Transaksi | Dibuat Oleh | IsShowClearName
```

### 4.3 Perbaikan Import (Mapping Kolom per Format)

Di `app/Imports/SPTSingleSheetImport.php`:

#### `mapFormat1()` (PK) — TAMBAH `penandatanganan`
```php
'penandatanganan'         => $col(['Penandatanganan']),
```
Hapus `'perekam'` dari Format 1 (PK tidak punya kolom Perekam) — atau biarkan saja karena `findColumn`
mengembalikan `null` jika kolom tidak ada (sehingga aman). **Rekomendasi: hapus mapping `perekam` di
Format 1** agar sesuai kenyataan file.

#### `mapFormat2()` (PM) — TAMBAH `perekam`, HAPUS `penandatanganan`
```php
'perekam'                 => $col(['Perekam']),
// hapus: 'penandatanganan' => $col(['Penandatanganan']),
```

#### `mapFormat3()` (PMS) — sudah benar (`perekam` ada), TIDAK ada penandatanganan. Tanpa perubahan.

> Catatan: `findColumn` aman — jika kolom tidak ditemukan di header mengembalikan `null`, dan `buildRecord`
> me-skip kolom yang index-nya `null`. Jadi menambah/menghapus mapping tidak akan error.

### 4.4 Struktur Kolom Tabel Dinamis

Agar tiap tab menampilkan kolom berbeda, definisikan **konfigurasi kolom per tab** yang dipakai bersama
oleh view (dan inline edit). Opsi:

- **Opsi A (diusulkan):** Definisikan array konfigurasi kolom di Blade langsung (3 array: `$pkColumns`,
  `$pmColumns`, `$pmsColumns`), tiap elemen berisi `{field, label, type, format}` untuk render & inline edit.
- **Opsi B:** Taruh konfigurasi di controller dan pass ke view.

Rekomendasi **Opsi A** — konfigurasi di view supaya mudah disesuaikan tampilan, dan controller cukup
menyediakan data mentah (`$sptData`). Jika ingin lebih rapi, gunakan helper/partial.

Setiap kolom dirender dengan pola yang sama seperti sekarang, dan tetap mendukung **inline edit**
(semua tab bisa inline-edit — konfirmasi user).

### 4.5 Inline Edit — Tetap Aktif di Semua Tab

- JS inline edit yang ada (double-click → `update-field`) tetap bekerja.
- Karena kolom tabel tiap tab berbeda, pastikan atribut `data-field`, `data-type`, `data-value` mengikuti
  konfigurasi kolom tab tsb.
- `updateField()` controller sudah memakai `getFillable()` sebagai whitelist → semua kolom DB otomatis
  bisa diedit. Tambahkan `penandatanganan` & `perekam` sudah ada di `$fillable` (sudah OK).

---

## 5. Files yang Perlu Diubah

| No | File | Tipe | Perubahan |
|----|------|------|-----------|
| 1 | `app/Imports/SPTSingleSheetImport.php` | Ubah | `mapFormat1`: tambah `penandatanganan`, hapus `perekam`. `mapFormat2`: tambah `perekam`, hapus `penandatanganan` |
| 2 | `resources/views/eqtax/spt/coretax/index.blade.php` | Ubah | Tambah 3 tab (PK/PM/PMS); render tabel per tab dengan konfigurasi kolom sesuai format; aktifkan inline edit untuk semua tab |
| 3 | `app/Http/Controllers/EQTax/SPTCoretaxController.php` | Ubah | `index()`: terima param `tab`, filter `entity`, teruskan `$activeTab` ke view |
| 4 | `app/Models/EQTAXCoretaxSPT.php` | (cek) | `$fillable` sudah memuat `penandatanganan` & `perekam` → tidak perlu ubah |
| 5 | (opsional) `database/...` migration | Tidak perlu | Skema DB sudah lengkap; tidak ada kolom baru |

### Tidak Perlu Diubah
- `app/Imports/SPTSheetImport.php` — dispatcher multi-sheet sudah benar.
- `routes/routers/eqtax.php` — route `index` sudah menangani query param otomatis.
- Equalization / Dashboard — tidak terkait (hanya baca kolom yang sudah ada).

---

## 6. Keputusan yang Perlu Dikonfirmasi (Opsional / Asumsi)

1. **Tab default** saat pertama buka halaman: diasumsikan **PK**. (Bisa disesuaikan.)
2. **Entity untuk tab**: karena `entity` di-set dari nama sheet Excel, perlu pastikan nilai sheet name
   konsisten (mis. sheet PK bernama "PK", dst). Jika sheet name berbeda dari label tab, perlu mapping
   di controller (mis. `str_contains(gunakan lowercase)`). **Rekomendasi: tab di-match ke `entity`
   secara case-insensitive** (normalisasi lowercase) agar robust terhadap perbedaan case nama sheet.
3. **Label kolom "NPWP Pembeli"/"Nama Pembeli"** di tab PM: ditampilkan sesuai makna (walau field DB
   `npwp_penjual`/`nama_penjual`).

---

## 7. Edge Cases

| Skenario | Penanganan |
|----------|------------|
| Tab tanpa data | Tampilkan pesan "Belum ada data" (pola `@forelse` yang sudah ada) |
| Entity sheet name berbeda case ("pk" vs "PK") | Normalisasi lowercase saat filter tab |
| Data entity tidak persis PK/PM/PMS | Tab tetap menampilkan berdasakan `entity`; tab yang tidak ada datanya kosong |
| File import tanpa semua format | Tab yang tidak punya data tampil kosong, tidak error |
| Pagination per tab | Gunakan `withQueryString()` agar param `tab`, `search`, `masa_pajak`, `tahun` terjaga |

---

## 8. Checklist Implementasi

- [x] Perbaiki `mapFormat1()` (PK): tambah `penandatanganan`, hapus `perekam`
- [x] Perbaiki `mapFormat2()` (PM): tambah `perekam`, hapus `penandatanganan`
- [x] Update `SPTCoretaxController::index()` untuk menerima & filter param `tab`
- [x] Tambah 3 tab (PK/PM/PMS) di view `index.blade.php`
- [x] Render table per tab dengan konfigurasi kolom sesuai format import
- [x] Pastikan inline-edit berfungsi di semua tab (termasuk tipe boolean)
- [ ] Uji import file PK → cek kolom `penandatanganan` terisi di DB
- [x] Ubah import jadi upsert (updateOrCreate by entity + no_faktur_pajak) untuk isi data lama tanpa duplikat
- [ ] Uji import file PM → cek kolom `perekam` terisi di DB
- [ ] Uji tampilan tab PK, PM, PMS menampilkan kolom yang benar
- [ ] Uji inline edit di masing-masing tab
- [ ] Uji regresi: import file 3 sheet tetap semua data masuk
- [ ] Uji pagination + filter + query param tab tetap terjaga
