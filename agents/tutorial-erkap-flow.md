# Tutorial Pengisian E-RKAP dari Awal sampai Akhir

**Tujuan dokumen ini:** membandu Anda mengisi satu periode RKAP secara lengkap, dari awal sampai dokumen selesai disahkan dan masuk arsip, lalu dilanjutkan ke monitoring bulanan.

Dokumen ini disusun untuk dibaca oleh pengguna akhir (user). Tidak ada istilah teknis di dalamnya — hanya bahasa bisnis: apa yang diisi, siapa yang mengisi, dan kapan harus diisi.

Setiap langkah disertai **kotak centang (checkbox)**. Gunakan sebagai panduan uji: satu per satu dicentang hanya setelah benar-benar dipastikan berhasil. Kotak centang yang tidak tercentang berarti ada yang belum selesai.

---

## Daftar Isi

1. [Memahami Peta Besar E-RKAP](#1-memahami-peta-besar-e-rkap)
2. [Siapa Saja yang Terlibat](#2-siapa-saja-yang-terlibat)
3. [Aturan Penting yang Harus Diketahui](#3-aturan-penting-yang-harus-diketahui)
4. [Tahap 0 — Persiapan Master Data](#tahap-0--persiapan-master-data)
5. [Tahap 1 — Inisiasi: Membuat Periode RKAP](#tahap-1--inisiasi-membuat-periode-rkap)
6. [Tahap 2 — Kick-off & Arahan Direksi](#tahap-2--kick-off--arahan-direksi)
7. [Tahap 3 — Penyusunan Form 1: Sasaran & Asesmen Risiko](#tahap-3--penyusunan-form-1-sasaran--asesmen-risiko)
8. [Tahap 4 — Penyusunan Form 2: Program Kerja](#tahap-4--penyusunan-form-2-program-kerja)
9. [Tahap 5 — Penyusunan Form 3: Biaya Rutin](#tahap-5--penyusunan-form-3-biaya-rutin)
10. [Tahap 6 — Penyusunan Form 4: Rencana Investasi](#tahap-6--penyusunan-form-4-rencana-investasi)
11. [Tahap 7 — Konsolidasi & Review](#tahap-7--konsolidasi--review)
12. [Tahap 8 — Finalisasi & Pengesahan](#tahap-8--finalisasi--pengesahan)
13. [Tahap 9 — Disahkan & Distribusi](#tahap-9--disahkan--distribusi)
14. [Tahap 10 — Arsip](#tahap-10--arsip)
15. [Tahap 11 — Monitoring & Realisasi Bulanan](#tahap-11--monitoring--realisasi-bulanan)
16. [Tahap 12 — Report Center](#tahap-12--report-center)
17. [Lampiran A — Daftar Pesan Error & Cara Mengatasinya](#lampiran-a--daftar-pesan-error--cara-mengatasinya)
18. [Lampiran B — Checklist Uji End-to-End Ringkas](#lampiran-b--checklist-uji-end-to-end-ringkas)
19. [Lampiran C — Skenario Pengujian Reset](#lampiran-c--skenario-pengujian-reset)

---

## 1. Memahami Peta Besar E-RKAP

E-RKAP (Electronic Rencana Kerja dan Anggaran Perusahaan) adalah sistem untuk menyusun **rencana kerja dan anggaran** perusahaan dalam satu tahun, lalu memantau hasilnya.

Inti dari sistem ini adalah **empat formulir berurutan**. Anda tidak boleh mengisi formulir yang lebih dulu sebelum formulir sebelumnya selesai:

| Urutan | Nama Formulir | Isi | Dikerjakan Oleh |
|---|---|---|---|
| 1 | **Form 1** — Sasaran & Asesmen Risiko | Sasaran perusahaan, sasaran departemen, dan daftar risiko | Pengisi (Pemilik Anggaran) |
| 2 | **Form 2** — Jadwal Kerja | Program kerja apa yang akan dijalankan | Pengisi (Pemilik Anggaran) |
| 3 | **Form 3** — Biaya Umum | Biaya rutin (operating) | Pengisi (Pemilik Anggaran) |
| 4 | **Form 4** — Biaya Investasi | Rencana investasi (capital) | Pengisi (Pemilik Anggaran) |

Setelah keempat formulir terisi, barulah masuk tahap konsolidasi, pengesahan, dan arsip.

### Peta alur secara visual

```
                    TAHAP 0  Persiapan Master Data
                              │
                              ▼
                    TAHAP 1  Inisiasi ── Buat Periode RKAP (tahun)
                              │
                              ▼
                    TAHAP 2  Kick-off & Arahan Direksi
                              │
                              ▼
        ┌──────────► TAHAP 3  Form 1: Sasaran & Asesmen Risiko ◄──┐
        │                            │                            │
        │                            │  Disetujui Manajemen Risiko  │
        │                            │  (data terkunci sejak        │
        │                            │   Form 1 diajukan)           │
        │                            ▼                            │
        │            TAHAP 4  Form 2: Program Kerja               │
        │                            │                            │
        │                            │  Disetujui PPK → Controller  │
        │                            ▼                            │
        │            TAHAP 5  Form 3: Biaya Rutin                 │
        │                            │                            │
        │                            │  Disetujui PPK → Controller  │
        │                            ▼                            │
        │            TAHAP 6  Form 4: Rencana Investasi           │
        │                            │                            │
        │                            │  Disetujui PPK → Manajemen   │
        │                            │  Aset → Direksi Keuangan     │
        │                            ▼                            │
        │            TAHAP 7  Konsolidasi & Review ◄───────────────┘
        │              • Review ZBB (anggaran)
        │              • Konsolidasi Anggaran Investasi
        │              • Laba Rugi (P&L)
        │              • Simulasi Skenario
        │                            │
        │                            ▼
        │            TAHAP 8  Finalisasi: Majukan fase, Ajukan Persetujuan
        │                            │  Disetujui Komisaris → Direksi
        │                            ▼
        │            TAHAP 9  Disahkan + Tandai Didistribusikan
        │                            │
        │                            ▼
        │            TAHAP 10 Arsip  ──── DATA ANGGARAN SEKARANG TERKUNCI
        │                            │
        │                            ▼
        └───── TAHAP 11 Monitoring & Realisasi (per bulan, sepanjang tahun)
```

### Enam fase lifecycle yang akan Anda lihat di sistem

Saat membuka halaman **Lifecycle RKAP**, Anda akan melihat enam fase berikut dalam bentuk lingkaran berurutan. Fase **hanya bisa dimajukan satu per satu, berurutan. Tidak bisa dilompati.**

| # | Nama Fase | Isi Fase | Bisa Tambah/Ubah Anggaran? |
|---|---|---|---|
| 1 | **Inisiasi & Kick-off** | Buat periode, jadwal & peserta kick-off, arahan direksi | ✅ Boleh |
| 2 | **Penyusunan** | Mengisi Form 1, 2, 3, 4 dan pengajuannya | ✅ Boleh |
| 3 | **Konsolidasi & Review** | Review ZBB, konsolidasi investasi, P&L | ✅ Boleh |
| 4 | **Finalisasi & Pengesahan** | Persiapan dokumen, mengajukan periode RKAP untuk persetujuan | ❌ **TERKUNCI** |
| 5 | **Disahkan** | Dokumen sudah ditandatangani, didistribusikan | ❌ **TERKUNCI** |
| 6 | **Arsip** | Dokumen disimpan sebagai arsip | ❌ **TERKUNCI** |

> **Penting:** Begitu fase berubah menjadi **Finalisasi & Pengesahan**, seluruh data anggaran (Form 3, Form 4, Rencana Pendapatan) **otomatis terkunci** dan tidak bisa ditambah atau diubah lagi. Pastikan semua angka sudah benar sebelum Anda memajukan fase ke titik ini.

---

## 2. Siapa Saja yang Terlibat

Berikut daftar orang yang terlibat dalam proses ini beserta tanggung jawabnya. Nama jabatan di bawah adalah nama peran yang muncul di sistem.

| Jabatan / Peran | Nama Peran di Sistem | Tugas Utama |
|---|---|---|
| **Pengisi / Pemilik Anggaran** | Cost Owner | Mengisi seluruh isi Form 1, 2, 3, 4. Mengetuskan datanya. Hanya melihat data **divisinya sendiri**. | Hanya melihat data **divisinya sendiri**. |
| **Pejabat Pembuat Komitmen (PPK)** | PPK | Meninjau dan menyetujui program kerja, biaya rutin, dan rencana investasi (tingkat pertama). Membuat periode RKAP, menjalankan kick-off, mengunggah arahan direksi, memajukan fase lifecycle, mengajukan dokumen periode RKAP ke approve. |
| **Manajemen Risiko** | Manajer Risiko | Meninjau dan menyetujui Form 1 (asesmen risiko). Sejak Form 1 diajukan, data Form 1 **mengunci** dan tidak bisa diubah. |
| **Budget Controller** | Controller | Meninjau dan menyetujui program kerja serta biaya rutin (tingkat kedua). |
| **Manajemen Aset** | Dept. Manajemen Aset | Meninjau dan menyetujui rencana investasi (tingkat kedua) beserta kajian kelayakan. |
| **Direksi Keuangan** | Direksi Keuangan | Menyetujui rencana investasi (tingkat ketiga). |
| **Komisaris** | Komisaris | Menyetujui dokumen Periode RKAP sebagai keseluruhan (tingkat pertama). |
| **Direksi / Direktur Utama** | Direksi | Menyetujui dokumen Periode RKAP sebagai keseluruhan (tingkat kedua). |
| **Administrator E-RKAP** | E-RKAP Admin | Mengelola data acuan, memantau proses, serta dapat mengunci atau membuka data yang terkunci. Menerima pemberitahuan saat dokumen didistribusikan. |
| **Auditor** | Auditor | Hanya melihat seluruh dokumen dan riwayat perubahan. Tidak mengubah data. |
| **Akuntansi** | Accounting | Hanya melihat data REALISASI dan keuangan. Tidak mengubah data. |

### Aturan "Hanya Satu Approver per Level"

Untuk setiap dokumen yang diajukan, sistem akan **otomatis menunjuk satu orang** dari masing-masing level persetujuan, berdasarkan peran dan divisi dokumen tersebut. Artinya:

- Jika program kerja berasal dari Divisi A, sistem akan mencari PPK **milik Divisi A** lebih dulu.
- Jika tidak ada PPK di Divisi A, sistem akan menunjuk PPK dari divisi lain.
- **Anda tidak bisa menunjuk approver secara manual.**
- Persetujuan harus berurutan: Level 1 menyetujui dulu, baru Level 2 bisa menyetujui. Level 2 **tidak bisa** menyetujui duluan — akan muncul pesan "Bukan giliran Anda".

### Peringatan penting tentang Role

Role (peran) yang dipakai sistem **bukan** jabatan di struktur organisasi, melainkan role yang diberikan administrator. Jadi orang yang secara struktur jabatan tidak punya wewenang, tetap bisa menjadi approver bila role-nya diberikan.

---

## 3. Aturan Penting yang Harus Diketahui

Baca bagian ini sebelum mulai mengisi. Beberapa aturan ini akan menghemat banyak waktu.

### 3.1 Data yang terkunci

| Yang terkunci | Kapan terkunci | Siapa yang bisa membuka |
|---|---|---|
| **Data Form 1** (sasaran departemen, identifikasi risiko, penyebab, dampak, analisis risiko, strategi) | Begitu Form 1 diajukan untuk evaluasi (tidak menunggu disetujui) | Admin E-RKAP, Super Admin, Admin, atau Auditor |
| **Data Form 3 & Form 4 & Rencana Pendapatan** | Setelah periode RKAP masuk fase **Finalisasi & Pengesahan** | Tidak bisa dibuka (kecuali reset fase) |
| **Periode RKAP itu sendiri** | Tidak bisa dihapus setelah masuk fase Finalisasi, Disahkan, atau Arsip | — |

### 3.2 Dokumen yang bisa diajukan untuk persetujuan

| Dokumen | Syarat sebelum bisa diajukan |
|---|---|
| Identifikasi Risiko (Form 1) | Harus sudah ada **Strategi Mitigasi** dan **Program Kerja** |
| Program Kerja (Form 2) | Harus sudah ada **anggaran** (Form 3 atau Form 4). Persentase bulanan harus sudah lengkap dan totalnya sama dengan Rencana Tahunan |
| Biaya Rutin (Form 3) | Data sudah lengkap dan benar |
| Rencana Investasi (Form 4) | **Wajib** sudah mengunggah file **Proposal** (PDF/DOC) |
| Periode RKAP | Sudah disusun, biasanya saat fase **Finalisasi** |

### 3.3 Aturan angka dan format

- Semua nominal uang ditulis dalam **Rupiah** dengan pemisah ribuan, contoh: `1.500.000`
- Kolom **Total** di Biaya Rutin, Rencana Investasi, Rencana Pendapatan, dan Rencana Beban **tidak bisa diisi manual**. Nilainya dihitung otomatis oleh sistem.
- Persentase di Program Kerja harus di antara **0 sampai 100**.
- Di Risk Assessment Bulanan, angka peluang dan dampak diisi antara **1 sampai 10**.

### 3.4 Aturan Rating Sasaran

Program Kerja **hanya bisa dibuat untuk sasaran departemen yang punya rating A ke atas**. Jika muncul pesan *"Program kerja hanya bisa dibuat untuk sasaran dengan rating A ke atas"*, berarti sasaran departemen yang Anda pilih ratingnya terlalu rendah. Pilih sasaran lain atau minta perubahan rating.

### 3.5 Penguncian Tombol Edit dan Hapus

Begitu data disetujui, tombol **Edit** dan **Hapus** akan hilang atau tidak bisa diklik. Itu normal, bukan error.

---

## TAHAP 0 — Persiapan Master Data

> **Siapa:** Administrator E-RKAP / PPK / yang ditunjuk untuk menyiapkan data acuan
> **Kapan:** Sekali di awal, sebelum periode RKAP baru dibuat
> **Kenapa:** Semua formulir Form 1–4 mengambil pilihan dari data-data di bawah. Kalau masih kosong, pilihan di formulir tidak akan muncul.

### 0.1 Chart of Accounts (Daftar Akun)

Menu: **Master Data → Chart of Accounts → Chart of Accounts**

```
Tombol: Tambah Chart of Account
```

| Isi | Keterangan |
|---|---|
| **Kode** | Wajib. Contoh: `6000000000000000` |
| **Tipe** | Wajib. Pilih: **Pendapatan (Revenue)** atau **Beban (Expense)** |
| **Nama Akun** | Wajib. Contoh: `Pendapatan Penjualan` |
| **Deskripsi** | Opsional |

- [ ] Minimal ada akun **Pendapatan** (dipakai di Menu Financial Projection → Rencana Pendapatan)
- [ ] Minimal ada akun **Beban** (dipakai di Menu Financial Projection → Rencana Beban)

---

### 0.2 Kategori Elemen Biaya

Menu: **Master Data → Chart of Accounts → Kategori Elemen Biaya**

```
Tombol: Tambah Kategori
```

| Isi | Keterangan |
|---|---|
| **Nama** | Wajib. Contoh: `Beban Pegawai` |

- [ ] Kategori sudah dibuat
- [ ] Tidak bisa dihapus kalau sudah dipakai Elemen Biaya (pesan: *"Kategori tidak dapat dihapus karena masih memiliki elemen biaya!"*)

---

### 0.3 Elemen Biaya

Menu: **Master Data → Chart of Accounts → Elemen Biaya**

```
Tombol: Tambah Elemen Biaya
```

| Isi | Keterangan |
|---|---|
| **Kode** | Wajib |
| **Kategori Elemen Biaya** | Wajib. Pilih dari daftar yang sudah dibuat di 0.2 |
| **Chart of Account** | Wajib. Pilih dari daftar akun di 0.1 |
| **Nama** | Wajib. Contoh: `Gaji Pokok` |

- [ ] Minimal ada elemen biaya untuk **Beban** (dipakai di Form 3)
- [ ] Minimal ada elemen biaya untuk **Pendapatan** (dipakai di Form 4 /Pendapatan)

---

### 0.4 Pusat Biaya (Cost Center)

Menu: **Master Data → Pusat Biaya (Cost Center)**

```
Tombol: Tambah Pusat Biaya
```

| Isi | Keterangan |
|---|---|
| **Kode** | Wajib. Contoh: `F0120215109100` |
| **Divisi** | Wajib. Pilih divisi pemilik biaya |
| **Nama** | Wajib |
| **Pemilik** | Wajib. Nama orang penanggung jawab |
| **Swakelola** | Opsional (centang). Centang jika termasuk kategori Swakelola (segmen kode ke-4 = 510) |
| **Biaya Tersentralisasi** | Opsional. Biaya terpusat (misal gaji → HR, TI → Departemen IT) hanya boleh diisi oleh departemen koordinator |
| **Departemen Koordinator** | Wajib **hanya jika** Biaya Tersentralisasi aktif |

> **Penting untuk Pengisi:** Form 3 (Biaya Rutin) memisahkan antara **Cost Center Swakelola** dan **Cost Center Non Swakelola**. Pastikan Anda tahu biaya Anda masuk kategori mana, dan pusat biayanya sudah terdaftar.

- [ ] Pusat biaya untuk divisi Anda sudah terdaftar
- [ ] Status Swakelola sudah benar sesuai kebijakan perusahaan Anda

---

### 0.5 Risk Appetite (Selera Risiko)

Menu: **Master Data → Risk Taxonomy → Risk Appetites**

```
Tombol: Tambah Risk Appetite
```

| Isi | Keterangan |
|---|---|
| **Nama** | Wajib. Contoh: `Tidak adventurous` |

- [ ] Risk Appetite sudah dibuat

---

### 0.6 Risk Taxonomy

Menu: **Master Data → Risk Taxonomy → Risk Taxonomies**

```
Tombol: Tambah Risk Taxonomy
```

| Isi | Keterangan |
|---|---|
| **Nama** | Wajib |
| **Risk Appetite** | Wajib. Pilih dari 0.5 |

- [ ] Risk Taxonomy sudah dibuat

---

### 0.7 Risk Type

Menu: **Master Data → Risk Taxonomy → Risk Types**

```
Tombol: Tambah Risk Type
```

| Isi | Keterangan |
|---|---|
| **Nama** | Wajib. Contoh: `Risiko Operasional` |
| **Risk Taxonomy** | Wajib. Pilih dari 0.6 |

- [ ] Risk Type sudah dibuat
- [ ] Risk Type sudah tersedia untuk divisi Anda

---

### 0.8 Rating Criteria (Kriteria Rating)

Menu: **Master Data → Rating Criteria**

```
Tombol: Tambah Rating Criteria
```

| Isi | Keterangan |
|---|---|
| **Rating** | Wajib. Contoh: `A`, `B`, `C` |
| **Kualifikasi** | Wajib. Contoh: `Sangat Baik` |
| **Deskripsi** | Wajib |

> **Penting:** Rating ini akan menentukan kelayakan Program Kerja nanti (lihat 3.4). Pastikan terdapat rating **A** agar sasaran bisa dibuat program kerja.

- [ ] Rating criteria sudah dibuat, dan **rating A tersedia**

---

### 0.9 Risk Matrix (Skala Risiko)

Menu: **Master Data → Risk Matrix**

Dibuat dalam empat halaman berurutan. Urutan penting — jangan dibalik.

**a) Risk Scales**

```
Tombol: Tambah Risk Scale
```

| Isi | Keterangan |
|---|---|
| **Scale** | Wajib. Angka (1, 2, 3, ...) |
| **Level** | Wajib. Contoh: `Rendah` |

- [ ] Risk Scale sudah dibuat

**b) Risk Probabilities**

```
Tombol: Tambah Risk Probability
```

| Isi | Keterangan |
|---|---|
| **Nama** | Wajib. Contoh: `Sangat Jarang` |
| **Point** | Wajib. Angka (1 = terendah) |

- [ ] Risk Probability sudah dibuat (disarankan 5 tingkat)

**c) Risk Impacts**

```
Tombol: Tambah Risk Impact
```

| Isi | Keterangan |
|---|---|
| **Nama** | Wajib. Contoh: `Tidak Signifikan` |
| **Point** | Wajib. Angka (1 = terendah) |

- [ ] Risk Impact sudah dibuat (disarankan 5 tingkat)

**d) Risk Score & Levels**

```
Tombol: Tambah Risk Score Level
```

| Isi | Keterangan |
|---|---|
| **Risk Probability** | Wajib. Pilih dari (b) |
| **Risk Impact** | Wajib. Pilih dari (c) |
| **Score** | Wajib. Angka (hasil perkalian Point Probability × Point Impact) |
| **Level** | Wajib. Pilih: **Low**, **Low To Moderate**, **Moderate**, **Moderate To High**, **High** |

> **Penting:** combinationssetiap pasangan (Probability × Impact) harus punya level. Kalau ada pasangan yang belum diisi, kolom **Skor & Level** di Form 1 (Analisis Risiko) tidak akan terisi otomatis.

- [ ] Semua pasangan Probability × Impact sudah memiliki Level
- [ ] Minimal ada level **Moderate** atau **Low** supaya risiko bisa dianalisis

---

### 0.10 Investment Criteria (Kriteria Investasi)

Menu: **Master Data → Investment Criteria**

Dibuat dalam tiga halaman:

**a) Investation Types**

```
Tombol: Tambah Investation Type
```

| Isi | Keterangan |
|---|---|
| **Code** | Wajib |
| **Name** | Wajib |

- [ ] sudah dibuat

**b) Investation Criterias**

```
Tombol: Tambah Investation Criterias
```

| Isi | Keterangan |
|---|---|
| **Code** | Wajib |
| **Name** | Wajib |

- [ ] sudah dibuat

**c) Investattion Categories**

```
Tombol: Tambah Investattion Category
```

| Isi | Keterangan |
|---|---|
| **Code** | Wajib |
| **Name** | Wajib |

- [ ] sudah dibuat

---

### ✅ Checkbox Penutup Tahap 0

- [ ] Chart of Accounts (Pendapatan & Beban) tersedia
- [ ] Kategori Elemen Biaya tersedia
- [ ] Elemen Biaya tersedia
- [ ] Pusat Biaya tersedia
- [ ] Risk Appetite, Risk Taxonomy, Risk Type tersedia
- [ ] Rating Criteria tersedia (termasuk rating A)
- [ ] Risk Matrix lengkap (Scale, Probability, Impact, Score Level)
- [ ] Investment Criteria lengkap (Type, Criteria, Category)

> **Jika semua sudah centang, lanjut ke TAHAP 1.**

---

## TAHAP 1 — Inisiasi: Membuat Periode RKAP

> **Siapa:** PPK (Pejabat Pembuat Komitmen)
> **Kapan:** Awal tahun anggaran, sebelum formulir diisi
> **Fase:** Inisiasi & Kick-off

### 1.1 Membuat Periode RKAP

Menu: **Lifecycle RKAP**

```
Tombol: Tambah Periode RKAP
```

| Isi | Keterangan |
|---|---|
| **Periode Tahun** | Wajib. Angka, contoh: `2026` |

Klik **Simpan**.

Pesan sukses yang muncul:
> `Periode RKAP baru berhasil disimpan!`

### 1.2 Verifikasi

Kembali ke daftar. Anda akan melihat baris baru dengan:
- **Periode**: tahun yang Anda buat
- **Fase Saat Ini**: `Inisiasi & Kick-off`
- **Status Dokumen**: `Draft`
- **Distribusi**: `Belum Didistribusikan`

Klik tombol **Detail & Lifecycle** untuk membuka halaman «Kontrol Fase».

- [ ] Periode RKAP berhasil dibuat
- [ ] Fase tampil `Inisiasi & Kick-off`
- [ ] Status tampil `Draft`
- [ ] Di halaman detail, terlihat enam fase lifecycle dengan fase pertama ter-highlight (berwarna kuning)

---

### 1.3 Menghapus Periode RKAP (jika salah input)

Klik tombol **Hapus** pada baris tersebut. Konfirmasi: *"Hapus Periode RKAP?"* → **Ya, Hapus!**

> **Perhatikan:** Penghapusan akan ditolak dengan pesan berikut:
> - *"Periode RKAP tidak dapat dihapus karena masih memiliki company target!"* — berarti sudah ada Sasaran Perusahaan yang terhubung. Hapus duluPEAT Company Target-nya.
> - *"Periode RKAP tidak dapat dihapus karena sudah memasuki fase 'Finalisasi & Pengesahan'."* — berarti sudah terlalu terlanjur maju.

- [ ] (Opsional, jika salah input) Periode berhasil dihapus

---

### ✅ Checkbox Penutup Tahap 1

- [ ] Periode RKAP untuk tahun yang benar sudah dibuat
- [ ] Fase = Inisiasi & Kick-off
- [ ] Status = Draft

---

## TAHAP 2 — Kick-off & Arahan Direksi

> **Siapa:** PPK
> **Kapan:** Setelah periode dibuat, sebelum penyusunan dimulai
> **Fase:** Inisiasi & Kick-off

Buka kembali halaman **Detail & Lifecycle** periode RKAP Anda.

### 2.1 Mengisi Jadwal & Peserta Kick-off

Di panel **Kick-off / Sosialisasi Penyusunan RKAP**, klik tombol **Isi / Ubah**.

| Isi | Keterangan |
|---|---|
| **Tanggal Kick-off** | Opsional. Pilih tanggal |
| **Catatan Kick-off** | Opsional. Agenda, lokasi, materi |
| **Nama peserta** | Opsional. Nama orang yang hadir |
| **Divisi** | Opsional. Pilih divisi peserta |

Untuk menambah peserta, klik **Tambah Peserta**. Baris peserta bisa dihapus dengan tombol hapus di baris tersebut.

Klik **Simpan**.

Pesan sukses:
> `Jadwal dan peserta kick-off/sosialisasi berhasil disimpan!`

### 2.2 Mengunggah Arahan Direksi / Memo Holding

Di panel **Arahan Direksi / Memo Holding**, klik tombol **Unggah / Ubah**.

| Isi | Keterangan |
|---|---|
| **Lampiran Arahan** | Opsional. Upload file (PDF, DOC, XLS, PPT, atau gambar) |
| **Catatan Arahan** | Opsional. Ringkasan arahan dari direksi/holding |

Klik **Simpan**.

Pesan sukses:
> `Arahan direksi / memo holding berhasil disimpan!`

Untuk mengunduh kembali lampiran yang sudah diunggah, gunakan tombol **Unggah / Ubah** — sistem akan menampilkan tautan unduh lampiran lama.

> **Catatan:** Kalau Anda mengunggah file baru, file lama akan otomatis diganti.

### 2.3 Memajukan Fase ke Penyusunan

Setelah kick-off dan arahan direksi selesai, PPK bisa memindahkan fase ke **Penyusunan** untuk mulai mengisi formulir.

Di panel **Kontrol Fase**, klik tombol **Majukan ke Penyusunan**.

Konfirmasi: *"Majukan fase RKAP ke 'Penyusunan'?"*

Pesan sukses:
> `Fase RKAP bergerak ke "Penyusunan".`

- [ ] Tanggal kick-off terisi
- [ ] Catatan kick-off terisi
- [ ] Daftar peserta kick-off terisi
- [ ] Lampiran arahan direksi terunggah
- [ ] Catatan arahan terisi
- [ ] Fase sudah dimajukan ke **Penyusunan**

> **Jika belum ingin memulai pengisian sekarang, fase boleh tetap di Inisiasi & Kick-off.** Form 1–4 tetap bisa diisi pada fase Penyusasunan. Tapi lebih orderly untuk langsung dimajukan setelah kick-off.

---

## TAHAP 3 — Penyusunan Form 1: Sasaran & Asesmen Risiko

> **Siapa:** Pengisi (Pemilik Anggaran / Cost Owner) mengisi · Manajemen Risiko menyetujui
> **Fase:** Penyusunan
> **Wajib:** Pengisi hanya melihat data **divisinya sendiri**

### Peta Form 1

```
Sasaran Perusahaan  (level perusahaan, diisi terpisah)
        │
        ▼
Sasaran Departemen  ──▶ butuh rating A atau lebih tinggi  ──▶ baru bisa buat Program Kerja
        │
        ▼
Identifikasi Risiko  (Arah Risiko, Jenis, Nama Risiko)
        │
        ├──► Penyebab Identifikasi  (bisa lebih dari 1)
        ├──► Dampak Identifikasi
        │
        ▼
Analisis Risiko  (Probabilitas × Dampak → Skor & Level otomatis)
        │
        ▼
Strategi Risiko Departemen  ( mitigasi, bisa lebih dari 1)
        │
        ▼
   [Ajukan Form 1] ──► 🔒 DATA TERKUNCI ──► Manajemen Risiko menyetujui
```

---

### 3.1 Sasaran Perusahaan

Menu: **Form 1 → Sasaran & Asesmen Risiko → Sasaran → Sasaran Perusahaan**

```
Tombol: Tambah Sasaran Perusahaan
```

| Isi | Keterangan |
|---|---|
| **Periode RKAP** | Wajib. Pilih periode yang sedang disusun |
| **Target** | Wajib. Uraikan target perusahaan untuk tahun tersebut |

Klik **Simpan**.

Pesan sukses:
> `Sasaran perusahaan baru berhasil disimpan!`

- [ ] Sasaran Perusahaan untuk periode aktif sudah dibuat
- [ ] Tidak bisa dihapus kalau sudah dipakai Sasaran Departemen

---

### 3.2 Sasaran Departemen

Menu: **Form 1 → Sasaran & Asesmen Risiko → Sasaran → Sasaran Departemen**

```
Tombol: Tambah Sasaran Departemen
```

| Isi | Keterangan |
|---|---|
| **Sasaran Perusahaan** | Wajib. Pilih dari 3.1 |
| **Divisi** | Wajib. **Otomatis sesuai divisi Anda** (tidak bisa diubah) |
| **Rating Criteria** | Wajib. Pilih dari master data (0.8). **Pilih rating A atau lebih baik** agar bisa dibuat Program Kerja |
| **Prioritas** | Opsional. Angka, 1 = prioritas tertinggi |
| **Target** | Wajib. Uraikan target departemen Anda |

Klik **Simpan**.

Pesan sukses:
> `Sasaran departemen baru berhasil disimpan!`

> **Masalah yang sering terjadi:**
> - Pesan *"Data divisi Anda tidak ditemukan. Silakan hubungi administrator."* → akun Anda belum terhubung ke data divisi. Hubungi administrator.
> - Tidak bisa dihapus kalau sudah dipakai Identifikasi Risiko.

- [X] Sasaran Departemen sudah dibuat
- [X] Rating yang dipilih **A atau lebih tinggi**
- [X] Divisi terisi otomatis dengan benar

> **Lakukan untuk SETIAP departemen di divisi Anda.** Satu departemen bisa punya lebih dari satu sasaran.

---

### 3.3 Identifikasi Risiko

Menu: **Form 1 → Sasaran & Asesmen Risiko → Identifikasi Risiko → Identifikasi Risiko**

```
Tombol: Tambah Identifikasi Risiko
```

| Isi | Keterangan |
|---|---|
| **Sasaran Departemen** | Wajib. Pilih dari 3.2 |
| **Risk Type** | Wajib. Pilih dari master data (0.7) |
| **Risk Taxonomy** | Wajib. Pilih dari master data (0.6) |
| **Arah Risiko** | Wajib. Pilih: **Negatif** atau **Positif** |
| **Risk** | Wajib. Nama/uraian risiko, maks 255 karakter |

Klik **Simpan**.

Pesan sukses:
> `Identifikasi risiko baru berhasil disimpan!`

> **Penting:** Arah Risiko **Negatif** adalah risiko yang berdampak negatif — dari mana saja kita harus menjauh. Arah Risiko **Positif** adalah peluang/keuntungan yang bisa dimanfaatkan. Pastikan Anda memilih dengan benar karena akan memengaruhi program kerja yang dibuat.

- [X] Identifikasi Risiko sudah dibuat untuk semua sasaran departemen Anda
- [X] Semua Arah Risiko dipilih dengan tepat

---

### 3.4 Penyebab Identifikasi Risiko

Menu: **Form 1 → Sasaran & Asesmen Risiko → Identifikasi Risiko → Penyebab Identifikasi**

```
Tombol: Tambah Penyebab
```

| Isi | Keterangan |
|---|---|
| **Identifikasi Risiko** | Wajib. Pilih dari 3.3 |
| **Penyebab Identifikasi Risiko** | Wajib. Maksimal 255 karakter. Placeholder: "Masukkan penyebab..." |

Untuk menambah beberapa penyebab sekaligus, klik **Tambah Penyebab** beberapa kali.

Klik **Simpan**.

Pesan sukses:
> `<jumlah> penyebab identifikasi risiko berhasil disimpan!`

> **Wajib:** Minimal satu penyebab harus diisi. Kalau tidak, muncul pesan: *"Minimal satu penyebab identifikasi risiko wajib diisi."*

- [X] Setiap Identifikasi Risiko sudah punya minimal satu Penyebab
- [X] Penyebab yang causes sudah deskriptif (bukan "tidak tahu")

---

### 3.5 Dampak Identifikasi Risiko

Menu: **Form 1 → Sasaran & Asesmen Risiko → Identifikasi Risiko → Dampak Identifikasi**

```
Tombol: Tambah Dampak
```

| Isi | Keterangan |
|---|---|
| **Identifikasi Risiko** | Wajib. Pilih dari 3.3 |
| **Dampak** | Wajib. Maksimal 255 karakter |

Klik **Simpan**.

Pesan sukses:
> `Dampak identifikasi risiko baru berhasil disimpan!`

- [X] Setiap Identifikasi Risiko sudah punya Dampak
- [X] Dampak terisi sesuaiANCARA yang masuk akal

---

### 3.6 Analisis Risiko

Menu: **Form 1 → Sasaran & Asesmen Risiko → Analisis Risiko**

```
Tombol: Tambah Analisis Risiko
```

| Isi | Keterangan |
|---|---|
| **Identifikasi Risiko** | Wajib. Pilih dari 3.3 |
| **Probabilitas** | Wajib. Pilih dari master data (0.9b) |
| **Dampak** | Wajib. Pilih dari master data (0.9c) |
| **Skor & Level** | **Otomatis** — terisi sendiri dari kombinasi Probabilitas × Dampak. Tidak bisa diisi manual. |

Klik **Simpan**.

Pesan sukses:
> `Analisis risiko baru berhasil disimpan!`

> **Jika kolom Skor & Level tidak terisi otomatis**, berarti pasangan Probabilitas × Dampak yang Anda pilih belum punya Level di master data Risk Score & Levels (lihat 0.9d). Hubungi administrator untuk melengkapinya.

- [X] Analisis Risiko sudah dibuat untuk semua Identifikasi Risiko
- [X] Skor & Level terisi otomatis untuk semua
- [X] Sudah dibahas dengan Manajemen Risiko (idealnya)

---

### 3.7 Strategi Risiko Departemen

Menu: **Form 1 → Sasaran & Asesmen Risiko → Strategi Risiko → Strategi Risiko Departemen**

```
Tombol: Tambah Strategi Risiko
```

| Isi | Keterangan |
|---|---|
| **Identifikasi Risiko** | Wajib. Pilih dari 3.3 |
| **Strategi Mitigasi** | Wajib. Maksimal 255 karakter. Placeholder: "Masukkan strategi..." |

Untuk menambah beberapa strategi, klik **Tambah Strategi** beberapa kali.

Klik **Simpan**.

Pesan sukses:
> `<jumlah> strategi risiko departemen berhasil disimpan!`

> **Wajib:** Minimal satu strategi mitigasi harus diisi. Kalau tidak: *"Minimal satu strategi mitigasi wajib diisi."*

- [X] Setiap Identifikasi Risiko sudah punya minimal satu Strategi Mitigasi

---

### 3.8 Mengajukan Form 1 untuk Disetujui

Menu: **Form 1 → Sasaran & Asesmen Risiko → Identifikasi Risiko → Identifikasi Risiko**

Ada dua cara:

**a) Ajukan satu per satu:** klik tombol **Ajukan untuk Evaluasi** di baris yang diinginkan.
**b) Ajukan semua sekaligus:** klik tombol **Ajukan Semua (jumlah)** di bagian atas.

Konfirmasi: *"Ajukan Form 1?"* → **Ya, Ajukan!**

Pesan sukses:
> `Form 1 (identifikasi risiko) berhasil diajukan ke Dept. Manajemen Risiko untuk evaluasi!`

> ⚠️ **WAJIB DIBACA — Urutan Pengajuan Form 1**
>
> Setiap risiko **wajib sudah punya Strategi Mitigasi dan minimal satu Program Kerja** sebelum Form 1 bisa diajukan. Ini berarti:
>
> **Urutan yang benar:**
> 1. Lengkapi Sasaran Departemen, Identifikasi Risiko, Penyebab, Dampak, Analisis, dan Strategi (semua dalam status *Draft*).
> 2. **Buat Program Kerja** untuk setiap risiko lebih dulu — lewat menu **Form 2 → Program Kerja → Tambah**. Anda **tidak perlu** diajukan/disetujui dulu; cukup dibuat.
> 3. Baru kembali ke menu ini dan tekan **Ajukan Semua**.
>
> **Kalau program kerja belum dibuat**, sistem akan menampilkan:
> `0 Form 1 berhasil diajukan untuk evaluasi Manajemen Risiko. 3 gagal diajukan. (Setiap Risiko wajib memiliki Program Kerja)`
>
> Ini **bukan kesalahan sistem** — artinya masih ada risiko yang belum punya Program Kerja. Buka daftar risiko yang gagal, buat program kerjanya, lalu ajukan lagi.

> **Peringatan besar:** Begitu Anda menekan tombol **Ajukan**, **seluruh data Form 1 langsung terkunci** — termasuk Sasaran Departemen, Identifikasi Risiko, Penyebab, Dampak, Analisis Risiko, dan Strategi. Anda tidak bisa menambah atau mengubah Program Kerja milik risiko tersebut lagi, kecuali dokumennya ditolak. **Pastikan semua sudah benar sebelum mengajukan.**

---

### 3.9 Persetujuan oleh Manajemen Risiko

Menu: **Approval** → tab **Menunggu Persetujuan** → grup **Tanpa Divisi** atau grup risk register

Klik **Lihat detail**, lalu:

**Untuk menyetujui:**
1. Tulis catatan (opsional, maks 500 karakter) di kolom yang tersedia
2. Klik **Setujui**
3. Konfirmasi: *"Setujui dokumen ini?"*

**Untuk menolak:**
1. Klik **Tolak**
2. Isi **Alasan** (wajib)
3. Klik **Tolak**

Pesan sukses:
- Setuju: `Dokumen berhasil disetujui!`
- Tolak: `Dokumen berhasil ditolak.`

> **Jika ditolak**, data Form 1 bisa Anda perbaiki, lalu **Ajukan ulang**. Hanya ada **satu level** persetujuan untuk Form 1, jadi setelah disetujui proses selesai.

- [X] Semua Form 1 sudah diajukan
- [X] Manajemen Risiko sudah menyetujui (atau data dikoreksi lalu diajukan ulang)
- [X] Status Form 1 sudah `Disetujui`

---

### ✅ Checkbox Penutup Tahap 3

- [X] Sasaran Perusahaan sudah dibuat
- [X] Sasaran Departemen sudah dibuat dengan rating A atau lebih tinggi
- [X] Identifikasi Risiko, Penyebab, Dampak, Analisis, dan Strategi sudah lengkap
- [X] **Setiap risiko sudah punya minimal satu Program Kerja** (dibuat di Tahap 4, belum harus diajukan)
- [X] Form 1 sudah diajukan dan disetujui Manajemen Risiko
- [X] Data Form 1 terkunci (otomatis begitu diajukan, tidak bisa diubah lagi)

---

## TAHAP 4 — Penyusunan Form 2: Program Kerja

> **Siapa:** Pengisi (Pemilik Anggaran) mengisi · PPK → Controller menyetujui
> **Fase:** Penyusunan
> **Prasyarat:** Sasaran Departemen sudah ada dengan rating **A, AA, atau AAA**
> **Penting:** Bagian **4.1 (membuat Program Kerja) dilakukan lebih dulu, sebelum Form 1 diajukan.** Pengajuan Program Kerja (4.2) justru dilakukan *setelah* Form 1 disetujui. Urutannya dijelaskan di bagian 3.8.

### 4.1 Membuat Program Kerja

Menu: **Form 2 → Jadwal Kerja → Program Kerja**

> ⚠️ **Lakukan bagian ini sebelum mengajukan Form 1 di Tahap 3.** Setiap risiko wajib punya minimal satu Program Kerja, dan Program Kerja hanya bisa ditambah selama risiko masih berstatus *Draft*.

```
Tombol: Tambah Program Kerja
```

| Isi | Keterangan |
|---|---|
| **Identifikasi Risiko** | Wajib. Pilih dari Form 1 (3.3). Hanya menampilkan risiko dari sasaran rating A, AA, atau AAA |
| **Program Kerja** | Wajib. Nama program kerja. Placeholder: "Nama program kerja" |
| **Satuan** | Wajib. Contoh: `Kegiatan`, `Armada`, `Unit` |
| **Rencana Tahunan (%)** | Wajib, 0–100. Persentase target tahunan |
| **Januari (%)** | Wajib, 0–100 |
| **Februari (%)** | Wajib, 0–100 |
| **Maret (%)** | Wajib, 0–100 |
| **April (%)** | Wajib, 0–100 |
| **Mei (%)** | Wajib, 0–100 |
| **Juni (%)** | Wajib, 0–100 |
| **Juli (%)** | Wajib, 0–100 |
| **Agustus (%)** | Wajib, 0–100 |
| **September (%)** | Wajib, 0–100 |
| **Oktober (%)** | Wajib, 0–100 |
| **November (%)** | Wajib, 0–100 |
| **Desember (%)** | Wajib, 0–100 |

Klik **Simpan**.

Pesan sukses:
> `Program kerja baru berhasil disimpan!`

> **Aturan penting:**
> - **Total seluruh bulanan harus sama dengan Rencana Tahunan.** Kalau tidak sama, program kerja tidak bisa diajukan untuk persetujuan.
> - Hanya bisa dibuat untuk sasaran dengan **rating A ke atas**. Pesan: *"Program kerja hanya bisa dibuat untuk sasaran dengan rating A ke atas."*

> **Tips:** Sebarkan persentase secara proporsional sesuai seasonality pekerjaan. Kalau program kerja berjalan sepanjang tahun, isi 8–9% per bulan. Jangan lupa totalnya harus 100%.

---

### 4.2 Mengajukan Program Kerja

Menu: **Form 2 → Jadwal Kerja → Program Kerja**

**a) Satu per satu:** klik **Ajukan Persetujuan** di baris yang diinginkan.
Konfirmasi: *"Ajukan program kerja ini untuk persetujuan?"*

**b) Semua sekaligus:** klik **Ajukan Semua Persetujuan** di bagian atas.
Konfirmasi: *"Ajukan N program kerja untuk persetujuan sekaligus?"*

Pesan sukses:
> `Program kerja berhasil diajukan untuk persetujuan!`

> **Syarat sebelum bisa diajukan:** Program Kerja harus sudah punya **anggaran** — artinya sudah ada minimal satu Baris **Biaya Rutin (Form 3)** atau **Rencana Investasi (Form 4)** yang terhubung dengannya. Bagian 4.3 menjelaskan.

---

### 4.3 Alur Persetujuan Program Kerja

```
Program Kerja diajukan
        │
        ▼
   Level 1: PPK  ──►  Setujui / Tolak (wajib isi alasan)
        │
        ▼
   Level 2: Budget Controller  ──►  Setujui / Tolak
        │
        ▼
   Status: Disetujui
```

**Proses persetujuan** (Menu **Approval**):
1. Buka tab **Menunggu Persetujuan**
2. Cari dokumen di grup divisi Anda
3. Klik **Lihat detail** untuk melihat isi lengkap
4. Klik **Setujui** atau **Tolak** (wajib isi alasan)

> **Perhatian:** PPK harus menyetujui **lebih dulu**. Controller tidak bisa menyetujui sebelum PPK selesai. Kalau Anda login sebagai Controller dan mencoba menyetujui, akan muncul pesan "Bukan giliran Anda".

- [X] Program Kerja sudah dibuat untuk semua risiko yang perlu tindakan
- [X] Persentase bulanan sudah lengkap dan total = Rencana Tahunan
- [X] Program Kerja sudah diajukan ke PPK
- [X] PPK sudah menyetujui
- [X] Controller sudah menyetujui
- [X] Status Program Kerja sudah `Disetujui`

---

### ✅ Checkbox Penutup Tahap 4

- [X] Semua Program Kerja sudah dibuat
- [X] Semua Program Kerja sudah punya anggaran (Form 3 atau Form 4)
- [X] Semua Program Kerja sudah disetujui sampai level Controller

---

## TAHAP 5 — Penyusunan Form 3: Biaya Rutin

> **Siapa:** Pengisi (Pemilik Anggaran) mengisi · PPK → Controller menyetujui
> **Fase:** Penyusunan

### 5.1 Menambah Biaya Rutin

Menu: **Form 3 → Biaya Umum → Biaya Rutin**

```
Tombol: Tambah Biaya Rutin
```

| Isi | Keterangan |
|---|---|
| **Program Kerja** | Wajib. Pilih dari Form 2 (4.1) |
| **Elemen Biaya** | Wajib. Pilih dari master data (0.3) |
| **Chart of Account** | Opsional. Kosongkan untuk otomatis mengikuti CoA elemen biaya |
| **Kebutuhan** | Wajib. Deskripsi kebutuhan. Placeholder: "Deskripsi kebutuhan" |
| **Cost Center Swakelola** | Opsional. Pilih jika termasuk kategori swakelola |
| **Cost Center Non Swakelola** | Opsional. Pilih jika bukan swakelola |
| **Pemilik Cost Center** | Wajib. Nama pemilik cost center |
| **Jumlah** | Wajib. Angka, minimum 0 |
| **Satuan** | Wajib. Contoh: `unit`, `set`, `liter` |
| **Harga Satuan** | Wajib. Nominal Rupiah |
| **Total** | **Otomatis** — dihitung dari Jumlah × Harga Satuan. Tidak bisa diisi manual. |
| **Januari** s.d. **Desember** | Opsional. Nominal Rupiah per bulan. Ada opsi **Kumulatif** untuk mengisi akumulatif. |

Klik **Simpan**.

Pesan sukses:
> `Biaya rutin baru berhasil disimpan!`

> **Tips:** Gunakan opsi **Kumulatif** jika lebih mudah — Anda cukup mengisi total kumulatif per akhir bulan, sistem menghitung selisihnya sendiri.

---

### 5.2 Mengajukan Biaya Rutin

Menu: **Form 3 → Biaya Umum → Biaya Rutin**

**a) Satu per satu:** klik **Ajukan Persetujuan** di baris yang diinginkan.
**b) Semua sekaligus:** klik **Ajukan Semua Persetujuan**.

Pesan sukses:
> `Biaya rutin berhasil diajukan untuk persetujuan!`

> **Syarat:** Baris biaya rutin harus sudah punya Total yang tidak nol.

---

### 5.3 Alur Persetujuan Biaya Rutin

```
Biaya Rutin diajukan
        │
        ▼
   Level 1: PPK  ──►  Setujui / Tolak
        │
        ▼
   Level 2: Budget Controller  ──►  Setujui / Tolak
        │
        ▼
   Status: Disetujui
```

**Proses persetujuan** identik dengan Program Kerja (lihat 4.3). Menu **Approval** → **Menunggu Persetujuan** → grup divisi Anda.

- [X] Semua Biaya Rutin sudah dibuat dan terhubung ke Program Kerja
- [X] Nominal dan pembagian bulanan sudah benar
- [X] Semua Biaya Rutin sudah diajukan
- [X] PPK sudah menyetujui
- [X] Controller sudah menyetujui
- [X] Status sudah `Disetujui`

---

### ✅ Checkbox Penutup Tahap 5

- [X] Semua Biaya Rutin sudah dibuat
- [X] Semua Biaya Rutin sudah disetujui

---

## TAHAP 6 — Penyusunan Form 4: Rencana Investasi

> **Siapa:** Pengisi (Pemilik Anggaran) mengisi · PPK → Manajemen Aset → Direksi Keuangan menyetujui
> **Fase:** Penyusunan
> **Paling banyak tahap persetujuan dari semua formulir**

### 6.1 Menambah Rencana Investasi

Menu: **Form 4 → Biaya Investasi → Rencana Investasi**

```
Tombol: Tambah Investasi
```

**Bagian 1: Informasi Dasar**

| Isi | Keterangan |
|---|---|
| **Program Kerja** | Wajib. Pilih dari Form 2 (4.1) |
| **Pusat Biaya (Cost Center)** | Opsional. Pilih dari master data (0.4) |
| **Chart of Account** | Opsional |
| **Kategori Investasi** | Wajib. Pilih dari master data (0.10c) |
| **Tipe Investasi** | Wajib. Pilih dari master data (0.10a) |
| **Kriteria Investasi** | Wajib. Pilih dari master data (0.10b) |
| **Satuan** | Wajib. Contoh: `unit`, `pcs`, `set` |
| **Nama Investasi** | Wajib. Nama barang/investasi |
| **Deskripsi** | Opsional. Placeholder: "Deskripsi investasi (opsional)" |
| **Jumlah (Qty)** | Wajib. Angka, minimum 0 |
| **Harga Satuan** | Wajib. Nominal Rupiah |
| **Total** | **Otomatis** — dihitung dari Qty × Harga Satuan |
| **Urutan Prioritas** | Opsional. Angka, 1 = tertinggi |
| **Januari** s.d. **Desember** | Opsional. Nominal Rupiah per bulan (rencana pencairan) |

**Bagian 2: Proposal & Kajian Kelayakan (CBA)**

| Isi | Keterangan |
|---|---|
| **Proposal (PDF/DOC)** | **Wajib saat mengajukan** (tidak wajib saat membuat). Format: `.pdf`, `.doc`, `.docx` |
| **Lampiran CBA** | Opsional. Format: `.pdf`, `.doc`, `.docx`, `.xls`, `.xlsx` |
| **NPV** | Opsional. Angka |
| **IRR (%)** | Opsional. Angka |
| **Payback Period (tahun)** | Opsional. Angka |
| **Justifikasi / Rekomendasi** | Opsional. Placeholder: "Justifikasi kelayakan investasi (opsional)" |

Klik **Simpan**.

Pesan sukses:
> `Rencana investasi baru berhasil disimpan!`

> **Penting:** Proposal boleh diunggah nanti, tapi **wajib ada sebelum Anda mengajukan** untuk persetujuan. Kalau tidak, tombol Ajukan Persetujuan tidak muncul atau muncul pesan bahwa proposal belum ada. Pesan yang muncul: *"Proposal tidak tersedia untuk rencana investasi ini."*

---

### 6.2 Mengajukan Rencana Investasi

Menu: **Form 4 → Biaya Investasi → Rencana Investasi**

**a) Satu per satu:** klik **Ajukan Persetujuan**.
**b) Semua sekaligus:** klik **Ajukan Semua Persetujuan**.

Pesan sukses:
> `Rencana investasi berhasil diajukan untuk persetujuan!`

---

### 6.3 Mengunggah Proposal (Jika Belum Ada)

Pada halaman Edit, bagian **Proposal & Kajian Kelayakan**, unggah file proposal Anda. Format yang diterima: PDF atau Word.

Untuk mengunduh proposal yang sudah diunggah, klik tombol **Download Proposal** di daftar.

---

### 6.4 Alur Persetujuan Rencana Investasi

```
Rencana Investasi diajukan
        │
        ▼
   Level 1: PPK  ──►  Setujui / Tolak
        │
        ▼
   Level 2: Dept. Manajemen Aset  ──►  Setujui / Tolak
        │
        ▼
   Level 3: Direksi Keuangan  ──►  Setujui / Tolak
        │
        ▼
   Status: Disetujui
```

> **Perbedaan dari Form 2 dan 3:** Rencana Investasi punya **tiga level** dan **wajib melewati Stage Gate Review** sebelum bisa disetujui. Lihat bagian 6.5.

---

### 6.5 Stage Gate Review (WAJIB untuk Rencana Investasi)

> **Siapa:** PPK, Manajemen Aset, Direksi Keuangan (sesuai level)
> **Kapan:** Setelah rencana investasi diajukan, sebelum persetujuan final

Menu: **Form 4 → Biaya Investasi → Stage Gate Review**

Klik tombol **Evaluasi Gate** atau **Detail Gate** untuk membuka.

Untuk setiap Gate, pilih salah satu keputusan:

| Keputusan | Kapan Dipakai | Wajib Isi |
|---|---|---|
| **Setujui** | Gate sudah satisfactory | Konfirmasi: *"Setujui gate ini?"* |
| **Minta Revisi** | Ada perbaikan yang diperlukan | Konfirmasi: *"Minta revisi?"*, isi **Catatan Evaluasi** |
| **Tolak** | Gate tidak layak | **Alasan Penolakan** (wajib) |

Field tambahan di form evaluasi:
- **Hasil Kajian** — Opsional. Pilih: **Layak**, **Tidak Layak**, **Perlu Revisi**
- **Catatan Evaluasi** — Opsional
- **Lampiran CBA** — Opsional. Upload ulang jika ada revisi

Ada **empat Gate** untuk setiap Rencana Investasi:
1. Gate **Proposal** — dinilai oleh PPK
2. Gate **CBA** — Dinilai oleh PPK
3. Gate **Aset** — Dinilai oleh Manajemen Aset
4. Gate **Direksi Keuangan** — Dinilai oleh Direksi Keuangan

Pesan sukses:
> `Evaluasi "<nama gate>" berhasil disimpan.`

> **Penting:** Semua Gate di level yang sama **harus selesai disetujui** sebelum Level 1 bisa menyetujui di Menu Approval. Kalau belum, muncul pesan: *"Stage Gate untuk level ini belum disetujui. Selesaikan Gate Review terlebih dahulu."*

- [X] Semua Rencana Investasi sudah dibuat
- [X] Semua sudah punya file **Proposal** (wajib)
- [X] Semua sudah diajukan
- [X] Semua Gate Review sudah disetujui (Stage Gate Review)
- [X] PPK sudah menyetujui (Level 1)
- [X] Manajemen Aset sudah menyetujui (Level 2)
- [X] Direksi Keuangan sudah menyetujui (Level 3)
- [X] Status sudah `Disetujui`

---

### ✅ Checkbox Penutup Tahap 6

- [X] Semua Rencana Investasi sudah dibuat
- [X] Semua sudah punya Proposal
- [X] Semua sudah disetujui sampai level Direksi Keuangan

---

## TAHAP 7 — Konsolidasi & Review

> **Siapa:** PPK, Budget Controller, Accounting, Admin E-RKAP
> **Kapan:** Setelah Form 1–4 selesai, sebelum pengesahan
> **Fase:** Konsolidasi & Review

### 7.1 Memajukan Fase ke Konsolidasi

Menu: **Lifecycle RKAP** → **Detail & Lifecycle**

Di panel **Kontrol Fase**, klik **Majukan ke Konsolidasi & Review**.

Konfirmasi: *"Majukan fase RKAP ke 'Konsolidasi & Review'?"*

- [ ] Fase sudah dimajukan ke **Konsolidasi & Review**

> **Catatan:** Data anggaran masih bisa diubah pada fase ini (masih terkunci di fase sebelumnya).

---

### 7.2 Review ZBB (Zero Based Budgeting)

> **Siapa:** PPK / Budget Controller
> **Tujuan:** Meninjau setiap pos anggaran yang **naik** dari tahun sebelumnya. Setiap kenaikan wajib punya justifikasi.

Menu: **Zero Based Budgeting → Review ZBB**

**Langkah 1 — Bangun Review**

| Isi | Keterangan |
|---|---|
| **Periode RKAP** | Wajib. Pilih periode aktif |

Klik **Bangun Review**.

Pesan sukses:
> `Review ZBB untuk RKAP 2026 berhasil dibangun: 45 pos anggaran (12 kenaikan, 33 auto-skip).`

Pesan tambahan jika ada yang perlu erheaf:
> `... N pos menunggu justifikasi.`

**Langkah 2 — Saring Pos yang Perlu Justifikasi**

Gunakan filter di bagian atas:
- **Periode RKAP** — Pilih periode
- **Divisi** — Pilih divisi (opsional)
- **Jenis Pos** — Pilih: **Biaya Rutin (OPEX)**, **Rencana Investasi (CAPEX)**, **Rencana Pendapatan**, atau **Program Kerja**
- **Status** — Pilih status: **Menunggu Review**, **Sedang Direview**, **Disetujui**, **Ditolak**, atau **Auto-skip (tidak naik)**

Klik tombol **Cari** (ikon magnifier).

**Langkah 3 — Isi Justifikasi**

Klik **Review** pada baris yang perlu ditinjau.

| Isi | Keterangan |
|---|---|
| **Justifikasi Kenaikan** | **Wajib**. Alasan kenaikan anggaran diusulkan. Placeholder: "Alasan kenaikan anggaran diusulkan..." |
| **Status Review** | Opsional. Pilih status |
| **Catatan Review** | Opsional. Placeholder: "Catatan tambahan (opsional)" |

Klik **Simpan Review**.

Pesan sukses:
> `Review ZBB berhasil disimpan.`

> **Pos auto-skip (tidak naik) tidak perlu justifikasi** — sistem otomatis menandainya.

- [X] Review ZBB sudah dibangun untuk periode aktif
- [X] Semua pos kenaikan sudah diberi justifikasi
- [X] Semua pos sudah direview (Disetujui / Ditolak)
- [X] Auto-skip (tidak naik) sudah diabaikan (sistem yang OL)

---

### 7.3 Konsolidasi Anggaran Investasi (CAPEX)

> **Siapa:** PPK / Admin
> **Tujuan:** Mengumpulkan seluruh rencana investasi semua divisi menjadi satu anggaran investasi perusahaan.

Menu: **Form 4 → Biaya Investasi → Anggaran Investasi**

| Isi | Keterangan |
|---|---|
| **Pilih RKAP/Tahun** | Wajib. Pilih periode aktif |

Klik **Konsolidasi**.

Pesan sukses:
> `Konsolidasi anggaran investasi berhasil dilakukan!`

Setelah dikonsolidasi, Anda dapat melihat status tiap divisi: **Draft**, **Submitted**, **Approved**, atau **Rejected**. Untuk mengubah status, klik **Detail** lalu ubah kolom **Status** dan **Catatan**, klik **Update Status**.

- [X] Konsolidasi anggaran investasi sudah dijalankan
- [X] Status anggaran investasi tiap divisi sudah ditinjau
- [X] Status sudah sesuai

---

### 7.4 Ringkasan Nilai Investasi & Distribusi Pembayaran

Menu: **Form 4 → Biaya Investasi → Ringkasan Nilai Investasi**

- Pilih Periode RKAP dari filter
- Lihat nilai investasi per bulan, diurutkan berdasarkan prioritas
- **Read-only** — tidak ada yang perlu diisi

Menu: **Form 4 → Biaya Investasi → Distribusi Pembayaran**

- Pilih Periode RKAP dari filter
- Lihat rencana pembayaran per bulan
- **Read-only** — tidak ada yang perlu diisi

- [X] Ringkasan nilai investasi sudah ditinjau
- [X] Distribusi pembayaran sudah ditinjau

---

### 7.5 Laba Rugi (P&L)

> **Siapa:** PPK / Accounting
> **Tujuan:** Melihat proyeksi laba rugi berdasarkan rencana pendapatan dan beban.

**Langkah 1 — Isi Rencana Pendapatan**

Menu: **Financial Projection → Rencana Pendapatan**

```
Tombol: Tambah Rencana Pendapatan
```

| Isi | Keterangan |
|---|---|
| **Tahun RKAP** | Wajib. Pilih periode aktif |
| **Divisi** | Wajib. Pilih divisi |
| **Akun Pendapatan** | Wajib. Pilih dari Chart of Accounts tipe **Pendapatan** |
| **Deskripsi** | Opsional. Placeholder: "Keterangan rencana pendapatan" |
| **Januari** s.d. **Desember** | Wajib. Nominal Rupiah per bulan |
| **Total Pendapatan** | **Otomatis** — jumlahkan seluruh bulan |

Klik **Simpan**.

**Langkah 2 — Isi Rencana Beban**

Menu: **Financial Projection → Rencana Beban**

```
Tombol: Tambah Rencana Beban
```

| Isi | Keterangan |
|---|---|
| **Tahun RKAP** | Wajib |
| **Divisi** | Wajib |
| **Akun Beban** | Wajib. Pilih dari Chart of Accounts tipe **Beban** |
| **Deskripsi** | Opsional |
| **Januari** s.d. **Desember** | Wajib. Nominal Rupiah per bulan |
| **Total Beban** | **Otomatis** |

Klik **Simpan**.

**Langkah 3 — Buat Laporan Laba Rugi**

Menu: **Financial Projection → Laba Rugi (P&L)**

| Isi | Keterangan |
|---|---|
| **Tahun RKAP** | Wajib. Pilih periode aktif |
| **Divisi** | Opsional. Kosongkan untuk seluruh perusahaan |

Klik **Buat Laporan**.

Pesan sukses:
> `Laporan laba rugi berhasil dibuat dari rencana pendapatan & beban!`

Klik **Detail** untuk melihat grafik bulanan pendapatan vs beban dan tabel per bulan.

---

### 7.6 Simulasi Skenario

Menu: **Financial Projection → Simulasi Skenario**

| Isi | Keterangan |
|---|---|
| **Tahun RKAP** | Wajib |
| **Divisi** | Opsional |

Klik **Jalankan Simulasi**.

Akan muncul hasil: **Multiplier**, **Pendapatan**, **Beban**, **Laba**, **Margin**. Berguna untuk melihat sensitivitas terhadap skenario terbaik/terburuk.

- [ ] Rencana Pendapatan sudah diisi
- [ ] Rencana Beban sudah diisi
- [ ] Laba Rugi sudah dibuat
- [ ] Simulasi skenario sudah dijalankan (opsional, tapi disarankan)

---

### ✅ Checkbox Penutup Tahap 7

- [ ] Fase sudah dimajukan ke **Konsolidasi & Review**
- [ ] Review ZBB sudah dibangun dan selesai
- [ ] Konsolidasi anggaran investasi sudah dijalankan
- [ ] Ringkasan nilai investasi dan distribusi pembayaran sudah ditinjau
- [ ] Laba Rugi sudah dibuat
- [ ] Simulasi skenario sudah dijalankan (opsional)

---

## TAHAP 8 — Finalisasi & Pengesahan

> **Siapa:** PPK
> **Fase:** Finalisasi & Pengesahan
> **⚠️ PERINGATAN: Begitu fase ini dimulai, SELURUH data anggaran terkunci.**

### 8.1 Pemeriksaan Akhir (SEBELUM Majukan Fase)

Sebelum Majukan fase, pastikan checklist ini tercentang:

- [ ] Semua Form 1, 2, 3, 4 sudah lengkap dan disetujui
- [ ] Review ZBB sudah selesai
- [ ] Laba Rugi sudah dibuat
- [ ] Anggaran investasi sudah dikonsolidasi
- [ ] Dokumen yang perlu diunduh sudah diunduh

> **Karena fase ini mengunci data, pastikan Anda benar-benar siap.** Anda hanya bisa membuka kunci dengan cara **reset fase** (lihat bagian 8.4), dan itu hanya bisa dilakukan jika status dokumen masih Draft atau Ditolak.

---

### 8.2 Memajukan Fase ke Finalisasi

Menu: **Lifecycle RKAP** → **Detail & Lifecycle**

Di panel **Kontrol Fase**, klik **Majukan ke Finalisasi & Pengesahan**.

Konfirmasi: *"Majukan fase RKAP ke 'Finalisasi & Pengesahan'?"*

Pesan sukses:
> `Fase RKAP bergerak ke "Finalisasi & Pengesahan".`

> **Setelah fase ini aktif, akan muncul kotak kuning peringatan:**
> *"Data anggaran periode ini terkunci pada fase Finalisasi & Pengesahan."*

- [ ] Semua data sudah lengkap (periksa 8.1)
- [ ] Fase sudah dimajukan ke **Finalisasi & Pengesahan**
- [ ] Kotak peringatan "terkunci" sudah muncul (konfirmasi bahwa penguncian aktif)

---

### 8.3 Mengajukan Dokumen RKAP untuk Persetujuan

Menu: **Lifecycle RKAP** (halaman daftar)

Klik tombol **Ajukan Persetujuan** pada baris periode RKAP Anda.

Konfirmasi: *"Ajukan periode RKAP ini untuk persetujuan?"*

Pesan sukses:
> `Periode RKAP berhasil diajukan untuk persetujuan!`

> **Syarat:** Hanya bisa diajukan jika status masih Draft atau Ditolak. Kalau status sudah Disetujui, tombol tidak muncul.

---

### 8.4 Alur Persetujuan Periode RKAP

```
Dokumen Periode RKAP diajukan
        │
        ▼
   Level 1: Komisaris  ──►  Setujui / Tolak
        │
        ▼
   Level 2: Direksi / Direktur Utama  ──►  Setujui / Tolak
        │
        ▼
   Status: Disetujui
```

**Proses persetujuan:**
1. Buka menu **Approval** → tab **Menunggu Persetujuan**
2. Cari di grup **Tanpa Divisi** (dokumen periode RKAP tidak punya divisi)
3. Klik **Lihat detail**
4. Klik **Setujui** atau **Tolak** (wajib isi alasan)

---

### 8.5 Reset Fase (Jika Ada Kesalahan)

> **Kapan boleh:** Hanya jika status dokumen masih **Draft** atau **Ditolak**
> **Apa yang terjadi:** Fase dikembalikan ke **Inisiasi & Kick-off** dan status distribusi direset

Menu: **Lifecycle RKAP** → **Detail & Lifecycle**

Di panel **Kontrol Fase**, klik **Reset Fase ke Inisiasi**.

Konfirmasi: *"Reset fase lifecycle ke Inisiasi?"*

Pesan sukses:
> `Fase lifecycle RKAP direset ke Inisiasi.`

> **Tidak bisa reset** jika status sudah Disetujui. Satu-satunya jalan adalah menghubungi Admin E-RKAP.

- [ ] Semua data sudah lengkap
- [ ] Fase sudah dimajukan ke **Finalisasi & Pengesahan**
- [ ] Dokumen periode RKAP sudah diajukan untuk persetujuan
- [ ] Komisaris sudah menyetujui
- [ ] Direksi sudah menyetujui
- [ ] Status dokumen sudah `Disetujui`

---

### ✅ Checkbox Penutup Tahap 8

- [ ] Semua pemeriksaan akhir (8.1) tercentang
- [ ] Fase = Finalisasi & Pengesahan
- [ ] Dokumen sudah diajukan
- [ ] Sudah disetujui Komisaris + Direksi
- [ ] Status = Disetujui

---

## TAHAP 9 — Disahkan & Distribusi

> **Siapa:** PPK
> **Fase:** Disahkan

### 9.1 Memajukan Fase ke Disahkan

Menu: **Lifecycle RKAP** → **Detail & Lifecycle**

Di panel **Kontrol Fase**, klik **Majukan ke Disahkan**.

Konfirmasi: *"Majukan fase RKAP ke 'Disahkan'?"*

Pesan sukses:
> `Fase RKAP bergerak ke "Disahkan".`

**Tanggal Penetapan** akan otomatis terisi dengan tanggal hari ini.

> **Penting:** Tombol ini muncul untuk siapa saja yang punya hak edit lifecycle — **tidak ada pengecekan apakah dokumen sudah disetujui atau belum.** Pastikan dokumen sudah benar-benar disetujui sebelum memajukan fase ini.

---

### 9.2 Menandai Dokumen Sudah Didistribusikan

Menu: **Lifecycle RKAP** → **Detail & Lifecycle**

Di panel **Kontrol Fase**, klik **Tandai Didistribusikan**.

Konfirmasi: *"Tandai RKAP ini telah didistribusikan?"*

Pesan sukses:
> `Dokumen RKAP ditandai telah didistribusikan!`

> **Informasi:** Saat tombol ini diklik, sistem akan mengirim **notifikasi internal** ke semua Administrator E-RKAP. Notifikasi ini muncul di menu notifikasi dalam aplikasi — **bukan email dan bukan WhatsApp**.

> **Sekali saja:** Tombol ini hilang setelah diklik. Tidak bisa dibatalkan dari halaman ini.

- [ ] Fase sudah dimajukan ke **Disahkan**
- [ ] Tanggal Penetapan sudah terisi
- [ ] Dokumen sudah ditandai **Telah Didistribusikan**
- [ ] Admin E-RKAP sudah menerima notifikasi

---

### ✅ Checkbox Penutup Tahap 9

- [ ] Fase = Disahkan
- [ ] Status distribusi = Telah Didistribusikan
- [ ] Tanggal Penetapan terisi

---

## TAHAP 10 — Arsip

> **Siapa:** PPK / Admin
> **Fase:** Arsip
> **Fase terakhir**

### 10.1 Memajukan Fase ke Arsip

Menu: **Lifecycle RKAP** → **Detail & Lifecycle**

Di panel **Kontrol Fase**, klik **Majukan ke Arsip**.

Konfirmasi: *"Majukan fase RKAP ke 'Arsip'?"*

Pesan sukses:
> `Fase RKAP bergerak ke "Arsip".`

Sekarang dokumen RKAP bersifat **historis**. **Tidak ada perubahan yang bisa dilakukan lagi.**

---

### 10.2 Verifikasi Akhir

Buka halaman **Detail & Lifecycle** dan pastikan:

- [ ] Semua fase sudah tercentang hijau (selesai)
- [ ] Fase = **Arsip**
- [ ] Status Dokumen = **Disetujui**
- [ ] Distribusi = **Telah Didistribusikan**
- [ ] Riwayat Persetujuan terisi lengkap
- [ ] Audit Trail terisi (mencatat semua perubahan)

---

### ✅ Checkbox Penutup Tahap 10

- [ ] Fase = Arsip
- [ ] Status = Disetujui
- [ ] Distribusi = Telah Didistribusikan
- [ ] Riwayat persetujuan lengkap

> **Selamat!** Satu periode RKAP sudah selesai dari awal sampai akhir.

---

## TAHAP 11 — Monitoring & Realisasi Bulanan

> **Siapa:** Pengisi (Pemilik Anggaran) mengisi · Accounting/Admin memantau
> **Kapan:** Sepanjang tahun berjalan, setiap bulan
> **Fase:** Periode RKAP sudah Arsip — tetapi data realisasi tetap bisa diisi

> **Penting:** Menu Monitoring & Realisasi **tetap aktif** meskipun periode RKAP sudah diarsipkan. Ini karena realisasi adalah pelaporan, bukan perubahan anggaran.

### 11.1 Realisasi Anggaran (BvA)

Menu: **Monitoring & Realisasi → Realisasi Anggaran (BvA)**

**Filter:** pilih **Periode RKAP** dan **Bulan**.

Tombol: **Tambah** → **Input Realisasi**

Ada dua cara:

**a) Input Manual**

| Isi | Keterangan |
|---|---|
| **Tahun RKAP** | Wajib |
| **Bulan** | Wajib |
| **Tahun** | Wajib. Angka |
| **Sumber** | Wajib. Pilih: **Manual** atau **Sistem Akuntansi** |
| **Biaya Rutin** | Opsional. Pilih pos biaya rutin yang direalisasikan |
| **Rencana Investasi** | Opsional. Pilih pos investasi yang direalisasikan |
| **Realisasi (Rp)** | Wajib. Nominal |

Klik **Simpan**.

Pesan sukses:
> `Realisasi anggaran berhasil disimpan!`

**b) Import dari Excel**

| Isi | Keterangan |
|---|---|
| **Tahun RKAP** | Wajib |
| **Bulan** | Wajib |
| **Tahun** | Wajib |
| **File** | Wajib. Format: `.xlsx`, `.xls`, `.csv` |

Klik **Import**.

Pesan sukses:
> `Import realisasi berhasil: N baris diproses.`

Tabel hasil menampilkan: **Budget**, **Actual**, **Variance**, **Variance %**, **Sumber**.

- [ ] Realisasi anggaran bulanan sudah diisi (input manual atau import)
- [ ] Variance terlihat dan masuk akal

---

### 11.2 Realisasi Program Kerja

Menu: **Monitoring & Realisasi → Realisasi Program Kerja**

**Filter:** pilih **Periode RKAP** dan **Bulan**.

Tombol: **Input Realisasi**

| Isi | Keterangan |
|---|---|
| **Program Kerja** | Wajib. Pilih dari program kerja Anda |
| **Bulan** | Wajib |
| **Tahun** | Wajib. Angka |
| **Target** | Wajib. Angka (default 100) |
| **Realisasi** | Wajib. Angka (default 0) |
| **URL Bukti** | Opsional. Tautan bukti pencapaian. Placeholder: `https://...` |
| **Catatan** | Opsional. Placeholder: "Keterangan progres" |

Klik **Simpan**.

Pesan sukses:
> `Realisasi program kerja berhasil disimpan!`

Tabel menampilkan: **Target**, **Realisasi**, **% Penyelesaian**, **Status**, **Catatan**.

- [ ] Realisasi program kerja sudah diisi setiap bulan
- [ ] URL bukti sudah dilampirkan untuk pencapaian penting

---

### 11.3 Risk Assessment Bulanan

> **Siapa:** Pengisi (Pemilik Anggaran) / Manajer Risiko
> **Tujuan:** Memantau perubahan level risiko dari bulan ke bulan.

Menu: **Monitoring & Realisasi → Risk Assessment Bulanan**

Tombol: **Input Assessment**

| Isi | Keterangan |
|---|---|
| **Identifikasi Risiko** | Wajib. Pilih dari Form 1 |
| **Bulan** | Wajib |
| **Tahun** | Wajib. Angka |
| **Inherent Probability** | Wajib. Angka 1–10 (risiko sebelum mitigasi) |
| **Inherent Impact** | Wajib. Angka 1–10 |
| **Current Probability** | Opsional. Angka 1–10 (risiko saat ini) |
| **Current Impact** | Opsional. Angka 1–10 |
| **Residual Probability** | Opsional. Angka 1–10 (risiko setelah mitigasi) |
| **Residual Impact** | Opsional. Angka 1–10 |
| **Status Mitigasi** | Opsional. Pilih: **On Progress** (default), **Done**, **Overdue** |
| **Risk Owner** | Opsional. Nama/Divisi pemilik risiko |
| **Risk Appetite** | Opsional |
| **Target Tanggal Selesai** | Opsional. Tanggal |
| **Rencana Mitigasi** | Opsional. Placeholder: "Keterangan rencana mitigasi" |

**Business Process (dinamis, opsional):**

| Isi | Keterangan |
|---|---|
| **Nama Proses** | Wajib. Placeholder: "Nama proses" |
| **Deskripsi** | Opsional |
| **Owner** | Opsional |
| **Risk Level** | Opsional. Pilih: **Low**, **Medium** (default), **High**, **Critical** |

Klik **Tambah Proses** untuk menambah baris. Klik **Simpan**.

Pesan sukses:
> `Risk assessment bulanan berhasil disimpan!`

Tabel menampilkan: **Risiko**, **Divisi**, **Periode**, **Inherent**, **Current**, **Residual**, **Status Mitigasi**, **Target Selesai**, **Business Process**.

---

### 11.4 Performance Scorecard (KPI)

> **Siapa:** Pengisi / PPK
> **Kapan:** Setiap triwulan (4 kali setahun)

Menu: **Monitoring & Realisasi → Performance Scorecard (KPI)**

**Filter:** pilih **Periode RKAP** dan **Triwulan** (Triwulan 1–4).

Tombol: **Input KPI**

| Isi | Keterangan |
|---|---|
| **Tahun RKAP** | Wajib |
| **Sasaran Departemen** | Wajib. Pilih dari Form 1 (3.2) |
| **Triwulan** | Wajib. Pilih: **Triwulan 1**, **Triwulan 2**, **Triwulan 3**, **Triwulan 4** |
| **Tahun** | Wajib. Angka |
| **Nama KPI** | Wajib. Placeholder: "contoh: Penyelesaian Program Kerja" |
| **Target KPI** | Wajib. Angka. Placeholder: `100` |
| **Actual KPI** | Wajib. Angka. Placeholder: `0` |
| **Bobot (%)** | Wajib. Angka 0–100 |

Klik **Simpan**.

Pesan sukses:
> `Scorecard KPI berhasil disimpan!`

Tabel menampilkan: **Triwulan**, **Target**, **Actual**, **Score**, **Weight**, **Weighted Score**. Ada badge total: **Total Weighted Score: N**.

- [ ] Realisasi anggaran bulanan sudah diisi
- [ ] Realisasi program kerja sudah diisi
- [ ] Risk Assessment Bulanan sudah diisi
- [ ] Performance Scorecard sudah diisi setiap triwulan

---

### ✅ Checkbox Penutup Tahap 11

- [ ] Realisasi anggaran (BvA) terisi setiap bulan
- [ ] Realisasi program kerja terisi setiap bulan
- [ ] Risk Assessment Bulanan terisi
- [ ] Performance Scorecard (KPI) terisi setiap triwulan

---

## TAHAP 12 — Report Center

> **Siapa:** Admin / PPK / Auditor / Accounting
> **Kapan:** Setiap kali laporan dibutuhkan

Menu: **Report Center**

Ada **lima jenis laporan** yang bisa dibuat:

| Jenis Laporan | Judul | Isi |
|---|---|---|
| **RKAP** | Laporan RKAP | Rencana Kerja dan Anggaran Perusahaan |
| **Financial** | Laporan Keuangan (P&L) | Rencana Pendapatan dan Beban |
| **Risk** | Laporan Risiko | Risk Assessment Bulanan |
| **Realization** | Laporan Realisasi Anggaran | Budget vs Actual (BvA) |
| **Performance** | Laporan Performa (KPI) | Performance Scorecard |

### Cara Membuat Laporan

| Isi | Keterangan |
|---|---|
| **Jenis Laporan** | Wajib. Pilih salah satu dari lima jenis di atas |
| **Format** | Wajib. Pilih: **PDF** atau **Excel** |
| **Periode RKAP** | Opsional. Pilih periode |
| **Tahun** | Wajib. Angka, 4 digit |
| **Bulan** | Opsional. Pilih 1–12 |

Klik **Buat Laporan**.

Pesan sukses:
> `Laporan berhasil dibuat dan tersedia untuk diunduh.`

Klik tombol unduh pada laporan yang sudah dibuat.

> **Ada dua cara membuat laporan:**
> - **Buat Laporan** langsung dari form.
> - **Pratinjau** dulu untuk melihat tampilan laporan sebelum diunduh.

- [ ] Laporan RKAP sudah dibuat
- [ ] Laporan Keuangan (P&L) sudah dibuat
- [ ] Laporan Risiko sudah dibuat
- [ ] Laporan Realisasi Anggaran sudah dibuat
- [ ] Laporan Performa (KPI) sudah dibuat
- [ ] Semua laporan sudah diunduh

---

## Lampiran A — Daftar Pesan Error & Cara Mengatasinya

### A.1 Pesan Error Saat Pengisian

| Pesan | Arti | Solusi |
|---|---|---|
| `Data divisi Anda tidak ditemukan. Silakan hubungi administrator.` | Akun Anda belum terhubung ke data divisi | Hubungi administrator untuk linkages akun ke divisi |
| `Program kerja hanya bisa dibuat untuk sasaran dengan rating A ke atas.` | Sasaran departemen yang dipilih rating-nya di bawah A | Pilih sasaran lain atau minta admin mengubah rating sasaran |
| `Minimal satu penyebab identifikasi risiko wajib diisi.` | Tidak ada baris penyebab yang terisi | Tambahkan minimal satu baris penyebab |
| `Minimal satu strategi mitigasi wajib diisi.` | Tidak ada baris strategi yang terisi | Tambahkan minimal satu strategi |
| `Skor & Level tidak terisi otomatis` | Pasangan Probabilitas × Dampak belum punya Level | Minta admin melengkapi Risk Score & Levels (0.9d) |
| `Proposal tidak tersedia untuk rencana investasi ini.` | File proposal belum diunggah | Buka Edit, unggah file proposal (PDF/DOC), lalu ajukan ulang |
| `Tidak ada Form 1 yang dapat diajukan untuk evaluasi.` | Tidak ada risiko yang memenuhi syarat (belum ada strategi + program kerja) | Pastikan semua risiko sudah punya strategi dan program kerja |
| `Risiko yang sudah memiliki program kerja tidak bisa dihapus.` | Risiko sudah punya program kerja terkait | Hapus program kerjanya dulu, lalu hapus risikonya |
| `Data Form 1 tidak valid.` | Ada baris yang tidak sesuai aturan di file import | Perbaiki file Excel sesuai template, lalu import ulang |
| `Lampiran arahan direksi tidak tersedia.` | Belum ada file arahan yang diunggah | Unggah file arahan lebih dulu |
| `Risiko dalam status [X] dan tidak dapat diubah. Hubungi E-RKAP Admin bila diperlukan perbaikan.` | Data Form 1 sudah dievaluasi (disetujui) | Hubungi Admin E-RKAP untuk membuka kunci |
| `Anda tidak memiliki izin untuk melakukan review. Hubungi Departemen Anggaran / Controller.` | Role Anda tidak punya hak review ZBB | Hubungi PPK atau Controller untuk review |

### A.2 Pesan Error Saat Pengesahan

| Pesan | Arti | Solusi |
|---|---|---|
| `Fase RKAP harus dijalankan berurutan` | Ada fase yang dilompati | Kembali ke fase sebelumnya, lalu majukan satu per satu |
| `Periode RKAP sudah memasuki fase [X]` | Mencoba reset fase, tapi status sudah Disetujui | Hubungi Admin E-RKAP |
| `Periode RKAP tidak dapat dihapus karena sudah memasuki fase [X]` | Mencoba menghapus, tapi sudah di fase akhir | Tidak bisa dihapus. Hubungi Admin |
| `Periode RKAP tidak dapat dihapus karena masih memiliki company target` | masih ada Sasaran Perusahaan terkait | Hapus Sasarannya dulu |
| `Stage Gate untuk level ini belum disetujui. Selesaikan Gate Review terlebih dahulu.` | Gate Review belum selesai di level tersebut | Buka menu Stage Gate Review, selesaikan semua gate di level itu |
| `Bukan giliran Anda untuk menyetujui dokumen ini.` | Sudah disetujui oleh level sebelumnya, atau belum giliran Anda | Tunggu giliran Anda, atau login sebagai approver yang sesuai |
| `Dokumen ini sudah disetujui.` | Mencoba menyetujui dokumen yang sudah disetujui | — |
| `Dokumen ini sudah diajukan sebelumnya.` | Mencoba mengajukan ulang yang sudah `submitted` | Tunggu proses approval |
| `Dokumen sudah ditandai tersebar.` | Mencoba menandai dua kali | Sudah selesai |

### A.3 Pertanyaan Umum (FAQ)

**T: Mengapa tombol Edit/Hapus saya hilang?**
J: Karena data Anda sudah disetujui atau sudah terkunci (lihat 3.1). Ini normal.

**T: Mengapa saya tidak bisa membuat Program Kerja untuk sasaran saya?**
J: Rating sasaran departemen Anda di bawah A. Minta admin revise ratingnya, atau pilih sasaran lain.

**T: Mengapa saya tidak bisa mengajukan Rencana Investasi untuk persetujuan?**
J: File proposal belum diunggah. Unggah proposal (PDF/DOC) terlebih dahulu.

**T: Apakah ada email atau WhatsApp yang dikirim saat pengajuan/persetujuan?**
J: Tidak. Notifikasi hanya berupa notifikasi internal di aplikasi, yang muncul di menu notifikasi dan badge merah di menu **Approval**.

**T: Saya adalah Cost Owner. Mengapa saya hanya melihat data divisi saya sendiri?**
J: memang begitu. Cost Owner sengaja dibatasi hanya untuk melihat dan mengisi data divisinnya sendiri. Untuk melihat semua divisi, hubungi Admin E-RKAP.

**T: Bagaimana cara melihat siapa saja yang sudah menyetujui oleh siapa?**
J: Buka **Lifecycle RKAP** → **Detail & Lifecycle** → panel **Riwayat Persetujuan**. Di sana terlihat nama, waktu, dan catatan dari setiap approver.

**T: Bagaimana cara mengetahui siapa saja yang mengubah data?**
J: Buka menu **Audit Trail**. Terlihat seluruh aktivitas: siapa, kapan, dan perubahan apa yang dilakukan.

**T: Saya ingin mengoreksi data yang sudah salah setelah periode di-arsip. Apa yang harus saya lakukan?**
J: Hubungi Admin E-RKAP. Data arsip tidak bisa diubah langsung.

**T: Bisa tidak mengisi periode RKAP lebih dari satu tahun?**
J: Bisa. Buat periode baru untuk tahun berikutnya, dan ulangi seluruh langkah dari Tahap 0 hingga 12.

---

## Lampiran B — Checklist Uji End-to-End Ringkas

Gunakan checklist ini untuk memverifikasi bahwa satu siklus penuh sudah ditumbangkan dengan benar.

### Persiapan
- [ ] Semua Master Data (Tahap 0) lengkap
- [ ] Risk Matrix terisi untuk semua pasangan Probabilitas × Dampak

### Inisiasi
- [ ] Periode RKAP 2026 dibuat
- [ ] Fase = Inisiasi & Kick-off
- [ ] Kick-off: tanggal, catatan, peserta terisi
- [ ] Arahan direksi: file + catatan terisi
- [ ] Fase dimajukan ke Penyusunan

### Form 1
- [ ] Sasaran Perusahaan dibuat
- [ ] Sasaran Departemen dibuat (rating A)
- [ ] Identifikasi Risiko, Penyebab, Dampak, Analisis, Strategi lengkap
- [ ] Form 1 diajukan dan disetujui Manajemen Risiko
- [ ] Data Form 1 terkunci

### Form 2
- [ ] Program Kerja dibuat (persentase bulanan lengkap)
- [ ] Program Kerja diajukan
- [ ] PPK menyetujui
- [ ] Controller menyetujui

### Form 3
- [ ] Biaya Rutin dibuat
- [ ] Biaya Rutin diajukan
- [ ] PPK menyetujui
- [ ] Controller menyetujui

### Form 4
- [ ] Rencana Investasi dibuat + proposal diunggah
- [ ] Rencana Investasi diajukan
- [ ] Semua Stage Gate Review disetujui
- [ ] PPK menyetujui
- [ ] Manajemen Aset menyetujui
- [ ] Direksi Keuangan menyetujui

### Konsolidasi
- [ ] Fase dimajukan ke Konsolidasi & Review
- [ ] Review ZBB dibangun
- [ ] Semua kenaikan sudah dijustifikasi
- [ ] Konsolidasi anggaran investasi dijalankan
- [ ] Rencana Pendapatan & Beban diisi
- [ ] Laba Rugi dibuat
- [ ] Simulasi skenario dijalankan

### Finalisasi & Pengesahan
- [ ] Semua data diverifikasi lengkap
- [ ] Fase dimajukan ke Finalisasi & Pengesahan
- [ ] Penguncian data terkonfirmasi
- [ ] Dokumen RKAP diajukan
- [ ] Komisaris menyetujui
- [ ] Direksi menyetujui
- [ ] Status = Disetujui

### Disahkan & Distribusi
- [ ] Fase dimajukan ke Disahkan
- [ ] Tanggal Penetapan terisi
- [ ] Dokumen ditandai Didistribusikan
- [ ] Admin E-RKAP menerima notifikasi

### Arsip
- [ ] Fase dimajukan ke Arsip
- [ ] Semua fase hijau (selesai)
- [ ] Riwayat Persetujuan lengkap
- [ ] Audit Trail terisi

### Monitoring
- [ ] Realisasi anggaran bulanan terisi
- [ ] Realisasi program kerja terisi
- [ ] Risk Assessment Bulanan terisi
- [ ] Performance Scorecard (KPI) terisi

### Report
- [ ] Laporan RKAP dibuat
- [ ] Laporan Keuangan dibuat
- [ ] Laporan Risiko dibuat
- [ ] Laporan Realisasi dibuat
- [ ] Laporan Performa dibuat
- [ ] Semua laporan diunduh

---

## Lampiran C — Skenario Pengujian Reset

Untuk menguji ulang bagian awal siklus, Anda bisa me-reset dengan salah satu cara berikut.

### Skenario 1: Mengulang dari Form 1
1. Pastikan Form 1 **belum** disetujui (status masih Draft)
2. Hapus atau perbaiki data sesuai kebutuhan
3. Ajukan ulang Form 1

> **Perhatian:** Kalau Form 1 sudah diajukan (dan belum ditolak), datanya sudah terkunci. Hubungi Admin E-RKAP untuk membuka kunci.

### Skenario 2: Mengulang dari Awal Periode
1. Buka **Lifecycle RKAP** → **Detail & Lifecycle**
2. Klik **Reset Fase ke Inisiasi**
3. Fase kembali ke Inisiasi & Kick-off
4. Ulangi seluruh langkah dari Tahap 1

> **Syarat:** Hanya bisa dilakukan jika status dokumen masih **Draft** atau **Ditolak**. Kalau sudah Disetujui, hubungi Admin E-RKAP.

### Skenario 3: Menghapus Periode RKAP
1. Buka **Lifecycle RKAP**
2. Klik **Hapus** pada baris periode
3. Konfirmasi: *"Hapus Periode RKAP?"* → **Ya, Hapus!**

> **Syarat:** Hanya bisa jika periode belum punya Company Target dan belum memasuki fase akhir.

### Skenario 4: Mengulang dengan Periode Baru
Jika ingin menguji dari awal tanpa mengganggu periode lama:
1. Buat periode RKAP baru (misal tahun 2027) di **Tambah Periode RKAP**
2. Ulangi seluruh langkah dari Tahap 1 dengan periode baru

---

## Penutup

Dengan mengikuti tutorial ini dari Tahap 0 hingga Tahap 12, Anda akan memiliki satu periode RKAP yang lengkap — dari master data, penyusunan sasaran, pengajuan program kerja dan anggaran, persetujuan berjenjang, pengesahan, distribusi, hingga arsip, dan dilanjutkan dengan monitoring bulanan serta pelaporan.

Selamat mencoba! Gunakan checkbox di setiap langkah sebagai panduan pengujian Anda. Jika ada kendala, jangan ragu menghubungi Administrator E-RKAP atau PPK Anda.

---

*Dokumen ini disusun berdasarkan kondisi sistem eRKAP saat ini. Struktur menu, nama field, dan alur persetujuan dapat berubah seiring perkembangan aplikasi. Selalu periksa tampilan aktual di sistem.*
