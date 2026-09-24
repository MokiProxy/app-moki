# Hasil Analisis Gap: Pedoman RKAP 2027 vs Implementasi Modul e-RKAP

> **Tanggal Analisis:** 23 September 2026
> **Ruang Lingkup:** `agents/recap-pedoman-rkap/bagian_1..5` (pedoman) vs kode modul `erkap` (migrations, models, controllers, services, views, routes, tests, seeders).
> **Metodologi:** Deep-read seluruh migration/model erkap, controller, service (Approval, BudgetOpexConsolidation, Form1ImportExport, Dashboard/Reporting), route, sidebar menu, seeder, dan template Excel Form 1; verifikasi ulang temuan kunci di beberapa file.

---

## 1. Ringkasan Eksekutif

Modul e-RKAP sudah sangat maju dan meng-cover **sebagian besar** substansi pedoman:

- ✅ 3 komponen anggaran (Pendapatan, OPEX, CAPEX) sudah ada sebagai modul terpisah.
- ✅ Proses asesmen risiko (Identifikasi → Analisis Impact×Likelihood → rating matrix 5×5) sudah terpenuhi dan terhubung ke sasaran.
- ✅ Form 1 (Sasaran + Asesmen Risiko) didukung via **Excel import/export** (16 kolom).
- ✅ Form 2 (jadwal rencana kerja) terpetakan ke `WorkProgram` + `WorkSchedule` (12 kolom bulanan).
- ✅ Form 3 (OPEX) hampir sempurna (Program, Barang/Jasa, Cost Center, Qty, Satuan, Harga Satuan, bulanan, konsolidasi).
- ✅ Form 4 (CAPEX) tersedia (Program, Kriteria Investasi, Kategori, Total, Rencana Pembayaran bulanan).
- ✅ Workflow submit/approval berjenjang (PPK → Controller / Direksi Keuangan; RKAP → Komisaris → Direksi).
- ✅ Konsolidasi OPEX & CAPEX, dashboard, BvA, report center, audit trail, RBAC.

Namun terdapat **sejumlah gap signifikan** yang belum diimplementasikan sesuai pedoman:

| # | Gap Kritis | Bagian Pedoman |
|---|-----------|----------------|
| G1 | **Stage Gate Review / Kelayakan Investasi (CBA) untuk CAPEX tidak ada sama sekali** (tanpa lampiran proposal, tanpa kajian kelayakan, tanpa gate review PT BMI) | Bagian 3 & 4 & 5 (Form 4) |
| G2 | **Step evaluasi oleh departemen fungsional tidak ada**: Form 1 dievaluasi Dept. Risk Management, CAPEX dievaluasi Dept. Manajemen Aset, Gate Review PT BMI — semuanya belum ada di matriks approval | Bagian 2, 4 (Form 1) & 5 |
| G3 | **Fase lifecycle RKAP (Inisiasi/Kick-off, Konsolidasi, Finalisasi/Pengesahan) belum terpetakan** — RKAP hanyalah record "tahun" | Bagian 5 |
| G4 | **Zero Based Budgeting (ZBB) belum diimplementasikan** — tidak ada logika reset-from-zero / pengikatan anggaran aktual tahun berjalan | Bagian 1 |
| G5 | **Prioritas Sasaran (SMART + urutan prioritas) belum presisi** — nilai rating tidak cocok antara pedoman (AAA/AA/A/BBB/BB), seeder (AAA/AA/A/B/BB), model (A+/A), dan template Excel | Bagian 2 & 4 (Form 1) |
| G6 | **COA 16 digit tidak terpenuhi** — kolom `chart_of_accounts.code` hanya `varchar(10)`, seeded 4 digit; COA tidak dapat dipilih langsung pada form OPEX | Bagian 3 |
| G7 | **Evaluasi & Penanganan Risiko belum "hidup"** — tabel `erkap_risk_treatments` ada tapi tanpa controller/rute/view; strategi bernilai `avoid/reduce/transfer/accept` (bukan `Avoidance/Reduction/Sharing/Acceptance`); "Sharing" tidak terwakili | Bagian 2 & 4 (Form 1) |
| G8 | **Form 2 belum kumulatif (%)** — jadwal tersimpan sebagai angka rencana per bulan, bukan **persentase kumulatif Jan–Des** | Bagian 4 (Form 2) |
| G9 | **Urutan Prioritas investasi tidak ada** — `erkap_investment_plans` tidak punya kolom `priority`, dan `cost_center_id` ada di DB tapi tidak ada di `$fillable` | Bagian 4 (Form 4) |
| G10 | **Biaya Tersentralisasi belum tuntas** — hanya flag `is_swakelola`; tidak ada alur "dikoordinir satu departemen (HR/IT)" dan ownership gaji/IT | Bagian 3 |

