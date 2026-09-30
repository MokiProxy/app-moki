# Planning Perbaikan Gap Flow Testing ERKAP

> **Tanggal:** 2026-09-29
> **Sumber:** `agents/report-gap-flow-testing.md`
> **Status:** PLANNING (Belum Implementasi)

---

## Ringkasan Eksekutif

Dari 5 area yang perlu diperbaiki berdasarkan gap testing, kita kelompokkan menjadi 2 kategori:

| Kategori | Gap | Pendekatan |
|---|---|---|
| **Perbaikan Kode (Code Fix)** | Stage Gate Review, Form Validation, Division Scoping | Refactor & perbaiki logika yang ada |
| **Perbaikan Data/Seeding (Test Infrastructure)** | ZBB Review, Konsolidasi | Tambahkan data seeding & validasi pre-condition |

---

## 1. Stage Gate Review — Flow Kompleks Perlu Disederhanakan

### Problem Saat Ini
- Stage gate memiliki 4 tahap sequential (`proposal` → `cba` → `aset` → `direksi_keuangan`) yang semuanya harus disetujui sebelum approval investasi.
- Setiap tahap di-review oleh role berbeda, sehingga proses menjadi panjang dan kompleks.
- Tidak ada notifikasi ke reviewer berikutnya saat satu gate disetujui.
- Race condition pada pengecekan sequential gate.
- `gate_review_bmi` ada di DB constraint tapi tidak ada di STAGES constant.

### Rencana Perbaikan

#### 1A. Evaluasi Ulang Kebutuhan Bisnis
- [ ] **Diskusi dengan stakeholder:** Apakah semua 4 tahap diperlukan untuk semua jenis investasi, atau bisa disederhanakan berdasarkan kriteria investasi (misal: investasi besar vs kecil)?
- [ ] **Keputusan:** Tentukan apakah stage gate bisa:
  - (a) Di-skip untuk investasi dengan nilai di bawah threshold tertentu, atau
  - (b) Di-parallel-kan (beberapa gate bisa di-review bersamaan), atau
  - (c) Di-sederhanakan menjadi 2 tahap saja (proposal + pengakhiran)

#### 1B. Penambahan Notifikasi antar Gate
- [ ] Tambahkan notifikasi ke reviewer gate berikutnya saat satu gate di-approve
- [ ] Buat Mailable class: `GateReviewNotification`
- [ ] Integrasikan di `InvestmentGateReviewService::review()` setelah approve

#### 1C. Perbaikan Race Condition
- [ ] Tambahkan `DB::transaction()` dan lock pada method `review()` di `InvestmentGateReviewService`
- [ ] Gunakan `lockForUpdate()` saat mengecek dan update status gate

#### 1D. Penyesuaian `gate_review_bmi`
- [ ] Evaluasi apakah stage `gate_review_bmi` diperlukan
- [ ] Jika ya: tambahkan ke `STAGES` constant dan buat logika review-nya
- [ ] jika tidak: hapus dari DB constraint migration

#### 1E. Division Scoping pada Stage Gate
- [ ] Tambahkan pengecekan division di `InvestmentStageGateReviewRequest::authorize()`
- [ ] Atau gunakan `ErkapAccess::assertDivisionAccess()` di controller

### File yang Akan Diubah
| File | Perubahan |
|---|---|
| `app/Services/Erkap/InvestmentGateReviewService.php` | Transaction, notifikasi, stage BMI |
| `app/Http/Controllers/Erkap/InvestmentStageGateController.php` | Division scoping |
| `app/Http/Requests/Erkap/InvestmentStageGateReviewRequest.php` | Authorize logic |
| `app/Notifications/GateReviewNotification.php` | **FILE BARU** — notifikasi gate |

### Estimasi Effort
- 1A (diskusi stakeholder): 1-2 hari (tergantung ketersediaan stakeholder)
- 1B (notifikasi): 0.5 hari
- 1C (race condition): 0.5 hari
- 1D (stage BMI): 0.5 hari
- 1E (division scoping): 0.5 hari

**Total: ~3-4 hari** (dengan asumsi 1A sudah diputuskan)

---

## 2. Form Validation — Field Names Tidak Konsisten

