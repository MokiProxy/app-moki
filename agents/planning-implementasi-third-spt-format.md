# Planning: Implementasi Third SPT Format (Format 3)

## Overview

Setelah implementasi multi-format (Format 1 & Format 2) pada fitur upload SPT Coretax, kini file SPT
yang diupload akan memiliki **3 sheet** dengan **3 format berbeda**:

- **2 format sudah berjalan** — Format 1 (`NPWP Penjual` dsb.) & Format 2 (`NPWP Pembeli` dsb.)
- **1 format baru (Format 3)** — kolom `NPWP Penjual` dsb., dengan karakteristik berbeda.

Tujuan: Tambahkan deteksi & pemetaan **Format 3** pada importer SPT, sesuaikan skema DB untuk kolom
yang belum ada, dan pastikan bisa diproses bersama Format 1 & 2 dalam satu file (3 sheet).

---

## 1. Kondisi Saat Ini (Setelah Implementasi Sebelumnya)

Arsitektur import SPT saat ini sudah multi-sheet & multi-format:

```
SPTCoretaxController::import()
   └─ SPTSheetImport (WithMultipleSheets) ── iterate semua sheet
        └─ SPTSingleSheetImport (ToArray, WithTitle)
             └─ detectFormat($header) → 'format1' | 'format2' | null
             └─ mapFormat1() / mapFormat2() → kolom ke DB
   └─ DB::transaction + array_chunk(500) + deteksi data kosong
```

### File yang relevan

| File | Peran |
|------|-------|
| `app/Http/Controllers/EQTax/SPTCoretaxController.php` | `import()` — sudah multi-sheet |
| `app/Imports/SPTSheetImport.php` | Dispatcher multi-sheet |
| `app/Imports/SPTSingleSheetImport.php` | Deteksi format + mapping per sheet |
| `app/Models/EQTAXCoretaxSPT.php` | `$fillable` |
| `database/migrations/2026_09_03_084044_...` | 6 kolom baru Format 2 sebelumnya |

### Skema DB `eqtax_coretax_spt` (saat ini)

```
id, npwp_penjual, nama_penjual, no_faktur_pajak (index), tgl_faktur_pajak (timestamp),
masa_pajak, tahun, entity,
kode_transaksi,                                   ← dari Format 2
masa_pajak_pengkreditan, tahun_pajak_pengkreditan,
status_faktur, esign_status,                      ← esign_status dari Format 2
harga_jual, dpp, ppn, ppnbm,
penandatanganan, perekam, referensi,              ← penandatanganan dari Format 2
metode_input, uraian, is_show_clear_name,         ← Format 2
no_sp2d, valid, dilaporkan, dilaporkan_oleh_penjual
```

### Cara kerja `detectFormat()` saat ini

```php
// cek header → mencari "npwppembeli" → format2, "npwppenjual" → format1, selainnya null
```

**Masalah:** Format 3 juga mengandung header `NPWP Penjual` (sama seperti Format 1), sehingga deteksi
berbasis `npwppenjual` saat ini akan **salah mengklasifikasikan Format 3 sebagai Format 1**. Perlu
cara pembeda yang lebih tegas.

---

## 2. Analisis Format 3

Kolom Format 3 (disusun user):

```
NPWP Penjual
Nama Penjual
Nomor Dokumen
Tanggal Dokumen
Jenis Transaksi
Masa Pajak
Tahun
Masa Pajak Pengkreditkan
Tahun Pajak Pengkreditan
Nilai Tagihan
DPP
PPN
PPnBM
Status
Valid
Dilaporkan
Keterangan
Perekam
Dibuat Oleh
IsShowClearName
```

### Ciri pembeda vs Format 1 & 2

| Ciri | Format 1 | Format 2 | Format 3 |
|------|----------|----------|----------|
| NPWP Penjual | ✅ | ❌ (Pembeli) | ✅ |
| NPWP Pembeli | ❌ | ✅ | ❌ |
| Nomor Dokumen | ❌ | ❌ | ✅ |
| Tanggal Dokumen | ❌ | ❌ | ✅ |
| Jenis Transaksi | ❌ | ❌ | ✅ |
| Nilai Tagihan | ❌ (Harga Jual) | ❌ | ✅ |
| Status | ❌ (Status Faktur) | ❌ (Status Faktur) | ✅ |
| Keterangan | ❌ | ❌ | ✅ |
| Dibuat Oleh | ❌ | ❌ | ✅ |

