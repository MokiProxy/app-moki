# Cara Menggunakan Fitur Merge Flows (Alur Birokrasi)

## Apa Itu?

Fitur ini menggabungkan beberapa jenis dokumen (contoh: Berita Acara + Invoice + Slip Pembayaran) menjadi satu file PDF final. Sistem akan mendeteksi secara otomatis apakah semua dokumen dalam satu alur sudah lengkap, lalu menggabungkannya.

---

## Langkah 1: Setup Permissions

Tambahkan permission berikut ke database (viaSeeder atau manual di tabel `roles_has_permissions`):

```
dokter.merge-flows.view
dokter.merge-flows.create
dokter.merge-flows.edit
dokter.merge-flows.delete
```

---

## Langkah 2: Buat Alur Birokrasi

1. Login ke aplikasi
2. Klik menu **Alur Birokrasi** di sidebar
3. Klik tombol **Tambah Alur**
4. Isi form:
   - **Nama Alur**: `BA-INV-SP`
   - **Deskripsi**: `Alur birokrasi: Berita Acara → Invoice → Slip Pembayaran`
   - **Step 1**: Pilih `Berita Acara`, Link Regex dikosongkan (ini dokumen root/induk)
   - **Step 2**: Pilih `Invoice`, Link Regex: `/No\s*BA\s*\n?\s*:\s*(.+)/i`, Link Label: `No BA`
   - **Step 3**: Pilih `Slip Pembayaran`, Link Regex: `/No\s*Inv\s*\n?\s*:\s*(.+)/i`, Link Label: `No Inv`
5. Klik **Simpan**

---

## Langkah 3: Seed Data (Alternatif)

Jika sudah ada document types BA, INV, dan SP, jalankan seeder:

```bash
php artisan db:seed --class=MergeFlowSeeder
```

---

## Cara Kerja Sistem

### Flow Normal (Urut)

```
Step 1: Scan/Scan BA
         │
         ├── OCR membaca dokumen
         ├── Sistem upload ke FTP: BERITA ACARA/VENDOR/BA_VENDOR_BA0001.pdf
         ├── Karena BA adalah root (order 1), sistem membuat Merge Group baru
         └── Status: Pending (1/3 dokumen)

Step 2: Scan Invoice
         │
         ├── OCR membaca dokumen
         ├── Sistem upload ke FTP: INVOICE/VENDOR/INV_VENDOR_INV0001.pdf
         ├── Sistem ekstrak "No BA: BA0001" dari teks OCR Invoice
         ├── Sistem cari Merge Group yang punya BA0001
         └── Status: Pending (2/3 dokumen)

Step 3: Scan Slip Pembayaran
         │
         ├── OCR membaca dokumen
         ├── Sistem upload ke FTP: PEMBAYARAN/VENDOR/SP_VENDOR_SP0001.pdf
         ├── Sistem ekstrak "No Inv: INV0001" dari teks OCR SP
         ├── Sistem cari Merge Group via INV0001 → BA0001
         ├── Semua dokumen lengkap (3/3) → TRIGGER FINAL MERGE
         ├── Download BA + INV + SP dari FTP
         ├── Gabung jadi 1 PDF
         └── Upload ke: FINAL/VENDOR/FINAL_VENDOR_BA0001_INV0001_SP0001.pdf
```

### Flow Tidak Urut (Invoice duluan)

```
1. Scan Invoice → "No BA: BA0001" → BA belum ada → Buat group baru (pending)
2. Scan BA → root document → Tambahkan ke group yang sudah ada
3. Scan SP → complete → trigger final merge
```

### Vendor Berbeda

```
- BA untuk "MADHANI TALATAH NUSANTARA" → group terpisah
- BA untuk "PT. LAINNYA" → group terpisah lainnya
- Vendor adalah bagian dari kunci unik group
```

---

## Contoh Penggunaan Lengkap

### Kasus: Proses Penggabungan Dokumen Vendor MADHANI

#### 1. Upload Berita Acara

```
File: BA_MADHANI TALATAH NUSANTARA_BA0001.pdf
FTP Path: BERITA ACARA/MADHANI TALATAH NUSANTARA/BA_MADHANI TALATAH NUSANTARA_BA0001.pdf

Hasil OCR:
- No BA: BA0001
- Vendor: MADHANI TALATAH NUSANTARA

Sistem:
→ Buat Merge Group: {vendor: "MADHANI TALATAH NUSANTARA", root: "BA0001", status: 0}
→ Tambah Item: {doc_type: BA, order: 1, number: BA0001}
→ Status: Pending (1/3)
```

#### 2. Upload Invoice

```
File: INV_MADHANI TALATAH NUSANTARA_INV0001.pdf
FTP Path: INVOICE/MADHANI TALATAH NUSANTARA/INV_MADHANI TALATAH NUSANTARA_INV0001.pdf

Hasil OCR:
- No Inv: INV0001
- No BA: BA0001  ← ini yang di-link
- Vendor: MADHANI TALATAH NUSANTARA

Sistem:
→ Ekstrak linked_number: BA0001
→ Cari Merge Group root_number = "BA0001" → DITEMUKAN
→ Tambah Item: {doc_type: INV, order: 2, number: INV0001}
→ Status: Pending (2/3)
```

#### 3. Upload Slip Pembayaran