### Problem Saat Ini
- `StoreRoutineCostRequest` menggunakan `des_cost` untuk Desember, sedangkan `StoreWorkProgramRequest` dan `StoreInvestmentPlanRequest` menggunakan `dec_plan`.
- `StoreRiskAnalysisRequest` menggunakan `erkap_risk_score_value_id` tapi tabelnya `erkap_risk_score_levels`.
- `StoreWorkProgramRequest` tidak memvalidasi field `code`.
- Sebagian besar `Store*Request` tidak memiliki logic `authorize()`.

### Rencana Perbaikan

#### 2A. Standarisasi Nama Field Bulanan
- [ ] **Keputusan naming convention:** Pilih salah satu standar — `des_cost` atau `dec_plan`
- [ ] Berdasarkan konsistensi yang ada mayoritas menggunakan `dec_plan`, maka:
  - Ubah `des_cost` → `dec_plan` di `StoreRoutineCostRequest`
  - Update migration dan model `RoutineCost` jika perlu

#### 2B. Perbaikan `erkap_risk_score_value_id`
- [ ] **Opsi 1 (Recommended):** Rename field di form & request dari `erkap_risk_score_value_id` → `erkap_risk_score_level_id` agar konsisten dengan tabel
- [ ] **Opsi 2:** Rename tabel dari `erkap_risk_score_levels` → `erkap_risk_score_values` (breaking change)

#### 2C. Tambah Validasi `code` di WorkProgram
- [ ] Tambahkan `code` ke `StoreWorkProgramRequest` rules: `required|string|max:255|unique:erkap_work_programs,code`
- [ ] Pastikan controller menyimpan field `code` saat create/update

#### 2D. Tambah Method `authorize()` di Store Requests
- [ ] Tambahkan `authorize()` yang memeriksa permission dasar di setiap `Store*Request`
- [ ] Contoh: `StoreWorkProgramRequest` → cek `erkap.work-program.create`

### File yang Akan Diubah
| File | Perubahan |
|---|---|
| `app/Http/Requests/StoreRoutineCostRequest.php` | `des_cost` → `dec_plan` |
| `app/Http/Requests/StoreRiskAnalysisRequest.php` | Rename field score |
| `app/Http/Requests/StoreWorkProgramRequest.php` | Tambah validasi `code` |
| `app/Http/Requests/Store*Request.php` (semua) | Tambah `authorize()` |
| `app/Models/Erkap/RoutineCost.php` | Update fillable jika perlu |
| `database/migrations/*_create_erkap_routine_costs_table.php` | Rename kolom |

### Estimasi Effort
- 2A (standarisasi): 1 hari
- 2B (score field): 0.5 hari
- 2C (code validation): 0.5 hari
- 2D (authorize): 1 hari

**Total: ~3 hari**

---

## 3. Division Scoping — Cost Owner Terblokir Tanpa Employee/Division

### Problem Saat Ini
- `ErkapAccess::isDivisionScoped()` mengembalikan `true` untuk role `erkap-cost-owner`.
- `ErkapAccess::divisionId()` mengembalikan `$user->employee->division_id`.
- Jika user tidak memiliki employee/division, `divisionId()` mengembalikan `null`, sehingga semua query mengembalikan kosong.
- Tidak ada middleware/policy yang memaksa pengecekan ini.

### Rencana Perbaikan

#### 3A. Fallback untuk Cost Owner Tanpa Division
- [ ] **Opsi 1 (Recommended):** Saat assign role `erkap-cost-owner`, wajibkan pilih division
  - Tambahkan validasi di user creation/assignment flow
  - Buat seeder yang memastikan semua cost-owner punya division
- [ ] **Opsi 2:** Jika cost-owner tidak punya division, treat sebagai non-scoped (lihat semua data)
  - Modifikasi `isDivisionScoped()`: return `false` jika `divisionId()` null
- [ ] **Opsi 3:** Block akses total jika cost-owner tidak punya division
  - `isDivisionScoped()` return `true` + `divisionId()` return `-1` (impossible value)

#### 3B. Middleware untuk Division Scoping
- [ ] Buat middleware `ErkapDivisionScope` yang:
  - Cek apakah route memerlukan division scoping
  - Validasi user memiliki division jika scoped
  - Inject `division_id` ke request attribute
