# Analisis Proses Ekualisasi Restitusi Pajak

## 1. Pengertian

Ekualisasi pajak adalah proses pencocokan (reconcile) data Faktur Pajak antara:
- **SPT Pajak (Coretax)**: Data yang dilaporkan ke sistem DJP (Direktorat Jenderal Pajak) melalui platform Coretax
- **General Ledger (GL)**: Data pencatatan akuntansi internal perusahaan (PPN Masukan)

Tujuannya adalah menemukan **selisih** antara nilai PPN yang dilaporkan di SPT dengan yang tercatat di GL, yang menjadi dasar pengajuan **restitusi pajak** (klaim pengembalian PPN).

---

## 2. Struktur Data

### 2.1 File SPT Pajak (Coretax)

**Contoh**: `Coretax SPT PPN Tahun 2026_Update.xlsx`
**Sheet**: `PM_0226` (163 baris, 29 kolom)

| Kolom | Field | Keterangan |
|-------|-------|------------|
| 1 | NPWP Penjual | NPWP penjual/faktur pajak |
| 2 | Nama Penjual | Nama vendor |
| 3 | Nomor Faktur Pajak | **Key utama** untuk pencocokan |
| 4 | Tanggal Faktur Pajak | Tanggal FP |
| 5 | Masa Pajak | Bulan pelaporan (contoh: "Februari") |
| 6 | Tahun | Tahun pelaporan |
| 7 | Masa Pajak Pengkreditkan | Masa pajak pengkreditan |
| 8 | Tahun Pajak Pengkreditan | Tahun pengkreditan |
| 9 | Status Faktur | Status: CREDITED, etc. |
| 10 | Harga Jual/Penggantian/DPP | Harga jual |
| 11 | DPP Nilai Lain/DPP | Dasar Pengenaan Pajak |
| 12 | PPN | **Nilai PPN dari SPT** |
| 13 | PPnBM | Pajak Penjualan Barang Mewah |
| 14 | Perekam | Operator yang merekam |
| 15 | Referensi | Referensi invoice |
| 16 | Nomor SP2D | Nomor Surat Perintah Pencairan Dana |
| 17 | Valid | Flag validitas |
| 18 | Dilaporkan | Flag pelaporan |
| 19 | Dilaporkan oleh Penjual | Flag pelaporan oleh penjual |
| 20 | *(kosong)* | - |
| 21 | SBHO | VLOOKUP: ada/tidaknya FP di GL PPNHO |
| 22 | TJMO | VLOOKUP: ada/tidaknya FP di GL PPNMO |
| 23 | PLTR | VLOOKUP: ada/tidaknya FP di GL PPNPLTR |
| 24 | *(kosong)* | - |
| 25 | NILAI SPT | = Kolom 12 (PPN dari SPT) |
| 26 | SBHO (GL) | SUMIF: Total PPN dari GL PPNHO per FP |
| 27 | TJMO (GL) | SUMIF: Total PPN dari GL PPNMO per FP |
| 28 | PLTR (GL) | SUMIF: Total PPN dari GL PPNPLTR per FP |
| 29 | **SELISIH** | = NILAI SPT - SBHO - TJMO - PLTR |

**Penting**: Kolom 21-29 adalah **formula** (VLOOKUP/SUMIF) yang mereferensi sheet GL dari workbook lain. Formula ini tidak tersimpan di database.

### 2.2 File General Ledger (GL)

**Contoh**: `GL-0326.xlsx`
**Sheet**: `PPNMO` (79 baris), `PPNHO` (39 baris)

**Struktur Sheet PPNMO** (header ada di baris 10-11, data mulai baris 12):

| Kolom Index | Field | Keterangan |
|-------------|-------|------------|
| 1 (B) | Supplier No | Nomor supplier |
| 2 (C) | Nama Supplier | Nama vendor |
| 4 (E) | Jurnal Date | Tanggal jurnal (format: YYYYMMDD) |
| 6 (G) | Jurnal No | Nomor jurnal |
| 7 (H) | Invoice Date | Tanggal invoice |
| 9 (J) | Invoice No | Nomor invoice |
| 10 (K) | Invoice Item | Item/kode barang |
| 11 (L) | No FP | **Nomor Faktur Pajak** (key pencocokan) |
| 12 (M) | DPP | Dasar Pengenaan Pajak |
| 13 (N) | PPN Masukan | **Nilai PPN dari GL** |
| 15 (P) | Keterangan | Keterangan transaksi |

**Struktur Sheet PPNHO** (header di baris 2-3, data mulai baris 4):

