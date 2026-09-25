# Planning: Penyesuaian Role & Permission E-RKAP agar Sesuai Tutorial End-User

> **Status:** BELUM DIIMPLEMENTASI
> **Terkait:** `agents/tutorial-pengisian-erkap-end-user-new.md` (acuan perilaku),
> `agents/planning-perubahan-alur-pt-bmi.md` (SELESAI DIIMPLEMENTASI),
> `database/seeders/RolePermissionSeeder.php` (file yang diubah).

---

## 1. Tujuan

Menyelaraskan **role & permission E-RKAP** di `RolePermissionSeeder.php` agar
perilaku aplikasi persis seperti yang didokumentasikan pada tutorial pengguna akhir
(*tutorial-pengisian-erkap-end-user-new.md*) dan konsisten dengan matriks persetujuan
yang sudah ada di `ApprovalService::getApprovalMatrix()` (pasca penghapusan BMI).

## 2. Sumber Acuan

| Acuan | Status | Isi |
|---|---|---|
| `ApprovalService::getApprovalMatrix()` | sudah sesuai | work_program/routine_cost: PPK→Controller; investment_plan: PPK→Manajemen Aset→Direksi Keuangan; rkap: Komisaris→Direksi; risk_register: Manajer Risiko |
| `InvestmentGateReviewService::STAGES` | sudah sesuai | proposal/PPK, cba/PPK, aset/Manajemen Aset, direksi_keuangan/Direksi Keuangan |
| `RKAP::PHASES` | sudah sesuai | initiation → preparation → consolidation → finalization → approved → archived |
| `RKAPLifecycleService` | sudah sesuai | BMI/Gate-Review-BMI dihapus |
| Tutorial end-user (baru) | **acuan utama** | alur 6 tahap + tanggung jawab per peran |

## 3. Hasil Analisis Codebase Menyusul Glossary

- Semua route E-RKAP menggunakan middleware `permission:erkap.<entity>.<action>`
  (`routes/routers/erkap.php`).
- Approval bersifat **role-based per matriks** (bukan per permission tambahan):
  `ApprovalController` hanya menampilkan approval milik `auth()->id()` (dari matriks).
  Artinya permission `*.approve`/`*.reject` di role non-matrik **tidak berpengaruh
  fungsional** — hanya mewarisi izin halaman, sehingga bisa dianggap "stale".
- Sidebar E-RKAP (`resources/views/layouts/partials/erkap/app-sidebar.blade.php`)
  menyembunyikan menu sesuai `@can(...)`:
  - `erkap.company-targets.view` → submenu **Sasaran Perusahaan** (baris 175)
  - `erkap.reports.view` → menu **Report Center** (baris 42)
  - `erkap.zbb-reviews.view` → menu **Zero Based Budgeting** (baris 279)
- `GenerateScheduledReports` memberitahukan kelahiran laporan kepada user dengan
  permission `erkap.reports.view` (baris 87-89).

## 4. Temuan Gap (Role Seharusnya vs Kondisi Seeder)

| # | Role | Per tutorial | Kondisi seeder saat ini | Aksi |
|---|---|---|---|---|
| G1 | `erkap-ppk` | Hak kelola periode: buat periode, kick-off, arahan, advance, submit periode, distribusi, arsip, reset. Mengajukan dokumen (Tahap 3). Menilai Stage Gate proposal + CBA. Review ZBB (Tahap 4). Pantau konsolidasi/P&L. | Punya view/approve/reject untuk work-programs, routine-costs, investment-plans, gate review. **TIDAK punya** `rkap.*`, `zbb-reviews.*`, `budget-capex.view`, `profit-loss.view`, submit dokumen, reports. | Tambahkan (lihat §5.2) |
| G2 | semua role | Report Center dipakai untuk monitoring (Tahap 4 & 6) oleh PPK/manajemen | Permission `erkap.reports.view` & `erkap.reports.generate` **tidak pernah dibuat** di seeder → semua role non-super-admin dapat 403. | Buat permission + assign (lihat §5.5) |
| G3 | `erkap-cost-owner` | Form 1: **tidak** menginput Sasaran Perusahaan — hanya inisiator RKAP (PPK) yang menginput. Form 4: memantau **Anggaran Investasi** (§4.5) & mengunggah proposal. | (versi lama: `company-targets` di-*revoke*; lalu sempat diberi lagi pada iterasi 1) | Cabut `company-targets.*` dari cost-owner; berikan `company-targets.view/create/edit` ke `erkap-ppk` (lihat §5.4) |
| G4 | `erkap-controller` | Hanya **L2 persetujuan Program Kerja & Biaya Rutin** + pengawasan anggaran. Matriks tidak memuat controller untuk RKAP/Investasi/Gate. | Menurunkan semua permission PPK (termasuk IP approve/reject + gate review + rkap approve/reject/edit). | Kunci jadi lebih ramping (lihat §5.3) |
| G5 | `erkap-cost-owner` | ZBB dibangun sistem & direview PPK | cost-owner punya `zbb-reviews.create` (ditetapkan sebagai tali sistem) | Biarkan (`view`+`create`), tidak merugikan — build bersifat idempoten |