- [ ] Daftarkan middleware di `app/Http/Kernel.php`
- [ ] Terapkan ke route group ERKAP

#### 3C. Pengecekan di User Assignment
- [ ] Saat assign role `erkap-cost-owner` ke user, validasi:
  - User memiliki employee record
  - Employee memiliki division_id
- [ ] Buat Artisan command `erkap:validate-cost-owners` untuk audit berkala

#### 4D. Perbaikan `scopeDivision()` Silent Override
- [ ] Jangan silently override — throw exception atau log warning jika user scoped mencoba akses division lain
- [ ] Atau kembalikan response yang jelas: "Anda hanya dapat mengakses data division sendiri"

### File yang Akan Diubah
| File | Perubahan |
|---|---|
| `app/Services/ErkapAccess.php` | Fallback logic, scopeDivision improvement |
| `app/Http/Middleware/ErkapDivisionScope.php` | **FILE BARU** — middleware |
| `app/Http/Kernel.php` | Daftarkan middleware |
| `routes/routers/erkap.php` | Terapkan middleware ke route group |
| `app/Console/Commands/ValidateCostOwners.php` | **FILE BARU** — audit command |
| `database/seeders/*` | Pastikan cost-owner users punya division |

### Estimasi Effort
- 3A (fallback): 0.5 hari
- 3B (middleware): 1 hari
- 3C (user assignment): 0.5 hari
- 3D (scopeDivision): 0.5 hari

**Total: ~2.5 hari**

---

## 4. ZBB Review — Memerlukan Data yang Cukup

### Problem Saat Ini
- `ZBBReviewService::buildReviews()` mengumpulkan data dari `RoutineCost`, `InvestmentPlan`, `RevenuePlan`.
- Jika data kosong (belum ada routine cost / investment plan), ZBB review tidak akan menghasilkan item apapun.
- `collectCurrent()` tidak filter berdasarkan status (draft/submitted/approved), sehingga data draft ikut masuk review.
- Tidak ada validasi pre-condition sebelum build.

### Rencana Perbaikan

#### 4A. Validasi Pre-condition Sebelum Build
- [ ] Tambahkan method `hasSufficientData($rkapId)` di `ZBBReviewService`
- [ ] Cek: apakah ada minimal 1 RoutineCost ATAU 1 InvestmentPlan ATAU 1 RevenuePlan untuk RKAP tersebut
- [ ] Jika tidak cukup, throw exception dengan pesan "Data anggaran belum mencukupi untuk ZBB review"
- [ ] Panggil method ini di `ZBBReviewController::build()` sebelum `buildReviews()`

#### 4B. Filter Data Berdasarkan Status
- [ ] Modifikasi `collectCurrent()` untuk hanya mengambil data dengan status `approved` (atau minimal `submitted`)
- [ ] Tambahkan parameter opsional `$status` di method
- [ ] Update query filter status: approved atau submitted

#### 4C. Data Seeding untuk Testing
- [ ] Buat seeder `ZBBReviewTestSeeder` yang:
  - Membuat RKAP dummy
  - Membuat RoutineCost, InvestmentPlan, RevenuePlan dummy dengan status approved
  - Memastikan data cukup untuk ZBB review
- [ ] Atau tambahkan method di existing test case untuk setup data

#### 4D. Division Scoping pada ZBB
- [ ] Tambahkan division scoping di `ZBBReviewController::build()` dan `update()`
- [ ] Gunakan `ErkapAccess::divisionId()` untuk filter review yang bisa diakses

#### 4E. Notifikasi ZBB Blocking Konsolidasi
- [ ] Saat `requireRationale()` gagal, kirim notifikasi ke user yang perlu review
- [ ] Atau tampilkan pesan error yang lebih informatif di UI

### File yang Akan Diubah
| File | Perubahan |
|---|---|
| `app/Services/Erkap/ZBBReviewService.php` | hasSufficientData, filter status, division scoping |
| `app/Http/Controllers/Erkap/ZBBReviewController.php` | Pre-condition check, division scoping |
| `database/seeders/ZBBReviewTestSeeder.php` | **FILE BARU** — test data |
| `tests/Feature/Erkap/ZBBReviewTest.php` | **FILE BARU** — test coverage |

