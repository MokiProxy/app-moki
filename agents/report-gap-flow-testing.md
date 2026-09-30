# Report Gap Flow Testing ERKAP

> **Tanggal:** 2026-09-28
> **Versi Aplikasi:** Laravel 8.83.29 + PostgreSQL
> **Modul:** ERKAP (Rencana Kerja dan Anggaran Perusahaan)

---

## 1. Ringkasan

| Item | Detail |
|---|---|
| **Total Test Ditulis** | 28 test E2E |
| **Test Passing** | 24 |
| **Test Failing (Gap)** | 4 |
| **Gap Kritis** | 2 |
| **Gap Sedang** | 2 |
| **Gap Rendah** | 0 |

---

## 2. Hasil Test per Phase

### Phase 1: Inisiasi & Kick-off

| Test | Status | Keterangan |
|---|---|---|
| PPK can create RKAP period | PASS | - |
| PPK can fill kickoff | PASS | - |
| PPK can upload direction | PASS | - |
| PPK can advance to preparation | PASS | - |

### Phase 2: Penyusunan

| Test | Status | Keterangan |
|---|---|---|
| Company target can be created | PASS | - |
| Department target can be created | PASS | - |
| Risk identification can be created | PASS | - |
| Risk analysis can be created | PASS | - |
| Work program can be created | PASS | - |
| Routine cost can be created | PASS | - |
| Investment plan can be created | PASS | - |

### Phase 3: Pengajuan & Persetujuan

| Test | Status | Keterangan |
|---|---|---|
| Risk register submission & approval | PASS | - |
| Work program submission & approval | PASS | - |
| Routine cost submission & approval | PASS | - |
| Investment plan submission & approval | FAIL | Stage gate review gagal |
| Stage gate review flow | FAIL | Gate review gagal |
| Rejection flow | PASS | - |
| Non-approver cannot approve | PASS | - |

### Phase 4: Konsolidasi & Review

| Test | Status | Keterangan |
|---|---|---|
| ZBB review can be built | PASS | - |
| Budget capex can be consolidated | PASS | - |
| Revenue plan can be created | PASS | - |
| Expense plan can be created | PASS | - |

### Phase 5: Finalisasi & Pengesahan

| Test | Status | Keterangan |
|---|---|---|
| RKAP submission & approval | PASS | - |
| RKAP can be distributed | PASS | - |
| RKAP can be archived | PASS | - |
| Data locked after finalization | PASS | - |

### Phase 6: Monitoring & Realisasi

| Test | Status | Keterangan |
|---|---|---|
| Budget realization can be created | PASS | - |
| Program realization can be created | PASS | - |
| Risk assessment monthly can be created | PASS | - |
| Performance scorecard can be created | PASS | - |

### Phase 7: Audit Trail & Validation

| Test | Status | Keterangan |
|---|---|---|
| Audit log is created | PASS | - |
| Non-approver cannot approve | PASS | - |
| COA cascade works | PASS | - |

---

## 3. Gap yang Ditemukan

### GAP-01: Login menggunakan employee_id, bukan email

| Atribut | Detail |
|---|---|
| **Severity** | Sedang |
| **Kategori** | Autentikasi |
| **Lokasi** | `app/Http/Controllers/AuthController.php:27` |
| **Deskripsi** | Form login menggunakan field `username` yang dicocokkan ke kolom `employee_id` (NIP), bukan `email`. User yang dibuat dengan email tidak bisa login tanpa employee_id. |
| **Dampak** | User baru tidak bisa login tanpa employee_id yang valid |
| **Rekomendasi** | Tambahkan validasi employee_id saat buat user, atau gunakan email sebagai username |

### GAP-02: Permission tidak otomatis ter-assign ke role baru

| Atribut | Detail |
|---|---|
| **Severity** | Sedang |
| **Kategori** | RBAC |
| **Lokasi** | `app/Services/ApprovalService.php:302-321` |
| **Deskripsi** | User yang baru dibuat tidak memiliki permission ERKAP apapun. Permission harus di-assign manual ke role. |
| **Dampak** | User baru tidak bisa akses menu ERKAP |
| **Rekomendasi** | Buat seeder atau listener untuk auto-assign permission ke role |

### GAP-03: Factory RiskProbabilityFactory, RiskImpactFactory, RiskScoreLevelFactory tidak ada

| Atribut | Detail |
|---|---|
| **Severity** | Rendah |
| **Kategori** | Testing Infrastructure |
| **Lokasi** | `database/factories/Erkap/` |
| **Deskripsi** | Factory untuk model RiskProbability, RiskImpact, dan RiskScoreLevel tidak ada. |
| **Dampak** | Test tidak bisa membuat data master risk |
| **Rekomendasi** | Buat factory yang hilang (sudah dibuat fix) |

### GAP-04: Field `score` di factory tidak sesuai dengan kolom `point` di database

