# Alur Pengajuan & Approval Modul ERKAP

Dokumen ini menjelaskan alur lengkap pengajuan (**submit**) dan persetujuan (**approval**) setiap modul dokumen di E-RKAP (Rencana Kerja & Anggaran Perusahaan), beserta contoh kasus.

---

## 1. Ringkasan Modul & Hierarki Data

ERKAP tersusun berjenjang dari atas ke bawah:

```
erkap_rkap                        → Periode RKAP (tahun)              [approval via RKAP]
  └─ erkap_company_targets        → Sasaran Perusahaan
      └─ erkap_department_targets → Sasaran Departemen (divisi)
          └─ erkap_risk_identifications → Identifikasi Risiko
              └─ erkap_work_programs    → Program Kerja              [approval via Program Kerja]
                  ├─ erkap_routine_costs→ Biaya Rutin (OPEX)         [approval via Biaya Rutin]
                  └─ erkap_investment_plans → Rencana Investasi (CAPEX) [approval via Investasi]
```

Empat tipe dokumen yang memiliki alur approval:

| Tipe | Model | Tabel | Label |
|------|-------|-------|-------|
| `work_program` | `WorkProgram` | `erkap_work_programs` | Program Kerja |
| `routine_cost` | `RoutineCost` | `erkap_routine_costs` | Biaya Rutin |
| `investment_plan` | `InvestmentPlan` | `erkap_investment_plans` | Rencana Investasi |
| `rkap` | `RKAP` | `erkap_rkap` | Periode RKAP |

> Dokumen lain (Sasaran, Identifikasi Risiko, Analisis Risiko, dll) adalah **data master/pendukung** dan tidak melalui alur approval.

---

## 2. Role yang Terlibat

| Role | Peran dalam Approval |
|------|----------------------|
| `erkap-cost-owner` | Input data (program kerja, biaya rutin, investasi) dan **submit pengajuan**. Saat submit, sekaligus menyatakan persetujuannya terhadap datanya sendiri (bukan approver di matriks). |
| `erkap-ppk` | Approver **Level 1** untuk Program Kerja, Biaya Rutin, Rencana Investasi. |
| `erkap-controller` | Approver **Level 2** untuk Program Kerja & Biaya Rutin. Approver **Level 1** untuk Periode RKAP. |
| `erkap-direksi-keuangan` | Approver **Level 2** untuk Rencana Investasi. |
| `erkap-direksi` | Approver **Level 2** untuk Periode RKAP. |
| `erkap-komisaris` | Approver **Level 3** untuk Periode RKAP. |
| `erkap-admin` | Akses penuh (view all + proses approval). |
| `erkap-accounting`, `erkap-risk-manager`, `erkap-auditor` | Read-only pada menu Approval (lihat saja). |

Definisi role & permission terdapat di `database/seeders/RolePermissionSeeder.php`.

---

## 3. Siklus Hidup Dokumen

```
 draft ──submit──▶ submitted ──approve semua level──▶ approved
  │                    │
  │                    └──reject──▶ rejected ──edit+submit ulang──▶ submitted
  └──(di-edit)──▶ masih draft
```

- **draft** — baru dibuat, bisa diedit/dihapus, belum masuk antrian approval.
- **submitted** — sudah diajukan, menunggu approval. Tidak bisa diedit/dihapus.
- **approved** — semua level menyetujui. Selesai.
- **rejected** — ditolak. Bisa diperbaiki lalu diajukan ulang.

Aturan transisi (dari trait `HasApprovalWorkflow`):
- `canBeSubmitted()` → hanya status `draft` atau `rejected` yang bisa disubmit.
- `submit()` menolak dokumen yang sudah `submitted` atau `approved` (`ApprovalService.php:98`).

---

## 4. Mekanisme Umum Approval

Semua logika ada di `app/Services/ApprovalService.php`.

### 4.1 Matriks Approval (`getApprovalMatrix`)

> Cost-owner berperan sebagai **submitter**, bukan approver — pengajuan (tombol submit) sudah merupakan persetujuannya terhadap data sendiri. Maka antrian approval dimulai dari PPK.