| Kolom Index | Field | Keterangan |
|-------------|-------|------------|
| 0 (A) | Supplier No | Nomor supplier |
| 1 (B) | Nama Supplier | Nama vendor |
| 3 (D) | Jurnal Date | Tanggal jurnal |
| 5 (F) | Jurnal No | Nomor jurnal |
| 6 (G) | Invoice Date | Tanggal invoice |
| 8 (I) | Invoice No | Nomor invoice |
| 9 (J) | Invoice Item | Item/kode barang |
| 10 (K) | No FP | **Nomor Faktur Pajak** |
| 11 (L) | DPP | Dasar Pengenaan Pajak |
| 12 (M) | PPN Masukan | **Nilai PPN dari GL** |
| 14 (O) | Keterangan | Keterangan transaksi |

**Catatan**: 
- PPNMO = PPN Masukan Office (TJMO - Tanjung Enim Mining Operation)
- PPNHO = PPN Masukan Head Office (SBHO)
- PPNPLTR = PPN Masukan Pulau Laut (belum ada file sample)

---

## 3. Proses Ekualisasi

### 3.1 Alur Proses

```
┌─────────────────────┐     ┌─────────────────────┐
│   Import SPT Pajak  │     │   Import GL (PPN)   │
│   (Coretax Excel)   │     │   (Multi-sheet)     │
└─────────┬───────────┘     └─────────┬───────────┘
          │                           │
          ▼                           ▼
┌─────────────────────┐     ┌─────────────────────┐
│ eqtax_coretax_spt   │     │     eqtax_gl        │
│ (1 row = 1 FP)      │     │ (1 row = 1 item)    │
└─────────┬───────────┘     └─────────┬───────────┘
          │                           │
          └───────────┬───────────────┘
                      ▼
         ┌────────────────────────┐
         │   PROSES EKUALISASI    │
         │  (Pencocokan & Hitung  │
         │       Selisih)         │
         └────────────┬───────────┘
                      ▼
         ┌────────────────────────┐
         │   HASIL EKUALISASI     │
         │  (Tabel Rekonsiliasi)  │
         │  Selisih PPN = PPN SPT │
         │              - PPN GL   │
         └────────────────────────┘
```

### 3.2 Detail Proses Ekualisasi

#### Langkah 1: Normalisasi Nomor Faktur Pajak

Nomor Faktur Pajak per dinormalisasi sebelum pencocokan:
- **Trim whitespace**: Hapus spasi di awal/akhir
- **Hapus leading zeros**: Hapus angka 0 di depan (opsional, tergantung format)
- Contoh: `" 04002600044584767 "` → `"04002600044584767"`

#### Langkah 2: Agregasi GL per Nomor Faktur Pajak

Karena satu Faktur Pajak bisa memiliki **beberapa baris di GL** (per item/barang), maka perlu di-agregasi (di-sum):

```sql
SELECT 
    TRIM(no_faktur_pajak) AS no_faktur_pajak,
    sheet,
    SUM(dpp) AS dpp_gl,
    SUM(ppn) AS ppn_gl,
    COUNT(*) AS jumlah_item
FROM eqtax_gl
GROUP BY TRIM(no_faktur_pajak), sheet
```

**Contoh**: Faktur Pajak `04002600068956503` (LINGGA MAS ABADI) memiliki 6 baris di GL PPNHO:
- DPP per baris: 63.180.000, 815.235.000, 550.579.000, 39.960.000, 50.310.000, 31.812.600
- **Total DPP GL**: 1.551.076.600
- **Total PPN GL**: 170.618.426

#### Langkah 3: Pencocokan (Matching)

Lakukan pencocokan antara SPT dan GL berdasarkan **Nomor Faktur Pajak**:

```
SPT (1 row per FP)                    GL (aggregated per FP + sheet)
┌──────────────────────┐              ┌──────────────────────────────┐
│ no_faktur_pajak      │              │ no_faktur_pajak | sheet      │
│ ppn (dari SPT)       │◄────────────►│ ppn_gl (total)   | PPNMO/... │
│ dpp (dari SPT)       │              │ dpp_gl (total)              │
└──────────────────────┘              └──────────────────────────────┘
```

**Jenis pencocokan**:
1. **Match (Cocok)**: FP ada di SPT DAN ada di GL → hitung selisih
2. **SPT Only**: FP ada di SPT tapi TIDAK ada di GL → PPN GL = 0, selisih = PPN SPT
3. **GL Only**: FP ada di GL tapi TIDAK ada di SPT → PPN SPT = 0, selisih = -PPN GL

#### Langkah 4: Perhitungan Selisih

```
Selisih PPN = PPN SPT - PPN GL (total dari semua entity)
```

Berdasarkan file SPT contoh, rumus di Excel adalah:
```
SELISIH = NILAI SPT - SBHO(GL) - TJMO(GL) - PLTR(GL)
```

Dimana:
- NILAI SPT = PPN dari kolom SPT (kolom 12)
- SBHO(GL) = SUMIF PPN dari sheet PPNHO
- TJMO(GL) = SUMIF PPN dari sheet PPNMO
- PLTR(GL) = SUMIF PPN dari sheet PPNPLTR

### 3.3 Kriteria Restitusi