## 5. Rencana Implementasi

Semua perubahan hanya pada **`database/seeders/RolePermissionSeeder.php`**.

### 5.1 Tambah definisi permission baru
```php
['name' => 'erkap.reports.view',     'guard_name' => 'web'],
['name' => 'erkap.reports.generate', 'guard_name' => 'web'],
```

### 5.2 Peran `erkap-ppk` (tutup G1)
Tambahkan ke `givePermissionTo`:
- `erkap.rkap.view`, `erkap.rkap.create`, `erkap.rkap.edit`, `erkap.rkap.submit`
- `erkap.work-programs.submit`, `erkap.routine-costs.submit`, `erkap.investment-plans.submit`
- `erkap.zbb-reviews.view`, `erkap.zbb-reviews.create`, `erkap.zbb-reviews.edit`
- `erkap.budget-capex.view`, `erkap.profit-loss.view`
- `erkap.reports.view`

### 5.3 Peran `erkap-controller` (tutup G4)
Ganti `givePermissionTo($erkapPpk->permissions...)` dengan daftar eksplisit:
- menu, approvals.view
- work-programs/routine-costs: view, submit, approve, reject
- investment-plans.view, investment-plans.download
- budget-capex.view, profit-loss.view
- zbb-reviews.view/create/edit
- rkap.view, reports.view

Hilangkan (stale vs matriks): `investment-plans.approve/reject`,
`investment-gates.view/review`, `rkap.approve/reject`, `rkap.edit`.

### 5.4 Sasaran Perusahaan: inisiator RKAP (PPK)
- `erkap-cost-owner`: **cabut** seluruh `erkap.company-targets.*` (view/create/edit/delete)
  → cost-owner tidak bisa membuka halaman Sasaran Perusahaan maupun menginputnya.
  (Dropdown pemilihan sasaran pada form Sasaran Departemen tetap terisi otomatis
  dari `CompanyTarget::all()` secara server-side — cost-owner masih bisa *merujuk*
  sasaran perusahaan yang sudah dibuat PPK, bukan menginputnya.)
- `erkap-ppk` (inisiator): + `erkap.company-targets.view`, `erkap.company-targets.create`,
  `erkap.company-targets.edit` (tanpa delete).
- `erkap-admin`/`admin`: tetap penuh (`delete` tercakup via daftar resource).
- `erkap-auditor`: otomatis mendapat view via pola `erkap.%.view`.
- **UI**: grup menu "Form 1" di sidebar kini tampil bila user punya
  `department-targets.view` **atau** `company-targets.view`; setiap submenu
  (Sasaran Perusahaan, Sasaran Departemen, Identifikasi Risiko, dst.) dirender
  sesuai permission masing-masing. Tombol Tambah/Edit/Hapus pada halaman
  Sasaran Perusahaan juga di-*gate* `@can`.
- Tambahan cost-owner (tetap): `erkap.budget-capex.view`, `erkap.investment-plans.download`,
  `zbb-reviews.view/create`.

### 5.5 Report Center (tutup G2)
- `erkap-admin`: + `erkap.reports.view`, `erkap.reports.generate`
- `admin` (global): + `erkap.reports.view`, `erkap.reports.generate`
- `erkap-ppk`, `erkap-controller`, `erkap-direksi-keuangan`, `erkap-manajemen-aset`,
  `erkap-direksi`, `erkap-komisaris`, `erkap-accounting`, `erkap-risk-manager`: + `erkap.reports.view`
- `erkap-auditor`: otomatis dapat via pola `erkap.%.view` (tanpa generate)
- `erkap-cost-owner`: TIDAK (Report Center khusus monitoring manajemen)

## 6. Keputusan & Alasan

1. **Sasaran Perusahaan diinput hanya oleh inisiator RKAP (PPK).**
   Keputusan akhir menyusul arahan: cost-owner **tidak boleh** mengakses/menginput
   sasaran perusahaan; inisiator RKAP = `erkap-ppk` yang mengisinya, sementara
   `admin`/`erkap-admin` tetap penuh sebagai operator & auditor hanya-baca.
   (Catatan: iterasi 1 sempat mengikuti tutorial §4.1 yang menempatkan Sasaran
   Perusahaan pada cost-owner — keputusan ini **membatalkan** hal tersebut.)
   `delete` tetap admin-only untuk mencegah penghapusan sasaran yang telah terpaut.