Gap G1–G4 bersifat **fungsional/absolut hilang**; G5–G10 bersifat **parsial / konsistensi data**.

---

## 2. Pemetaan Pedoman → Modul (Coverage Matrix)

### Bagian 1: Prinsip Dasar & Ketentuan Umum

| Ketentuan Pedoman | Status | Keterangan |
|---|---|---|
| Risk Based Budgeting | ⚠️ Parsial | **Tidak ada mekanisme "setiap rupiah berkorelasi risiko"**. Namun secara struktural, rantai *Budget → WorkProgram → RiskIdentification → DepartmentTarget → CompanyTarget* **dipaksa** oleh validasi `WorkProgram.booted()` (`app/Models/Erkap/WorkProgram.php:79-96`, wajib rating A + strategi) dan `StoreRoutineCostRequest`/`StoreInvestmentPlanRequest` (wajib `erkap_work_program_id`). Jadi RBB terjamin secara struktur, bukan sebagai penilaian kualitatif eksplisit. |
| Zero Based Budgeting (ZBB) | ❌ Tidak ada | Tidak ada logika penyusunan dari nol, perbandingan prior year, atau analisis persentase kenaikan. `previous_year_remaining` hanya muncul di ringkasan CAPEX (`BudgetCapexController::summary`, `app/Http/Controllers/Erkap/BudgetCapexController.php`). Tidak ada engine ZBB/carry-over. |

### Bagian 2: Sasaran & Manajemen Risiko

| Ketentuan Pedoman | Status | Keterangan |
|---|---|---|
| Sasaran SMART (Specific/Measurable/Achievable/Relevant/Time) | ⚠️ Parsial | Tidak ada field SMART eksplisit. Dipetakan implisit: `department_targets.target` (Specific/Relevant), `work_programs.year_plan` + 12 rencana bulanan = measurable & time frame (validasi Σ bulanan = tahunan di `WorkProgram::validateMonthlyBreakdown()`). Achievable tidak ada penilaian. |
| Prioritas Sasaran AAA/AA/A/BBB/BB | ❌ Mismatch | Pedoman: `AAA(A) / AA(A) / 'A'(Sangat Penting) / BBB(Penting) / BB(Cukup Penting)`. **Seeder `ErkapRatingCriteriaSeeder.php:17-23` menanam `AAA, AA, A, B, BB`** ("B" menggantikan "BBB"). Tidak ada field `priority` di `erkap_department_targets`. Model `WorkProgram.booted()` memvalidasi rating `['A+','A']` (`WorkProgram.php:83`), dan `WorkProgramController::ALLOWED_RATINGS = ['AAA','AA','A']`. Template Excel Form 1 (`app/Exports/Erkap/Form1TemplateExport.php`) justru menawarkan `A+, A, BB, B, C, A-Adjusted, BBB, CCC, D`. → **3 sumber nilai rating berbeda.** |
| Proses Asesmen: Identifikasi (negatif/positif) | ✅ Ada | `erkap_risk_identifications` + `risk_direction` (positive/negative), `risk_identification_reasons`, `risk_identification_impacts`. |
| Analisis (Impact × Likelihood) | ✅ Ada | `erkap_risk_analysis` menghubungkan probability × impact → `erkap_risk_score_levels` (matrix 5×5, score 1–25, level Low..High). Skor & level auto-fetch via `RiskAnalysisController::getScoreLevel()` dan `StoreRiskAnalysisRequest` memvalidasi kombinasi. |
| Evaluasi & Penanganan: Avoidance/Reduction/Sharing/Acceptance | ⚠️ Parsial | Strategi disimpan sebagai `avoid/reduce/transfer/accept` (CHECK constraint di `2026_09_08_120004` + `2026_09_11_000002`). **Terminologi "Sharing" tidak ada** (diganti "transfer"). Tabel `erkap_risk_treatments` ada (`treatment_type` = avoid/mitigate/transfer/accept) **tanpa controller/rute/view**. Tidak ada menu "Perlakuan Risiko". Dept Risk Management tidak punya step evaluasi. |

