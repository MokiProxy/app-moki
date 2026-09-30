# Planning: Seeder Simulasi Alur RKAP Lengkap 1 Divisi

> Dokumen perencanaan untuk membangun seeder yang mensimulasikan **satu divisi** yang telah menyelesaikan proses RKAP **dari tahap awal (inisiasi) sampai akhir (pengesahan + distribusi)**, termasuk approval bertingkat, ZBB, dan konsolidasi anggaran.
> Referensi implementasi: seluruh path di bawah relatif terhadap root repo `E:\Software-Dev\magang\app-moki`.

---

## 1. Tujuan

Seeder menghasilkan kondisi akhir (end-state) yang realistis: 1 divisi punya **RKAP terselesaikan penuh** sehingga:

- Dashboard ERKAP, konsolidasi OPEX/CAPEX, laporan ZBB, dan menu persetujuan menampilkan data utuh.
- Semua dokumen (Form 1 risiko, Program Kerja, Biaya Rutin, Rencana Investasi, Periode RKAP) berada pada status `approved`.
- RKAP berada pada fase `approved`/`archived` (end-state) dengan distribusi selesai.
- Data realisasi (anggaran vs realisasi, progres program, scorecard) tersedia untuk divisi tsb.

**Non-tujuan:** seeder tidak perlu memakai HTTP/route; cukup memakai *service + model* yang sama dengan controller agar state konsisten (approval row, gate, status) dan pengujian mudah.

---

## 2. Ringkasan Analisis Codebase Modul E-RKAP

### 2.1 Rantai entitas inti (chain)
```
RKAP (erkap_rkap, 1 per tahun, company-level, punya phase & status approval)
└── CompanyTarget     (erkap_company_targets, target perusahaan)
    └── DepartmentTarget (erkap_department_targets, division_id + rating)
        └── RiskIdentification (erkap_risk_identifications, Form 1; status = approval_status)
            ├── RiskIdentificationReason / Impact (form1)
            ├── RiskAnalysis   (probabilitas × impact → score)
            ├── RiskRanking    (peringkat risiko)
            ├── DepartmentRiskStrategy (strategi mitigasi: avoidance/reduction/sharing/acceptance)
            ├── RiskTreatment       (perlakuan risiko)
            └── WorkProgram   (erkap_work_programs, kode unik, bulanan == tahunan)
                ├── RoutineCost   (erkap_routine_costs → OPEX; elemen biaya, cost center, COA)
                ├── InvestmentPlan(erkap_investment_plans → CAPEX; proposal + 5 stage gate)
                └── ProgramRealization (realisasi progres)
```
Referensi: `app/Models/Erkap/` (WorkProgram.php, RiskIdentification.php, RKAP.php, dsb.) dan alur tes `tests/Concerns/BuildsErkapChain.php:20-89`.

### 2.2 Dua sumbu status yang terpisah pada RKAP
1. **Status approval dokumen** (`status`): `draft → submitted → approved | rejected` (trait `app/Models/Erkap/Traits/HasApprovalWorkflow.php:20-53`; enum di migration `2026_09_21_000016...`).
2. **Fase lifecycle** (`phase`): `initiation → preparation → consolidation → finalization → approved → archived` (`app/Models/Erkap/RKAP.php:42-53`). Input dikunci saat `finalization/approved/archived` (`isLockedForInput()`, RKAP.php:114-117).

Fase digerakkan `RKAPLifecycleService::advance()` (`app/Services/Erkap/RKAPLifecycleService.php:23-55`) — **tidak** ada prasyarat di kode, hanya harus berurutan. Status `approved` (approval) vs fase `approved` adalah hal berbeda dan sebaiknya keduanya tercapai.

### 2.3 Approval matrix (hardcoded di ApprovalService)
`app/Services/ApprovalService.php:21-46`:

| Tipe dokumen | Level 1 | Level 2 | Level 3 | Level 4 |
|---|---|---|---|---|
| `work_program` | `erkap-ppk` | `erkap-controller` | – | – |
| `routine_cost` | `erkap-ppk` | `erkap-controller` | – | – |
| `investment_plan` | `erkap-ppk` | `erkap-manajemen-aset` | `erkap-direksi-keuangan` | `erkap-gate-review` |
| `rkap` | `erkap-komisaris` | `erkap-direksi` | – | – |
| `risk_register` | `erkap-risk-manager` | – | – | – |