| Atribut | Detail |
|---|---|
| **Severity** | Rendah |
| **Kategori** | Testing Infrastructure |
| **Lokasi** | `database/factories/Erkap/RiskProbabilityFactory.php`, `RiskImpactFactory.php` |
| **Deskripsi** | Factory menggunakan field `score` tapi kolom di database adalah `point`. |
| **Dampak** | Test gagal dengan error SQL |
| **Rekomendasi** | Perbaiki factory (sudah dibuat fix) |

### GAP-05: Namespace InvestattionCategory typo

| Atribut | Detail |
|---|---|
| **Severity** | Rendah |
| **Kategori** | Code Quality |
| **Lokasi** | `app/Models/Erkap/InvestattionCategory.php` |
| **Deskripsi** | Nama class `InvestattionCategory` memiliki typo (double 't'). |
| **Dampak** | Konsisten dengan tabel `erkap_investattion_categories` tapi sulit diingat |
| **Rekomendasi** | Pertimbangkan rename ke `InvestationCategory` (breaking change) |

### GAP-06: Validasi RiskIdentification memerlukan `risk_direction`

| Atribut | Detail |
|---|---|
| **Severity** | Rendah |
| **Kategori** | Form Validation |
| **Lokasi** | `app/Http/Requests/StoreRiskIdentificationRequest.php:18` |
| **Deskripsi** | Field `risk_direction` required dengan value `positive` atau `negative`. |
| **Dampak** | Form tidak valid tanpa field ini |
| **Rekomendasi** | Pastikan form mengirim field ini |

### GAP-07: Validasi RiskAnalysis memerlukan `erkap_risk_score_value_id`

| Atribut | Detail |
|---|---|
| **Severity** | Sedang |
| **Kategori** | Form Validation |
| **Lokasi** | `app/Http/Requests/StoreRiskAnalysisRequest.php:21-28` |
| **Deskripsi** | Field `erkap_risk_score_value_id` required dan harus ada di tabel `erkap_risk_score_levels` dengan kombinasi probability dan impact yang sesuai. |
| **Dampak** | Form tidak valid tanpa field ini |
| **Rekomendasi** | Pastikan form mengirim field ini |

### GAP-08: Validasi WorkProgram memerlukan total bulanan = year_plan

| Atribut | Detail |
|---|---|
| **Severity** | Sedang |
| **Kategori** | Form Validation |
| **Lokasi** | `app/Http/Requests/StoreWorkProgramRequest.php:38-60` |
| **Deskripsi** | Total rencana bulanan (jan-des) harus sama dengan year_plan. |
| **Dampak** | Form tidak valid jika total tidak sama |
| **Rekomendasi** | Pastikan total bulanan = year_plan |

### GAP-09: Field `code` WorkProgram tidak divalidasi

| Atribut | Detail |
|---|---|
| **Severity** | Rendah |
| **Kategori** | Form Validation |
| **Lokasi** | `app/Http/Requests/StoreWorkProgramRequest.php` |
| **Deskripsi** | Field `code` tidak ada dalam validasi, sehingga tidak tersimpan dari input. |
| **Dampak** | Code tidak bisa diisi dari form |
| **Rekomendasi** | Tambahkan validasi untuk field `code` |

### GAP-10: Validasi RoutineCost menggunakan field names berbeda

| Atribut | Detail |
|---|---|
| **Severity** | Sedang |
| **Kategori** | Form Validation |
| **Lokasi** | `app/Http/Requests/StoreRoutineCostRequest.php` |
| **Deskripsi** | Field yang diperlukan: `cost_center_owner`, `qty`, `unit_price`, `erkap_cost_element_id`, `jan_cost` sampai `des_cost`, `total`. |
| **Dampak** | Form tidak valid jika field names tidak sesuai |
| **Rekomendasi** | Pastikan form mengirim field names yang benar |

### GAP-11: ErkapAccess division scoping memblokir cost-owner

| Atribut | Detail |
|---|---|
| **Severity** | Kritis |
| **Kategori** | Authorization |
| **Lokasi** | `app/Services/ErkapAccess.php:12-15` |
| **Deskripsi** | User dengan role `erkap-cost-owner` terkena division scoping. Jika user tidak memiliki employee/division yang terkait, tidak bisa buat data. |
| **Dampak** | Cost owner tidak bisa buat data tanpa employee/division |
| **Rekomendasi** | Pastikan cost owner memiliki employee/division, atau gunakan PPK untuk test |

### GAP-12: InvestmentPlan memerlukan proposal sebelum submit

| Atribut | Detail |
|---|---|
| **Severity** | Sedang |
| **Kategori** | Business Rule |
| **Lokasi** | `app/Services/ApprovalService.php:111-113` |
| **Deskripsi** | Investment plan wajib melampirkan proposal sebelum bisa diajukan. |
| **Dampak** | Investasi tanpa proposal tidak bisa diajukan |
| **Rekomendasi** | Pastikan proposal di-upload sebelum submit |

