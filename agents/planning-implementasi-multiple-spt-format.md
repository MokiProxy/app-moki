# Planning: Implementasi Multiple SPT Format (Upload 3 Sheet SPT Coretax)

## Overview

Fitur upload SPT Coretax saat ini hanya memproses **1 sheet pertama** dari file Excel yang diupload
(`EQTaxImport implements ToModel, WithHeadingRow` — Maatwebsite Excel hanya baca sheet aktif pertama).

**Kondisi baru:** File SPT yang akan diupload akan memiliki **3 sheet**:
- **2 sheet** dengan **format saat ini (Format 1)** — kolom `NPWP Penjual`, `Nama Penjual`, dst.
- **1 sheet** dengan **format baru (Format 2)** — kolom `NPWP Pembeli`, `Nama Pembeli`, dst.

Tujuan: Mesin import harus bisa membedakan kedua format tersebut, memetakan tiap kolom ke skema DB yang
benar, dan memproses **semua 3 sheet** dalam satu file.

---

## 1. Analisis Kondisi Saat Ini

### 1.1 Arsitektur Upload SPT

```
resources/views/eqtax/spt/coretax/index.blade.php
        │  POST /eqtax/spt/coretax/import  (file)
        ▼
app/Http/Controllers/EQTax/SPTCoretaxController.php::import()
        │  Excel::import(new EQTaxImport, $file)
        ▼
app/Imports/EQTaxImport.php   (ToModel, WithHeadingRow → HANYA sheet pertama)
        ▼
eqtax_coretax_spt table
```

### 1.2 File & Lokasi Kunci

| File | Peran |
|------|-------|
| `app/Imports/EQTaxImport.php` | Import SPT saat ini (single sheet) |
| `app/Http/Controllers/EQTax/SPTCoretaxController.php` | `index()`, `import()`, `updateField()` |
| `app/Models/EQTAXCoretaxSPT.php` | Model + array `$fillable` |
| `app/Http/Controllers/EQTax/GLController.php` | Contoh pola multi-sheet (`PPNSheetImport`) |
| `app/Imports/PPNSheetImport.php` | Contoh dispatcher `WithMultipleSheets` |
| `app/Imports/PPNSingleSheetImport.php` | Contoh importer per sheet |
| `database/migrations/2026_08_14_133832_...` | Skema tabel `eqtax_coretax_spt` (base) |
| `database/migrations/2026_08_16_000002_...` | Tambah kolom `entity` + index `no_faktur_pajak` |
| `routes/routers/eqtax.php` | Route `eqtax.spt.coretax.import` |

### 1.3 Skema DB `eqtax_coretax_spt` (Saat Ini)

```
id
npwp_penjual            string
nama_penjual             string
no_faktur_pajak         string              (index)
tgl_faktur_pajak        timestamp
masa_pajak              string
tahun                   string
entity                  string              (ditambahkan migration ke-2)
masa_pajak_pengkreditan string
tahun_pajak_pengkreditan string
status_faktur           string
harga_jual              bigint
dpp                     bigint
ppn                     bigint
ppnbm                   bigint
perekam                 string
referensi               string
no_sp2d                 string
valid                   boolean
dilaporkan              boolean
dilaporkan_oleh_penjual boolean
created_at / updated_at
```

### 1.4 Format 1 (saat ini — kolom header sheet yang sudah ada)

Dari file contoh `agents/Coretax SPT PPN Tahun 2026_Update.xlsx`:

```
NPWP Penjual | Nama Penjual | Nomor Faktur Pajak | Tanggal Faktur Pajak | Masa Pajak
| Tahun | Masa Pajak Pengkreditkan | Tahun Pajak Pengkreditan | Status Faktur
| Harga Jual/Penggantian/DPP | DPP Nilai Lain/DPP | PPN | PPnBM | Perekam
| Referensi | Nomor SP2D | Valid | Dilaporkan | Dilaporkan oleh Penjual | ...
```

### 1.5 Format 2 (baru — 1 sheet di file 3-sheet)

Kolom yang disebutkan user:
```
NPWP Pembeli / Identitas lainnya
Nama Pembeli
Kode Transaksi
Nomor Faktur Pajak
Tanggal Faktur Pajak
Masa Pajak
Tahun
Status Faktur
ESignStatus
Harga Jual/Penggantian/DPP
DPP Nilai Lain/DPP
PPN
PPnBM
Penandatanganan
Referensi
Metode Input
Dilaporkan oleh
IsShowClearName
Uraian
```

### 1.6 Masalah yang Ditemukan di Implementasi Saat Ini

