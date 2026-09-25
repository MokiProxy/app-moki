# Tutorial Pengisian RKAP dari Awal sampai Akhir (Modul E-RKAP)

Panduan tutorial lengkap mengisi Rencana Kerja & Anggaran Perusahaan (RKAP) mulai dari
**pembuatan Periode RKAP** sampai **pengesahan, distribusi, dan arsip**, berdasarkan
alur yang benar-benar dieksekusi di kode (`RKAPLifecycleService`, `ApprovalService`,
`InvestmentGateReviewService`, `ZBBReviewService`, `BudgetOpexConsolidationService`).

Dokumen ini adalah sumber kebenaran ("source of truth") alur teknis. Untuk detail alur
approval per dokumen lihat juga `agents/alur-approval-erkap.md` (perhatikan bahwa matriks
di dokumen itu belum sinkron dengan kode — matriks aktual ada di Bagian 7 dokumen ini).

---

## Daftar Isi

1. [Gambaran Umum & Hierarki Data](#1-gambaran-umum--hierarki-data)
2. [Fase Lifecycle RKAP](#2-fase-lifecycle-rkap)
3. [Role & Permissions](#3-role--permissions)
4. [Prasyarat Master Data](#4-prasyarat-master-data)
5. [TAHAP A — Inisiasi & Kick-off](#5-tahap-a--inisiasi--kick-off)
6. [TAHAP B — Penyusunan (Form 1 s.d. Form 4)](#6-tahap-b--penyusunan-form-1-sd-form-4)
7. [TAHAP C — Submit & Approval](#7-tahap-c--submit--approval)
8. [TAHAP D — Konsolidasi & Review (ZBB)](#8-tahap-d--konsolidasi--review-zbb)
9. [TAHAP E — Finalisasi & Pengesahan](#9-tahap-e--finalisasi--pengesahan)
10. [TAHAP F (Opsional) — Monitoring & Realisasi](#10-tahap-f-opsional--monitoring--realisasi)
11. [Matriks Approval & Alur Submit (Aktual di Kode)](#11-matriks-approval--alur-submit-aktual-di-kode)
12. [Aturan Validasi Penting & Error Umum](#12-aturan-validasi-penting--error-umum)
13. [Studi Kasus Lengkap 1 Divisi](#13-studi-kasus-lengkap-1-divisi)
14. [Cara Cepat Menyiapkan Data Simulasi](#14-cara-cepat-menyiapkan-data-simulasi)
15. [Referensi Kode](#15-referensi-kode)

---

## 1. Gambaran Umum & Hierarki Data

RKAP disusun berjenjang. Urutan pengisian Wajib mengikuti hierarki ini:

```
erkap_rkap (Periode RKAP / tahun)          ← dibuat pertama kali
  └─ erkap_company_targets                 ← Sasaran Perusahaan
      └─ erkap_department_targets          ← Sasaran Departemen (per divisi)
          └─ erkap_risk_identifications    ← Form 1: Identifikasi Risiko
              ├─ erkap_risk_identification_reasons  ← Penyebab risiko
              ├─ erkap_risk_identification_impacts  ← Dampak risiko
              ├─ erkap_risk_analysis                 ← Analisis Risiko (probabilitas × dampak)
              ├─ erkap_department_risk_strategies    ← Strategi Mitigasi (teks, boleh > 1)
              └─ erkap_work_programs       ← Form 2: Program Kerja (hanya rating ≥ A)
                  ├─ erkap_routine_costs   ← Form 3: Biaya Rutin (OPEX)
                  └─ erkap_investment_plans ← Form 4: Rencana Investasi (CAPEX)
```

Alur dokumen yang masuk proses **approval** hanya 5 jenis (lihat `ApprovalService::documentTypes()`):
Program Kerja, Biaya Rutin, Rencana Investasi, Periode RKAP, dan Register Risiko (Form 1).
Sasaran, Analisis, Strategi, dst. adalah data pendukung (tanpa approval).

> **Konsep Form (dari sidebar):**
> - **Form 1** — Sasaran & Asesmen Risiko (company-targets, department-targets, risk-identifications, risiko anak, analysis, strategies)
> - **Form 2** — Jadwal Kerja (work-programs)
> - **Form 3** — Biaya Umum (routine-costs)
> - **Form 4** — Biaya Investasi (investment-plans, budget-capex, stage gate)

**Kunci utama siklus:** Periode RKAP memiliki *phase* yang dijalankan berurutan. Selama
fase **Inisiasi/Penyusunan/Konsolidasi** data boleh diisi. Begitu fase masuk
**Finalisasi/Disahkan/Arsip**, seluruh data anggaran **terkunci** (`RKAP::isLockedForInput()`).

---

## 2. Fase Lifecycle RKAP

Didefinisikan di `app/Models/Erkap/RKAP.php` (`PHASES` & `PHASE_LABELS`):

| Urutan | `phase` | Label | Aktivitas utama |
|--------|---------|-------|-----------------|
| 0 | `initiation` | Inisiasi & Kick-off | Membuat periode, kick-off/sosialisasi, arahan direksi |
| 1 | `preparation` | Penyusunan | Isi Form 1 → Form 2 → Form 3 → Form 4 |
| 2 | `consolidation` | Konsolidasi & Review | Submit & approval dokumen, ZBB, konsolidasi OPEX/CAPEX |
| 3 | `finalization` | Finalisasi & Pengesahan | Submit Periode RKAP, approval, alignment PT BMI |
| 4 | `approved` | Disahkan | Menandai didistribusikan |
| 5 | `archived` | Arsip | Dokumen selesai |

Aturan transisi (`RKAP::canTransitionTo()`): fase **hanya bisa maju satu langkah per klik**.
Tombol "Majukan ke …" ada di halaman detail (`erkap.rkap.show`) dan dijalankan lewat
`RKAPLifecycleService::advance()` (route `POST /erkap/rkap/{rkap}/advance`, butuh permission
`erkap.rkap.edit` — dimiliki `erkap-controller` & `erkap-admin`).

`RKAPLifecycleService::resetPhase()` dapat mengembalikan fase ke `initiation`
hanya jika status masih `draft`/`rejected` (tombol "Reset Fase ke Inisiasi").

> **Siapa yang menggerakkan fase?** Route `advance`, `kickoff`, `direction`, `distribute`,
> `reset-phase` semua butuh `erkap.rkap.edit`. Role yang memegang: **`erkap-controller`** dan
> **`erkap-admin`** (super-admin). Panah lifecycle juga hanya muncul untuk pemilik permission ini.

---

## 3. Role & Permissions

Definisi lengkap di `database/seeders/RolePermissionSeeder.php`. Ringkasannya:

| Role | Tugas utama | Approval jadi apa |
|------|-------------|-------------------|
| `erkap-cost-owner` | Input Form 1-4 & submit | Submitter (bukan approver) |
| `erkap-ppk` | Review proposal investasi | L1 Approval WP/RC/IP, gate `proposal` & `cba` |
| `erkap-controller` | Menggerakkan lifecycle, review anggaran | L2 Approval WP/RC |
| `erkap-manajemen-aset` | Review aset | L2 Approval IP, gate `aset` |
| `erkap-direksi-keuangan` | Review finansial investasi | L3 Approval IP, gate `direksi_keuangan` |
| `erkap-gate-review` | Review PT BMI | L4 Approval IP, gate `gate_review_bmi`, isi `erkap.rkap.bmi` |
| `erkap-bmi-admin` | Admin alignment PT BMI | isi `erkap.rkap.bmi` |
| `erkap-direksi` | Menyetujui RKAP | L2 Approval RKAP |
| `erkap-komisaris` | Menyetujui RKAP | L1 Approval RKAP |
| `erkap-risk-manager` | Evaluasi Form 1 | Approval Register Risiko |
| `erkap-accounting` | Lihat data | read-only |
| `erkap-auditor` | Audit | read-only + override edit |
| `erkap-admin` | Admin penuh | semua termasuk submit RKAP & lifecycle |

**Isolasi divisi:** user `erkap-cost-owner` hanya melihat/menginput data divisinya sendiri
(`ErkapAccess::isDivisionScoped()`), dan divisi di **Sasaran Departemen otomatis di-lock**
ke divisi user (tidak bisa diganti manual).

---

## 4. Prasyarat Master Data

Sebelum mulai mengisi RKAP, pastikan master data berikut sudah ada (menu **Master Data**
di sidebar). Bisa di-seed satu kali lewat seeder modul:

| Master Data | Tabel/Model | Route | Catatan |
|---|---|---|---|
| Kategori & Elemen Biaya | `erkap_cost_element_categories`, `erkap_cost_elements` | `cost-element-categories`, `cost-elements` | Elemen biaya wajib punya COA (kolom `chart_of_account_id`) untuk OPEX |
| Chart of Accounts | `chart_of_accounts` | `chart-of-accounts` | Ada `scope expense()` |
| Cost Center | `cost_centers` | `cost-centers` | `is_swakelola`, `division_id`, 4 digit terakhir kode = kode elemen biaya |
| Risk Appetite / Taxonomy / Type | `erkap_risk_appetites`, `erkap_risk_taxonomies`, `erkap_risk_types` | `risk-appetites`, `risk-taxonomies`, `risk-types` | Diisi oleh Risk Owner |
| Rating Criteria (AAA s.d. BB) | `erkap_rating_criterias` | `rating-criterias` | Wajib ada rating `A`; disediakan `ErkapRatingCriteriaSeeder` |
| Risk Matrix | `risk-scales`, `risk-probabilities`, `risk-impacts`, `risk-score-levels` | `risk-scales`, dll. | Score level = probability×impact |
| Investasi | `investattion-categories`, `investation-types`, `investation-criterias` | sesuai nama | Kategori/Tipe/Kriteria investasi |
| Divisi & Pegawai/User | `divisions`, `employees`, `users` | di luar ERKAP | `users.employee_id → employees.division_id` untuk scope divisi |

Seeder yang tersedia (dipanggil lewat `php artisan db:seed --class=...`):
`ErkapRatingCriteriaSeeder`, `ErkapRiskAppetiteSeeder`, `ErkapRiskTaxonomySeeder`,
`ErkapRiskTypeSeeder`, `ErkapRiskScales`, `ErkapRiskProbabilitiy`, `ErkapRiskImpact`,
`ErkapRiskScoreLevelSeeder`, `ErkapInvestattionCategorySeeder`, `ErkapInvestationTypeSeeder`,
`ErkapInvestationCriteriaSeeder`.

> Tips: jalankan `php artisan db:seed --class=ErkapRatingCriteriaSeeder` dulu karena rating
> adalah syarat membuat Program Kerja.

---

## 5. TAHAP A — Inisiasi & Kick-off

> **Pelaku:** `erkap-controller` / `erkap-admin` (permission `erkap.rkap.view` & `erkap.rkap.edit`).
> **Fase saat ini:** `initiation`.

### A1. Buat Periode RKAP
- Menu **Lifecycle RKAP** → tombol **"+ Tambah Periode RKAP"** (`GET /erkap/rkap/create`).
- Isi **Periode Tahun** (mis. `2026`), klik **Simpan** (`POST /erkap/rkap`).
- Setelah tersimpan, status otomatis `draft`, fase `initiation`.

### A2. Isi Kick-off / Sosialisasi Penyusunan RKAP
Buka halaman detail (`GET /erkap/rkap/{id}`) → kartu **"Kick-off / Sosialisasi Penyusunan RKAP"** →
klik **"Isi / Ubah"** → isi:

- **Tanggal Kick-off** (date)
- **Catatan Kick-off** (text)
- **Daftar Peserta** — tombol "Tambah Peserta", isi **Nama**, **Divisi**, dan checklist **Hadir**.
  (Simpan via `POST /erkap/rkap/{rkap}/kickoff`, butuh `erkap.rkap.edit`.)

### A3. Unggah Arahan Direksi / Memo Holding
Kartu **"Arahan Direksi / Memo Holding"** → **"Unggah / Ubah"** →

- **Lampiran Arahan** (file pdf/doc/xls/ppt/gambar)
- **Catatan Arahan** (text)
  (Simpan via `POST /erkap/rkap/{rkap}/direction`.)

### A4. Majukan Fase ke Penyusunan
Panel kanan **"Kontrol Fase"** → tombol **"Majukan ke Penyusunan"** →
`RKAPLifecycleService::advance()` → fase `preparation`, `phase_started_at = now()`.

Sekarang user divisi (`erkap-cost-owner`) mulai bisa mengisi data.

---

## 6. TAHAP B — Penyusunan (Form 1 s.d. Form 4)

> **Pelaku:** `erkap-cost-owner` divisi terkait (data dibatasi ke divisinya sendiri).
> **Fase saat ini:** `preparation` (masih boleh juga di `consolidation`).

Urutan wajib: **Sasaran → Risiko → Program Kerja → Biaya/Investasi**.

### B1. Sasaran Perusahaan (Form 1)
Menu **Sasaran & Asesmen Risiko ▸ Sasaran ▸ Sasaran Perusahaan** (`/erkap/company-targets`).
- **+ Buat** → pilih **Periode RKAP**, isi **Sasaran/Target** (teks, mis. "Meningkatkan profitabilitas 10%").
- Tidak bisa dihapus bila sudah punya Sasaran Departemen.

### B2. Sasaran Departemen (Form 1)
Menu **… ▸ Sasaran Departemen** (`/erkap/department-targets`).
- **+ Buat** → pilih **Sasaran Perusahaan**, **Rating Criteria** (AAA s.d. BB), **Prioritas** (1 = tertinggi).
- **Divisi**: user `erkap-cost-owner` → *terkunci otomatis* (tampil sebagai teks/badge);
  `admin`/`super-admin` → dropdown bebas. `store/update` selalu me-override `division_id` dari user (anti-tamper, `DepartmentTargetController`).
- Sasaran yang **belum punya rating** tidak bisa jadi dasar Program Kerja.

### B3. Identifikasi Risiko (Form 1)
Menu **… ▸ Identifikasi Risiko** (`/erkap/risk-identifications`).

Isi di create (`POST /erkap/risk-identifications`):
- **Sasaran Departemen** (dropdown terfilter divisi, jika cost-owner)
- **Risiko** (teks kejadian risiko)
- **Arah Risiko** (`risk_direction`): `positive` / `negative`
- **Risk Type** & **Risk Taxonomy**

Setelah risiko tersimpan, lengkapi entitas anaknya (masing-masing menu di bawah
**Identifikasi Risiko**):
- **Penyebab Identifikasi** (`/erkap/risk-identification-reasons`) — penyebab risiko (boleh lebih dari satu).
- **Dampak Identifikasi** (`/erkap/risk-identification-impacts`) — dampak jika risiko terjadi.
- **Analisis Risiko** (`/erkap/risk-analysis`) — pilih `Probability × Impact`; skor otomatis dari `RiskScoreLevel` (route AJAX `get-score-level`).
- **Strategi Risiko Departemen** (`/erkap/department-risk-strategies`) — strategi mitigasi, **input teks bebas** dan boleh lebih dari satu (pilih 1 risiko, isi beberapa strategi).

> **Syarat submit Form 1** (`RiskIdentification::validateHasStrategyAndWorkProgram()`):
> setiap risiko WAJIB sudah punya **Strategi Mitigasi** dan **minimal 1 Program Kerja**.
> Tanpa keduanya, submit ditolak.

Alternatif: import massal via **Form 1 (Import/Export)** (`/erkap/form1`) — unduh
`template-form1.xlsx`, isi, lalu upload (butuh `erkap.risk-identifications.create`).

### B4. Program Kerja — Form 2 (Jadwal Kerja)
Menu **Jadwal Kerja ▸ Program Kerja** (`/erkap/work-programs`).

Syarat pembuatan (dicek di `WorkProgram::booted()` & `WorkProgramController::checkRating()`):
- Risiko harus berasal dari sasaran ber-rating **A/A+/AAA** (`ErkapRatingLevel::allowedForWorkProgram()` = [AAA, AA, A]).
- Risiko **sudah punya Strategi Mitigasi**.

Field yang diisi:
- **Identifikasi Risiko** (dropdown terfilter divisi)
- **Kode** (`code`, contoh `WP-OPR/2026-01`)
- **Nama Program** (default: "Program Kerja: {risiko}" bila kosong)
- **Satuan** (`units`, contoh "unit", "proyek", "program")
- **Rencana Tahunan** (`year_plan`)
- **Rencana Bulanan** (12 kolom: `jan_plan`…`dec_plan`)
- **(opsional)** Dependensi `depends_on_work_program_id`

Validasi bawaan: total 12 kolom bulanan **harus sama** dengan `year_plan`
(`WorkProgram::validateMonthlyBreakdown()`); plus saat submit wajib sudah punya anggaran
(`canSubmitForApproval()` = minimal 1 biaya rutin/rencana investasi).

### B5. Biaya Rutin — Form 3 (Biaya Umum)
Menu **Biaya Umum ▸ Biaya Rutin** (`/erkap/routine-costs`).

Satu baris = satu kebutuhan (`need`). Alur input `POST /erkap/routine-costs`:
- **Program Kerja** (`erkap_work_program_id`, terfilter divisi)
- **Kebutuhan** (`need`, contoh "Biaya ATK dan Konsumsi Rapat")
- **Cost Center** (`cost_center_id`) + **Cost Center Owner** — cost center swakelola/centralized
  punya aturan siapa boleh menginput (`CentralizedCostService::assertCanInput`)
- **Elemen Biaya** (`erkap_cost_element_id`) → **Chart of Account** diisi otomatis dari elemen
  (`CostElement::coaSuggestion()`) bila kosong
- **Qty**, **Satuan** (`units`), **Harga Satuan** (`unit_price`)
- **Alokasi 12 bulan** (`jan_cost`…`des_cost`, NOTE kolom Desember = `des_cost` bukan `dec_cost`)
- **Total** — non-kumulatif: total = qty × harga = jumlah bulanan; kumulatif: hanya qty × harga

Setiap simpan otomatis me-rebuild **Review ZBB** untuk periode terkait (`rebuildZbb`).

Menu **Konsolidasi OPEX** (`/erkap/routine-costs/consolidate`) menampilkan rekap per elemen
biaya/cost center/program (hanya tampilan, bukan approval).

### B6. Rencana Investasi — Form 4 (Biaya Investasi)
Menu **Biaya Investasi ▸ Rencana Investasi** (`/erkap/investment-plans`).

Field di create (`POST /erkap/investment-plans`):
- **Program Kerja** (terfilter divisi), **Cost Center**, **Chart of Account** (COA non-akrual)
- **Kategori/Tipe/Kriteria Investasi** (`investattion_category_id`, `investation_type_id`, `investation_criteria_id`)
- **Nama**, **Deskripsi**, **Satuan (`unit`)**, **Qty**, **Harga Satuan**
- **Total** = qty × harga (dihitung otomatis)
- **Prioritas** (`priority_order`)
- **Proposal** (`proposal` file) — **WAJIB** sebelum bisa disubmit (`InvestmentPlan::canBeSubmitted()` / `ApprovalService::submit()`)
- **CBA** (opsional): `cba_npv`, `cba_irr`, `cba_payback`, `cba_justification`, dan/atau `cba_file`
- **Jadwal pembayaran 12 bulan** (`jan_plan`…`dec_plan`) — untuk non-kumulatif, jumlah bulanan
  harus = total (`validatePaymentSchedule()`)

---

## 7. TAHAP C — Submit & Approval

Submit dilakukan di masing-masing halaman index (tombol **Ajukan Persetujuan** per baris, atau
**Ajukan Semua Persetujuan** = batch `submit-batch`). Logika inti: `ApprovalService`.

### Matriks Approval AKTUAL (kode `ApprovalService::getApprovalMatrix()`)

> ⚠️ Ini matriks yang dieksekusi di kode — beberapa berbeda dari `agents/alur-approval-erkap.md`
> yang masih versi lama.

| Tipe (route prefix) | Model | Level approval |
|---|---|---|
| `work_program` | `WorkProgram` | L1 `erkap-ppk` → L2 `erkap-controller` |
| `routine_cost` | `RoutineCost` | L1 `erkap-ppk` → L2 `erkap-controller` |
| `investment_plan` | `InvestmentPlan` | L1 `erkap-ppk` → L2 `erkap-manajemen-aset` → L3 `erkap-direksi-keuangan` → L4 `erkap-gate-review` |
| `rkap` | `RKAP` | L1 `erkap-komisaris` → L2 `erkap-direksi` |
| `risk_register` (Form 1) | `RiskIdentification` | L1 `erkap-risk-manager` |

### C1. Submit Program Kerja / Biaya Rutin
- `POST /erkap/{work-programs|routine-costs}/{id}/submit` (per baris) atau `.../submit-batch`.
- Batch hanya memproses baris status `draft`/`rejected` dalam scope divisi user.
- Setiap submit di-reset: approval lama dihapus, dibuat ulang (WP/RC = 2 baris), status → `submitted`,
  notifikasi ke approver L1 (PPK).
- **PPK setujui → notifikasi Controller → Controller setujui → `approved`.**

### C2. Submit Rencana Investasi (alur ganda: Gate + Approval)
1. `POST /erkap/investment-plans/{id}/submit` — syarat: **proposal wajib ada** dan
   `validatePaymentSchedule()` lolos.
2. `ApprovalService::submit()` otomatis membuat **5 Stage Gate** (`InvestmentGateReviewService::initialize()`)
   dan 4 baris approval (per matriks di atas). Status → `submitted`, `gate_review_status = in_review`.
3. **Gate Review** (`/erkap/investment-gates`) dijalankan berurutan per stage, masing-masing oleh reviewer_role-nya:

   | Urutan | Stage | Reviewer |
   |--------|-------|----------|
   | 1 | `proposal` | `erkap-ppk` |
   | 2 | `cba` | `erkap-ppk` |
   | 3 | `aset` | `erkap-manajemen-aset` |
   | 4 | `direksi_keuangan` | `erkap-direksi-keuangan` |
   | 5 | `gate_review_bmi` | `erkap-gate-review` |

   Saat satu stage di-approve, approval ber-role yang sama ikut di-approve otomatis
   (`approveMatchingApproval`). Jika semua 5 gate `approved` → seluruh approval `approved`,
   status investasi → `approved`. Jika ada gate `rejected`/`revised` → approval pending ikut
   `rejected`, status investasi → `rejected`/`revised`.

### C3. Submit Form 1 (Register Risiko)
- Tombol submit di halaman Identifikasi Risiko → wajib lolos `validateHasStrategyAndWorkProgram()`
  (strategi + program kerja + perlakuan).
- Approval 1 level oleh `erkap-risk-manager` (`risk_register` matrix).

---

## 8. TAHAP D — Konsolidasi & Review (ZBB)

> **Pelaku:** `erkap-controller`/`erkap-admin` (lifecycle) + `erkap-ppk` (review ZBB).
> **Fase saat ini:** `consolidation` (setelah ditekan "Majukan ke Konsolidasi" oleh controller).

1. **Zero Based Budgeting** (`/erkap/zbb-reviews`):
   - Tombol **Build** → `POST /erkap/zbb-reviews/build` (`ZBBReviewService::buildReviews($rkap)`)
     membuat baris review ZBB per pos anggaran (kenaikan vs tahun sebelumnya).
   - Baris yang `blocksConsolidation()` harus direview: `erkap-ppk` setujui/tolak dengan isian
     `increase_rationale` (justifikasi kenaikan) & `review_notes`.
   - `ZBBReviewService::requireRationale($rkap)` memastikan kelengkapan.

2. **Konsolidasi OPEX** — `BudgetOpexConsolidationService::consolidate($rkap)` +
   `recalculateVariance($rkap)` (dipanggil lewat Dashboard / command seed; lihat `ErkapDashboardController`).
   Tampilan ringkasan di menu **Konsolidasi Biaya Rutin (OPEX)**.

3. **Konsolidasi CAPEX** — `BudgetCapex`: menu **Biaya Investasi ▸ Anggaran Investasi**
   (`/erkap/budget-capex`) dengan `consolidate`, `summary`, dan `payment-distribution`.

---

## 9. TAHAP E — Finalisasi & Pengesahan

> **Pelaku:** `erkap-controller`/`erkap-admin` (lifecycle), `erkap-admin` (submit RKAP),
> `erkap-komisaris` & `erkap-direksi` (approval), `erkap-gate-review`/`erkap-bmi-admin` (BMI).
> **Fase saat ini:** `consolidation`.

### E1. Majukan "Konsolidasi → Finalisasi"
Tombol **"Majukan ke Finalisasi & Pengesahan"** di halaman detail RKAP.

### E2. Submit Periode RKAP
Di halaman index **Lifecycle RKAP** (`/erkap/rkap`), tombol **✈ Ajukan Persetujuan**
(ber-permission `erkap.rkap.submit` — **hanya `erkap-admin`**/super-admin; lihat catatan di bawah).
`ApprovalService::submit($rkap)` membuat 2 baris approval: **Komisaris (L1) → Direksi (L2)**.

> Catatan: controller `RKAPController::submit()` diproteksi `permission:erkap.rkap.submit`.
> Role `erkap-admin` memegang permission ini. Approver RKAP (Komisaris/Direksi) hanya punya
> `erkap.rkap.view` + `approve`/`reject` (proses approval lewat menu **Approval**).

### E3. Approve RKAP (menu Approval)
Menu **Approval** (group **"Tanpa Divisi"** → Periode RKAP):
1. Login `erkap-komisaris` → **Setujui** (L1).
2. Login `erkap-direksi` → **Setujui** (L2).
3. Status RKAP → `approved`.

### E4. Alignment PT BMI
Kartu **"Alignment dengan PT BMI"** di halaman detail → isi status
(`none`/`in_review`/`aligned`/`rejected`) + catatan (`POST /erkap/rkap/{rkap}/bmi`).
Dibatasi role `erkap-gate-review` / `erkap-bmi-admin` (`RKAPLifecycleService::assertBmiRole()`).

### E5. Pengesahan & Distribusi
1. **"Majukan ke Disahkan"** → `advance()` ke `approved`; `resolution_date` diisi otomatis bila masih kosong.
2. **"Tandai Didistribusikan"** → `RKAPLifecycleService::distribute()` (tombol `erkap.rkap.edit`,
   biasanya controller/admin). Mencatat `distribution_status = distributed` + notifikasi ke `erkap-admin`.
3. **"Majukan ke Arsip"** → fase `archived`. Selesai.

Setelah fase `finalization`/`approved`/`archived` semua input data anggaran **terkunci**
(`assertNotLocked` di `RKAPLifecycleService`) — seluruh menu create/edit biaya/investasi
menampilkan pesan "terkunci".

---

## 10. TAHAP F (Opsional) — Monitoring & Realisasi

Setelah RKAP disahkan (`approved`), pencatatan realisasi berjalan (bisa via `erkap-cost-owner`,
`erkap-accounting`, dsb.):

| Menu | Model | Keterangan |
|---|---|---|
| **Monitoring & Realisasi ▸ Realisasi Anggaran (BvA)** | `BudgetRealization` | `budgeted` vs `realized` per bulan (kolom `month`,`year`), `calculateVariance()`; import Excel tersedia |
| **▸ Realisasi Program Kerja** | `ProgramRealization` | `target` vs `realized` per bulan, `calculatePercentComplete()` |
| **▸ Risk Assessment Bulanan** | `RiskAssessmentMonthly` | penilaian bulanan (inherent/current/residual), `calculateScores()` |
| **▸ Performance Scorecard (KPI)** | `PerformanceScorecard` | KPI per kuartal & tahun, `calculateWeightedScore()` |
| **Financial Projection** | `RevenuePlan`, `ExpensePlan`, `ProfitLossStatement` | proyeksi pendapatan/beban/loga rugi |

Dashboard & **Analytics & Widgets** (`/erkap`, `/erkap/dashboard/widgets`) serta
**Report Center** (`/erkap/reports`) menyajikan agregasi & export (Excel/PDF).

---

## 11. Matriks Approval & Alur Submit (Aktual di Kode)

Ringkasan alur submit→approve per dokumen:

```
Program Kerja   : draft ─submit─▶ submitted ─PPK▶ ─Controller▶ approved
Biaya Rutin     : draft ─submit─▶ submitted ─PPK▶ ─Controller▶ approved
Rencana Investasi: draft ─submit(proposal wajib)─▶ submitted
                    └▶ Gate proposal(PPK) → cba(PPK) → aset(Manajemen Aset)
                       → direksi_keuangan(Dir. Keuangan) → gate_review_bmi(Gate Review)
                       → semua gate approved → approved
Periode RKAP    : draft ─submit─▶ submitted ─Komisaris▶ ─Direksi▶ approved
Form 1 (Reg. Risiko): draft ─submit(strategi+program+treatment wajib)─▶ Risk Manager ▶ approved
```

Aturan `ApprovalService`:
- `submit()` hanya untuk status `draft`/`rejected`; dokumen `submitted`/`approved` ditolak submit ulang.
- Approver tiap level dicari `getApproverByRole(role, divisionId)` — diprioritaskan user
  ber-role di divisi yang sama; fallback ke user ber-role mana pun. Jika role tak ter-assign → submit gagal.
- `requireTurn()`: hanya approver dengan **level pending terendah** yang boleh memproses (giliran wajib berurutan).
- `reject()`: cukup 1 level menolak → **seluruh baris pending ikut ditolak**, status → `rejected`.
- Semua transaksi approval dibungkus `DB::transaction()` (gagal → rollback).

---

## 12. Aturan Validasi Penting & Error Umum

| # | Aturan | Sumber | Error jika dilanggar |
|---|--------|--------|----------------------|
| 1 | Program Kerja hanya untuk rating **A ke atas** | `WorkProgram::booted()`, `checkRating()` | "Program Kerja hanya bisa dibuat untuk Sasaran dengan Rating A ke atas" |
| 2 | Program Kerja wajib punya Strategi Mitigasi | `WorkProgram::booted()` | wajib buat strategi dulu |
| 3 | Total bulanan program = `year_plan` | `validateMonthlyBreakdown()` | "Total bulanan harus sama dengan target tahunan" |
| 4 | Biaya Rutin: total = qty×harga = jumlah bulanan (non-kumulatif) | `validateTotal()` | "Total harus sama dengan qty × harga satuan..." |
| 5 | Biaya Rutin Desember = kolom **`des_cost`** | `MONTH_COLUMNS` | pastikan nama kolom benar saat import/manual DB |
| 6 | Investasi wajib lampirkan **proposal** untuk submit | `ApprovalService::submit()` | "Usulan investasi wajib melampirkan proposal" |
| 7 | Jadwal pembayaran investasi = total (non-kumulatif) | `validatePaymentSchedule()` | "Jadwal pembayaran bulanan harus sama..." |
| 8 | Form 1 wajib: strategi + program kerja | `validateHasStrategyAndWorkProgram()` | "Setiap Risiko wajib memiliki..." |
| 9 | Data terkunci pada fase Finalisasi/Disahkan/Arsip | `RKAPLifecycleService::assertNotLocked()` | "data anggaran ... sudah terkunci" |
| 10 | RKAP tidak bisa dihapus jika sudah ada company target / terkunci | `RKAPController::destroy()` | pesan error spesifik |
| 11 | Reset fase hanya saat status draft/rejected | `resetPhase()` | "Reset fase hanya dapat dilakukan saat status Draft atau Ditolak" |
| 12 | BMI alignment hanya role BMI | `assertBmiRole()` | "Hanya role Gate Review PT BMI atau BMI Admin..." |
| 13 | Approver belum di-set → submit gagal & transaksi rollback | `getApproverByRole()` | "Tidak ditemukan approver untuk level ..." |
| 14 | Cost-owner tanpa relasi employee/divisi | `ErkapAccess` | akses form diblokir / 403 |

**Pitfall umum:**
- Lupa membuat **strategi mitigasi** atau **program kerja** → submit Form 1 gagal.
- Lupa **proposal** file di Rencana Investasi → tombol submit error.
- Submit **Periode RKAP** butuh role `erkap-admin` (permission `erkap.rkap.submit`) — bukan controller.
- `admin` di luar role `erkap-*` tetap bisa due ke `super-admin` Gate::before hanya untuk super-admin;
  **admin** tidak otomatis punya semua permission erkap → jalankan `php artisan db:seed --class=RolePermissionSeeder`
  lalu `php artisan permission:cache-reset` setelah mengganti seeder.

---

## 13. Studi Kasus Lengkap 1 Divisi

Contoh isian nyata (diadaptasi dari `ErkapRkapSimulasiSeeder` — divisi "SIMULASI-MS", tahun 2027):

**Periode RKAP**
- Tahun: `2027`, status `draft`, fase `initiation` → kickoff + arahan direksi → advance `preparation`.

**Sasaran**
- Perusahaan: "RKAP 2027" 🡢 Departemen: "Sasaran SIMULASI-MS 2027", rating `A`, prioritas `1`.

**Risiko (Form 1)** — 2 risiko, tiap risiko dilengkapi alasan, dampak, analisis (P=3, I=3 → skor 9,
level Moderate), peringkat, strategi (`reduction`/`sharing`), perlakuan (`responsible_party`, dll):

1. "Keterlambatan penyelesaian pengadaan aset pendukung operasi" (direction `negative`)
2. "Pembengkakan biaya operasional divisi" (direction `negative`)

**Program Kerja (Form 2)** — tiap risiko 1 program:
- WP-SIM/2027-01 "Optimalisasi Pengelolaan Aset Divisi", satuan `Proyek`, `year_plan = 100`
  (bulanan disebar = 100).
- WP-SIM/2027-02 "Penguatan Efisiensi Biaya Operasional", satuan `Program`, `year_plan = 100`.

**Biaya Rutin (Form 3)** — contoh di program 1:
- "Honorarium Tim Evaluasi Aset": qty 4 × Rp 5.000.000 = **Rp 120.000.000** (12 bulan)
- "Biaya ATK dan Konsumsi Rapat": qty 12 × Rp 3.000.000 = **Rp 36.000.000**

**Rencana Investasi (Form 4)** — contoh di program 1:
- "Peralatan Penunjang Digitalisasi": qty 25 × Rp 6.000.000 = **Rp 150.000.000**, prioritas 1,
  dengan proposal.

**Alur selanjutnya:** submit WP → PPK → Controller; submit RC → PPK → Controller; submit IP →
5 gate review; submit Form 1 → Risk Manager. Lalu ZBB build+review, konsolidasi, advance ke
finalization, submit RKAP → Komisaris → Direksi, BMI aligned, advance approved, distribute, archiv.

---

## 14. Cara Cepat Menyiapkan Data Simulasi

Jika ingin melihat seluruh alur terisi otomatis sekaligus (tahap 0-7, idempoten):

```
php artisan erkap:seed-simulasi --year=2027 --division=SIMULASI-MS
```

- Membuat: master data, divisi, cost center, user-approver (email `{role}@simulasi-ms.local`,
  password `password`), RKAP, sasaran, risiko lengkap, program, biaya rutin, investasi, approval
  semua level, ZBB, konsolidasi, pengesahan, distribusi, dan data realisasi.
- Kunci di `database/seeders/Erkap/Support/RkapSimulasi.php` — gunakan sebagai **acuan urutan**
  dan **contoh payload** pengisian manual.

---

## 15. Referensi Kode

| Komponen | Lokasi |
|----------|--------|
| Lifecycle RKAP (phase, advance, reset, BMI, distribute, lock) | `app/Models/Erkap/RKAP.php`, `app/Services/Erkap/RKAPLifecycleService.php` |
| Approval (submit/approve/reject/batch, matriks, approver) | `app/Services/ApprovalService.php` |
| Gate review investasi | `app/Services/Erkap/InvestmentGateReviewService.php` |
| ZBB | `app/Services/Erkap/ZBBReviewService.php` |
| Konsolidasi OPEX | `app/Services/BudgetOpexConsolidationService.php` |
| Access divisi (cost-owner) & lock evaluasi | `app/Services/ErkapAccess.php`, `app/Services/ErkapEvaluationLock.php` |
| Controller submit & input per modul | `RKAPController`, `CompanyTargetController`, `DepartmentTargetController`, `RiskIdentificationController`, `WorkProgramController`, `RoutineCostController`, `InvestmentPlanController` |
| Model & trait status | `app/Models/Erkap/*`, `app/Models/Erkap/Traits/HasApprovalWorkflow.php` |
| Route & permission | `routes/routers/erkap.php`, `database/seeders/RolePermissionSeeder.php` |
| Sidebar / menu | `resources/views/layouts/partials/erkap/app-sidebar.blade.php` |
| Views approval & lifecycle | `resources/views/erkap/{approvals,rkap}/…` |
| Simulasi end-to-end | `database/seeders/Erkap/Support/RkapSimulasi.php` (+ command `ErkapSeedSimulasi`) |
| Validation Requests | `app/Http/Requests/{Store,Update}*Request.php`, `app/Http/Requests/Erkap/*.php` |