# Planning G1 — Stage Gate Review / Kajian Kelayakan (CBA) untuk CAPEX

> **Gap:** G1 (Kritis) — Bagian 3 & 4 (Form 4) & 5
> **Prioritas:** P0 — Wajib
> **Status:** Belum Diimplementasikan ❌
> **Referensi:** `agents/recap-pedoman-rkap/hasil-analisis-gap.md` baris 26–27, 69, 78, 93–97, 159.

---

## 1. Latar Belakang

Pedoman mewajibkan setiap usulan investasi non-rutin (CAPEX) **lulus kajian kelayakan / Cost
Benefit Analysis (CBA)** dan **persetujuan Gate Review berjenjang** (termasuk review PT BMI)
sebelum usulan masuk ke dalam anggaran.

Fakta di codebase:

- Grep `stage.?gate|kelayakan|cost.?benefit|feasibility|gate_review` pada `app/` → **tidak ada hasil**.
- `database/migrations/2026_09_11_000002_create_investment_plans_table.php` tidak memiliki kolom
  lampiran/proposal/file maupun data CBA.
- `App\Models\Erkap\InvestmentPlan` (`$fillable`) tidak punya field CBA/kelayakan — lihat
  `app/Models/Erkap/InvestmentPlan.php:17-42`.
- Matriks approval di `app/Services/ApprovalService.php:32-35` untuk `investment_plan` hanya
  `PPK → erkap-direksi-keuangan`. Tidak ada step Manajemen Aset / Gate Review / PT BMI.
- **Akibat:** investasi bebas masuk `erkap_investment_plans` tanpa kajian kelayakan.

---

## 2. Tujuan

1. Setiap `InvestmentPlan` wajib memiliki **proposal lampiran** dan **hasil kajian kelayakan/CBA** sebelum dapat diajukan.
2. Menyediakan **alur Gate Review berjenjang**: PPK → Dept. Manajemen Aset → Direksi Keuangan → **Gate Review PT BMI**.
3. Menampilkan status gate review pada daftar & detail investasi.

---

## 3. Desain Solusi

### 3.1 Struktur Data Baru

Migration baru: `2026_09_26_000001_create_investment_stage_gates_table.php`

Tabel `erkap_investment_stage_gates`:

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint PK | |
| `erkap_investment_plan_id` | FK `erkap_investment_plans` (cascadeOnDelete) | |
| `stage` | enum `['proposal','cba','aset','direksi_keuangan','gate_review_bmi']` | urutan gate |
| `stage_order` | smallint | urutan (1..5) |
| `status` | enum `['pending','approved','rejected','revised']` default `pending` | |
| `reviewer_role` | string (role name spatie) | role yang berhak menilai |
| `reviewed_by` | FK `users` nullable | reviewer aktual |
| `reviewed_at` | timestamp nullable | |
| `notes` | text nullable | catatan evaluasi |
| `result` | string nullable | mis. `layak`, `tidak_layak`, `revisi` |
| `timestamps` | | |

Kolom tambahan pada `erkap_investment_plans` (migration `add_feasibility_to_investment_plans_table`):

| Kolom | Tipe | Keterangan |
|---|---|---|
| `proposal_file_path` | string nullable | path lampiran proposal (PDF/DOC/XLS) |
| `proposal_original_name` | string nullable | nama file asli |
| `cba_json` | json nullable | hasil CBA terstruktur (NPV/IRR/payback/justifikasi) — bisa ditingkatkan ke tabel normal jika dibutuhkan |
| `cba_attachment_path` | string nullable | dokumen CBA |
| `gate_review_status` | enum `['none','in_review','partial','approved','rejected']` default `none` | agregat status gate |

### 3.2 Model

- Baru: `App\Models\Erkap\InvestmentStageGate` (`$fillable`, `belongsTo InvestmentPlan`, scope
  `pending()`, helper `label()`, `canReviewBy(User $user)`).
- Ubah: `App\Models\Erkap\InvestmentPlan`:
  - tambah `proposal_file_path`, `proposal_original_name`, `cba_json`, `cba_attachment_path`, `gate_review_status` ke `$fillable` + cast `cba_json => array`;
  - relasi `stageGates()` → `hasMany(InvestmentStageGate, 'erkap_investment_plan_id')` (order `stage_order`);
  - helper `hasProposal(): bool`, `isGateComplete(): bool` (semua stage status `approved`), `currentGate()` (stage pertama yg pending);
  - booted `saving`: menolak `ApprovalService::submit` bila `! hasProposal()` (guard pada controller lebih utama, di sini sebagai jaring pengaman).

### 3.3 Service

Baru `App\Services\Erkap\InvestmentGateReviewService`:

```php
public static function defaultStages(): array // 1..5 sesuai matriks
public static function initialize(InvestmentPlan $plan): void
    // buat 5 baris stage gate pending setelah plan dibuat/pertama submit
public static function review(InvestmentPlan $plan, string $stage, User $user, array $payload): void
    // validasi giliran (reviewer_role user, stage urut), update status+notes, auto-advance
public static function aggregateStatus(InvestmentPlan $plan): string
```

### 3.4 Matriks Approval