1. `EQTaxImport` hanya impl `ToModel + WithHeadingRow` → **hanya membaca sheet pertama**. Sheet ke-2
   dan ke-3 diabaikan sama sekali.
2. `Excel::import()` selalu mengembalikan `true` saat sukses (bahkan jika 0 baris) → cabang "Gagal" di
   `SPTCoretaxController::import()` adalah **dead code**; tidak ada deteksi upload kosong.
3. Tidak ada `DB::transaction` / chunking → tidak atomik dan lambat untuk file besar (pola GL sudah
   menerapkan keduanya).
4. Kolom `entity` tidak pernah diisi oleh import (hanya bisa diisi manual via inline edit).
5. `masa_pajak_pengkreditkan` (typo di key header) harus cocok persis dengan teks header Excel.
6. Tanggal (`tgl_faktur_pajak`) `timestamp` tanpa parsing eksplisit → bergantung pada default Maatwebsite.

---

## 2. Desain Solusi

### 2.1 Arsitektur Baru (Multi-Sheet + Multi-Format)

Pola: ikuti dispatcher `WithMultipleSheets` yang sudah terbukti di modul GL (`PPNSheetImport`),
lalu pada tiap sheet gunakan deteksi header untuk menentukan Format 1 atau Format 2.

```
SPTCoretaxController::import()
   └─> Excel::import(new SPTSheetImport($file), $file)   // dispatcher multi-sheet
             └─ sheets() iterate semua sheet → tiap sheet pakai SPTSingleSheetImport
                         └─ collection($rows): deteksi format via kolom header
                              ├─ Punya "NPWP Penjual"  → FORMAT 1 → map lama
                              └─ Punya "NPWP Pembeli"  → FORMAT 2 → map baru
   └─ DB::transaction + array_chunk insert → berhasil/gagal (deteksi data kosong)
```

### 2.2 File Import Baru

#### A. `app/Imports/SPTSheetImport.php` — Dispatcher (`WithMultipleSheets`)

- Terima `UploadedFile`, load `IOFactory::getSheetNames()`, buat `SPTSingleSheetImport` per sheet.
- Mirip `PPNSheetImport`.

#### B. `app/Imports/SPTSingleSheetImport.php` — Importer per sheet (`ToCollection`, `WithHeadingRow`, `ToArray`/`WithTitle`)

- Baca **baris header** untuk mendeteksi format:
  - Jika header berisi `NPWP Penjual` / `Nama Penjual` → **FORMAT 1**.
  - Jika header berisi `NPWP Pembeli` / `Nama Pembeli` → **FORMAT 2**.
- `begin(n): void` — catat sheet index untuk `entity` (opsional, mengikuti pola GL dari nama sheet).
- `collection($rows)`:
  - Ambil baris ke-0 sebagai header. Skip baris kosong / `#N/A` / `#VALUE!` / `NULL`.
  - Map kolom via **index kolom** (lebih robust daripada asumsi posisi tetap; tapi karena format setiap
    sheet konsisten, yang penting deteksi format dulu, lalu map index sesuai format).
  - Push array asosiatif yang sudah disesuaikan kolom DB ke `$this->parent->result[]`.

### 2.3 Pemetaan Kolom → DB

#### Format 1 (sama seperti `EQTaxImport` saat ini — dipertahankan)

| Header Excel | DB column |
|---|---|
| NPWP Penjual | `npwp_penjual` |
| Nama Penjual | `nama_penjual` |
| Nomor Faktur Pajak | `no_faktur_pajak` |
| Tanggal Faktur Pajak | `tgl_faktur_pajak` |
| Masa Pajak | `masa_pajak` |
| Tahun | `tahun` |
| Masa Pajak Pengkreditkan | `masa_pajak_pengkreditan` |
| Tahun Pajak Pengkreditan | `tahun_pajak_pengkreditan` |
| Status Faktur | `status_faktur` |
| Harga Jual/Penggantian/DPP | `harga_jual` |
| DPP Nilai Lain/DPP | `dpp` |
| PPN | `ppn` |
| PPnBM | `ppnbm` |
| Perekam | `perekam` |
| Referensi | `referensi` |
| Nomor SP2D | `no_sp2d` |
| Valid | `valid` (bool) |
| Dilaporkan | `dilaporkan` (bool) |
| Dilaporkan oleh Penjual | `dilaporkan_oleh_penjual` (bool) |

#### Format 2 (baru — pemetaan yang diusulkan)

