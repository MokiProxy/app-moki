# Tutorial Pengisian E-RKAP (untuk Pengguna Akhir)

> Dokumen ini menjelaskan **alur lengkap penyusunan, pengajuan, dan pengesahan RKAP** dari
> awal sampai akhir, ditulis untuk pengguna aplikasi (bukan untuk teknis).
> Baca dari urutan awal, lalu ikuti bagian yang sesuai dengan peran Anda.

---

## 1. Apa itu E-RKAP?

E-RKAP adalah aplikasi untuk menyusun **Rencana Kerja dan Anggaran Perusahaan (RKAP)**
secara terstruktur, mulai dari sasaran perusahaan, sasaran departemen, risiko dan strateginya,
program kerja beserta anggarannya, hingga pengesahan oleh direksi dan komisaris.

Satu periode RKAP melewati **6 tahap (fase) berurutan**:

```
Inisiasi & Kick-off  →  Penyusunan  →  Konsolidasi & Review  →  Finalisasi & Pengesahan  →  Disahkan  →  Arsip
```

Setiap tahap dijalankan berurutan. Data anggaran baru bisa diubah saat penyusunan;
setelah masuk tahap Finalisasi & Pengesahan, data **terkunci otomatis**.

---

## 2. Peran yang Terlibat

| Peran | Tugas utama |
|---|---|
| **Pemilik Anggaran (Cost Owner)** | Mengisi formulir (Form 1 s.d. Form 4) dan data realisasi bulanan |
| **PPK (Pejabat Pembuat Komitmen)** | Mengelola/memajukan tahap periode, mengajukan dokumen, menyetujui tingkat pertama program kerja & biaya rutin, penilai Stage Gate |
| **Budget Controller** | Menyetujui program kerja & biaya rutin (tingkat kedua) |
| **Manajemen Aset** | Menilai kelayakan investasi (Stage Gate) dan menyetujui rencana investasi |
| **Manajer Risiko** | Mengevaluasi dan menyetujui register risiko |
| **Direksi Keuangan** | Menilai kelayakan investasi akhir dan menyetujui rencana investasi |
| **Komisaris** | Menyetujui periode RKAP di tingkat pertama |
| **Direksi** | Menyetujui periode RKAP (tingkat terakhir / pengesahan) |
| **Admin / Operator Master Data** | Menyiapkan data dasar (akun, elemen biaya, pusat biaya, kriteria risiko & investasi) |

> Tiap dokumen yang diajukan hanya boleh disetujui oleh **pemegang giliran persetujuan**.
> Menu **Approval** di sisi kiri menampilkan jumlah dokumen yang menunggu persetujuan Anda.

---

## 3. Tahap 1 — Inisiasi & Kick-off

**Siapa yang melakukannya:** PPK (dengan hak kelola periode).

Cara:

1. Buka menu **Lifecycle RKAP** → **Periode RKAP**.
2. Buat **periode baru** untuk tahun berjalan.
3. Buka halaman detail periode, lalu isi bagian:
   - **Kick-off / Sosialisasi Penyusunan RKAP** — tombol *Isi / Ubah*:
     - Tanggal kick-off dan catatan sosialisasi.
     - Daftar peserta yang mengikuti (nama, divisi, dan tanda hadir).
   - **Arahan Direksi / Memo Holding** — tombol *Unggah / Ubah*:
     - Unggah lampiran arahan (surat memo/penetapan) dan catatan.
4. Klik **Majukan ke Penyusunan** (tombol pada panel *Kontrol Fase* di kanan halaman)
   untuk membuka tahap berikutnya.

> Halaman detail periode menampilkan bagan **Fase Lifecycle RKAP**, status dokumen,
> riwayat persetujuan, dan audit trail setiap perubahan.

---

## 4. Tahap 2 — Penyusunan (Mengisi Form 1 s.d. Form 4)

**Siapa yang melakukannya:** Pemilik Anggaran (pengisi formulir per divisi/departemen).
**Urutan pengisian wajib:** Form 1 → Form 2 → Form 3 → Form 4.

### 4.1 Sasaran (Form 1)

Menu **Form 1 — Sasaran & Asesmen Risiko** → submenu **Sasaran**:

1. **Sasaran Perusahaan** — isi sasaran/target perusahaan untuk tahun tersebut.
2. **Sasaran Departemen** — isi sasaran departemen Anda dan kaitkan dengan sasaran perusahaan,
   lengkapi rating prioritasnya.

### 4.2 Identifikasi & Asesmen Risiko (Form 1)

Pada menu yang sama, submenu **Identifikasi Risiko**, **Analisis Risiko**, dan **Strategi Risiko**:

1. **Identifikasi Risiko** — tuliskan risiko yang relevan untuk mencapai sasaran, termasuk
   alasan dan dampaknya. Bisa dikerjakan per baris atau diunggah massal lewat
   **Form 1 (Import/Export)** di submenu Identifikasi Risiko.