| Tipe Dokumen | Level 1 | Level 2 | Level 3 |
|--------------|---------|---------|---------|
| `work_program` | `erkap-ppk` | `erkap-controller` | — |
| `routine_cost` | `erkap-ppk` | `erkap-controller` | — |
| `investment_plan` | `erkap-ppk` | `erkap-direksi-keuangan` | — |
| `rkap` | `erkap-controller` | `erkap-direksi` | `erkap-komisaris` |

### 4.2 Submit — `ApprovalService::submit($model)`

1. Validasi status (harus `draft`/`rejected`).
2. Tentukan tipe dokumen & matriks.
3. Cari **approver tiap level** berdasarkan role + divisi dokumen (`getApproverByRole`). Jika ada level tanpa user ber-role tsb → error, transaksi **rollback**.
4. Set status dokumen `submitted`.
5. **Hapus** approval lama, buat ulang 1 baris `erkap_approvals` per level ber-status `pending` (polimorfik: Program Kerja/Biaya Rutin/Investasi = 2 baris, Periode RKAP = 3 baris).
6. Kirim **notifikasi** ke approver **Level 1** (`approvalable` + `approver`).

### 4.3 Approve — `ApprovalService::approve($model, $user, $notes)`

- **Wajib giliran** (`requireTurn`): dokumen harus ber-status `submitted` dan hanya approval menunggu dengan **level terendah** yang boleh diproses, oleh `approver_id` yang bersangkutan. Jika bukan gilirannya → error "Bukan giliran Anda".
- Baris approval di-set `approved` + `approved_at` + catatan.
- Jika **semua level sudah approved** → status dokumen `approved`.
- Jika belum → **notifikasi** dikirim ke approver level berikutnya.

### 4.4 Reject — `ApprovalService::reject($model, $user, $notes)`

- Giliran yang sama berlaku.
- Baris approval saat ini di-set `rejected` (**alasan penolakan wajib diisi** via modal).
- **Semua baris approval lain yang masih pending ikut ditolak**.
- Status dokumen → `rejected`, lalu notifikasi ke approver level lain yang masih menunggu.

### 4.5 Submit Batch — `ApprovalService::submitBatch(iterable $models)`

- Mengulang `submit()` untuk banyak dokumen sekaligus (per-dokumen tetap menulis approval masing-masing).
- Menghitung hasil: `submitted`, `skipped` (tidak lolos `canBeSubmitted`), `failed` (error per baris, mis. approver kosong). Kegagalan satu baris **tidak menghentikan** baris lain.

---

## 5. Menu Approval

Route utama (`routes/routers/erkap.php` → prefix `erkap/approvals`, guard `permission:erkap.approvals.view`):

| Method | URL | Aksi | Keterangan |
|--------|-----|------|------------|
| GET | `/erkap/approvals` | `index` | Daftar pending **dikelompokkan per divisi** + riwayat |
| GET | `/erkap/approvals/history` | `history` | Riwayat approval milik user |
| GET | `/erkap/approvals/division/{division}` | `division` | Detail pending per divisi (approve/reject per baris) |
| GET | `/erkap/approvals/{type}/{id}` | `show` | Detail 1 dokumen + timeline approval |
| POST | `/erkap/approvals/{type}/{id}/approve` | `approve` | Setujui |
| POST | `/erkap/approvals/{type}/{id}/reject` | `reject` | Tolak |

**Hanya menampilkan approval yang ditugaskan ke user login** (`approver_id = auth()->id()`).

### 5.1 Pengelompokan per Divisi

Struktur menu approval berjenjang:

```
Menu Approval
├── Kelompok per Divisi          (tab "Menunggu Persetujuan")
│   ├── Divisi A
│   │   ├── Program Kerja         (tabel kolom field program kerja)
│   │   ├── Biaya Rutin           (tabel kolom field biaya rutin)
│   │   └── Rencana Investasi     (tabel kolom field investasi)
│   ├── Divisi B
│   │   └── ...
│   └── Tanpa Divisi              (mis. Periode RKAP)
│       └── Periode RKAP
└── Riwayat Approval
```