| Header Excel | DB column | Catatan |
|---|---|---|
| NPWP Pembeli / Identitas lainnya | `npwp_penjual` (reuse) | SPT = pembeli; disimpan di kolom NPWP yang ada |
| Nama Pembeli | `nama_penjual` (reuse) | Sama alasan di atas |
| Kode Transaksi | `kode_transaksi` | **BARU** |
| Nomor Faktur Pajak | `no_faktur_pajak` | |
| Tanggal Faktur Pajak | `tgl_faktur_pajak` | |
| Masa Pajak | `masa_pajak` | |
| Tahun | `tahun` | |
| Status Faktur | `status_faktur` | |
| ESignStatus | `esign_status` | **BARU** |
| Harga Jual/Penggantian/DPP | `harga_jual` | |
| DPP Nilai Lain/DPP | `dpp` | |
| PPN | `ppn` | |
| PPnBM | `ppnbm` | |
| Penandatanganan | `penandatanganan` | **BARU** |
| Referensi | `referensi` | |
| Metode Input | `metode_input` | **BARU** |
| Dilaporkan oleh | `dilaporkan_oleh_penjual` (bool) | ⚠️ lihat keputusan di §4 |
| IsShowClearName | `is_show_clear_name` | **BARU** |
| Uraian | `uraian` | **BARU** |

> **Pembeli (buyer) di Format 2 di-masukkan ke kolom `npwp_penjual`/`nama_penjual`** karena itu adalah
> NPWP/Nama pemilik SPT itu sendiri (bukan lawan transaksi). Ini agar konsisten dengan responses
> equalization & GL yang memakai `npwp_penjual` sebagai identitas SPT. Jika ternyata Format 2
> "Pembeli" merujuk ke penjual/vendor (lawan transaksi), maka perlu kolom baru — didekonsiliasi di §4.

### 2.4 Database — Migration Baru

Buat migration baru `add_multi_format_columns_to_eqtax_coretax_spt_table`:

```php
Schema::table('eqtax_coretax_spt', function (Blueprint $table) {
    $table->string('kode_transaksi')->nullable()->after('tahun');
    $table->string('esign_status')->nullable()->after('status_faktur');
    $table->string('penandatanganan')->nullable()->after('ppnbm');
    $table->string('metode_input')->nullable()->after('referensi');
    $table->string('uraian')->nullable()->after('metode_input');
    $table->boolean('is_show_clear_name')->nullable()->after('uraian');
});
```

### 2.5 Model — `EQTAXCoretaxSPT`

Tambahkan kolom baru ke array `$fillable`:

```
'kode_transaksi', 'esign_status', 'penandatanganan', 'metode_input', 'uraian', 'is_show_clear_name'
```

> `updateField()` memakai `getFillable()` sebagai whitelist → kolom baru otomatis bisa diedit inline
> (aman).

### 2.6 Controller — `SPTCoretaxController::import()` (refactor)

Ubah dari:

```php
$excel = Excel::import(new EQTaxImport, $request->file('file'));
if ($excel) { ... success ... } else { ... error ... }  // dead code
```

Menjadi (pola `GLController::import`):

```php
$import = new SPTSheetImport($request->file('file'));
Excel::import($import, $request->file('file'));

if (empty($import->result)) {
    return redirect()->route("eqtax.spt.coretax.index")
        ->with("error", "Import SPT Coretax Gagal, Data Kosong");
}

DB::transaction(function () use ($import) {
    foreach (array_chunk($import->result, 500) as $chunk) {
        EQTAXCoretaxSPT::insert($chunk);
    }
});

return redirect()->route("eqtax.spt.coretax.index")
    ->with("success", "Import SPT Coretax Berhasil");
```

Keuntungan: deteksi data kosong, transaksi atomik, chunking untuk performa file besar.

### 2.7 Penanganan Tanggal & Boolean

- `tgl_faktur_pajak`: parse eksplisit dari nilai Excel (`2026-02-18T00:00:00` atau serial number) menjadi
  string datetime, mis. `\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject()` bila perlu.
- Boolean Format 2 (`IsShowClearName`, `Dilaporkan oleh`): pakai `parseBoolean()` yang sudah ada
  (pindahkan ke method shared di class baru).
- Nilai non-numerik (`#N/A`, `#VALUE!`, kosong) → skip baris / set `null`.

---

## 3. Files yang Perlu Diubah / Dibuat