### Estimasi Effort
- 4A (pre-condition): 0.5 hari
- 4B (filter status): 0.5 hari
- 4C (seeding): 1 hari
- 4D (division scoping): 0.5 hari
- 4E (notifikasi): 0.5 hari

**Total: ~3 hari**

---

## 5. Konsolidasi — Memerlukan Data yang Cukup

### Problem Saat Ini
- `BudgetCapexController::consolidate()` memanggil `ZBBReviewService::requireRationale()` sebagai gate.
- Jika ZBB review belum lengkap (belum ada data atau belum di-review), konsolidasi gagal.
- Konsolidasi tidak menggunakan `DB::transaction()`, berisiko partial update.
- Tidak ada validasi bahwa investment plans sudah approved sebelum dikonsolidasi.
- OPEX consolidation terpisah dari CAPEX consolidation.

### Rencana Perbaikan

#### 5A. Validasi Pre-condition Sebelum Konsolidasi
- [ ] Tambahkan method `canConsolidate($rkapId)` di `BudgetCapexController` atau service baru
- [ ] Cek:
  - RKAP exists dan status-nya appropriate
  - Ada minimal 1 InvestmentPlan (untuk CAPEX) atau 1 RoutineCost (untuk OPEX)
  - Semua InvestmentPlan/RoutineCost sudah approved
  - ZBB review sudah lengkap (sudah ada `requireRationale()`)
- [ ] Return boolean + array of error messages

#### 5B. Transaction Wrapping
- [ ] Bungkus seluruh logic konsolidasi di `DB::transaction()`
- [ ] Rollback otomatis jika terjadi error di tengah proses
- [ ] Tambahkan try-catch dengan pesan error yang jelas

#### 5C. Filter Investment Plan Berdasarkan Status
- [ ] Modifikasi query di `consolidate()` untuk hanya mengambil InvestmentPlan dengan status `approved`
- [ ] Atau tambahkan parameter `$status` dan default ke `approved`

#### 5D. Unified Consolidation Endpoint (Opsional)
- [ ] Buat endpoint `/erkap/consolidasi/run` yang menjalankan CAPEX + OPEX consolidation dalam satu transaction
- [ ] Atau buat command `erkap:consolidate-all` yang bisa dijalankan via scheduler

#### 5E. Idempotency Check
- [ ] Tambahkan flag `consolidated_at` di `BudgetCapex` dan `BudgetOpex`
- [ ] Cek apakah konsolidasi sudah pernah dijalankan untuk RKAP + division tertentu
- [ ] Jika ya, skip atau tanya user untuk konfirmasi re-consolidate

#### 5F. Data Seeding untuk Testing
- [ ] Buat seeder `ConsolidationTestSeeder` yang:
  - Membuat RKAP dummy
  - Membuat InvestmentPlan dan RoutineCost approved
  - Membuat ZBB review lengkap
  - Memastikan konsolidasi bisa dijalankan

### File yang Akan Diubah
| File | Perubahan |
|---|---|
| `app/Http/Controllers/Erkap/BudgetCapexController.php` | Pre-condition, transaction, filter status |
| `app/Services/BudgetOpexConsolidationService.php` | Pre-condition, transaction |
| `app/Services/Erkap/ConsolidationService.php` | **FILE BARU** — unified consolidation (opsional) |
| `database/seeders/ConsolidationTestSeeder.php` | **FILE BARU** — test data |
| `database/migrations/*_add_consolidated_at_to_budget_capex.php` | **FILE BARU** — idempotency |
| `tests/Feature/Erkap/ConsolidationTest.php` | **FILE BARU** — test coverage |

### Estimasi Effort
- 5A (pre-condition): 0.5 hari
- 5B (transaction): 0.5 hari
- 5C (filter status): 0.5 hari
- 5D (unified endpoint): 1 hari (opsional)
- 5E (idempotency): 0.5 hari
- 5F (seeding): 1 hari

**Total: ~3-4 hari** (termasuk opsi unified endpoint)

---

## Ringkasan Prioritas & Timeline