- Submit: `ApprovalService::submit()` (`:99-157`). Prasyarat: status `draft|rejected`; untuk `investment_plan` wajib `hasProposal()` (`:111-113`, `InvestmentPlan.php:183-186`); untuk `risk_register` wajib `validateHasStrategyAndWorkProgram()` (`:115-117`, `RiskIdentification.php:128-147`).
- Approve/reject: `approve()`/`reject()` (`:182-257`) memeriksa giliran (`requireTurn`, `:269-289`) → nilai `approver_id` tiap level = user dengan role tsb (`getApproverByRole`, `:291-310`; mengutamakan `employee.division_id` matching divisi dokumen, else fallback user role apa pun).
- `investment_plan`: submit juga membentuk 5 gate (`InvestmentGateReviewService::initialize`, `app/Services/Erkap/InvestmentGateReviewService.php:40-66`) dan approval per level hanya lolos bila gate grup role tsb `approved` (`ApprovalService.php:259-267`, `InvestmentGateReviewService::approveMatchingApproval`, `:166-193`). Jika 5 gate approved → plan otomatis `approved` (`:144-154`).

### 2.4 Stage Gate investasi
`InvestmentGateReviewService::STAGES` (`:14-20`): `proposal(1, erkap-ppk) → cba(2, erkap-ppk) → aset(3, erkap-manajemen-aset) → direksi_keuangan(4, erkap-direksi-keuangan) → gate_review_bmi(5, erkap-gate-review)`.
Gate `canReviewBy(user)` = `user->hasRole(reviewer_role)` (`InvestmentStageGate.php:101-104`) — mensimulasikannya via service butuh user dengan role tsb.

### 2.5 ZBB (gerbang konsolidasi)
`ZBBReviewService::buildReviews()` (`app/Services/Erkap/ZBBReviewService.php:28-121`) membuat baris `erkap_zbb_reviews` per pos anggaran OPEX/Investasi/Revenue/Program; `delta_percent > 0` (tidak ada riwayat tahun lalu → selalu 100%) = `zbb_status pending` dan **memblokir konsolidasi** sampai `increase_rationale` terisi **dan** `zbb_status === 'approved'` (`ZBBReview.php:77-81`, `ZBBReviewService::requireRationale` `:123-148`).
`review()` (`:150-192`) menerima payload `zbb_status`, `increase_rationale`, `review_notes` (tanpa cek permission — aman dipakai seeder dengan user mana pun).

### 2.6 Konsolidasi anggaran
- OPEX: `app/Services/BudgetOpexConsolidationService.php:20-81` (klaim `requireRationale` dulu, agregat per `division-cost_center-COA`, tulis `erkap_budget_opex`). Perintah: `php artisan erkap:consolidate-budget-opex --year=...` (`app/Console/Commands/ConsolidateBudgetOpex.php`).
- CAPEX: `BudgetCapexController::consolidate` (`app/Http/Controllers/Erkap/BudgetCapexController.php:155-191`) — agregat per divisi → `erkap_budget_capex` (klaim `requireRationale` juga).

### 2.7 Data realisasi (optional, setelah pengesahan)
Pola dari `database/seeders/MonitoringRealisasiSeeder.php`: `BudgetRealization` (anggaran=kolom bulanan; `calculateVariance()`), `ProgramRealization` (`calculatePercentComplete()`), `RiskAssessmentMonthly` (`calculateScores()`), `PerformanceScorecard` (`calculateWeightedScore()`).

### 2.8 Role & user existing (database/seeders/)
| User | Employee division | Role erkAP |
|---|---|---|
| Sahlul | 2 (MSI) | `erkap-ppk`, `erkap-admin` |
| Agung | 2 (MSI) | `erkap-controller`, `erkap-admin` |
| Fajriwan | 2 (MSI) | `erkap-risk-manager` |
| Karmono | 2 (MSI) | `erkap-accounting` |
| Vita | 2 (MSI) | `erkap-auditor` |
| Harmoko / Rudi / ErkapCost | 2 (MSI) | `erkap-cost-owner` |