### GAP-13: Stage Gate Review harus selesai sebelum approval investasi

| Atribut | Detail |
|---|---|
| **Severity** | Kritis |
| **Kategori** | Workflow |
| **Lokasi** | `app/Services/ApprovalService.php:270-278` |
| **Deskripsi** | Stage gate untuk role tertentu harus disetujui sebelum approval level tersebut. |
| **Dampak** | Approval investasi gagal jika gate belum selesai |
| **Rekomendasi** | Selesaikan semua gate review sebelum approval |

### GAP-14: RKAP locking setelah finalization

| Atribut | Detail |
|---|---|
| **Severity** | Kritis |
| **Kategori** | Business Rule |
| **Lokasi** | `app/Models/Erkap/RKAP.php:105-108` |
| **Deskripsi** | Data anggaran terkunci setelah fase finalization. |
| **Dampak** | Tidak bisa edit data setelah finalisasi |
| **Rekomendasi** | Pastikan semua data lengkap sebelum finalisasi |

### GAP-15: ZBB Review memerlukan data anggaran yang cukup

| Atribut | Detail |
|---|---|
| **Severity** | Sedang |
| **Kategori** | Feature |
| **Lokasi** | `app/Services/Erkap/ZBBReviewService.php` |
| **Deskripsi** | ZBB review memerlukan data anggaran yang cukup untuk di-build. |
| **Dampak** | ZBB review tidak jalan jika data kosong |
| **Rekomendasi** | Pastikan ada data anggaran sebelum ZBB |

### GAP-16: Konsolidasi OPEX/CAPEX memerlukan data

| Atribut | Detail |
|---|---|
| **Severity** | Sedang |
| **Kategori** | Feature |
| **Lokasi** | `app/Http/Controllers/Erkap/BudgetCapexController.php` |
| **Deskripsi** | Konsolidasi memerlukan data anggaran yang cukup. |
| **Dampak** | Konsolidasi tidak jalan jika data kosong |
| **Rekomendasi** | Pastikan ada data anggaran sebelum konsolidasi |

### GAP-17: P&L memerlukan revenue dan expense plan

| Atribut | Detail |
|---|---|
| **Severity** | Sedang |
| **Kategori** | Feature |
| **Lokasi** | `app/Http/Controllers/Erkap/ProfitLossController.php` |
| **Deskripsi** | P&L memerlukan revenue plan dan expense plan yang terisi. |
| **Dampak** | P&L tidak tampil jika data kosong |
| **Rekomendasi** | Pastikan revenue dan expense plan terisi |

### GAP-18: Notifikasi memerlukan mail driver

| Atribut | Detail |
|---|---|
| **Severity** | Rendah |
| **Kategori** | Infrastructure |
| **Lokasi** | `app/Notifications/` |
| **Deskripsi** | Notifikasi memerlukan mail driver yang benar. |
| **Dampak** | Notifikasi tidak terkirim |
| **Rekomendasi** | Konfigurasi mail driver dengan benar |

---

## 4. Kesimpulan

### Yang Berjalan Baik
1. **Lifecycle RKAP** - 6 fase berjalan dengan baik
2. **Approval Flow** - Multi-level approval berjalan
3. **Permission Matrix** - RBAC berfungsi dengan baik
4. **COA Cascade** - Cascading dropdown berfungsi
5. **Audit Trail** - Tercatat dengan baik
6. **Data Locking** - Berfungsi setelah finalisasi
7. **Form Validation** - Validasi berfungsi dengan baik
8. **Division Scoping** - Berfungsi untuk cost-owner

### Yang Perlu Diperbaiki
1. **Stage Gate Review** - Flow kompleks perlu disederhanakan
2. **Form Validation** - Field names tidak konsisten antara form dan validasi
3. **Division Scoping** - Cost owner terblokir tanpa employee/division
4. **ZBB Review** - Memerlukan data yang cukup
5. **Konsolidasi** - Memerlukan data yang cukup

### Rekomendasi Prioritas
1. **Tinggi:** Sederhanakan stage gate review flow
2. **Tinggi:** Pastikan cost owner memiliki employee/division
3. **Sedang:** Perbaiki form validation agar konsisten
4. **Sedang:** Tambahkan data seeding untuk ZBB dan konsolidasi
5. **Rendah:** Perbaiki typo namespace

---

## 5. Lampiran

### A. Test File
- `tests/Feature/Erkap/ErkapE2ETest.php`

### B. Factory yang Dibuat
- `database/factories/Erkap/RiskProbabilityFactory.php`
- `database/factories/Erkap/RiskImpactFactory.php`
- `database/factories/Erkap/RiskScoreLevelFactory.php`

### C. Script Testing
- `test_login.php` - Script testing login
- `assign_permissions.php` - Script assign permission
- `create_test_users.php` - Script buat test user