2. **Controller dirampingkan.** Matriks persetujuan adalah kebenaran otoritatif;
   controller tidak ada di matriks rkap/investasi/gate. Permission approve/reject
   yang tersisa tidak berfungsi namun menyesatkan audit RBAC → dihapus.
   Jika seorang user memegang `erkap-ppk` sekaligus `erkap-controller`, kemampuan
   IP approval tetap utuh lewat role `erkap-ppk`.
3. **PPK mendapat submit dokumen.** Tutorial §5: "PPK mengajukan dokumen satu per satu".
   Cost-owner tetap memegang submit sebagai backup.
4. **ZBB create dibiarkan pada cost-owner.** `ZBBReviewService::buildReviews` tidak
   menghapus data; bisa dijalankan ulang kapan saja tanpa efek samping.

## 7. Risiko & Mitigasi

| Risiko | Mitigasi |
|---|---|
| Menghapus `rkap.approve/reject` dari controller membingungkan pengguna lama | Matriks telah bergeser ke Komisaris→Direksi; menu Approval tetap berbasis `approver_id` dari matriks, bukan permission |
| Cost-owner (banyak) mengisi satu sasaran perusahaan → duplikat | Tidak ada constraint unik; dikendalikan proses (1 RKAP/tahun, sasaran dibuat satu kali oleh owner). Dicatat sebagai known-limitation |
| `erkap.reports.generate` terlalu longgar | Hanya `admin` & `erkap-admin`; role lain view-only |

## 8. Verifikasi

1. `php -l database/seeders/RolePermissionSeeder.php`
2. `php artisan db:seed --class=RolePermissionSeeder` (DB dev) — verifikasi jumlah
   permission & assignment per role.
3. Test: `php artisan test tests/Unit/Erkap/ApprovalMatrixTest.php
   tests/Feature/Erkap/RKAPLifecycleFeatureTest.php
   tests/Feature/Erkap/ScheduledReportsTest.php
   tests/Feature/Erkap/RiskIdentificationApprovalFeatureTest.php`
   (RefreshDatabase memakai `asset_management_system_test` dari `.env.testing`).
4. Manual: login sebagai Sahlul (`staff,erkap-ppk`) → menu Lifecycle RKAP, ZBB,
   Report Center, Form 1 Sasaran Perusahaan tampil tanpa 403.
5. Manual: login sebagai cost owner (`costowner@deverkap.com`) → submenu
   **Sasaran Perusahaan** tidak muncul dan rute `erkap.company-targets.*`
   mengembalikan 403; form Sasaran Departemen tetap dapat memilih sasaran perusahaan.

---

## 9. Perubahan Terminologi & Input Multi-Penyebab (Identifikasi Risiko)

> **Status:** SELESAI DIIMPLEMENTASI
> **Acuan terminologi:** kolom **Penyebab** pada template/import Form 1
> (`Form1ImportExportService::HEADINGS`).

1. **Terminologi** `Alasan Identifikasi Risiko` diganti menjadi
   `Penyebab Identifikasi Risiko` (label UI saja — tabel/kolom/rute tetap
   `erkap_risk_identification_reasons`, `reason`, `erkap.risk-identification-reasons.*`
   agar tidak memutus data, audit, dan tes yang ada):
   - `RiskIdentificationReasonController` (pageName + pesan sukses)
   - `AuditLog::typeLabel()` → `'Penyebab Identifikasi'`
   - Sidebar `app-sidebar.blade.php` → submenu **Penyebab Identifikasi**
   - View `risk-identification-reason/{index,create,edit}` (label `Alasan` → `Penyebab`)

2. **Satu identifikasi risiko dapat memiliki banyak penyebab**, dimasukkan
   **dalam 1 form** di **menu Penyebab Identifikasi** (bukan di form Identifikasi
   Risiko — sesuai arahan "pindahkan ke menu penyebab identifikasi"):
   - `risk-identification-reason/create.blade.php`: pilih Identifikasi Risiko
     sekali, lalu input dinamis `reasons[]` (add/remove baris via JS).
   - `StoreRiskIdentificationReasonRequest` menerima `reasons` (array) + tetap
     menerima `reason` tunggal (kompatibilitas).
   - `RiskIdentificationReasonController::store` membuat N baris
     `erkap_risk_identification_reasons` untuk 1 identifikasi risiko dalam satu
     transaksi; guard `ErkapAccess` + `ErkapEvaluationLock` dijalankan lebih dulu
     (risiko terkunci/submit → ditolak).
   - Form Identifikasi Risiko (create/edit) **dikembalikan ke kondisi semula**
     (tanpa input penyebab); `RiskIdentificationController`/request tidak lagi
     menangani `reasons`.
   - Edit/hapus per penyebab tetap lewat index penyebab identifikasi.