Catatan: `EmployeeSeeder.php:117-134` wajib memasang `division_id = 2` untuk semua employee (kecuali Abimana CEO → 1). Semua divisi dibuat `DivisionSeeder.php`. Rating criteria di-seed `ErkapRatingCriteriaSeeder` untuk 5 nilai (`AAA,AA,A,BBB,BB`).

**Role yang TIDAK ada user-nya** (harus dibuat/di-assign oleh seeder): `erkap-manajemen-aset`, `erkap-direksi-keuangan`, `erkap-komisaris`, `erkap-direksi`, `erkap-gate-review`, `erkap-bmi-admin`. Role roles tsb sudah dibuat di `RolePermissionSeeder.php:42-54`; hanya belum ter-assign ke user.

---

## 3. Keputusan Desain (disetujui)

1. **Divisi target**: **buat divisi baru `SIMULASI-MS`** (company_id 1, regional_id 1) + buat user/employee approval di divisi itu (tipe "self-contained"). Tidak bertabrakan dengan data demo existing yang menumpuk di divisi 2 / RKAP 2026.
2. **Tahun RKAP**: **`2027`** (bebas dari year 2026 yang dipakai seeders existing) agar `RKAP::where('year',...)` unik dan simulasi mandiri.
3. **Cara membuat data**: pakai model `::create/updateOrCreate` + **layanan yang sama dengan controller**:
   - `ApprovalService::submit/approve` untuk seluruh dokumen;
   - `InvestmentGateReviewService::initialize + review` untuk gate investasi;
   - `RKAPLifecycleService::advance/markBmiAligned/distribute` untuk fase;
   - `ZBBReviewService::buildReviews + review` untuk ZBB;
   - `BudgetOpexConsolidationService::consolidate + recalculateVariance` untuk OPEX (atau jalankan command).
   Pendekatan ini menjamin `erkap_approvals`, `erkap_investment_stage_gates`, dan status selalu konsisten lintas tabel.
4. **User simulasi approval**: seeder membuat **set tak terpisah dari user approver** beserta employee-nya pada divisi `SIMULASI-MS` (agar `getApproverByRole` memilih user tujuan). Minimal harus ada user ber-role: ppk, controller, manajemen-aset, direksi-keuangan, gate-review, komisaris, direksi, risk-manager, bmi-admin.
5. **Idempoten**: gunakan `updateOrCreate` pada kunci stabil (`year`, `code`, kombinasi FK unik) + guard: bila RKAP tahun tsb sudah ada → skip revisi, cukup lakukan backfill yang kurang.
6. **File proposal investasi**: `hasProposal()` hanya cek `filled(proposal_file_path)` → seeder cukup menulis jalur + file placeholder ke `storage/app/public/...` agar tombol download di UI tidak error.
7. **Termasuk fase realisasi pasca pengesahan** (tahap 7 wajib, bukan opsional): `BudgetRealization`, `ProgramRealization`, `RiskAssessmentMonthly`, `PerformanceScorecard`.

---

## 4. Langkah Implementasi Seeder (urutan)

File usulan (lihat §7): `ErkapRkapSimulasiSeeder` + class pendukung `RkapSimulasi` (helper) dan, opsional, `app/Console/Commands/ErkapSeedSimulasi.php` agar bisa `php artisan erkap:seed-simulasi --year=2027 --division=SIMULASI-MS`.

### Tahap 0 — Persiapan referensi & user approval
1. Ambil/buat master: `RatingCriteria` rating `A` (`ErkapRatingLevel::allowedForWorkProgram()` = AAA/AA/A → `app/Enums/ErkapRatingLevel.php:76-79`); pilih `RiskTaxonomy`/`RiskType`/`RiskProbability`/`RiskImpact`/`RiskScoreLevel` existing (first()) atau buat via factory data.
2. Buat divisi (Opsi A) atau ambil divisi 2 (Opsi B).
3. Buat/assign user approver + employee-nya (division_id = divisi target agar `getApproverByRole` memilih user tujuan). Feature test mencontohkan pembuatan role+user: `tests/Concerns/ActsAsSuperAdmin.php:12-18`, `App\Models\User::assignRole`.
4. Cost center & cost element: pakai yang sudah di-seed (`CostCentersSeeder`, `CostElementsSeeder`) — cukup ambil `first()`; bila Opsi A tetap boleh memakai shared master CA (unit biaya itu global).