**Deteksi Format 3** tidak bisa mengandalkan `NPWP Penjual` (sama dengan Format 1). Cara paling andal:
deteksi dengan **kombinasi header unik** yang hanya ada di Format 3, mis. `Nomor Dokumen`,
`Jenis Transaksi`, `Nilai Tagihan`, `Keterangan`, atau `Dibuat Oleh`.

> Rekomendasi deteksi: pertahankan deteksi Format 1 & 2 via `npwppenjual`/`npwppembeli`, lalu
> tambahkan deteksi Format 3 via header unik (`nomordokumen` / `jenistransaksi` / `nila_tagihan` /
> `keterangan`). Urutan deteksi penting: cek header unik Format 3 & Format 2 **sebelum** `npwppenjual`
> (agar Format 3 tidak tertangkap menjadi Format 1).

---

## 3. Pemetaan Kolom Format 3 → DB

| Header Excel | DB column | Tipe | Catatan |
|---|---|---|---|
| NPWP Penjual | `npwp_penjual` | string | reuse |
| Nama Penjual | `nama_penjual` | string | reuse |
| Nomor Dokumen | `no_faktur_pajak` | string | reuse — **kunci join ekualisasi** |
| Tanggal Dokumen | `tgl_faktur_pajak` | timestamp | reuse (date) |
| Jenis Transaksi | `jenis_transaksi` | string | **BARU** |
| Masa Pajak | `masa_pajak` | string | reuse |
| Tahun | `tahun` | string | reuse |
| Masa Pajak Pengkreditkan | `masa_pajak_pengkreditan` | string | reuse |
| Tahun Pajak Pengkreditan | `tahun_pajak_pengkreditan` | string | reuse |
| Nilai Tagihan | `harga_jual` | bigint | reuse (jumlah) |
| DPP | `dpp` | bigint | reuse |
| PPN | `ppn` | bigint | reuse |
| PPnBM | `ppnbm` | bigint | reuse |
| Status | `status_faktur` | string | reuse |
| Valid | `valid` | boolean | reuse |
| Dilaporkan | `dilaporkan` | boolean | reuse |
| Keterangan | `keterangan` | string | **BARU** |
| Perekam | `perekam` | string | reuse |
| Dibuat Oleh | `dibuat_oleh` | string | **BARU** |
| IsShowClearName | `is_show_clear_name` | boolean | reuse |

### Kolom baru yang perlu ditambah DB (3)

- `jenis_transaksi` — string nullable
- `keterangan` — string nullable (tidak boleh bentrok dengan `referensi`/`uraian`)
- `dibuat_oleh` — string nullable

> **Catatan `no_faktur_pajak`**: Format 3 memakai `Nomor Dokumen` sebagai nomor unik. Karena
> ekualisasi (`EqualizationController`) melakukan join SPT↔GL berdasarkan `TRIM(no_faktur_pajak)`,
> maka `Nomor Dokumen` harus di-`map` ke kolom `no_faktur_pajak` agar data Format 3 tetap ikut
> ter-ekualisasi. Demikian juga `Tanggal Dokumen` → `tgl_faktur_pajak`.
>
> ⚠️ Keputusan konfirmasi: bila "Nomor Dokumen" justru bukan nomor faktur pajak (beda makna), perlu
> kolom baru `no_dokumen` + penyesuaian logika ekualisasi. Rekomendasi default: **reuse** ke
> `no_faktur_pajak` sesuai semantik kunci matching. (lihat §6)

---

## 4. Perubahan Database

Migration baru `add_format3_columns_to_eqtax_coretax_spt_table`:

```php
Schema::table('eqtax_coretax_spt', function (Blueprint $table) {
    $table->string('jenis_transaksi')->nullable()->after('no_sp2d');
    $table->string('keterangan')->nullable()->after('jenis_transaksi');
    $table->string('dibuat_oleh')->nullable()->after('keterangan');
});
```