Berdasarkan selisih yang dihitung:

| Selisih | Keterangan | Aksi |
|---------|------------|------|
| Selisih = 0 | SPT dan GL balance | Tidak perlu aksi |
| Selisih > 0 | PPN SPT > PPN GL | **Kandidat Restitusi** (lebih bayar) |
| Selisih < 0 | PPN SPT < PPN GL | Perlu review (kurang bayar) |
| FP hanya di SPT | Belum tercatat di GL | Perlu review |
| FP hanya di GL | Tidak dilaporkan di SPT | Perlu review |

---

## 4. Temuan dari Data Sample

### 4.1 SPT Pajak (PM_0226)
- Total: 162 faktur pajak (baris 2-163)
- Masa Pajak: Februari 2026
- Status: Semua CREDITED
- Contoh FP: `04002600062730207` (KAWAN LAMA SOLUSI), PPN: 3.366.000

### 4.2 GL PPNMO
- Total: 60 baris data (baris 12-71, excl total)
- Periode: Maret 2026 (202603)
- Entity: TJMO (Tanjung Enim Mining Operation)
- Contoh: FP `04002600048819928` (PT. TRAKINDO UTAMA), DPP: 432.340, PPN: 47.557,4

### 4.3 GL PPNHO
- Total: 27 baris data (baris 4-30, excl total)
- Periode: Maret 2026
- Entity: SBHO (Head Office)
- Contoh: FP `04002600068956503` (LINGGA MAS ABADI), 6 item, total DPP: 1.551.076.600

### 4.4 Perbedaan Periode
**Penting**: File SPT adalah periode **Februari 2026**, seduning GL adalah periode **Maret 2026**. Untuk ekualisasi yang valid, **periode SPT dan GL harus sama**.

### 4.5 Perbedaan Format No. Faktur Pajak
- SPT: `04002600044584767` (tanpa spasi)
- GL: `04002600044584767 ` (dengan spasi trailing dari Excel)
- **Solusi**: TRIM() diperlukan saat pencocokan

---

## 5. SQL Query Ekualisasi (Current Implementation)

```sql
WITH gl_agg AS (
    SELECT
        TRIM(LEADING '0' FROM TRIM(no_faktur_pajak)) AS no_faktur_pajak,
        SUM(dpp) AS dpp_gl,
        SUM(ppn) AS ppn_gl
    FROM eqtax_gl
    GROUP BY TRIM(LEADING '0' FROM TRIM(no_faktur_pajak))
),
spt_norm AS (
    SELECT
        TRIM(LEADING '0' FROM TRIM(no_faktur_pajak)) AS no_faktur_pajak,
        dpp AS dpp_spt,
        ppn AS ppn_spt
    FROM eqtax_coretax_spt
)
SELECT
    COALESCE(spt.no_faktur_pajak, gl.no_faktur_pajak) AS no_faktur_pajak,
    spt.dpp_spt,
    gl.dpp_gl,
    spt.ppn_spt,
    gl.ppn_gl,
    COALESCE(spt.ppn_spt, 0) - COALESCE(gl.ppn_gl, 0) AS selisih_ppn
FROM spt_norm AS spt
FULL OUTER JOIN gl_agg AS gl
    ON spt.no_faktur_pajak = gl.no_faktur_pajak
```

### Issue dengan Query Saat Ini:
1. **Tidak ada filter periode** - Semua data GL dari semua periode di-aggregate
2. **Tidak ada pemisahan entity** - PPNMO, PPNHO, PPNPLTR di gabung jadi satu
3. **Leading zero trim bisa bermasalah** - `TRIM(LEADING '0'...)` bisa menghapus digit signifikan
4. **Tidak ada kolom entity** di tabel `eqtax_gl` untuk membedakan PPNMO/PPNHO/PPNPLTR

---

## 6. Ringkasan Alur Ekualisasi untuk Aplikasi

```
1. User upload SPT Pajak (Coretax Excel)
   → Data masuk ke tabel eqtax_coretax_spt

2. User upload GL (Multi-sheet Excel: PPNMO, PPNHO, PPNPLTR)
   → Data masuk ke tabel eqtax_gl (dengan kolom sheet sebagai penanda entity)

3. User klik "Ekualisasi"
   → Sistem melakukan:
   a. Normalisasi no_faktur_pajak (TRIM)
   b. Agregasi GL per no_faktur_pajak (SUM dpp, SUM ppn)
   c. FULL OUTER JOIN SPT ↔ GL
   d. Hitung selisih_ppn = ppn_spt - ppn_gl
   e. Tampilkan hasil dalam tabel rekonsiliasi

4. User melihat hasil:
   - FP yang match (ada di SPT & GL) dengan selisih
   - FP yang hanya ada di SPT (belum tercatat di GL)
   - FP yang hanya ada di GL (tidak dilaporkan di SPT)
   - Total selisih PPN (kandidat restitusi)
```