| No | File | Tipe | Perubahan |
|----|------|------|-----------|
| 1 | `app/Imports/SPTSheetImport.php` | **BARU** | Dispatcher multi-sheet (`WithMultipleSheets`) |
| 2 | `app/Imports/SPTSingleSheetImport.php` | **BARU** | Importer per sheet + deteksi Format 1/2 |
| 3 | `database/migrations/xxxx_add_multi_format_columns_to_eqtax_coretax_spt_table.php` | **BARU** | Tambah 6 kolom baru |
| 4 | `app/Http/Controllers/EQTax/SPTCoretaxController.php` | Ubah | Refactor `import()` → `SPTSheetImport` + transaction/chunk |
| 5 | `app/Models/EQTAXCoretaxSPT.php` | Ubah | Tambah field ke `$fillable` |
| 6 | `app/Imports/EQTaxImport.php` | Dipertahankan / hapus | Bisa dipertahankan sementara untuk Format 1, atau dihapus bila logic dipindah ke `SPTSingleSheetImport` |

### Tidak Perlu Diubah

- `resources/views/eqtax/spt/coretax/index.blade.php` — tampilan tabel tetap sama (kolom query data
  tidak berubah untuk format 1; opsional tampilkan kolom baru bila diinginkan).
- Route (`routes/routers/eqtax.php`) — route `import` sudah ada, hanya controller logic yang berubah.
- Equalization / Dashboard — tidak terkait karena hanya membaca kolom yang sudah ada
  (`no_faktur_pajak`, `dpp`, `ppn`, `masa_pajak`, `tahun`, `entity`).

---

## 4. Keputusan yang Perlu Dikonfirmasi User

1. **`NPWP Pembeli` / `Nama Pembeli` di Format 2** — apakah disimpan ke kolom `npwp_penjual` /
   `nama_penjual` (reuse, karena SPT = identitas pembeli yang sama), atau perlu kolom baru
   `npwp_pembeli` / `nama_pembeli`? Rekomendasi: **reuse** agar tidak mengganggu ekualisasi yang
   memakai `npwp_penjual`.

2. **`Dilaporkan oleh` di Format 2** — nilainya boolean (TRUE/FALSE) atau teks (nama pihak yang
   melaporkan)? Rekomendasi: reuse kolom boolean `dilaporkan_oleh_penjual` jika nilainya boolean.

3. **Apakah kolom Format 2 yang memakai kolom lama (`harga_jual`, `dpp`, `ppn`, `ppnbm`, `no_faktur_pajak`,
   dst.) perlu dibedakan** dari Format 1 / perlu penanda sumber format? Rekomendasi: tidak perlu —
   keduanya record SPT yang sama; cukup dua format di-merge jadi satu.

4. **Apakah kolom baru perlu ditampilkan di halaman SPT Coretax**, atau cukup tersimpan di DB?
   Rekomendasi: cukup tersimpan dulu (bisa ditambahkan tampilan di iterasi berikutnya).

---

## 5. Edge Cases

| Skenario | Penanganan |
|----------|------------|
| File tidak punya sheet sama sekali | Deteksi `empty($import->result)` → pesan error |
| Sheet dengan header tak dikenal (bukan Format 1/2) | Skip sheet tersebut / log, jangan error |
| Baris kosong / `#N/A` / `#VALUE!` | Skip baris |
| Tanggal format Excel serial number | Konversi eksplisit ke datetime |
| Data besar (> ribuan baris) | `array_chunk(..., 500)` + `DB::transaction` |
| 2 sheet Format 1 + 1 sheet Format 2 dalam satu file | Semua di-merge ke `$import->result` & di-insert bersama |
| Nilai non-numerik pada kolom jumlah (`ppn`, `dpp`) | Set `0` / `null`, validasi |

---

## 6. Checklist Implementasi

- [ ] Buat planning file ini
- [ ] Migration: tambah 6 kolom baru (`kode_transaksi`, `esign_status`, `penandatanganan`, `metode_input`, `uraian`, `is_show_clear_name`)
- [ ] Update model `EQTAXCoretaxSPT` `$fillable`
- [ ] Buat `SPTSheetImport` (dispatcher `WithMultipleSheets`)
- [ ] Buat `SPTSingleSheetImport` (deteksi Format 1/2, mapping kolom, parsing tanggal/bool)
- [ ] Refactor `SPTCoretaxController::import()` (transaction + chunk + deteksi kosong)
- [ ] Jalankan `php artisan migrate`
- [ ] Uji upload file 3 sheet → cek semua baris masuk ke DB
- [ ] Uji upload file 1 sheet Format 1 (regresi)
- [ ] Uji upload file 1 sheet Format 2
- [ ] Uji upload file kosong / salah format → pesan error
- [ ] (Opsional) Tampilkan kolom baru di view SPT