### Prioritas Tinggi (P1) — Dikerjakan Pertama
| Gap | Task | Effort |
|---|---|---|
| Stage Gate Review | 1B (notifikasi), 1C (race condition), 1E (division scoping) | 1.5 hari |
| Division Scoping | 3A (fallback), 3B (middleware) | 1.5 hari |
| ZBB Review | 4A (pre-condition), 4B (filter status) | 1 hari |
| Konsolidasi | 5A (pre-condition), 5B (transaction), 5C (filter status) | 1.5 hari |

**Subtotal P1: ~5.5 hari**

### Prioritas Sedang (P2) — Dikerjakan Kedua
| Gap | Task | Effort |
|---|---|---|
| Form Validation | 2A (standarisasi), 2B (score field), 2C (code validation) | 2 hari |
| Division Scoping | 3C (user assignment), 3D (scopeDivision) | 1 hari |
| ZBB Review | 4C (seeding), 4D (division scoping) | 1.5 hari |
| Konsolidasi | 5E (idempotency), 5F (seeding) | 1.5 hari |

**Subtotal P2: ~6 hari**

### Prioritas Rendah (P3) — Dikerjakan Terakhir
| Gap | Task | Effort |
|---|---|---|
| Stage Gate Review | 1A (diskusi stakeholder), 1D (stage BMI) | 1-2 hari |
| Form Validation | 2D (authorize) | 1 hari |
| Konsolidasi | 5D (unified endpoint) | 1 hari |

**Subtotal P3: ~3-4 hari**

### Total Estimasi Effort
| Prioritas | Effort |
|---|---|
| P1 (Tinggi) | 5.5 hari |
| P2 (Sedang) | 6 hari |
| P3 (Rendah) | 3-4 hari |
| **TOTAL** | **14.5-15.5 hari** |

---

## Urutan Kerja yang Disarankan

```
Minggu 1:
├── Hari 1-2: P1 - Division Scoping (3A + 3B) + mulai P1 Stage Gate (1B + 1C)
├── Hari 3-4: P1 - ZBB Review (4A + 4B) + Konsolidasi (5A + 5B + 5C)
└── Hari 5:   P1 - Stage Gate (1E) + P2 - Form Validation (2A + 2B)

Minggu 2:
├── Hari 1-2: P2 - Form Validation (2C + 2D) + Division Scoping (3C + 3D)
├── Hari 3-4: P2 - ZBB Review (4C + 4D) + Konsolidasi (5E + 5F)
└── Hari 5:   P3 - Stage Gate (1A + 1D) + Konsolidasi (5D)

Minggu 3 (jika ada):
├── Hari 1-2: Final review, testing ulang, dokumentasi
└── Hari 3-4: Buffer untuk revisi
```

---

## Risiko & Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Stakeholder tidak tersedia untuk diskusi 1A | Blocker untuk simplifikasi stage gate | Kerjakan task lain dulu, jadwalkan diskusi |
| Rename field `des_cost` → `dec_plan` breaking change | Frontend perlu update bersamaan | Koordinasi dengan tim frontend, gunakan API versioning jika perlu |
| Middleware division scoping mengganggu flow yang ada | Existing features bisa broken | Test thoroughly, gunakan feature flag |
| Seeder data tidak realistik | Test tidak meaningful | Gunakan data yang mirip production, liburkan domain expert |

---

## Kriteria Keberhasilan

Setelah semua perbaikan selesai, kriteria berikut harus terpenuhi:

1. **Semua 28 test E2E passing** (dari yang sebelumnya 24/28)
2. **Tidak ada test yang memerlukan workaround** (seperti manual assign permission)
3. **Division scoping bekerja konsisten** di semua controller ERKAP
4. **Form validation konsisten** — tidak ada lagi mismatch field names
5. **ZBB Review & Konsolidasi** bisa dijalankan dengan data seeding yang cukup
6. **Tidak ada regression** di fitur yang sudah working sebelumnya

---

## Catatan

- Planning ini bersifat **incremental** — bisa dikerjakan bertahap per prioritas
- Setiap task harus diikuti dengan **test update** agar gap tidak muncul lagi
- Komunikasi dengan stakeholder (terkait 1A) harus dilakukan **sebelum** implementasi
- Semua perubahan harus melalui **code review** sebelum merge