### Bagian 3: Panduan Teknis Anggaran

| Ketentuan Pedoman | Status | Keterangan |
|---|---|---|
| Anggaran Pendapatan | ✅ Ada | `erkap_revenue_plans` (12 kolom bulanan + total, scoped per divisi, COA). |
| Anggaran OPEX | ✅ Ada | `erkap_routine_costs` lengkap + konsolidasi (`BudgetOpexConsolidationService` → `erkap_budget_opex`). |
| Anggaran CAPEX | ✅ Ada | `erkap_investment_plans` + konsolidasi `erkap_budget_capex`, summary, payment distribution. |
| COA 16 digit | ❌ Tidak | `chart_of_accounts.code` = `varchar(10)` (**migration `2026_09_07_125216`**), seeded 4 digit (`6000`–`9109`). Tidak ada validasi 16 digit di request mana pun. |
| Biaya Tersentralisasi | ⚠️ Parsial | Hanya flag `cost_centers.is_swakelola` (segment kode "510"). Tidak ada alur barang jasa terpusat (gaji→HR, IT→Dept IT) & pembatasan multi-departemen. |
| Investasi Baru → Stage Gate Review | ❌ Tidak | Tidak ada kajian kelayakan/CBA, tidak ada attachment, tidak ada gate review berjenjang. Lihat G1. |

### Bagian 4: Formulir

| Formulir | Kolom Pedoman | Status di Codebase |
|---|---|---|
| Form 1: Sasaran & Asesmen Risiko | Deskripsi sasaran SMART, prioritas, identifikasi risiko, nilai risiko (I×L), rencana perlakuan risiko, evaluasi Dept RM | ⚠️ Import/Export Excel 16 kolom ada (`Form1ImportExportService::HEADINGS:28-45`) MENCANTUMKAN Strategi (bukan "rencana perlakuan risiko", tanpa detail rencana perlakuan). Tidak ada halaman form on-screen terpadu. Dept RM tidak mengevaluasi. Prioritas = rating yang bermasalah (G5). |
| Form 2: Jadwal Rencana Kerja | Sasaran, risiko/peluang, program kerja, **target % kumulatif Jan–Des** | ⚠️ `work_schedules` sudah punya 12 kolom bulanan, tapi isinya **angka rencana**, bukan **% kumulatif**. `work_programs` menampilkan 12 kolom bulanan. Konsep kumulatif (%) tidak ada di manapun (G8). |
| Form 3: Biaya Umum (OPEX) | Program Kerja, Jenis Barang/Jasa, Kode Cost Center, Qty, Satuan, Harga Satuan, COA | ✅ Hampir lengkap: `routine_costs` (need, cost_center_id, qty, units, unit_price, total, 12 bulan, is_kumulatif). ⚠️ **COA/Commitment Item tidak sebagai field input** — tertanam via `erkap_cost_element_id → chart_of_account_id`. |
| Form 4: Investasi (CAPEX) | Program Kerja, Kriteria Investasi, Kategori, Total, Rencana Pembayaran bulanan, **Urutan Prioritas**, **lampiran proposal kelayakan (CBA)** | ✅ Program/Kriteria/Kategori/Total/pembayaran bulanan ada. ❌ Tanpa `priority`, tanpa attachment/CBA (G1, G9). |

### Bagian 5: Alur & Proses Bisnis