Perluas `App\Services\ApprovalService::getApprovalMatrix()` untuk `investment_plan`:

- Level 1 → `erkap-ppk`
- Level 2 → **`erkap-manajemen-aset`** (role baru)
- Level 3 → `erkap-direksi-keuangan`
- Level 4 → **`erkap-gate-review`** (role baru, representasi review/decision PT BMI)

`ApprovalService::submit()` jalankan `InvestmentGateReviewService::initialize()` sebelum membuat
approvals bila plan belum punya gate.

### 3.5 Controller & Routes

Baru `App\Http\Controllers\Erkap\InvestmentStageGateController`:

- `index(?erkap_investment_plan_id)` — daftar gate pendingdari semua plan (untuk panel Manajemen Aset / Gate Review).
- `show(InvestmentStageGate $gate)` — form evaluasi.
- `store(InvestmentStageGateReviewRequest $request, InvestmentStageGate $gate)` — simpan evaluasi (approve/reject/revisi + notes + lampiran CBA di level cba).

Tambahan method pada `App\Http\Controllers\Erkap\InvestmentPlanController`:

- `create`/`edit`: kirim data COA cost center + input upload proposal.
- `store`/`update`: proses upload via `$request->file('proposal')->storeAs(...)`; update `cba_json`.

Routes di `routes/routers/erkap.php` (prefix `investment-gates`):

```php
GET  /erkap/investment-gates                  index   permission:erkap.investment-gates.view
GET  /erkap/investment-gates/{gate}           show    permission:erkap.investment-gates.view
POST /erkap/investment-gates/{gate}/review    store   permission:erkap.investment-gates.review
```

Route download lampiran:
```php
GET /erkap/investment-plans/{plan}/proposal   download  permission:erkap.investment-plans.view
```

### 3.6 Views

- `resources/views/erkap/investment-gate/index.blade.php` — tabel plan + progress gate (dot/steps).
- `resources/views/erkap/investment-gate/show.blade.php` — ringkasan investasi + CBA + form evaluasi.
- Ubah `resources/views/erkap/investment-plan/form.blade.php` (atau create/edit): tambah input file proposal + CBA + ringkasan JSON.

### 3.7 Permission & Role

`database/seeders/RolePermissionSeeder.php`:

- Role baru: `erkap-manajemen-aset`, `erkap-gate-review`.
- Resource baru (auto cycle `view/create/edit/delete`): `investment-gates`.
- Permission ekstra: `erkap.investment-gates.review`, `erkap.investment-plans.download`.
- Beri `erkap-manajemen-aset`: view/review gate untuk stage `aset`, `investment-plans.view`.
- Beri `erkap-gate-review`: view/review gate stage `gate_review_bmi`.
- Beri `erkap-direksi-keuangan` tetap approval level 3.

### 3.8 Tests

- Unit: `InvestmentGateReviewServiceTest` — initialisasi stage, urutan, tolak review di luar giliran, agregat status.
- Feature: `InvestmentStageGateFeatureTest` — alur lengkap submit plan → review aset → direksi keuangan → gate review BMI → plan `approved`.
- Pastikan `InvestmentPlan` tanpa proposal → `submit()` gagal (guard test).

---

## 4. Urutan Implementasi

1. Migration kolom proposal/CBA + tabel stage gates.
2. Model `InvestmentStageGate` + update `InvestmentPlan` (fillable, relasi, helper, guard).
3. Service `InvestmentGateReviewService`.
4. Perluas matriks approval + inisialisasi gate pada submit.
5. Controller + routes + import/export jika perlu.
6. Views + menu sidebar (bawah grup "Rencana Investasi").
7. Seeder role/permission.
8. Tests + `php artisan test --filter=InvestmentGate`.

---

## 5. Kriteria Penerimaan (Acceptance Criteria)

- [ ] Membuat `InvestmentPlan` baru → otomatis terbentuk 5 stage gate pending.
- [ ] Plan tanpa `proposal_file_path` **tidak bisa** di-submit.
- [ ] Stage dievaluasi berurutan; user dengan role selain `reviewer_role` ditolak.
- [ ] Seluruh stage approved → plan dapat lolos hingga approval final.
- [ ] Ringkasan CBA (`cba_json`) tampil di detail & ekspor CAPEX.
- [ ] Menu "Gate Review / Kelayakan Investasi" muncul di sidebar & dashboard approval.

---

## 6. Risiko & Dependensi

- **Dependensi:** G9 (kolom `cost_center_id` + lampiran) tumpang-tindih; koordinasikan agar
  `proposal_file_path` dibuat sekali saja.
- **Dependensi:** G2 (role Dept. Manajemen Aset) — pastikan penamaan role seragam
  (`erkap-manajemen-aset` dipakai di sini dan di planning G2).
- **Risiko:** penyimpanan file besar — simpan di `storage`/S3 (sesuai pattern `Maatwebsite` /
  existing upload), jangan di DB.
- **Risiko:** ruang lingkup "PT BMI" tidak ber-role jelas di organisasi — buat role `erkap-gate-review`
  generic sehingga bisa di-assign ke user BMI nanti.