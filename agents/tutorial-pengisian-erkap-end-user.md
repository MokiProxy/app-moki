# Tutorial Pengisian RKAP dari Awal sampai Akhir (untuk Pengguna / End User)

Panduan lengkap cara mengisi **Rencana Kerja dan Anggaran Perusahaan (RKAP)** melalui
aplikasi E-RKAP — mulai dari membuat periode anggaran hingga dokumen disahkan dan
didistribusikan. Ditulis khusus untuk pengguna aplikasi, tanpa istilah teknis.

---

## Daftar Isi

1. [Apa itu RKAP dan E-RKAP?](#1-apa-itu-rkap-dan-e-rkap)
2. [Siapa Saja yang Terlibat?](#2-siapa-saja-yang-terlibat)
3. [Bagan Alur Singkat](#3-bagan-alur-singkat)
4. [Sebelum Mulai (Data Pendukung)](#4-sebelum-mulai-data-pendukung)
5. [Tahap 1 — Inisiasi & Kick-off](#5-tahap-1--inisiasi--kick-off)
6. [Tahap 2 — Penyusunan (Mengisi Form 1 s.d. Form 4)](#6-tahap-2--penyusunan-mengisi-form-1-sd-form-4)
7. [Tahap 3 — Pengajuan & Persetujuan](#7-tahap-3--pengajuan--persetujuan)
8. [Tahap 4 — Konsolidasi & Review ZBB](#8-tahap-4--konsolidasi--review-zbb)
9. [Tahap 5 — Finalisasi & Pengesahan](#9-tahap-5--finalisasi--pengesahan)
10. [Tahap 6 — Pemantauan & Realisasi (Setelah Disahkan)](#10-tahap-6--pemantauan--realisasi-setelah-disahkan)
11. [Arti Status Dokumen](#11-arti-status-dokumen)
12. [Pertanyaan yang Sering Diajukan](#12-pertanyaan-yang-sering-diajukan)

---

## 1. Apa itu RKAP dan E-RKAP?

**RKAP (Rencana Kerja dan Anggaran Perusahaan)** adalah dokumen tahunan yang berisi:
- **Sasaran** perusahaan dan tiap departemen/divisi;
- **Risiko** yang mengancam tercapainya sasaran tersebut beserta upaya penanganannya;
- **Program kerja** yang akan dijalankan;
- **Anggaran biaya** (biaya rutin/operasional) dan **rencana investasi** yang dibutuhkan.

**E-RKAP** adalah aplikasi tempat RKAP ini disusun, diajukan, disetujui, dan dipantau secara
digital. Seluruh proses berjenjang: dari level perusahaan turun ke departemen, lalu naik lagi
untuk disetujui sampai tingkat direksi/komisaris.

> **Konsep penting:** RKAP disusun **berjenjang**. Anda tidak bisa mengisi program kerja
> sebelum sasaran dan risiko divisi dibuat. Urutannya sudah diatur oleh aplikasi.

---

## 2. Siapa Saja yang Terlibat?

| Peran | Tugasnya |
|-------|----------|
| **Penyusun / Cost Owner** | Orang yang paling banyak mengisi data: sasaran departemen, identifikasi risiko, program kerja, biaya rutin, dan rencana investasi. Setiap divisi punya penyusunnya sendiri. |
| **Pejabat Pembuat Komitmen (PPK)** | Memeriksa dan menyetujui **tingkat pertama** dari program kerja, biaya rutin, dan rencana investasi. Juga menilai kelayakan proposal investasi. |
| **Budget Controller** | Menyetujui **tingkat kedua** dari program kerja dan biaya rutin. Bertugas menjaga arah anggaran dan menggerakkan tahapan RKAP ke fase berikutnya. |
| **Manajemen Aset** | Menilai rencana investasi dari sisi aset (apakah perlu, layak, dan sesuai kebutuhan). |
| **Direksi Keuangan** | Menyetujui rencana investasi dan menilai dampaknya keuangan. |
| **Gate Review PT BMI** | Tim penilai investasi dari PT BMI; menyetujui tahapan akhir investasi dan mencatat keselarasan RKAP dengan arah PT BMI. |
| **BMI Admin** | Mencatat status keselarasan (alignment) RKAP dengan PT BMI. |
| **Manajer Risiko** | Mengevaluasi hasil identifikasi risiko yang diajukan penyusun (menyetujui/menolak). |
| **Direksi** | Menyetujui dokumen RKAP secara keseluruhan (tingkat direksi). |
| **Komisaris** | Menyetujui dan mengesahkan dokumen RKAP tingkat komisaris. |
| **Admin E-RKAP** | Mengelola semua data, membuat periode RKAP, mengajukan periode untuk persetujuan, dan membantu perbaikan data bila diperlukan. |

---

## 3. Bagan Alur Singkat

```
┌──────────────────────────────────────────────────────────────────┐
│ TAHAP 1 · INISiasi                                                 │
│  Admin/Controller membuat Periode RKAP (tahun)                     │
│  → mengisi jadwal & peserta Kick-off                               │
│  → mengunggah Arahan Direksi / Memo Holding                        │
└───────────────────────────────┬────────────────────────────────────┘
                                ▼
┌──────────────────────────────────────────────────────────────────┐
│ TAHAP 2 · PENYUSUNAN (oleh Cost Owner tiap divisi)                │
│  Sasaran Perusahaan → Sasaran Departemen                          │
│  → Isi & Asesmen Risiko (Form 1)                                  │
│  → Program Kerja (Form 2)                                         │
│  → Biaya Rutin (Form 3)                                           │
│  → Rencana Investasi (Form 4)                                     │
└───────────────────────────────┬────────────────────────────────────┘
                                ▼
┌──────────────────────────────────────────────────────────────────┐
│ TAHAP 3 · PENGAJUAN & PERSETUJUAN                                 │
│  Tiap dokumen diajukan oleh Cost Owner → disetujui berjenjang      │
│  (PPK → Controller / Manajemen Aset → Direksi Keuangan → Gate)     │
│  Form 1 dievaluasi Manajer Risiko                                  │
└───────────────────────────────┬────────────────────────────────────┘
                                ▼
┌──────────────────────────────────────────────────────────────────┐
│ TAHAP 4 · KONSOLIDASI & REVIEW                                    │
│  Review Zero Based Budgeting (ZBB) oleh PPK                        │
│  Konsolidasi anggaran OPEX & CAPEX                                 │
└───────────────────────────────┬────────────────────────────────────┘
                                ▼
┌──────────────────────────────────────────────────────────────────┐
│ TAHAP 5 · FINALISASI & PENGESAHAN                                 │
│  Periode RKAP diajukan → Komisaris → Direksi                       │
│  Dicatat keselarasan dengan PT BMI                                 │
│  → Disahkan → Didistribusikan → Diarsipkan                        │
└───────────────────────────────┬────────────────────────────────────┘
                                ▼
┌──────────────────────────────────────────────────────────────────┐
│ TAHAP 6 · PEMANTAUAN & REALISASI (jalannya tahun)                 │
│  Realisasi anggaran, progres program, penilaian risiko, KPI        │
└──────────────────────────────────────────────────────────────────┘
```

---

## 4. Sebelum Mulai (Data Pendukung)

Sebelum periode RKAP diisi, pastikan data dasar berikut sudah tersedia (biasanya dikelola Admin
atau bagian terkait di menu **Master Data**):

- **Elemen biaya & kode akun (chart of account)** — jenis-jenis biaya yang bisa dipakai.
- **Pusat biaya (cost center)** — tempat/milik siapa biaya itu dibebankan (per divisi).
- **Kategori & tipe risiko** — kerangka penilaian risiko (selera risiko, taksonomi, tipe).
- **Skala penilaian risiko** — kemungkinan (probabilitas), dampak, dan level skor.
- **Tingkat kepentingan sasaran (rating)** — AAA, AA, A, BBB, BB.
- **Kategori/tipe/kriteria investasi** — untuk pengisian rencana investasi.
- **Data divisi & pegawai pengguna** — agar tiap penyusun otomatis terhubung ke divisinya.

Jika data ini belum lengkap, hubungi **Admin E-RKAP**. Anda (sebagai penyusun) tidak perlu
mengurusnya sendiri.

---

## 5. Tahap 1 — Inisiasi & Kick-off

> **Dilakukan oleh:** Admin / Budget Controller.
> **Yang terlihat oleh Anda:** periode RKAP akan muncul sebagai baris baru di menu **Lifecycle RKAP**.

### 5.1 Membuat Periode RKAP
1. Buka menu **Lifecycle RKAP**.
2. Klik tombol **"+ Tambah Periode RKAP"**.
3. Isi **Periode Tahun** (contoh: `2026`).
4. Klik **Simpan**.

Periode baru muncul dengan status **Draft** dan tahapan **"Inisiasi & Kick-off"**.

### 5.2 Mengisi Kick-off / Sosialisasi
1. Klik ikon **mata (Detail)** pada periode tersebut.
2. Pada kartu **"Kick-off / Sosialisasi Penyusunan RKAP"**, klik **"Isi / Ubah"**.
3. Isi **Tanggal Kick-off**, **Catatan**, dan daftar **Peserta** (nama + divisi + hadir/tidak).
4. Klik **Simpan Jadwal & Peserta**.

### 5.3 Mengunggah Arahan Direksi / Memo Holding
1. Pada kartu **"Arahan Direksi / Memo Holding"**, klik **"Unggah / Ubah"**.
2. Unggah file **lampiran arahan** dan/atau isi **catatan arahan**.
3. Klik **Simpan Arahan**.

### 5.4 Melanjutkan ke Tahap Penyusunan
Pada panel **"Kontrol Fase"** (sebelah kanan halaman detail), klik
**"Majukan ke Penyusunan"**. Setelah tahap ini terbuka, seluruh divisi mulai bisa mengisi data.

---

## 6. Tahap 2 — Penyusunan (Mengisi Form 1 s.d. Form 4)

> **Dilakukan oleh:** Cost Owner tiap divisi.
> **Catatan:** Anda hanya melihat dan mengisi data **divisi Anda sendiri**. Divisi pada Sasaran
> Departemen sudah terisi otomatis dan tidak bisa diganti.

Urutan pengisian **wajib** mengikuti langkah berikut.

### 6.1 Sasaran Perusahaan (Form 1)
Menu **Sasaran & Asesmen Risiko → Sasaran → Sasaran Perusahaan**.

1. Klik **"+ Buat"**.
2. Pilih **Periode RKAP** yang sedang berjalan.
3. Isi **Sasaran** — pernyataan target perusahaan (contoh: *"Meningkatkan profitabilitas 10% dibanding tahun sebelumnya"*).
4. Klik **Simpan**.

### 6.2 Sasaran Departemen (Form 1)
Menu **… → Sasaran Departemen**.

1. Klik **"+ Buat"**.
2. Pilih **Sasaran Perusahaan** yang berkaitan.
3. Pilih **Tingkat Kepentingan (Rating)** dan isi **Prioritas** (1 = paling penting).
4. Isi **Sasaran** divisi Anda (contoh: *"Meningkatkan pemanfaatan aset operasi menjadi 90%"*).
5. Klik **Simpan**.

> **Ingat:** program kerja hanya bisa dibuat untuk sasaran ber-rating **A ke atas** (A, AA, AAA).

### 6.3 Identifikasi & Asesmen Risiko (Form 1)
Menu **… → Identifikasi Risiko** — lengkapi dalam empat langkah:

**(a) Identifikasi Risiko**
1. Klik **"+ Buat"**.
2. Pilih **Sasaran Departemen**, **arah risiko** (positif/negatif), **tipe risiko**, dan **taksonomi risiko**.
3. Isi teks **Risiko** — kejadian yang bisa menghambat sasaran (contoh: *"Keterlambatan penyelesaian pengadaan aset pendukung operasi"*).
4. Klik **Simpan**.

**(b) Penyebab & Dampak**
- Di menu **Penyebab Identifikasi**: tambahkan penyebab/alasan risiko (boleh lebih dari satu).
- Di menu **Dampak Identifikasi**: tambahkan dampaknya jika risiko terjadi.

**(c) Analisis dan Strategi**
- **Analisis Risiko**: pilih **probabilitas** dan **dampak**; skor & level risiko muncul otomatis.
- **Strategi Risiko Departemen**: tulis strategi penanganan **dalam bentuk teks** (contoh:
  "Mengurangi dampak lewat ..."), bisa diisi lebih dari satu per risiko.

> **Catatan untuk mengajukan Form 1:** setiap risiko wajib memiliki **strategi mitigasi**
> dan **minimal satu program kerja**. Pastikan keduanya sudah diisi sebelum mengajukan.

### 6.4 Program Kerja (Form 2 — Jadwal Kerja)
Menu **Jadwal Kerja → Program Kerja**.

Syarat: risiko yang dipilih berasal dari sasaran ber-rating **A ke atas** dan sudah punya
**strategi mitigasi**.

1. Klik **"+ Buat"**.
2. Pilih **Identifikasi Risiko** yang ingin ditangani.
3. Isi **Kode** (contoh: `WP-OPR/2026-01`), **Nama Program**, **Satuan** (unit/proyek/program).
4. Isi **Rencana Tahunan** (target satu tahun) dan **Rencana per Bulan** (12 kolom).
5. Klik **Simpan**.

> Total 12 kolom bulanan **harus sama** dengan rencana tahunan. Program kerja baru dapat
> diajukan jika sudah memiliki anggaran (biaya rutin dan/atau rencana investasi).

### 6.5 Biaya Rutin (Form 3 — Biaya Umum)
Menu **Biaya Umum → Biaya Rutin**. Satu baris = satu kebutuhan belanja.

1. Klik **"+ Buat"**.
2. Pilih **Program Kerja** yang akan dibiayai.
3. Isi **Kebutuhan** (contoh: *"Biaya ATK dan konsumsi rapat"*).
4. Pilih **Pusat Biaya (Cost Center)** dan pemiliknya.
5. Pilih **Elemen Biaya** — kode akun terisi otomatis.
6. Isi **Qty**, **Satuan**, dan **Harga Satuan**.
7. Isi **alokasi 12 bulan** (Januari–Desember).
8. Cek **Total** — otomatis dihitung (qty × harga = jumlah 12 bulan).
9. Klik **Simpan**.

> Menu **Konsolidasi OPEX** menampilkan rekap semua biaya rutin (per elemen biaya, program,
> pusat biaya) untuk keperluan pengecekan sebelum diajukan.

### 6.6 Rencana Investasi (Form 4 — Biaya Investasi)
Menu **Biaya Investasi → Rencana Investasi**.

1. Klik **"+ Buat"**.
2. Pilih **Program Kerja**, **Pusat Biaya**, dan **kode akun**.
3. Pilih **Kategori, Tipe, dan Kriteria Investasi**.
4. Isi **Nama** dan **Deskripsi** rencana (contoh: *"Peralatan Penunjang Digitalisasi"*).
5. Isi **Satuan, Qty, Harga Satuan** (Total terhitung otomatis), dan **Prioritas**.
6. **Unggah Proposal** — wajib sebelum diajukan.
7. (Opsional) Lengkapi **analisis CBA** (NPV/IRR/payback) dan lampirannya.
8. Sesuaikan **jadwal pembayaran 12 bulan** jika perlu.
9. Klik **Simpan**.

---

## 7. Tahap 3 — Pengajuan & Persetujuan

Setiap dokumen diajukan oleh Cost Owner melalui tombol **"Ajukan Persetujuan"**
(atau **"Ajukan Semua Persetujuan"** untuk mengirim banyak baris sekaligus). Setelah diajukan,
dokumen tidak bisa diubah/dihapus sampai disetujui atau ditolak.

### Siapa menyetujui apa?

| Dokumen | Alur persetujuan |
|---------|------------------|
| **Form 1 (Identifikasi Risiko)** | Diajukan → dievaluasi **Manajer Risiko** |
| **Program Kerja (Form 2)** | PPK (tingkat 1) → Budget Controller (tingkat 2) |
| **Biaya Rutin (Form 3)** | PPK (tingkat 1) → Budget Controller (tingkat 2) |
| **Rencana Investasi (Form 4)** | Tiga lapis: **PPK** → **Manajemen Aset** → **Direksi Keuangan** → **Gate Review PT BMI**. Sebelumnya ada **penilaian Gate** yang dijalankan berurutan (proposal → analisis CBA → aset → finansial → penilaian PT BMI) |
| **Periode RKAP** | **Komisaris** → **Direksi** (pada tahap finalisasi) |

### Bagaimana cara menyetujuinya?
1. Buka menu **Approval** (angka merah di samping menu menunjukkan jumlah yang menunggu).
2. Pilih kartu divisi, lalu kelompok dokumen.
3. Klik **Review** untuk melihat detail, lalu:
   - **Setujui** — langsung menyetujui; atau
   - **Tolak** — wajib menuliskan **alasan penolakan**.

> **Giliran wajib berurutan.** Level kedua tidak bisa menyetujui sebelum level pertama.
> Setiap orang hanya melihat dokumen yang memang menjadi gugus tugasnya.
> Jika ditolak, dokumen kembali ke penyusun untuk **diperbaiki lalu diajukan ulang**.

---

## 8. Tahap 4 — Konsolidasi & Review ZBB

Setelah semua dokumen disetujui, Budget Controller memajukan RKAP ke tahap
**"Konsolidasi & Review"**.

### 8.1 Review Zero Based Budgeting (ZBB)
Menu **Zero Based Budgeting → Review ZBB**:
1. Klik **Build** untuk membuat daftar review dari seluruh anggaran.
2. **PPK** membuka tiap baris yang perlu review.
3. Untuk anggaran yang **naik** dibanding sebelumnya, wajib mengisi **alasan kenaikan**.
4. Menyetujui atau menolak baris tersebut.

### 8.2 Konsolidasi Anggaran
- **OPEX (biaya rutin):** direkap lewat menu **Konsolidasi OPEX** / tampilan dashboard.
- **CAPEX (investasi):** direkap lewat menu **Anggaran Investasi** (ringkasan nilai dan
  distribusi pembayaran).

Hasil konsolidasi menjadi bahan review sebelum RKAP difinalkan.

---

## 9. Tahap 5 — Finalisasi & Pengesahan

1. **Budget Controller** memajukan RKAP ke tahap **"Finalisasi & Pengesahan"**.
2. **Admin E-RKAP** membuka menu **Lifecycle RKAP** dan mengklik tombol
   **"Ajukan Persetujuan"** pada periode yang bersangkutan.
3. **Komisaris** menyetujui (tingkat 1) melalui menu **Approval**.
4. **Direksi** menyetujui (tingkat 2). Periode RKAP berstatus **Disetujui**.
5. **Gate Review PT BMI / BMI Admin** mencatat **keselarasan dengan PT BMI**
   (status: selaras / perlu penyesuaian + catatan) lewat halaman detail RKAP.
6. **Budget Controller / Admin**:
   - Klik **"Majukan ke Disahkan"** — tanggal penetapan otomatis tercatat;
   - Klik **"Tandai Didistribusikan"** — RKAP dinyatakan tersebar ke unit terkait;
   - Klik **"Majukan ke Arsip"** — selesai.

> Setelah melewati tahap Konsolidasi, **data anggaran terkunci** dan tidak bisa diubah lagi.
> Jika ada perbaikan mendesak, hubungi Admin E-RKAP.

---

## 10. Tahap 6 — Pemantauan & Realisasi (Setelah Disahkan)

Sepanjang tahun berjalan, data berikut dicatat untuk memantau pelaksanaan:
- **Realisasi Anggaran (BvA)** — realisasi belanja dibandingkan anggaran per bulan.
- **Realisasi Program Kerja** — progres penyelesaian program.
- **Risk Assessment Bulanan** — penilaian risiko tiap bulan.
- **Performance Scorecard (KPI)** — pencapaian indikator per kuartal.
- **Proyeksi Keuangan** — rencana pendapatan, beban, dan laba rugi.

Hasilnya dapat dilihat di **Dashboard**, **Analytics & Widgets**, dan **Report Center**
(termasuk unduhan laporan Excel/PDF).

---

## 11. Arti Status Dokumen

| Status | Artinya | Bisa diubah? |
|--------|---------|--------------|
| **Draft** | Baru dibuat, belum diajukan | ✅ Bisa edit/hapus |
| **Menunggu Persetujuan** | Sudah diajukan, sedang diproses | ❌ Terkunci sampai diputuskan |
| **Disetujui** | Semua tingkat menyetujui | ❌ Terkunci |
| **Ditolak** | Ada tingkat yang menolak (dengan alasan) | ✅ Bisa diperbaiki & diajukan ulang |

---

## 12. Pertanyaan yang Sering Diajukan

**Q: Kok menu divisi di Sasaran Departemen tidak bisa saya ubah?**
A: Wajar — divisi Anda sudah otomatis terhubung dengan akun Anda. Ini menjaga data tetap
sesuai dengan divisi masing-masing.

**Q: Mengapa saya tidak bisa membuat Program Kerja?**
A: Pastikan (1) sasaran ber-rating **A ke atas**, dan (2) risiko sudah memiliki
**strategi mitigasi**. Setelah itu coba buat ulang.

**Q: Kenapa tombol "Ajukan" pada Rencana Investasi tidak bisa?**
A: Pastikan **proposal sudah diunggah** dan **jadwal pembayaran 12 bulan** sudah diisi
sesuai totalnya.

**Q: Bagaimana kalau pengajuan saya ditolak?**
A: Buka menu **Approval → Riwayat**, lihat alasan penolakan, perbaiki data, lalu ajukan ulang.

**Q: Siapa yang bisa mengajukan Periode RKAP untuk disetujui?**
A: Admin E-RKAP. Komisaris dan Direksi kemudian menyetujuinya lewat menu Approval.

**Q: Saya salah input biaya rutin, tapi statusnya sudah "Menunggu Persetujuan".**
A: Data terlanjur terkunci. Beri tahu penilai untuk **menolak** dengan alasan, lalu perbaiki
dan ajukan ulang. Atau hubungi Admin E-RKAP.

**Q: Kapan data terkunci permanen?**
A: Setelah RKAP masuk tahap **Finalisasi & Pengesahan** dan seterusnya. Pastikan seluruh data
sudah lengkap dan disetujui sebelum tahap tersebut.