| Tahapan | Status | Keterangan |
|---|---|---|
| 1. Persiapan & Inisiasi (Top-down): aspirasi holding, arahan direksi, buku pedoman, kick-off/sosialisasi | ❌ Tidak | Tidak ada menu/modul "inisiasi", "arahan direksi", "kick-off". `erkap_rkap` hanya berisi `year`. |
| 2. Penyusunan (Bottom-up): Form 1 dievaluasi Dept RM; Capex dievaluasi Dept Manajemen Aset & Gate Review PT BMI | ⚠️ Sebagian | Penyusunan Form 1–4 ada. **Evaluasi Dept RM / Dept Manajemen Aset / Gate Review BMI tidak ada** (approval hanya PPK/Controller/Direksi Keuangan/Komisaris/Direksi). |
| 3. Konsolidasi & Review: seluruh dokumen ke Dept Anggaran, konsolidasi data keuangan, rapat berjenjang antar unit usaha | ⚠️ Sebagian | Konsolidasi OPEX/CAPEX ada (tombol Konsolidasi). **Tidak ada konsep "dept anggaran" sebagai gatekeeper, tidak ada rapat berjenjang antar unit usaha, tidak ada status/alpha phase per unit.** |
| 4. Finalisasi & Pengesahan: presentasi ke Direksi & Dewan Komisaris, alignment PT BMI, pengesahan & distribusi | ⚠️ Sebagian | Approval RKAP (Komisaris→Direksi) ada sebagai status dokumen. **Tidak ada "presentasi"/"alignment PT BMI"/pendistribusian.** |

---

## 3. Rincian Temuan per Gap (dengan bukti file)

### G1. Stage Gate Review / Kelayakan / CBA untuk CAPEX — TIDAK ADA ❌
- Grep `stage.?gate|kelayakan|cost.?benefit|feasibility|gate_review` di seluruh `app/` → **0 hasil**.
- Migration `2026_09_11_000002_create_investment_plans_table.php` tidak memiliki kolom attachment/proposal/file, `InvestmentPlan::$fillable` (`app/Models/Erkap/InvestmentPlan.php`) tidak punya field CBA/kelayakan.
- Tidak ada approval level berlabel gate review untuk investasi di `ApprovalService.php:21-48`.
- **Akibat:** pedoman mewajibkan usulan investasi non-rutin lulus kajian kelayakan & persetujuan berjenjang sebelum masuk anggaran — saat ini invest bebas masuk `erkap_investment_plans`.

### G2. Evaluasi Dept Risk Management (Form 1), Dept Manajemen Aset & Gate Review PT BMI (CAPEX) — TIDAK ADA ❌
- Matriks approval (`app/Services/ApprovalService.php:21-48`) hanya: work_program/routine_cost/work_schedule = PPK → Controller; investment_plan = PPK → Direksi Keuangan; rkap = Komisaris → Direksi; risk_register = Risk Manager.
- `type 'risk_register'` didefinisikan (`RiskIdentification`) **tetapi tidak ada route submit** untuk risk identification (`routes/routers/erkap.php` tidak memuat `submit` untuk `RiskIdentificationController`), sehingga praktis tidak bisa digunakan.
- Tidak ada role/step "Manajemen Aset", tidak ada step "PT BMI / Gate Review".

### G3. Fase Lifecycle RKAP tidak ada ❌
- `RKAPController.php` hanya create/edit/delete record `year` + submit approval. Tidak ada status yang mewakili fase "Inisiasi", "Penyusunan", "Konsolidasi", "Finalisasi/Pengesahan" (status hanya `draft/submitted/approved/rejected/revised`).
- Tidak ada modul kick-off/sosialisasi; tidak ada "alignment PT BMI" atau distribusi dokumen.

### G4. Zero Based Budgeting (ZBB) TIDAK ADA ❌
- Tidak ada pengambilan anggaran tahun lalu sebagai pembanding "nol", tidak ada logika persentase kenaikan, tidak ada review prior-year. Satu-satunya fitur terkait tahun lalu: `previous_year_remaining` pada `BudgetCapexController::summary()`.

### G5. Ketidak-konsistenan Nilai Rating / Prioritas Sasaran ⚠️
Empat sumber yang saling bertentangan:
- Pedoman: `AAA, AA, A, BBB, BB`.
- Seeder `ErkapRatingCriteriaSeeder.php:17-23`: `AAA, AA, A, B, BB` (tanpa BBB; memakai "B").
- Model `WorkProgram::$validRatings = ['A+','A']` (`WorkProgram.php:83`) — `A+` tidak ada di seeder, sehingga program di bawah rating A terblokir, dan program dengan rating A+ (factory default) bisa lolos padahal tidak terdaftar.
- `WorkProgramController::ALLOWED_RATINGS = ['AAA','AA','A']` (`app/Http/Controllers/Erkap/WorkProgramController.php:20`).
- Template Form 1 (`Form1TemplateExport.php`): `A+, A, BB, B, C, A-Adjusted, BBB, CCC, D` — importer `Form1ImportExportService.cpp:168-173` akan **gagal/gagal-menolak** nilai yang tidak ada di DB (`RatingCriteria::where('rating', $ratingCode)->first()` → fail bila tidak dikenal), misal memasukkan "BBB" dari template → error.