```
File: SP_MADHANI TALATAH NUSANTARA_SP0001.pdf
FTP Path: PEMBAYARAN/MADHANI TALATAH NUSANTARA/SP_MADHANI TALATAH NUSANTARA_SP0001.pdf

Hasil OCR:
- No SP: SP0001
- No Inv: INV0001  ← ini yang di-link
- Vendor: MADHANI TALATAH NUSANTARA

Sistem:
→ Ekstrak linked_number: INV0001
→ Cari Merge Group via INV0001 → BA0001 → DITEMUKAN
→ Tambah Item: {doc_type: SP, order: 3, number: SP0001}
→ Total items (3) >= total steps (3) → COMPLETE
→ Trigger Final Merge:
   - Download BA + INV + SP dari FTP
   - Gabung jadi 1 PDF
   - Upload: FINAL/MADHANI TALATAH NUSANTARA/FINAL_MADHANI TALATAH NUSANTARA_BA0001_INV0001_SP0001.pdf
→ Status: Selesai (2)
```

---

## Melihat Status Grup

1. Klik menu **Alur Birokrasi** di sidebar
2. Scroll ke bagian **Grup Penggabungan Terbaru**
3. Atau klik tombol **Grup Penggabungan** untuk melihat semua grup

### Filter Grup

- **Status**: Pending, Lengkap, Selesai
- **Vendor**: Ketik nama vendor untuk mencari

### Keterangan Status

| Status | Badge | Keterangan |
|--------|-------|------------|
| Pending | Kuning | Belum semua dokumen lengkap |
| Lengkap | Biru | Semua dokumen sudah ada, menunggu merge |
| Selesai | Hijau | File final sudah digabung dan diupload |

---

## Membuat Alur Baru (Extensible)

Sistem ini dirancang extensible. Anda bisa membuat alur baru selain BA-INV-SP:

### Contoh: Alur SPK-PO-GR

1. Buat document types baru: SPK, PO, GR (jika belum ada)
2. Klik **Tambah Alur**
3. Isi:
   - Nama: `SPK-PO-GR`
   - Step 1: SPK (root, tanpa link regex)
   - Step 2: PO (link ke SPK via regex)
   - Step 3: GR (link ke PO via regex)

### Contoh: Alur QUOTATION-CONTRACT-SO

1. Buat document types: QUOTATION, CONTRACT, SO
2. Buat alur baru:
   - Step 1: QUOTATION (root)
   - Step 2: CONTRACT (link ke QUOTATION)
   - Step 3: SO (link ke CONTRACT)

---

## Tips Link Regex

Link regex digunakan untuk mengekstrak nomor dokumen induk dari teks OCR.

### Contoh Regex Umum

```
# Ekstrak nomor BA dari Invoice
/No\s*BA\s*\n?\s*:\s*(.+)/i

# Ekstrak nomor Invoice dari SP
/No\s*Inv\s*\n?\s*:\s*(.+)/i

# Ekstrak nomor SPK dari PO
/No\s*SPK\s*\n?\s*:\s*(.+)/i

# Ekstrak nomor PO dari GR
/No\s*PO\s*\n?\s*:\s*(.+)/i
```

### Tips:
- Gunakan `(.+)` untuk menangkap nomor dokumen
- Tambahkan `/i` di akhir untuk case-insensitive
- Gunakan `\s*` untuk spasi fleksibel
- Gunakan `\n?` untuk newline opsional

---

## Troubleshooting

### Dokumen tidak masuk group

**Penyebab**: Link regex tidak match dengan teks OCR

**Solusi**:
1. Cek teks OCR di folder `storage/app/private/scanner/ocr-results/`
2. Pastikan regex sesuai dengan format teks OCR
3. Edit alur dan perbaiki link regex

### Vendor tidak cocok

**Penyebab**: Vendor pada dokumen berbeda

**Solusi**: Pastikan semua dokumen dalam satu alur punya vendor yang sama

### Final merge gagal

**Penyebab**: File tidak ditemukan di FTP

**Solusi**:
1. Cek apakah semua dokumen sudah ter-upload ke FTP
2. Cek path FTP di tabel `document_merge_group_items`
3. Upload ulang dokumen yang missing

### File final tidak muncul di FTP

**Cek**:
1. Folder `FINAL/{NAMA VENDOR}/` di FTP
2. Tabel `document_merge_groups` → kolom `final_pdf_path`
3. Log di tabel `scan_logs` → event `final_merge_completed` atau `final_merge_failed`

---

## FTP Path Structure Setelah Implementasi

```
ftp_final/
├── BERITA ACARA/
│   └── MADHANI TALATAH NUSANTARA/
│       └── BA_MADHANI TALATAH NUSANTARA_BA0001.pdf
├── INVOICE/
│   └── MADHANI TALATAH NUSANTARA/
│       └── INV_MADHANI TALATAH NUSANTARA_INV0001.pdf
├── PEMBAYARAN/
│   └── MADHANI TALATAH NUSANTARA/
│       └── SP_MADHANI TALATAH NUSANTARA_SP0001.pdf
└── FINAL/                              ← Hasil merge
    └── MADHANI TALATAH NUSANTARA/
        └── FINAL_MADHANI TALATAH NUSANTARA_BA0001_INV0001_SP0001.pdf
```

---

## Toggle Fitur

Untuk menonaktifkan fitur merge flow tanpa menghapus kode, tambahkan di `.env`:

```
MERGE_FLOW_ENABLED=false
```

Untuk mengaktifkan kembali:

```
MERGE_FLOW_ENABLED=true
```

Atau hapis baris tersebut (default: aktif).