### Tahap 1 — Inisiasi (`phase = initiation`)
1. `RKAP::updateOrCreate(['year' => 2027], ['company_id' => ..., 'status' => 'draft', 'phase' => 'initiation'])`.
2. Kick-off: isi `kickoff_date`, `kickoff_notes`; buat `KickoffAttendee` (model `erkap.rkap.kickoff` → `RKAP::kickoffAttendees()`).
3. Arahan direksi: isi `direction_notes` (opsional `direction_file_path`).
4. `RKAPLifecycleService::advance($rkap)` → `preparation`. (advance aman dipanggil, tidak ada prasyarat; RKAPLifecycleService.php:23-55)

### Tahap 2 — Penyusunan (`phase = preparation`)
1. `CompanyTarget::create(['target' => '...', 'erkap_rkap_id' => ...])`.
2. `DepartmentTarget::create(['target' => '...', 'division_id' => $div, 'erkap_rating_criteria_id' => $ratingA, 'erkap_company_target_id' => ...])` — **divisi target + rating ≥ A**.
3. Untuk tiap risiko (`RiskIdentification::create`): isi `risk`, `risk_direction`, FK taxonomy/type. `status` otomatis `draft`.
   - `RiskIdentificationReason` / `RiskIdentificationImpact` (Form 1).
   - `RiskAnalysis` (prob×impact→score; lihat `database/seeders/SasaranDanAsesmenRisikoSeeder.php:102-126`), `RiskRanking`.
   - **`DepartmentRiskStrategy::create`** (wajib, jika kurang → WorkProgram gagal dibuat) — nilai `ErkapRiskTreatmentType::values()` (`app/Enums/ErkapRiskTreatmentType.php:30-35`): `avoidance/reduction/sharing/acceptance`.
   - **`RiskTreatment::create`** (wajib sebelum submit Form 1) — `RiskIdentification.php:142-146`.
4. **Program Kerja** (`WorkProgram::create`):
   - `erkap_risk_identification_id`-nya; isi `code` unik; **jumlah `jan_plan..dec_plan` == `year_plan`** (`WorkProgram.php:113-123`) karena hook `creating` (WorkProgram.php:80-98) butuh rating ≥ A + strategy.
   - 1–3 program per risiko sudah cukup untuk demo.
5. **Anggaran**:
   - **OPEX** `RoutineCost::create`: `need`, `cost_center_id`, `cost_center_owner`, `qty`, `units`, `unit_price`, `erkap_cost_element_id`, `chart_of_account_id` (boleh null; di-resolve nanti), bulanan (jumlah == total bila `is_kumulatif=false`; atau `is_kumulatif=true` + total = qty×harga). Referensi aturan: `RoutineCostController::validateTotal` (`app/Http/Controllers/Erkap/RoutineCostController.php:419-432`).
   - **CAPEX** `InvestmentPlan::create`: `name`, `qty`, `unit_price`, bulanan + `total` (jumlah bulan == total; `InvestmentPlan.php:103-131`), `proposal_file_path` (bikin file placeholder), `priority_order` unik per divisi.
   - (Opsional) `RevenuePlan` / `ExpensePlan` untuk proyeksi laba — pola `database/seeders/FinancialProjectionSeeder.php`.
6. `RKAPLifecycleService::advance` → `consolidation`.

### Tahap 3 — Submit & approval bertingkat
Urutan submit/approve (semua lewat `ApprovalService`):
1. **WorkProgram** → submit; approve level1 (ppk user), level2 (controller user).
2. **RoutineCost** → submit; approve level1 (ppk), level2 (controller).
3. **InvestmentPlan** (jika ada):
   - submit → otomatis `InvestmentGateReviewService::initialize` (5 gate, status `in_review`).
   - Review gate berurutan `proposal → cba → aset → direksi_keuangan → gate_review_bmi` via `InvestmentGateReviewService::review($plan, $stage, $user, ['status' => 'approved', 'result' => 'layak', 'notes' => '...'])` — user harus punya role reviewer tsb. Setelah gate 5 approved → plan terautomati `approved` (`InvestmentGateReviewService.php:144-154`).
   - Unit test/show referensi: `InvestmentGateReviewServiceTest`.