Selain itu tidak ada **field "Prioritas"** terpisah (mis. kolom `priority`) pada `erkap_department_targets`; prioritas disamakan dengan rating.

### G6. COA 16 Digit Tidak Terpenuhi ❌
- `chart_of_accounts.code` = `varchar(10)`.
- Tidak ada validasi `size:16|digits` di `app/Http/Requests` manapun.
- Tidak ada opsi memilih COA langsung pada form OPEX/CAPEX (COA di-sync label dari elemen biaya, `ChartOfAccountController::sync()`).

### G7. Evaluasi & Penanganan Risiko
- `erkap_department_risk_strategies.strategy` terkunci CHECK `('avoid','reduce','transfer','accept')` — bukan _"Avoidance/Reduction/Sharing/Acceptance"_.
- `erkap_risk_treatments` tabel, model, seeder & test ada; **tidak ada controller/rute/view** (grep `RiskTreatmentController|risk-treatments` → hanya test & model & migration).

### G8. Form 2 Belum Persentase Kumulatif (%)
- `erkap_work_schedules` menyimpan `year_plan` + `jan_plan..dec_plan` sebagai **angka**; index view `work-schedule` bahkan tidak menampilkan kolom bulanan (hanya list). Belum ada kolom/komputasi **% kumulatif Jan–Des** seperti di Form 2 pedoman.

### G9. Form 4: Urutan Prioritas & Lampiran
- Tidak ada kolom `priority`/`priority_order`.
- Kolom `cost_center_id` **ada di DB** (`2026_09_22_073418`) namun **tidak ada di `InvestmentPlan::$fillable`** → insert lewat mass-assignment tidak pernah mengisi cost center.
- Tidak ada bidang upload proposal/kelayakan di migration maupun form `investment-plan/create`.

### G10. Biaya Tersentralisasi Parsial
- Hanya `cost_centers.is_swakelola` + helper `CostCenter::isSwakelola()` (segment kode '510'). Tidak ada pemetaan "biaya dikoordinir oleh departemen X" / aturan cost-owner untuk gaji (HR) & IT.

---

## 4. Temuan Kualitas Data / Bug Potensial (selain gap fungsional)

1. **`WorkProgram::booted()`** memakai `['A+','A']` (valid) sedangkan seeder tidak punya A+ → inkonsistensi logika bisnis (lihat G5). Ada test `WorkProgramBusinessRulesTest.php` yang menguji `validRatings` `['AAA','AA','A']` pada `canSubmitForApproval`, tapi method `canSubmitForApproval()` (`WorkProgram.php:99`) **tidak dipanggil** di `WorkProgramController::submit()` — program kerja dapat di-submit **tanpa anggaran** (Form 3/4) padahal pedoman Form 2 berdiri bersama Form 3/4.
2. **Konsolidasi bukan single source of truth** — dashboard & export budget (Excel/PDF) menghitung ulang langsung dari `routine_costs`/`investment_plans` (`DashboardController::budgetData`), bukan membaca `erkap_budget_opex`/`erkap_budget_capex`. Rawan deviasi antar penyajian.
3. **Duplikasi logika level risiko** — level di-resolve dari tabel `erkap_risk_score_levels`, tapi `DashboardController::scoreLevel()` (`app/Http/Controllers/Erkap/DashboardController.php:441-447`) memakai `match` hardcoded; keduanya bisa tidak sinkron.
4. **Penamaan bulan Desember** `des_cost` pada `erkap_routine_costs` (bukan `dec_*` seperti tabel lain) — rawan saat mapping kolom generik.
5. **`erkap_work_programs.code` nullable tanpa unique** — uniqueness hanya di app logic.
6. **`erkap_approvals.role`** dipilih per-role via `getApproverByRole` (prefer divisi sama) — **tidak ada review panel per departemen fungsional** sehingga step evaluasi tidak mungkin dibuat terminal.
7. **`risk_business_processes`** nama tabel tanpa prefix `erkap_` meski model di namespace `Erkap` — inconsistency penamaan.
8. Rating di index `department-target` ditampilkan sebagai kolom "Rating", bukan "Prioritas" sesuai istilah pedoman.

