# Planning G2 — Evaluasi Dept Risk Management (Form 1), Dept Manajemen Aset & Gate Review PT BMI

> **Gap:** G2 (Kritis) — Bagian 2, 4 (Form 1) & 5
> **Prioritas:** P0 — Wajib
> **Status:** Belum Diimplementasikan ❌
> **Referensi:** `agents/recap-pedoman-rkap/hasil-analisis-gap.md` baris 27, 58, 85, 99–102, 160.

---

## 1. Latar Belakang

Pedoman menetapkan evaluasi berjenjang oleh **departemen fungsional**:

1. **Form 1 (Sasaran & Asesmen Risiko)** dievaluasi **Dept. Risk Management**.
2. **CAPEX (Form 4)** dievaluasi **Dept. Manajemen Aset** dan melewati **Gate Review PT BMI**.

Fakta di codebase:

- Matriks approval `app/Services/ApprovalService.php:21-48` TIDAK memuat step evaluasi fungsional,
  kecuali `risk_register → erkap-risk-manager` (level tunggal).
- `type 'risk_register'` ada di `ApprovalService::documentTypes()` & matriks
  (`RiskIdentification` model), **tetapi tidak ada route submit**:
  `routes/routers/erkap.php` untuk `risk-identifications` hanya CRUD + export, tanpa `submit`
  (`app/Http/Controllers/Erkap/RiskIdentificationController` tidak punya `submit()`).
- **Tidak ada** role/step "Manajemen Aset" dan "PT BMI / Gate Review".
- Status `erkap_risk_identifications.approval_status` (enum draft/submitted/approved/rejected) ada
  (migration `2026_09_22_073957`), tapi `RiskIdentification::$fillable` memakai kolom tersebut, tidak dipakai approval flow karena kolom approvals memakai `status` dari trait `HasApprovalWorkflow` → perlu pengecekan saat implementasi.

---

## 2. Tujuan

1. **Form 1** dapat di-submit oleh Cost Owner / PPK dan **dievaluasi Dept. Risk Management**
   (approve/reject + catatan evaluasi), dengan **batasan edit pasca-evaluasi**.
2. **CAPEX** memiliki step evaluasi **Dept. Manajemen Aset** dan **Gate Review PT BMI**
   (selaras dengan G1 — koordinasikan agar tidak dobel).

---

## 3. Desain Solusi

### 3.1 Wire submit untuk Risk Identification (Form 1)

- Tambah method di `App\Http\Controllers\Erkap\RiskIdentificationController`:
  - `submit(RiskIdentification $risk)` → `ApprovalService::submit($model)` dengan guard
    `validateHasStrategyAndWorkProgram()` (`RiskIdentification.php:117`) — Risk wajib punya strategi
    & program kerja sebelum dikirim ke RM.
  - Opsional `submitBatch()` (seperti pola WorkProgramController).
- Routes (prefix `risk-identifications`):
  ```php
  POST /erkap/risk-identifications/{riskIdentification}/submit   name=submit   permission:erkap.risk-identifications.submit
  POST /erkap/risk-identifications/submit-batch                   name=submit-batch
  ```
- Matriks `risk_register` (sudah ada) tetap `erkap-risk-manager` level 1. Terapkan **evaluasi** secara natural: approve → `approved`, reject → `rejected` (mekanisme `ApprovalService::approve/reject` sudah ada).
- **Batasan edit**: di `RiskIdentificationController::edit/update`, tolak bila `approval_status === 'submitted' || 'approved'` (kecuali user `erkap-admin`). Terapkan juga pada child: `RiskIdentificationReason/Impact`, `RiskAnalysis`, `RiskRanking`, `DepartmentRiskStrategy` controller (guard via helper).

### 3.2 Step evaluasi Departemen Manajemen Aset & Gate Review PT BMI (CAPEX)

- Lihat planning **G1** — matriks `investment_plan` diperluas menjadi
  `PPK → erkap-manajemen-aset → erkap-direksi-keuangan → erkap-gate-review`.
- Untuk file ini cukup: pastikan role & permission dibuat & approval dapat dibaca pada panel
  Approval (`ApprovalController::index`) — tidak ada perubahan ApprovalController diperlukan.

### 3.3 Panel review Risk Management

- `resources/views/erkap/approvals/*` sudah generic (type+id). Pastikan label `risk_register`
  tampil rapi: `ApprovalService::documentTypes()['risk_register']['label']` = "Register Risiko".
- Tambah tampilan detail `risk-identifications/show` (atau reuse approvals/show) yang menampilkan:
  ringkasan asesmen (I×L), strategi, program kerja, evaluasi RM.

### 3.4 Guard helper

`App\Services\ErkapAccess` atau helper baru `App\Support\ErkapEvaluationLock`:

```php
public static function lockIfEvaluated(int $status, ?User $user): void
// throw ValidationException jika status in ('submitted','approved') dan user bukan admin/auditor
```

Dipasang pada update/delete: risk & turunannya, work programs yang sudah lewat evaluasi RM.

### 3.5 Permissions

`RolePermissionSeeder`:
- Tambah permission `erkap.risk-identifications.submit` (masukkan ke `$approvalPermissions` / submit set cost owner).
- Role `erkap-risk-manager` saat ini memiliki `view` saja → tambah `erkap.risk-identifications.approve/reject` (atau via approvals generik `erkap.approvals.view` + proses approve—tentukan: ApprovalController pakai permission `erkap.approvals.view` untuk approve/reject; aman).
- Role `erkap-manajemen-aset`, `erkap-gate-review` sesuai G1.

---

## 4. Urutan Implementasi

1. Route + method `submit`/`submitBatch` RiskIdentification.
2. Guard evaluasi-lock pada update/delete risk & turunannya.
3. Validasi `validateHasStrategyAndWorkProgram()` sebelum submit (batch + single).
4. Panel detail Form 1 + evaluasi RM di Approval screen.
5. (Bersama G1) matriks `investment_plan` + role Manajemen Aset / Gate Review.
6. Seeder permission & run test.

---

## 5. Kriteria Penerimaan

- [ ] Cost Owner dapat submit Form 1 (tunggal & batch) → muncul di panel approval RM.
- [ ] Risk tanpa strategi/program kerja gagal di-submit.
- [ ] Dept RM approve/reject dengan catatan evaluasi tersimpan di `erkap_approvals.notes`.
- [ ] Risk/child yang sudah `submitted|approved` **tidak bisa diedit** oleh selain admin.
- [ ] CAPEX melewati evaluasi Manajemen Aset & Gate Review PT BMI (G1).

---

## 6. Risiko & Dependensi

- **Dependensi berat ke G1** untuk matriks CAPEX; implementasikan G2 & G1 bersamaan.
- **Risiko:** kolom ganda `status` vs `approval_status` pada `risk_identifications`
  (`HasApprovalWorkflow` memakai `status`; migration approval menambah `approval_status`).
  Sinkronkan: pindahkan logika approval ke `status` (trait), deprecate `approval_status`, atau
  samakan saat submit. Verifikasi di migration `2026_09_21_000016` (status columns) sebelum coding.
- **Risiko:** Cost Owner identity untuk evaluasi RM — pakai `divisionIdFor()` yang sudah ada di
  `ApprovalService` agar approver RM ter-scope divisi bila ada.