4. **Form 1 / `risk_register`** (`RiskIdentification::submit`) → submit; approve level1 (risk-manager user). Syarat submit: strate-gi + program kerja + treatment sudah ada (`RiskIdentification.php:128-147`).
5. **RKAP** → submit; approve level1 (komisaris), level2 (direksi). `divisionIdFor` RKAP = null (tanpa scoping divisi).

Catatan urutan: Form 1 boleh diajukan lebih awal/di akhir — tidak ada dependensi antar-dokumen selain syarat submit masing-masing. RKAP paling alami diajukan menjelang akhir (setelah konsolidasi).

### Tahap 4 — ZBB build & review (prasyarat konsolidasi)
1. `ZBBReviewService::buildReviews($rkap)` → baris ZBB dibuat (`pending` untuk yang naik, `skipped` untuk yang sama/turun).
2. Untuk tiap baris dengan `zbb_status == 'pending'` (atau `blocksConsolidation()` true): `ZBBReviewService::review($rkap, $id, $user, ['zbb_status' => 'approved', 'increase_rationale' => 'Justifikasi kenaikan (seed simulasi)...', 'review_notes' => 'Disetujui otomatis seeder.'])`.
3. Verifikasi `ZBBReviewService::requireRationale($rkap)` tidak melempar.

### Tahap 5 — Konsolidasi anggaran
1. OPEX: `(new BudgetOpexConsolidationService)->consolidate($rkap)` + `recalculateVariance($rkap)` (atau `php artisan erkap:consolidate-budget-opex --year=2027`).
2. CAPEX: replikasi logika `BudgetCapexController::consolidate` (`BudgetCapexController.php:166-183`) → `BudgetCapex::updateOrCreate(['erkap_rkap_id', 'division_id'], ['total_investment' => sum total IP divisi])`.
3. `RKAPLifecycleService::advance` → `finalization` (lalu `approved` setelah pengesahan; lihat §4e).

### Tahap 6 — Finalisasi, pengesahan, BMI, distribusi
1. (Opsional) `markBmiAligned($rkap, $bmiUser, ['bmi_alignment_status' => 'aligned', 'bmi_notes' => '...'])` — user dengan role `erkap-bmi-admin`/`erkap-gate-review` (`RKAPLifecycleService.php:74-93`).
2. `RKAPLifecycleService::advance($rkap)` → `approved` (mengisi `resolution_date` bila kosong) → `archived`.
3. `RKAPLifecycleService::distribute($rkap, $user)` → `distribution_status = distributed` (`RKAPLifecycleService.php:95-115`).

### Tahap 7 — Realisasi pasca pengesahan (wajib, disetujui)
Ikuti pola `MonitoringRealisasiSeeder.php:35-139` untuk bulan 1–N:
- `BudgetRealization` per bulan (anggaran dari kolom bulanan RC/IP; `realized` → `calculateVariance()`).
- `ProgramRealization` per bulan (`target`/`realized`/`calculatePercentComplete()`).
- `RiskAssessmentMonthly` per bulan (`calculateScores()`).
- `PerformanceScorecard` per kuartal (`calculateWeightedScore()`).

---

## 5. Peta Tabel → Data yang diisi (ringkas)