> Penempatan `after()` bersifat opsional (MySQL). Di PostgreSQL `after` diabaikan — aman.
> File `.env` saat ini tampak memakai PostgreSQL (berdasarkan error `SQLSTATE[42601]` dari tes
> sebelumnya). Pastikan urutan kolom tidak dikunci logika.

### Model — `EQTAXCoretaxSPT`

Tambahkan ke `$fillable`: `jenis_transaksi`, `keterangan`, `dibuat_oleh`.
→ `updateField()` otomatis mengizinkan edit inline field ini (whitelist via `getFillable()`).

---

## 5. Perubahan Kode

### 5.1 `app/Imports/SPTSingleSheetImport.php`

1. **Perbaiki `detectFormat()`** agar Format 3 terdeteksi dengan benar dan tidak tertukar Format 1:

```php
protected function detectFormat(array $header): ?string
{
    $headerText = implode(' ', array_map(fn($c) => $this->normalize($c), $header));

    // Format 2: header Pembeli
    if (str_contains($headerText, 'npwppembeli')) {
        return 'format2';
    }

    // Format 3: header unik (Nomor Dokumen / Jenis Transaksi / Nilai Tagihan / Keterangan / Dibuat Oleh)
    if (str_contains($headerText, 'nomordokumen')
        || str_contains($headerText, 'jenistransaksi')
        || str_contains($headerText, 'nila_tagihan')
        || str_contains($headerText, 'dibuatoleh')) {
        return 'format3';
    }

    // Format 1: header Penjual
    if (str_contains($headerText, 'npwppenjual')) {
        return 'format1';
    }

    return null;
}
```

> Urutan: cek Format 2 & Format 3 **sebelum** `npwppenjual` agar Format 3 (yang punya `NPWP Penjual`)
> tidak terdeteksi sebagai Format 1.

2. **Pilih mapper** berdasarkan format:

```php
$map = match ($format) {
    'format2' => $this->mapFormat2($header),
    'format3' => $this->mapFormat3($header),
    default  => $this->mapFormat1($header),
};
```

> Versi PHP proyek: **8.3** (dikonfirmasi), sehingga `match` aman dipakai.

3. **Tambahkan `mapFormat3($header)`:**

```php
protected function mapFormat3(array $header): array
{
    $col = function (array $needles) use ($header) {
        return $this->findColumn($header, $needles);
    };

    return [
        'npwp_penjual'            => $col(['NPWP Penjual']),
        'nama_penjual'            => $col(['Nama Penjual']),
        'no_faktur_pajak'         => $col(['Nomor Dokumen', 'Nomor Dokumen Pajak']),
        'tgl_faktur_pajak'        => $col(['Tanggal Dokumen']),
        'masa_pajak'              => $col(['Masa Pajak']),
        'tahun'                   => $col(['Tahun']),
        'masa_pajak_pengkreditan' => $col(['Masa Pajak Pengkreditkan', 'Masa Pajak Pengkreditan']),
        'tahun_pajak_pengkreditan' => $col(['Tahun Pajak Pengkreditan']),
        'harga_jual'              => $col(['Nilai Tagihan']),
        'dpp'                     => $col(['DPP']),
        'ppn'                     => $col(['PPN']),
        'ppnbm'                   => $col(['PPnBM']),
        'status_faktur'           => $col(['Status']),
        'valid'                   => $col(['Valid']),
        'dilaporkan'              => $col(['Dilaporkan']),
        'keterangan'              => $col(['Keterangan']),
        'perekam'                 => $col(['Perekam']),
        'jenis_transaksi'         => $col(['Jenis Transaksi']),
        'dibuat_oleh'             => $col(['Dibuat Oleh']),
        'is_show_clear_name'      => $col(['IsShowClearName']),
    ];
}
```

4. **Syarat row valid** — `array()` saat ini mengembalikan lebih awal jika
   `$map['no_faktur_pajak'] === null`. Ini masih berlaku untuk Format 3 (karena `no_faktur_pajak`
   = `Nomor Dokumen`).

5. **`buildRecord`** — tambahkan kolom baru ke array inisialisasi (`jenis_transaksi`, `keterangan`,
   `dibuat_oleh`) dengan `null` default, agar semua baris (Format 1/2/3) konsisten → bulk insert
   dengan `array_chunk` tidak error "VALUES lists must all be the same length".