- Tab **"Menunggu Persetujuan"** di `index` menampilkan kartu per divisi (nama divisi + jumlah dokumen).
- Divisi ditentukan dari rantai dokumen: `WorkProgram → RiskIdentification → DepartmentTarget → division`; dokumen tanpa divisi (mis. RKAP) masuk grup **"Tanpa Divisi"**.
- Klik kartu divisi → halaman `division` menampilkan **kelompok per tipe dokumen** (Program Kerja, Biaya Rutin, Rencana Investasi, Periode RKAP).
- Di dalam setiap tipe dokumen, tabel menampilkan **kolom sesuai field dokumen tersebut** (mis. Biaya Rutin: kebutuhan, pusat biaya, qty, harga, alokasi bulanan, total; Program Kerja: identifikasi risiko, satuan, rencana tahunan, rencana bulanan).
- Setiap baris bisa langsung: **Review** (buka halaman detail), **Setujui**, atau **Tolak** (wajib isi alasan di modal).
- Setelah baris diproses, baris otomatis hilang dari daftar pending.

---

## 6. Alur & Studi Kasus per Modul

### 6.1 Program Kerja (`work_program`)

**Alur:**
1. Cost-owner membuat Program Kerja (harus dari Sasaran dengan rating A ke atas) — `POST /erkap/work-programs` (`work-programs.create`).
2. Submit per baris — `POST /erkap/work-programs/{workProgram}/submit` (`work-programs.submit`). Submit = persetujuan cost-owner.
3. Approval 2 level: **PPK → Controller**.
4. Setelah level akhir (Controller) menyetujui → status `approved`.

**Studi Kasus:**
> Divisi Operasional, Program Kerja **"Pemeliharaan Armada Operasional 2026"** (satuan: unit, rencana tahunan: 120 unit).
> 1. Cost-owner A membuat program kerja di atas (status `draft`).
> 2. A klik **Ajukan Persetujuan** → status `submitted`; 2 baris approval pending dibuat (PPK & Controller). Notifikasi ke PPK (L1).
> 3. PPK (L1) setujui → notifikasi ke Controller.
> 4. Controller (L2) setujui → **semua level selesai** → status `approved`.
>
> Jika di langkah 3 PPK **menolak** dengan alasan — baris approval L1 & L2 jadi `rejected`, Program Kerja kembali ke `rejected`, bisa diperbaiki & diajukan ulang.

---

### 6.2 Biaya Rutin (`routine_cost`)

**Alur:**
1. Cost-owner input Biaya Rutin satu per satu (`POST /erkap/routine-costs`). Tiap baris = satu kebutuhan (need) dengan elemen biaya, Qty, harga, dan alokasi 12 bulan. Total harus valid (`qty × harga` = jumlah bulanan, kecuali kumulatif).
2. Submit → **Dua opsi**:
   - **Per baris**: `POST /erkap/routine-costs/{routineCost}/submit`.
   - **Sekaligus (batch)**: tombol "Ajukan Semua Persetujuan" → `POST /erkap/routine-costs/submit-batch`. Hanya baris ber-status `draft`/`rejected` di scope divisi user yang diproses. **Yang masuk sistem tetap per baris** (tiap baris dibuatkan approval sendiri).
3. Approval 2 level: **PPK → Controller**.
4. Data selesai disetujui per baris → bisa direkapitulasi lewat menu **Konsolidasi OPEX** (hanya menampilkan ringkasan, bukan approval).

**Studi Kasus (submit batch):**
> Cost-owner Divisi Umum selesai input 5 baris Biaya Rutin (ATK, langganan internet, listrik, dll), semuanya `draft`.
> 1. A klik **Ajukan Semua Persetujuan** → 5 baris langsung jadi `submitted`; masing-masing membuat 2 approval pending (total 10 baris approval di `erkap_approvals`).
> 2. Di menu **Approval**, oba baris muncul terkelompok di kartu **"Divisi Umum"**.
> 3. Klik kartu → daftar 5 baris. Approver menyetujui/menolak baris per baris.
> 4. Baris yang disetujui semua level → `approved`; yang ditolak → `rejected`.

---

### 6.3 Rencana Investasi (`investment_plan`)