| Tabel | Kolom penting | Sumber nilai |
|---|---|---|
| `erkap_rkap` | `year` (unique), `status`, `phase`, `company_id`, `kickoff_date`, `direction_notes`, `bmi_alignment_status`, `resolution_date`, `distribution_status` | Tahap 1, 6 |
| `erkap_kickoff_attendees` | `name`, `division_id`, `attended` | Tahap 1 |
| `erkap_company_targets` | `target`, `erkap_rkap_id` | Tahap 2 |
| `erkap_department_targets` | `target`, `division_id`, `erkap_rating_criteria_id`, `erkap_company_target_id`, `priority` | Tahap 2 |
| `erkap_risk_identifications` | `risk`, `risk_direction`, FK taxonomy/type, `status` | Tahap 2 |
| `erkap_risk_identification_reasons` / `..._impacts` | `reason`/`impact`, FK risk | Tahap 2 |
| `erkap_risk_analysis` | FK prob/impact/score | Tahap 2 |
| `erkap_risk_rankings` | FK risk, `ranking` | Tahap 2 |
| `erkap_department_risk_strategies` | FK risk, `strategy` (4 nilai enum) | Tahap 2 |
| `erkap_risk_treatments` | FK risk, FK strategy, `treatment_type`, `status`, `target_date` | Tahap 2 |
| `erkap_work_programs` | `code` unik, `erkap_risk_identification_id`, plan bulanan = `year_plan`, `status` | Tahap 2 |
| `erkap_routine_costs` | FK program, FK cost element/cost center, `qty`/`unit_price`/bulanan/`total`/`is_kumulatif`, `status` | Tahap 2 |
| `erkap_investment_plans` | FK program, `qty`/`unit_price`/bulanan/`total`, `proposal_file_path`, `priority_order`, `status` | Tahap 2 |
| `erkap_approvals` | morph `approvalable`, `level`, `role`, `status`, `approver_id`, `approved_at` | Tahap 3 (via ApprovalService) |
| `erkap_investment_stage_gates` | FK plan, `stage`, `stage_order`, `status`, `reviewer_role`, `reviewed_by/at`, `result` | Tahap 3 (via service gate) |
| `erkap_zbb_reviews` | FK rkap, `division_id`, `subject_type/id`, amounts, `increase_rationale`, `zbb_status` | Tahap 4 |
| `erkap_budget_opex` | FK rkap, `division_id`, `cost_center_id`, `chart_of_account_id`, `budget_amount` | Tahap 5 |
| `erkap_budget_capex` | FK rkap, `division_id`, `total_investment` | Tahap 5 |
| `erkap_budget_realizations` | FK rkap + FK RC/IP, `month`, `year`, `budgeted`, `realized`, `source` | Tahap 7 |
| `erkap_program_realizations`, `erkap_risk_assessments_monthly`, `erkap_performance_scorecards` | sesuai pola seeder realisasi | Tahap 7 |

---

## 6. Aturan/Jebakan yang WAJIB dipatuhi

1. **WorkProgram `creating` hook** (`WorkProgram.php:80-98`): rating dept target harus `AAA|AA|A` DAN risk sudah punya `DepartmentRiskStrategy` → buat strategi **sebelum** program kerja.
2. **Risk submit** (`RiskIdentification.php:128-147`): risk wajib punya strategi + program kerja + treatment sebelum `ApprovalService::submit` tipe `risk_register`.
3. **Monthly breakdown**:
   - WorkProgram: `sum(jan..dec_plan) == year_plan` (`WorkProgram.php:113-123`).
   - RoutineCost: `sum(jan..des_cost) == total` bila `is_kumulatif=false`; bila kumulatif cukup `total == qty × unit_price` (`RoutineCostController.php:419-432`).
   - InvestmentPlan: `sum(jan..dec_plan) == total` (`InvestmentPlan.php:103-131`).
4. **Proposal investasi wajib** pada submit (`ApprovalService.php:111-113`): set `proposal_file_path` sebelum submit.
5. **Gate investasi**: review berurutan; user harus punya role reviewer gate (`InvestmentStageGate.php:101-104`). Gate `cba` opsional menyimpan `cba_attachment_path`.
6. **ZBB blocking**: setiap pos dengan kenaikan butuh `increase_rationale` **dan** `zbb_status = approved` sebelum konsolidasi OPEX/CAPEX (`ZBBReview.php:77-81`).
7. **`getApproverByRole`** (`ApprovalService.php:291-310`): approve harus memakai user yang benar-benar punya role tsb (dan bila memungkinkan `employee.division_id == division dokumen`). Saat submit, `Approval` row mengunci `approver_id` → approve berikutnya harus user yang sama.
8. **Employee seeder** mengunci semua user ke division 2 → jika memakai divisi baru (Opsi A), buat employee terpisah untuk user approver divisi tsb.
9. **Kode unik**: `erkap_work_programs.code`, `cost_centers.code`, `chart_of_accounts.code` unik secara global — pastikan prefiks seeder (mis. `WP-SIM/2027-01`).
10. **Notification/side-effect**: `ApprovalService` & `distribute` memicu notifikasi & WhatsApp service; jalankan seeder dengan `QUEUE_CONNECTION=sync` (atau wrap `Notification::send` non-blocking) agar tidak memblok lintasan.
11. **Jangan panggil `$request->string('...')->toString()`** — macro `Request::string` hanya return string (lihat catatan bug G9; `AppServiceProvider.php:40`).
12. **`RKAPLifecycleService`/`BudgetOpexConsolidationService` adalah class non-static sebagian** → panggil lewat `app(...)` / `new ...` (BudgetOpex: instance; ZBB/InvestmentGate/RKAPLifecycle: static).