2. **Analisis Risiko** — tetapkan tingkat kemungkinan dan dampak (sistem menghitung skor
   dan level risiko secara otomatis), lalu beri **peringkat risiko** (prioritas).
3. **Strategi Risiko** — untuk setiap risiko, isi **strategi departemen** dan **perlakuan risikonya**
   (misal: menghindari, mengurangi, mengalihkan, menerima), lengkap dengan penanggung
   jawab dan target penyelesaian.
4. Setiap risiko yang diajukan **wajib** memiliki strategi, program kerja, dan perlakuan risiko.
   Tanpa itu, risiko tidak dapat diajukan.

### 4.3 Program Kerja (Form 2)

Menu **Form 2 — Jadwal Kerja** → **Program Kerja**:

1. Buat program kerja yang mendukung mitigasi risiko departemen Anda.
2. Isi target besaran (unit) dan rincian rencana bulanan (Januari s.d. Desember),
   termasuk persentase/kumulatif pencapaian bila tersedia.

### 4.4 Biaya Rutin (Form 3 — Biaya Umum / OPEX)

Menu **Form 3 — Biaya Umum** → **Biaya Rutin**:

1. Buat baris anggaran per kebutuhan (contoh: gaji, operasional, jasa).
2. Isi elemen biaya, pusat biaya, besaran/kuantitas, satuan, harga satuan, dan
   rincian per bulan — total terhitung otomatis.

### 4.5 Rencana Investasi (Form 4 — Biaya Investasi / CAPEX)

Menu **Form 4 — Biaya Investasi** → **Rencana Investasi**:

1. Buat rencana investasi untuk program kerja Anda.
2. Isi kategori, tipe, kriteria investasi, deskripsi kebutuhan, jumlah, satuan, harga satuan,
   prioritas urutan investasi, dan rincian pembayaran per bulan.
3. **Wajib melampirkan proposal kelayakan** (file PDF) — tanpa proposal, investasi
   tidak dapat diajukan.
4. Pantau totalnya pada **Anggaran Investasi** (Ringkasan Nilai Investasi dan Distribusi Pembayaran).

> **Urutan pengisian penting:** buat **Sasaran** lalu **Identifikasi Risiko** sebelum program kerja
> (program kerja "menempel" pada risiko). Biaya rutin dan rencana investasi "menempel" pada
> program kerja. Jadi pastikan turunan diisi setelah induknya ada.

---

## 5. Tahap 3 — Pengajuan & Persetujuan

**Siapa yang melakukannya:** PPK mengajukan; pejabat berwenang memberikan persetujuan.
Setelah seluruh formulir terisi, dokumen diajukan satu per satu. Alurnya:

| Dokumen | Alur persetujuan |
|---|---|
| **Register Risiko (Form 1)** | Diajukan → disetujui **Manajer Risiko** |
| **Program Kerja (Form 2)** | **PPK** (tingkat 1) → **Budget Controller** (tingkat 2) |
| **Biaya Rutin (Form 3)** | **PPK** (tingkat 1) → **Budget Controller** (tingkat 2) |
| **Rencana Investasi (Form 4)** | **Stage Gate Review** berurutan, lalu **PPK** → **Manajemen Aset** → **Direksi Keuangan** |

### 5.1 Stage Gate Review (khusus Rencana Investasi)

Sebelum persetujuan, setiap rencana investasi dinilai melalui **4 gerbang (gate)**
pada menu **Form 4 — Biaya Investasi** → **Stage Gate Review**, berurutan:

1. **Proposal** — dinilai PPK: apakah proposal kelayakan lengkap dan dapat diterima.
2. **Analisis CBA** — dinilai PPK: apakah kajian biaya-manfaat (cost-benefit) layak.
3. **Aset** — dinilai Manajemen Aset: kelayakan dari sisi aset/perusahaan.
4. **Direksi Keuangan** — dinilai Direksi Keuangan: kelayakan finansial akhir.

Setiap gerbang diberi hasil (**layak** / **tidak layak**) dan catatan. Gerbang merupakan
**prasyarat** bagi persetujuan tingkat yang berwenang — artinya, semakin tinggi nilainya,
semakin banyak gerbang yang harus selesai lebih dulu.

### 5.2 Memberi Persetujuan / Penolakan

Proses persetujuan berjalan **bertingkat** dan **bergiliran**:

1. Setelah dokumen diajukan, statusnya menjadi *Menunggu Persetujuan* dan giliran
   persetujuan berada pada tingkat pertama (sesuai tabel di atas).
2. Pejabat yang bersangkutan membuka menu **Approval**, memeriksa dokumen, lalu memilih
   **Setujui** (bisa dengan catatan) atau **Tolak** (wajib disertai catatan).
3. Jika **disetujui**, giliran berpindah ke tingkat berikutnya otomatis (pemegang giliran
   berikutnya mendapat pemberitahuan).