### Verifikasi (Penyebab)
1. `php -l` seluruh file yang diubah (PHP bersih).
2. `php artisan view:cache` — semua Blade terkompilasi.
3. `php artisan test tests/Feature/Erkap/RiskIdentificationApprovalFeatureTest.php
   tests/Feature/Erkap/Form1ImportExportTest.php` (RefreshDatabase,
   `asset_management_system_test`) — semua lulus.
4. Manual: buka menu **Penyebab Identifikasi → Tambah Penyebab** → pilih satu
   Identifikasi Risiko, klik **Tambah Penyebab** beberapa kali, isi teks → Simpan
   → cek baris di `erkap_risk_identification_reasons`; pastikan form menu
   Identifikasi Risiko (create/edit) tidak lagi menampilkan input penyebab.

---

## §10. Hilangkan Peringkat & Perlakuan Risiko + Strategi jadi multi-teks

> **Status:** SELESAI DIIMPLEMENTASI (untuk analisis definisi user:
> "hilangkan peringkat risiko; perlakuan & strategi digabung; strategi input teks
> multiple seperti fitur penyebab identifikasi").

1. **Peringkat Risiko dihapus total** (menu, rute `erkap.risk-rankings.*`,
   `RiskRankingController`, model `RiskRanking`, request, view, permission
   `erkap.risk-rankings.*` di seeder, link sidebar, label AuditLog, tabel
   `erkap_risk_rankings` di-drop di migration).
2. **Perlakuan Risiko dihapus total** — karena perlakuan & strategi digabung:
   - Rute `erkap.risk-treatments.*`, `RiskTreatmentController`, model
     `RiskTreatment`, request, view, permission, link sidebar, enum
     `ErkapRiskTreatmentType`, factory & test, tabel `erkap_risk_treatments`
     di-drop, `RiskTreatmentSeeder` dihapus.
   - Syarat submit `RiskIdentification::validateHasStrategyAndWorkProgram()`
     kini hanya: **Strategi Mitigasi** + **Program Kerja** (requirement
     "minimal 1 Rencana Perlakuan" dihapus; `hasTreatment()` & relasi
     `riskTreatments()`/`rankings()` dihapus).
3. **Strategi menjadi input teks bebas multi-baris** (pola sama seperti
   Penyebab Identifikasi):
   - `StoreDepartmentRiskStrategyRequest`: menerima `strategies[]` (array,
     `max:255`) + tetap menerima `strategy` tunggal.
   - `DepartmentRiskStrategyController::store`: membuat N baris
     `erkap_department_risk_strategies` untuk 1 risiko dalam satu transaksi
     (guard `ErkapAccess` + `ErkapEvaluationLock` dulu).
   - `create.blade.php`: dropdown Identifikasi Risiko + row dinamis `strategies[]`
     (add/remove via JS). Edit per-baris tetap lewat index/edit.
   - Model `DepartmentRiskStrategy`: `getStrategies()`/`riskTreatments()` dan
     enum dihapus; `strategy` bebas teks.
   - DB: migration `2026_09_26_000020` drop CHECK constraint & ubah
     `strategy` `VARCHAR(10)` → `TEXT`.
   - Form 1 import/export: strategi disimpan/ditampilkan apa adanya
     (`resolveStrategy` tidak lagi mapping ke enum `Kurangi` → `reduction`;
     template tetap boleh berisi teks bebas seperti "Kurangi").
   - Tampilan (`index`, `approvals/show`, Form 1) menampilkan teks strategi
     langsung tanpa label enum.

### Verifikasi (Peringkat/Perlakuan/Strategi)
1. `php -l` seluruh file PHP yang diubah (bersih).
2. `php artisan view:cache` OK.
3. `php artisan route:list --path=risk` → tidak ada `risk-rankings` /
   `risk-treatments`; `department-risk-strategies.*` tetap ada.
4. Test lulus: `RiskIdentificationApprovalFeatureTest` (11),
   `Form1ImportExportTest` (8), `ErkapRkapSimulasiSeederTest` (3),
   `RiskIdentificationBusinessRulesTest` (6), `WorkProgramBusinessRulesTest` (9),
   `ZBBReviewServiceTest` (12), `ZBBReviewFeatureTest` (10), `BudgetOpexTest` (5),
   `BudgetCapexFeatureTest` (3), `RoutineCostFeatureTest` (4),
   `RoutineCostCoaFeatureTest` (4).
5. Manual: menu **Strategi Risiko Departemen → Tambah Strategi** → pilih satu
   Identifikasi Risiko, klik **Tambah Strategi** beberapa kali, isi teks bebas →
   Simpan → cek baris `erkap_department_risk_strategies`; sidebar tidak lagi
   menampilkan **Peringkat Risiko** / **Perlakuan Risiko**.