**Alur:**
1. Cost-owner membuat Rencana Investasi (kategori, tipe, kriteria investasi, Qty × harga = total).
2. Submit per baris — `POST /erkap/investment-plans/{investmentPlan}/submit`.
3. Approval 2 level: **PPK → Direksi Keuangan**.
4. Level akhir (Direksi Keuangan) menyetujui → `approved`.

**Studi Kasus:**
> Divisi IT, Rencana Investasi **"Pembelian 50 Laptop Pengganti 2026"** (Qty: 50, Rp 15.000.000/unit, total Rp 750.000.000, tipe kumulatif).
> 1. Cost-owner membuat & submit (status `submitted`).
> 2. PPK (L1) setujui → **Direksi Keuangan** (L2) setujui → `approved`.
> 3. Controller tidak terlibat dalam alur investasi; hanya bisa melihat.

---

### 6.4 Periode RKAP (`rkap`)

**Alur:**
1. Admin/Controller membuat Periode RKAP (tahun) — `POST /erkap/rkap`.
2. Submit — `POST /erkap/rkap/{rkap}/submit`.
3. Approval 3 level: **Controller → Direksi → Komisaris** (level 1 bukan cost-owner; tidak terikat divisi → termasuk grup "Tanpa Divisi" di menu approval).
4. Level akhir (Komisaris) menyetujui → `approved`, menandakan RKAP tahunan resmi.

**Studi Kasus:**
> Periode RKAP **2026** dibuat.
> 1. Admin/Controller membuat & submit Periode RKAP 2026.
> 2. Controller (L1) setujui → Direksi (L2) setujui → Komisaris (L3) setujui → RKAP 2026 `approved`.

---

## 7. Notifikasi

`App\Notifications\ApprovalNotification` (channel: database):

| Context | Pesan |
|---------|-------|
| submit (`submitted`) | "Dokumen {tipe} {judul} membutuhkan persetujuan Anda." (ke approver level berikutnya) |
| approve lanjutan (`submitted`) | "membutuhkan persetujuan level berikutnya." |
| reject (`rejected`) | "Dokumen {tipe} {judul} telah ditolak." |

Notifikasi mengarah ke halaman detail approval dokumen. Badge jumlah pending tampil di sidebar menu **Approval** (`app-sidebar.blade.php`).

---

## 8. Aturan Penting & Batasan

- **Giliran harus berurutan** — level 2 tidak bisa approve sebelum level 1, dan approver yang berbeda tidak bisa menggantikan giliran. Dilindungi `requireTurn()`.
- **Sekali submit, data terkunci** — dokumen `submitted`/`approved` tidak bisa diedit/dihapus.
- **Ditolak = salah satu level cukup** — reject di level manapun langsung menggagalkan seluruh baris pending.
- **Approver otomatis** — approver tiap level dicari berdasarkan role + divisi dokumen. Jika role tidak di-assign ke user manapun, submit akan gagal dengan pesan "Tidak ditemukan approver ...".
- **Batch submit Biaya Rutin** hanya memproses baris `draft`/`rejected` di scope divisi pengguna (untuk role `erkap-cost-owner`); kegagalan satu baris tidak menghentikan baris lain.

---

## 9. Referensi Kode

| Komponen | Lokasi |
|----------|--------|
| Logika approval | `app/Services/ApprovalService.php` |
| Controller approval | `app/Http/Controllers/Erkap/ApprovalController.php` |
| Controller submit per modul | `WorkProgramController`, `RoutineCostController`, `InvestmentPlanController`, `RKAPController` |
| Model & trait approval | `app/Models/Erkap/Approval.php`, `app/Models/Erkap/Traits/HasApprovalWorkflow.php` |
| Sidebar & menu | `resources/views/layouts/partials/erkap/app-sidebar.blade.php` |
| Views approval | `resources/views/erkap/approvals/{index,show,division}.blade.php` |
| View submit biaya rutin batch | `resources/views/erkap/routine-cost/index.blade.php` |
| Route | `routes/routers/erkap.php` |
| Role & permission | `database/seeders/RolePermissionSeeder.php` |
| Notifikasi | `app/Notifications/ApprovalNotification.php` |