---

## 5. Prioritas Rekomendasi

### P0 — Wajib (memenuhi inti pedoman)
- [ ] **Implementasi Stage Gate Review untuk CAPEX** (modul kelayakan/CBA + attachment proposal + status gate review + approval berjenjang di `ApprovalService`; matriks investasi ≥ PPK → Manajemen Aset → Direksi Keuangan → Gate Review/PT BMI).
- [ ] **Evaluasi Form 1 oleh Dept Risk Management**: wire `risk_register` submit route ke `RiskIdentificationController` + step approval `erkap-risk-manager` + batasan edit pasca-evaluasi.
- [ ] **Unifikasi nilai rating sasaran** (pedoman `AAA/AA/A/BBB/BB`) di seeder + model `WorkProgram.booted` + `WorkProgramController::ALLOWED_RATINGS` + template/import Form 1 (satu sumber tunggal, idealnya konstanta enum PHP / config).
- [ ] **Dukungan COA 16 digit** — perluasan `chart_of_accounts.code` (min. varchar(16)), validasi digit pada request, dan pilihan COA/Commitment Item pada form OPEX (& CAPEX).

### P1 — Penting
- [ ] **Rencana perlakuan risiko (Evaluasi & Penanganan)** — aktifkan CRUD `erkap_risk_treatments` (controller/rute/view/menu) dan selaraskan kata kunci (Avoidance/Reduction/**Sharing**/Acceptance).
- [ ] **Form 2 kumulatif %** — kolom/tampilan % kumulatif Jan–Des pada Work Schedule, berbasis realisasi & rencana.
- [ ] **Urutan prioritas & lampiran kelayakan investasi** (`priority` column + `file_path` upload CBA) dan tambahkan `cost_center_id` ke `InvestmentPlan::$fillable`.
- [ ] **Enforce `canSubmitForApproval()`** saat submit Program Kerja (wajib ada anggaran).
- [ ] **Model lifecycle fase RKAP** (inisiasi/kick-off → penyusunan → konsolidasi → finalisasi/pengesahan) sebagai status/fase on `erkap_rkap` + menu "Pedoman/Kick-off".

### P2 — Perbaikan kualitas
- [ ] Jadikan `erkap_budget_opex`/`erkap_budget_capex` satu-satunya sumber konsolidasi di dashboard/export.
- [ ] Hapus duplikasi `scoreLevel` hardcoded → gunakan tabel `risk_score_levels`.
- [ ] Perbaiki `des_cost` → `dec_cost` (atau buat `monthColumns()` helper konsisten).
- [ ] Unik-kan `erkap_work_programs.code` di DB.
- [ ] Seragamkan penamaan `risk_business_processes` (prefix `erkap_`).
- [ ] Strukturisasi field SMART pada sasaran (specific/measurable/achievable/relevant/time_frame) agar measurable.

---

## 6. Perkiraan Cakupan Implementasi

Perkiraan komposisi terhadap substansi pedoman (kualitatif):

| Bagian Pedoman | Cakupan Sudah | Cakupan Kurang |
|---|---|---|
| Bagian 1 (Prinsip) | RBB terstruktur | ZBB tidak ada |
| Bagian 2 (Sasaran & Risiko) | ~70% | SMART eksplisit, rating konsisten, penanganan risiko UI, prioritas |
| Bagian 3 (Teknis Anggaran) | ~75% | COA 16 digit, tersentralisasi penuh, stage gate |
| Bagian 4 (Formulir) | ~70% | Form 2 kumulatif %, Form 4 `priority`+CBA, COA di form |
| Bagian 5 (Alur/Proses) | ~45% | Inisiasi/kick-off, evaluasi fungsional, gate review BMI, konsolidasi antar unit, finalisasi & distribusi |