---

## 7. Struktur File yang Diusulkan

```
database/seeders/ErkapRkapSimulasiSeeder.php     # entry: run() memanggil helper
database/seeders/Erkap/Support/RkapSimulasi.php  # (opsional) helper tahapan 1-7
app/Console/Commands/ErkapSeedSimulasi.php       # (opsional) php artisan erkap:seed-simulasi {--year=} {--division=}
tests/Feature/Erkap/ErkapRkapSimulasiSeederTest.php  # (validasi hasil seeder)
```
- Registrasi: panggil dari `DatabaseSeeder.php` (setelah `MonitoringRealisasiSeeder`) ATAU biarkan mandiri (`php artisan db:seed --class=ErkapRkapSimulasiSeeder` / command).
- Konstanta: `YEAR = env('ERKAP_SIM_YEAR', 2027)`, `DIVISION_NAME = env('ERKAP_SIM_DIVISION', 'SIMULASI-MS')`.
- Idempotensi: core function `runSimulasi(RKAP $rkap, Division $division)` — jalankan ulang aman: `updateOrCreate` di semua tabel + `updateOrCreate` di appro… (approval dibersihkan lalu dibentuk ulang).

---

## 8. Verifikasi

1. **Unit/Feature test** `ErkapRkapSimulasiSeederTest`:
   - `assertDatabaseHas` untuk tiap tabel (§5) dengan kunci tahun/divisi.
   - Status: `erkap_rkap.status == approved` & `phase == archived`; semua `work_program`/`routine_cost`/`investment_plan`/`risk_identification` berstatus `approved`.
   - `erkap_approvals` ada 2 baris per WP/RC, 4 per IP, 2 per RKAP, 1 per risk — semua `approved`.
   - `investment_stage_gates` 5 baris `approved`.
   - `zbb_reviews` tidak ada yang `blocksConsolidation()`.
   - `budget_opex`/`budget_capex` terisi untuk divisi target.
   - Jalan ulang seeder → total baris tidak berlipat (idempoten).
2. **CLI smoke test**: `php artisan erkap:seed-simulasi`, `php artisan erkap:consolidate-budget-opex --year=2027`.
3. **UI** (login sebagai user approval): menu `Approval ERKAP` menampilkan histori; `Detail RKAP 2027` fase "Disahkan"/"Arsip"; tab konsolidasi OPEX & payment distribution CAPEX terisi; dashboard widget (budget, ZBB, risk) menampilkan divisi baru.
4. **Full suite** `vendor\bin\phpunit` tetap hijau.

---

## 9. Risiko & Mitigasi

| Risiko | Mitigasi |
|---|---|
| Tabrakan dengan data seed 2026 existing | Gunakan tahun 2027 + (opsional) divisi baru |
| Approver tidak ter-resolve (role kosong) | Seeder wajib membuat/meng-assign user untuk 6 role yang hilang (§2.8) sebelum submit |
| Pedoman `WorkProgram` rating < A | Paksa rating `A` pada department target |
| ZBB memblokir konsolidasi | Approve semua baris `pending` + isi rationale sebelum konsolidasi |
| Gate investasi jarang diketahui | Replikasi persis `InvestmentGateReviewService::review` agar state gate konsisten |
| Notifikasi/WhatsApp memblokir | Jalankan dengan `QUEUE_CONNECTION=sync` / disable event listener |
| Seeder tidak idempoten | `updateOrCreate` berbasis `year`+`code`; hapus & regenerasi approval/gate |