6. **`castValue`** — tidak perlu ubahan: `jenis_transaksi`/`keterangan`/`dibuat_oleh` → teks default,
   `valid`/`dilaporkan`/`is_show_clear_name` → boolean, `harga_jual`/`dpp`/`ppn`/`ppnbm` → number,
   `tgl_faktur_pajak` → date. `status_faktur` → teks.

### 5.2 Tidak perlu ubah

- `SPTSheetImport.php` — dispatcher sudah iterasi semua sheet, tak peduli format.
- `SPTCoretaxController::import()` — sudah transaction + chunk + deteksi kosong.
- `routes/routers/eqtax.php` — route import sudah ada.
- View SPT Coretax — tampilan tidak wajib menambah kolom baru (opsional).

---

## 6. Keputusan yang Perlu Dikonfirmasi

1. **`Nomor Dokumen`** → di-reuse ke `no_faktur_pajak` (agar ekualisasi tetap jalan) atau perlu kolom
   baru `no_dokumen` + perubahan logika ekualisasi? Rekomendasi: **reuse** ke `no_faktur_pajak`.

2. **`Nilai Tagihan`** → di-reuse ke `harga_jual`? Format 1/2 memakai `harga_jual` untuk nilai
   transaksi. Rekomendasi: **reuse** ke `harga_jual`.

3. **`Status`** → di-reuse ke `status_faktur` (karena `status_faktur` dipakai ekualisasi). Rekomendasi:
   **reuse**.

4. **Apakah kolom baru (`jenis_transaksi`, `keterangan`, `dibuat_oleh`) perlu ditampilkan di view SPT
   Coretax?** Rekomendasi: cukup tersimpan di DB dulu (opsional tampilkan nanti).

---

## 7. Detail Tambahan / Edge Cases

| Skenario | Penanganan |
|----------|------------|
| Deteksi Format 3 mirip Format 1 (sama punya NPWP Penjual) | Cek header unik Format 3 (`Nomor Dokumen`/`Jenis Transaksi`/`Nilai Tagihan`/`Keterangan`/`Dibuat Oleh`) sebelum fallback `npwppenjual` |
| Header tak dikenal / bukan 1/2/3 | `detectFormat()` → `null` → sheet di-skip, tidak error |
| Nilai `#N/A`, kosong, `NULL` | Di-skip / jadi `null` (sudah ditangani `buildRecord`) |
| Campuran baris Format 1/2/3 dalam satu file | Semua dinormalisasi ke kolom lengkap → chunked insert aman |
| Format 3 dengan kolom pilihan berbeda (mis. `Nomor Dokumen Pajak`) | `findColumn` multi-`needle` menerima variasi nama |
| Data non-numerik pada `Nilai Tagihan`/`DPP`/`PPN`/`PPnBM` | `toNumber()` strip non-digit → 0 |
| `IsShowClearName`/`Valid`/`Dilaporkan` bernilai boolean | `toBoolean()` (TRUE/FALSE/1/0) |

---

## 8. Checklist Implementasi

- [ ] Buat planning file ini
- [ ] Migration: tambah `jenis_transaksi`, `keterangan`, `dibuat_oleh` ke `eqtax_coretax_spt`
- [ ] Jalankan `php artisan migrate`
- [ ] Update model `EQTAXCoretaxSPT` `$fillable`
- [ ] `detectFormat()` — tambah deteksi `format3` (urutan sebelum `npwppenjual`)
- [ ] `mapFormat3($header)` — mapping kolom sesuai tabel §3
- [ ] Pilih mapper berdasar format (Format 1/2/3)
- [ ] `buildRecord` — tambah inisialisasi kolom baru (konsistensi bulk insert)
- [ ] Uji upload file 1 sheet Format 3
- [ ] Uji upload file 3 sheet (Format 1 + Format 2 + Format 3) sekaligus
- [ ] Uji regresi Format 1 & Format 2 (file lama)
- [ ] Uji upload kosong / format tak dikenal → pesan error
- [ ] Verifikasi data Format 3 ikut masuk ekualisasi (`no_faktur_pajak` join)
- [ ] (Opsional) Tampilkan kolom baru di view SPT