4. Jika **ditolak**, dokumen kembali ke status *Ditolak* dan dapat diperbaiki lalu diajukan
   ulang.

---

## 6. Tahap 4 — Konsolidasi & Review

**Siapa yang melakukannya:** PPK (terbantu penilaian antar-lini).

1. Menu **Zero Based Budgeting** → **Review ZBB**:
   - Sistem membangun baris-baris anggaran untuk direview berdasarkan kenaikan dari dasar nol.
   - PPK mereview setiap baris yang memblokir konsolidasi dan memberikan status
     (misal *disetujui*) beserta justifikasi untuk pos yang naik.
   - Konsolidasi baru bisa berjalan setelah seluruh baris penahan selesai direview.
2. Menu **Form 4 — Biaya Investasi** → **Anggaran Investasi** dan subsistem anggaran:
   - **OPEX** (biaya rutin) dan **CAPEX** (rencana investasi) dikonsolidasikan,
     dan selisih anggaran (variance) dihitung ulang otomatis.
3. Menu **Financial Projection**: lengkapi **Rencana Pendapatan** dan **Rencana Beban**
   agar tampilan **Laba Rugi (P&L)** dan **Simulasi Skenario** dapat disusun.
4. Memantau kelengkapan dapat dilakukan lewat **Dashboard**, **Analytics & Widgets**,
   dan **Report Center**.

Setelah seluruh persetujuan dan review selesai, PPK mengklik
**Majukan ke Finalisasi & Pengesahan**.

---

## 7. Tahap 5 — Finalisasi & Pengesahan

**Siapa yang melakukannya:** PPK mengajukan periode; Komisaris dan Direksi menyetujui.

1. PPK **mengajukan periode RKAP** itu sendiri (dokumen "Periode RKAP").
2. Persetujuan periode RKAP berlangsung **2 tingkat**:
   - Tingkat 1: **Komisaris**.
   - Tingkat 2: **Direksi** → periode berstatus **Disetujui/Disahkan**.
     Tanggal penetapan tercatat otomatis.
3. PPK mengklik **Tandai Didistribusikan** setelah dokumen disebarluaskan kepada unit terkait.
4. PPK mengklik **Majukan ke Arsip** untuk menutup periode RKAP.

> Bila periode ditolak, status kembali menjadi *Ditolak*. Selama suatu periode masih berstatus
> **Draft** atau **Ditolak**, tombol **Reset Fase ke Inisiasi** dapat dipakai untuk mengulang
> tahapan dari awal. Setelah masuk Finalisasi & Pengesahan, data anggaran **terkunci**
> dan tidak dapat diubah.

---

## 8. Tahap 6 — Pasca Pengesahan: Monitoring & Realisasi

**Siapa yang melakukannya:** Pemilik Anggaran mengisi realisasi; manajemen memantau.

Menu **Monitoring & Realisasi**:

1. **Realisasi Anggaran (BvA)** — isi realisasi bulanan per biaya rutin & rencana investasi;
   selisih anggaran vs realisasi dihitung otomatis.
2. **Realisasi Program Kerja** — isi capaian target program per bulan dan persentase penyelesaian.
3. **Risk Assessment Bulanan** — isi penilaian risiko secara berkala.
4. **Performance Scorecard (KPI)** — pantau KPI perusahaan/departemen.

Kinerja dan realisasi dapat dipantau lewat **Dashboard**, **Analytics & Widgets**,
dan **Report Center**.

---

## 9. Rangkuman Alur Singkat

```
Tahap 1 Inisiasi & Kick-off   →  buat periode, isi kick-off, arahan direksi      [PPK]
Tahap 2 Penyusunan            →  Form 1 (sasaran+risiko) → Form 2 (program)     [Pemilik Anggaran]
                                → Form 3 (biaya rutin) → Form 4 (investasi+proposal)
Tahap 3 Pengajuan&Persetujuan →  Form 1 → Manajer Risiko                         [PPK + Pejabat]
                                Form 2 & 3 → PPK → Budget Controller
                                Form 4 → Gate Review (proposal→CBA→aset→keuangan)
                                          → PPK → Manajemen Aset → Direksi Keuangan
Tahap 4 Konsolidasi&Review    →  Review ZBB, konsolidasi OPEX/CAPEX, P&L          [PPK]
Tahap 5 Finalisasi&Pengesahan →  periode RKAP → Komisaris → Direksi → ditetapkan [PPK + Komisaris + Direksi]
                                → tandai didistribusikan → arsip
Tahap 6 Monitoring&Realisasi  →  BvA, realisasi program, risiko, KPI              [Pemilik Anggaran]
```

Setiap aksi yang mengubah data tercatat di **Audit Trail** sehingga tetap bisa ditelusuri.
Selamat bekerja — pastikan tiap formulir lengkap dan lampiran proposal tersedia sebelum
mengajukan dokumen.