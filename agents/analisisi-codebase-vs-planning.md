# ANALISIS CODEBASE E-RKAP vs RANCANGAN "ARKITEKTUR & ALUR BISNIS RKAP"

> Dokumen ini adalah hasil *deep analysis* kesenjangan (*gap analysis*) antara kondisi aktual modul **ERKAP** pada codebase `app-moki` (Laravel 8, Blade/jQuery) dengan rancangan pada `agents/ARKITEKTUR DAN ALUR BISNIS RKAP.md`.
>
> Tanggal analisis: 11 September 2026

---

## 1. RINGKASAN EKSEKUTIF

Modul ERKAP di codebase saat ini **baru mengimplementasikan tahap awal** dari rancangan: sebagian besar **Master Data** dan **rantai "Sasaran → Risiko → Program Kerja → Biaya Rutin"** (jalur OPEX). Bagian-bagian besar berikut **belum ada sama sekali** di dalam codebase:

| Area Rancangan | Status Implementasi |
|---|---|
| Master Data (CoA/Elemen Biaya, Risk Taxonomy, Rating, Risk Matrix, Investment master) | ✅ Sebagian besar sudah (CRUD + Seeder) |
| Sasaran Perusahaan & Divisi (FORM 1 bagian atas) | ✅ Sudah (CRUD + rating manual) |
| Identifikasi Risiko, Alasan, Dampak (FORM 1) | ✅ Sudah (CRUD) |
| Analisis Risiko, Peringkat, Strategi (FORM 1) | ⚠️ Sebagian (analisis manual, tidak auto-calc; peringkat manual; strategi free-text) |
| Program Kerja + Jadwal 12 bulan (FORM 2) | ⚠️ Sebagian (ada WorkProgram + kolom bulanan, tanpa validasi/business rule) |
| Biaya Rutin (FORM 3 / OPEX) | ⚠️ Sebagian (ada RoutineCost, cost center tanpa tabel, tanpa sub-total agregasi) |
| Budget Investasi (FORM 4 & FORM 5 / CAPEX) | ❌ **Belum ada** (hanya master data Investation yang tidak terpakai) |
| Konsolidasi RKAP SK & Perusahaan, P&L (Phase 3-4) | ❌ **Belum ada** |
| Approval Workflow semua dokumen (Program Kerja, Anggaran, RKAP) | ❌ **Belum ada** |
| Monitoring: Realisasi, BvA, Risk Assessment Bulanan (FORM 6), KPI | ❌ **Belum ada** |
| Dashboard & Analytics (6 jenis dashboard) | ❌ Hanya halaman statis "Halo, {nama}" tanpa widget data |
| Export/Import Excel & PDF sesuai format Form 1-6 | ❌ **Belum ada** |
| Audit trail, versioning RKAP, notifikasi | ❌ **Belum ada** |

Estimasi kemajuan terhadap keseluruhan rancangan (secara horisontal implementasi fitur): **± 25–30%**. Jalur Input RKAP (Form 1 → Form 2 → Form 3) sudah berjalan secara linier dan terhubung lewat foreign key, namun belum ada penegakan business rule, belum ada jalur CAPEX, belum ada konsolidasi, dan belum ada approval/monitoring/dashboard/reporting.

---

## 2. GAMBARAN UMUM CODEBASE ERKAP SAAT INI

### 2.1 Karakteristik Teknis (berbeda dari rekomendasi rancangan)

| Aspek | Rancangan (dokumen planning) | Aktual di codebase |
|---|---|---|
| Frontend | React/Next.js + TypeScript + Ant Design + React Query | **Blade (server-rendered)** + jQuery + Bootstrap (tema Skote). Inline JS per halaman. |
| Backend | Laravel 11 (PHP 8.2+), Sanctum, Laravel Workflow, Laravel Excel | **Laravel 8 (PHP 7.3/8.0)**, session-auth + Spatie Permission, Maatwebsite Excel (tersedia tapi belum dipakai ERKAP), DomPDF (belum dipakai ERKAP) |
| DB | MySQL 8/PostgreSQL 14 | MySQL (migration-style, semua prefix `erkap_`) |
| Approval engine | Laravel Workflow | Tidak ada engine approval khusus ERKAP (hanya FormIT punya approval sederhana) |
| Notifikasi | Email/in-app | WhatsApp (Fonnte) dipakai modul lain; belum ada untuk ERKAP |

Kesimpulan: perbedaan stack tidak dianggap *gap kritis* karena rancangan menyebut "sesuai stack yang ada di lingkungan". Yang menjadi gap sebenarnya adalah **fitur bisnis** yang belum ada.

### 2.2 Struktur Modul ERKAP (implementasi aktual)

- **Rute**: `routes/routers/erkap.php` (prefix `/erkap`, 24 grup resource + dashboard + 1 endpoint AJAX `get-score-level`) → di-`require` dari `routes/web.php:63`.
- **Controller**: 25 file di `app/Http/Controllers/Erkap/` (CRUD standar `index/create/store/edit/update/destroy`).
- **Model**: 24 model di `app/Models/Erkap/`.
- **Form Request**: 44 class (Store/Update).
- **View**: 76 file Blade di `resources/views/erkap/` (25 folder) + layout `layouts/Erkap.blade.php` + sidebar `layouts/partials/erkap/app-sidebar.blade.php`.
- **Service**: `app/Services/ErkapAccess.php` (pembatasan akses per-divisi untuk role `erkap-cost-owner`).
- **Migrasi**: 25 migrasi (2026-09-07 s.d. 2026-09-11).
- **Seeder**: 15 seeder terkait ERKAP terdaftar di `DatabaseSeeder` (baris 50–64).

### 2.3 Rantai data yang sudah terbangun

```
erkap_rkap (Periode/Tahun)
  └─ erkap_company_targets (Sasaran Perusahaan)
       └─ erkap_department_targets (Sasaran Divisi: division_id, rating_criteria_id)
            └─ erkap_risk_identifications (Risiko: risk_type_id, risk_taxonomy_id)
                 ├─ erkap_risk_identification_reasons
                 ├─ erkap_risk_identification_impacts
                 ├─ erkap_risk_analysis (probability_id, impact_id, score_value_id)
                 ├─ erkap_risk_rankings (ranking)
                 ├─ erkap_department_risk_strategies (strategy free-text)
                 └─ erkap_work_programs (Program Kerja + jan_plan..dec_plan)
                      └─ erkap_routine_costs (Biaya Rutin + jan_cost..des_cost, cost_element_id)
```

Rangkaian ini sudah memenuhi **urutan input** pada rancangan bagian 1.3: *Program Kerja → Biaya → Konsolidasi → P&L → Approval*. Namun urutan baru berjalan sampai **Biaya Rutin (OPEX)**; konsolidasi, P&L, dan approval belum ada.

---

## 3. MATRIKS PEMETAAN IMPLEMENTASI vs RENCANA

### 3.1 Business Process Flow (Bagian 1.2)

| Step | Proses | Implementasi | Status |
|---|---|---|---|
| 1.1 | Penetapan Visi & Misi | Tidak ada entitas visi/misi | ❌ |
| 1.2 | Sasaran Perusahaan (Corporate OKR) | `company_targets` = teks target | ⚠️ Tanpa OKR/indikator/tampilan berkontribusi |
| 1.3 | Sasaran Satuan Kerja | `department_targets` (division_id, rating) | ✅ |
| 1.4 | Penilaian Rating (AAA/AA/A/BB/B) auto-calc "berdasarkan kontribusi" | Rating dipilih manual via dropdown `rating_criteria_id` | ⚠️ **Tidak auto-calc** |
| 1.5 | Validasi Sasaran (approval Direksi) | Tidak ada workflow | ❌ |
| 2.1 | Identifikasi Risiko (positif/negatif) | `risk_identifications` — **tidak ada kolom positif/negatif** | ⚠️ Separuh |
| 2.2 | Klasifikasi Risiko (dropdown A1-A7, B1-B15, C1-C6) | `risk_types` + `risk_taxonomies` (seeder berisi kode A1-A7, B1-B5?) | ⚠️ Ada, tapi kode taksonomi A/B/C perlu dicek kelengkapan |
| 2.3 | Analisis Risiko (Prob 1-5, Dampak 1-5) | `risk_analysis` memilih probability & impact exisiting | ✅ |
| 2.4 | Inherent Risk auto-calc Prob × Dampak (1-25) + level | `risk_analysis` menyimpan `risk_score_value_id` yang dipilih user via AJAX `get-score-level`; **tidak dihitung otomatis di backend**; skor disimpan dari master, bukan dihitung dari Prob×Dampak | ⚠️ Tidak auto-calc |
| 2.5 | Strategi Perlakuan (Avoid/Reduce/Transfer/Accept) | `department_risk_strategies.strategy` = free text | ⚠️ Tanpa enum strategi baku |
| 2.6 | Program Kerja (risk-based) | `work_programs` terhubung ke risk identification | ✅ |
| 2.7 | Penjadwalan 1 tahun + bulanan | Kolom `jan_plan..dec_plan` ada; **tanpa validasi** SUM(bulan) = target tahunan | ⚠️ |
| 2.8 | Approval Program Kerja | Tidak ada | ❌ |
| 3.1 | Biaya Rutin (FORM 3) | `routine_costs` (need, qty, unit_price, cost_element_id, 12 bulan) | ✅ basis |
| 3.2 | Distribusi biaya per bulan | Kolom `jan_cost..des_cost` | ✅ |
| 3.3 | Biaya Investasi (FORM 4/5) | **Tidak ada entitas** | ❌ |
| 3.4 | Approval Biaya (PPK + Direksi Keuangan) | Tidak ada | ❌ |
| 3.5 | Konsolidasi Anggaran SK | Tidak ada (tidak ada laporan agregasi per divisi/cost center) | ❌ |
| 3.6 | Konsolidasi RKAP Perusahaan | Tidak ada | ❌ |
| 4.1 | Forecast Pendapatan (6000-6940) | Elemen biaya pendapatan sudah di-seed; **tidak ada entitas revenue plan per bulan** | ❌ |
| 4.2 | Rencana Beban (7000-9109) | Elemen beban di-seed; **tidak ada entitas expense plan bulanan** | ❌ |
| 4.3 | Kalkulasi Laba Rugi | Tidak ada | ❌ |
| 4.4 | Margin & KPI | Tidak ada | ❌ |
| 4.5 | Simulasi Skenario (Best/Base/Worst) | Tidak ada | ❌ |
| 4.6 | Approval RKAP Final (Dewan Komisaris/Direksi) | Tidak ada | ❌ |
| 5.1-5.2 | Realisasi Pendapatan/Beban | Tidak ada | ❌ |
| 5.3 | Monitoring Program Kerja (% penyelesaian) | Tidak ada | ❌ |
| 5.4 | Monitoring Anggaran (BvA) | Tidak ada | ❌ |
| 5.5 | Risk Assessment Bulanan (FORM 6: Inherent/Current/Residual) | **Tidak ada** | ❌ |
| 5.6 | Evaluasi Kinerja (KPI Scorecard) | Tidak ada | ❌ |
| 5.7 | Laporan bulanan/triwulan/tahunan | Tidak ada (tidak ada route export/PDF/excel ERKAP) | ❌ |
| 6.1-6.6 | Dashboard & Analytics | Hanya halaman welcome statis | ❌ |

### 3.2 Approval Workflow Matrix (Bagian 1.4)

| Dokumen | Status |
|---|---|
| Program Kerja | ❌ Tidak ada approval |
| Anggaran Rutin | ❌ Tidak ada approval |
| Anggaran Investasi | ❌ (CAPEX sendiri belum ada) |
| RKAP Perusahaan | ❌ Tidak ada approval |
| Risk Register | ❌ Tidak ada approval |

Tidak ada tabel `approvals`, tidak ada status dokumen (`draft/submitted/approved/rejected`), tidak ada route approval di `erkap.php`.

### 3.3 Module Breakdown (Bagian 2)

| Modul | Fitur Rancangan | Status | Catatan |
|---|---|---|---|
| 2.1 Master Data | Organization (Satker, Cost Center, pemilik, user) | ⚠️ | Divisi ada; **Cost Center TIDAK ada tabel** (`cost_center_id` pada routine_costs adalah `unsignedBigInteger` nullable tanpa FK) |
| | Chart of Accounts (6000-6940, 7000-9109) | ⚠️ | Tabel `chart_of_accounts` **hanya kolom id + timestamps (kosong)**; data kode 6000-9109 justru di-seed ke `erkap_cost_elements` (CostElementsSeeder) tanpa relasi ke `chart_of_accounts`. Model `ChartOfAccount` milik EQTax, tidak terhubung ERKAP |
| | Risk Taxonomy, A-C types | ✅ | `risk_taxonomies`, `risk_types` (A1-C6) |
| | Rating Criteria (AAA-BB) | ✅ | `rating_criterias` |
| | Risk Matrix (Prob 1-5, Dampak 1-5, Nilai, Peringkat) | ⚠️ | Ada `risk_probabilities`, `risk_impacts`, `risk_score_levels`. Level memakai label `Low/Low To Moderate/Moderate/Moderate To High/High` sedangkan rancangan memakai `VL/L/M/H/VH`. Matriks skor seeder TIDAK mengikuti rumus Prob×Dampak |
| | Investment Criteria (A-E, SDU/PSN/OTH, Jenis 1-6) | ✅ | `investation_criterias`, `investattion_categories`, `investation_types` — namun **tidak dipakai oleh entitas planning manapun** |
| 2.2 Strategic Planning (FORM 1) | CRUD Sasaran Perusahaan + rating | ✅ | Rating sebenarnya ada di level Department Target |
| | Sasaran SK ber-referensi perusahaan | ✅ | `department_targets.erkap_company_target_id` |
| | Risiko positif/negatif, tipe, taksonomi, penyebab, dampak | ⚠️ | Alasan & dampak ada (tabel terpisah); **tanda positif/negatif TIDAK ada** |
| | Analisis risiko auto-calc | ⚠️ | Nilai & level dipilih dari master; **tidak dihitung otomatis** |
| | Peringkat auto (`VL/L/M/H/VH`) | ❌ | `risk_rankings.ranking` input manual; label level juga beda skema |
| | Strategi perlakuan | ⚠️ | Free text |
| | Program Kerja linked Form 2 | ✅ | `work_programs.erkap_risk_identification_id` |
| 2.3 Work Schedule (FORM 2) | Target bulanan Jan-Des | ✅ | Kolom `jan_plan..dec_plan` |
| | Auto-populate dari Form 1 | ❌ | Harus pilih manual dari dropdown risk identification; **tidak menampilkan Sasaran/SK/Rating/Program** dalam satu baris form |
| | Target tahunan (col 9) + breakdown (col 10-21) | ⚠️ | Kolom ada; tanpa validasi kesesuaian |
| | Referensi biaya ke Form 3/4 | ⚠️ | WorkProgram ↔ RoutineCost ada; ke CAPEX tidak ada |
| | Validasi SUM(bulanan) = target tahunan | ❌ | Tidak ada (store bekerja langsung tanpa aturan) |
| 2.4 OPEX (FORM 3) | Barang/jasa referensi Program Kerja | ✅ | `routine_costs.erkap_work_program_id` |
| | Cost Center (Kode + Pemilik) | ⚠️ | `cost_center_id` tanpa tabel/FK; `cost_center_owner` free text |
| | Qty, satuan, harga satuan | ⚠️ | Ada `qty`, `unit_price`; **satuan (unit) tidak ada**, dan tidak ada validasi total = qty × harga atau total = Σ bulanan di sisi validasi |
| | Elemen biaya dropdown CoA | ✅ | `erkap_cost_element_id` |
| | Breakdown bulanan + TOTAL | ⚠️ | Kolom ada; `total` dihitung oleh controller (`calcTotal`), bukan di DB/rule |
| | Auto-subtotal per elemen, per program, per SK | ❌ | Tidak ada agregasi tampilan apa pun |
| 2.5 CAPEX (FORM 4 & 5) | Kriteria, kategori, jenis investasi | ⚠️ | Master data saja; **tidak ada form input investasi** |
| | Rencana pembayaran bulanan | ❌ | Tidak ada |
| | Total Investasi = Qty × Harga | ❌ | Tidak ada |
| 2.6 Risk Assessment Bulanan (FORM 6) | Inherent/Current/Residual risk | ❌ | Tidak ada |
| | Mitigasi plan + realisasi | ❌ | Tidak ada |
| | Status On Progress/Done/Overdue | ❌ | Tidak ada |
| 2.7 Financial Projection & P&L | Pendapatan/Beban per CoA | ❌ | Tidak ada |
| | Laba Rugi, Margin, Cash Flow, Scenario | ❌ | Tidak ada |
| 2.8 Approval & Workflow | Multi-level approval, notifikasi, audit trail, versioning | ❌ | Tidak ada sama sekali di ERKAP |
| 2.9 Reporting & Dashboard | Real-time dashboard, export Excel/PDF, drill-down, mobile | ❌ | Hanya halaman welcome; tidak ada export |

### 3.4 Data Model (Bagian 3.2)

| Tabel Rancangan | Padanan Aktual | Status |
|---|---|---|
| `companies` | `companies` | ✅ |
| `departments` | `divisions` (dipakai ERKAP) | ✅ (beda nama) |
| `cost_centers` | — | ❌ **Tidak ada tabel** |
| `chart_of_accounts` | `chart_of_accounts` (kosong) + `erkap_cost_elements` | ⚠️ Kolom bisnis ada di tabel lain, `chart_of_accounts` kosong |
| `risk_taxonomies` | `erkap_risk_taxonomies` | ✅ |
| `rating_criteria` | `erkap_rating_criterias` | ✅ |
| `risk_matrix` | `erkap_risk_score_levels` | ⚠️ konsep mirip, skema level/skor berbeda |
| `investment_criteria` | `erkap_investation_criterias`, `_types`, `_categories` | ✅ (master) |
| `company_goals` | `erkap_company_targets` | ✅ |
| `department_goals` | `erkap_department_targets` | ✅ |
| `risk_registers` | `erkap_risk_identifications` | ✅ |
| `risk_treatments` | `erkap_department_risk_strategies` | ✅ (versi sederhana) |
| `work_schedules` | `erkap_work_programs` | ⚠️ + child `routine_costs` lebih besar dari rancangan |
| `budget_details` | `erkap_routine_costs` | ⚠️ sebagian |
| `budget_opex` | — | ❌ (digantikan per-item `routine_costs`; tidak ada tabel "header" OPEX per SK) |
| `budget_capex` | — | ❌ **Tidak ada** |
| `revenue_plans` | — | ❌ Tidak ada |
| `expense_plans` | — | ❌ Tidak ada |
| `profit_loss_statements` | — | ❌ Tidak ada |
| `budget_realizations` | — | ❌ Tidak ada |
| `risk_assessments_monthly` | — | ❌ Tidak ada |
| `program_realizations` | — | ❌ Tidak ada |
| `performance_scorecards` | — | ❌ Tidak ada |

Catatan tambahan integritas data:

- Migrasi yang men-generate rata-rata hanya `timestamps()`; semua tabel ERKAP **tidak punya `created_by`/`updated_by`** sehingga audit trail (Bagian 8.2) tidak terpenuhi.
- Tidak ada kolom `is_positive`/`is_negative` pada `erkap_risk_identifications` (FORM 1 identifikasi risiko positif/negatif).
- Tidak ada kolom mitigasi/inherent/current/residual pada tabel risiko mana pun (FORM 6).
- `erkap_routine_costs.cost_center_id` tidak punya FK maupun tabel referensi.

### 3.5 Dashboard Design (Bagian 4)

Semua widget di **Executive Summary**, **Risk**, **Program**, **Budget**, dan **P&L Dashboard** tidak ada. Halaman `erkap.dashboard.index` hanya menampilkan sapaan dan tanggal (tidak ada query data sama sekali; lihat `DashboardController` yang hanya `return view(...)`).

### 3.6 Technical Architecture (Bagian 5)

- Render Blade vs React — perbedaan stack, bukan gap fungsional.
- Tidak ada **Workflow/Approval Engine**, notifikasi, audit trail sebagai shared services untuk ERKAP.
- Tidak ada **Export/Import** service untuk ERKAP (Maatwebsite Excel & DomPDF tersedia di composer tapi tidak dipakai modul ini).
- Tidak ada Redis/queue usage spesifik untuk proses ERKAP (kalkulasi/export batch).

### 3.7 Business Rules (Bagian 7)

| Rule | Status |
|---|---|
| 7.1 Hierarki input (Sasaran → SK → Program → Biaya → RKAP SK → RKAP Corp → P&L & Approval) | ⚠️ Terhubung cuma sampai Biaya Rutin; RKAP SK/Corp/P&L/Approval tidak ada |
| "Program Kerja hanya bisa dibuat dari sasaran ber-rating A ke atas" | ❌ Tidak ada guard di `WorkProgramController.store`/`create` (semua risk identification valid) |
| "Setiap Risiko wajib memiliki Strategi dan Program Kerja" | ❌ Tidak ada validasi keberadaan strategi/program saat menyimpan risiko atau sasaran |
| "Nilai Risiko = Probabilitas × Dampak (1-25)" | ⚠️ Tidak dihitung; skor disimpan dari `erkap_risk_score_levels` yang di-seed memakai skema skor tersendiri (mis. Prob=1 × Dampak=2 disimpan skor 5, bukan 2) |
| 7.2 "Program kerja berasal dari sasaran yang memiliki risiko; budget bottom-up" | ⚠️ Struktur data mendukung (budget di bawah program), tapi tidak ada penegakan "budget hanya boleh untuk risiko bersasaran" |
| 7.3 Mapping CoA wajib | ⚠️ Elemen biaya wajib dipilih (`erkap_cost_element_id`), namun `chart_of_accounts` kosong & tidak ada tipe Pendapatan/Beban pada elemen |
| 7.4 Monthly cycle (input, monitoring N-1, risk assessment N-1, evaluasi, reporting) | ❌ Hanya baru sisi input; siklus monitoring/evaluasi/reporting tidak ada |
| "SUM(bulanan) = target tahunan" (Work Program) | ❌ |
| "Wajib isi 12 bulan" | ❌ Semua kolom bulan nullable; tidak ada aturan |
| "Jika program kerja ada, wajib anggaran Form 3/4" | ❌ Tidak ada rule; program kerja bisa disimpan tanpa biaya |

### 3.8 Security & RBAC (Bagian 8)

Sudah ada:
- Permissions granular per entitas (`erkap.<entity>.{view,create,edit,delete}`) di route.
- Pembatasan akses per-divisi untuk role `erkap-cost-owner` via `ErkapAccess` (index/create/store/edit/delete pada entitas tingkat divisi).

Belum ada / belum sesuai:
- Role bisnis pada matriks rancangan: **PPK, Controller, Accounting, Manajemen Risiko, Direksi, Auditor** — untuk approval & dashboard belum didefinisikan/disecondensikan ke ERKAP (hanya role `erkap-cost-owner` plus admin generik).
- **Versioning RKAP** (draft → submitted → approved → rejected → revised): tidak ada kolom `status` di `erkap_rkap` maupun entitas lainnya.
- **Approval history** (siapa, kapan, komentar): tidak ada.
- **Check constraint** numerik (Prob 1-5, Dampak 1-5): memakai foreign key ke master, jadi terkendali — namun kolom numerik lain (`qty`, `unit_price`, skor) tidak ada batasan non-negatif.
- **Unique constraint** kode (Cost Center, Elemen Biaya): `erkap_cost_elements.code` tidak unique (harus dicek migrasi `2026_09_07_125823`; tidak ada `->unique()`).

### 3.9 Integration Points (Bagian 9)

- Export Excel/PDF / import Excel sesuai Format Form 1-6: ❌ tidak ada route/export/import class untuk ERKAP.
- Integrasi sistem akuntansi, HR, procurement, asset: ❌ tidak ada.
- Notifikasi WhatsApp/email: ❌ tidak ada untuk ERKAP (infrastruktur Fonnte ada di modul lain).

---

## 4. DEEP ANALYSIS PER GAP UTAMA

### 4.1 GAP — CAPEX / Anggaran Investasi (FORM 4 & FORM 5) BELUM ADA

Hanya 3 tabel master yang ada (`investation_types`, `investation_criterias`, `investattion_categories`) dan semuanya **tidak direferensikan** oleh entitas planning mana pun. Tidak ada:
- Tabel transaksi/biaya investasi (barang, qty, harga satuan, jadwal pembayaran Jan-Des, total).
- Controller/route/view untuk input biaya investasi.
- Tabel ringkasan "Anggaran Investasi" (FORM 5).

Akibatnya jalur CAPEX pada Flow 3.1-3.3, modul 2.5, dan dashboard budget (OPEX vs CAPEX) tidak dapat berfungsi. Ini gap fungsional terbesar kedua setelah ekosistem monitoring/approval.

**File terkait**: hanya `app/Models/Erkap/Investation{Type,Criteria}.php`, `InvestattionCategory.php` + controller sejenis; tidak ada model `BudgetCapex`/`InvestmentPlan`.

### 4.2 GAP — Chart of Accounts Tidak Terpakai / Kosong

- Migrasi `2026_09_07_125216_create_chart_of_accounts_table.php` hanya membuat `id` + `timestamps`.
- Data akun 6000-6940 / 7000-9109 di-seed ke `erkap_cost_elements` (CostElementsSeeder) dengan kategori (`erkap_cost_element_categories`) yang mencakup kelompok Pendapatan (1..13) dan Beban (14..28).
- Tidak ada pemisahan/modelling tipe `Pendapatan | Beban` pada elemen biaya meskipun seeder telah mengelompokkannya via kategori → ini penting untuk P&L (Pendapatan - Beban) dan Revenue/Expense Planning.
- Model `App\Models\ChartOfAccount` dimiliki EQTax dan tidak berelasi ke `erkap_cost_elements`.

Dampak: P&L (Phase 4) tidak bisa menghitung pendapatan vs beban secara otomatis; mapping CoA (business rule 7.3) hanya bersifat seleksi dropdown tanpa tipe.

### 4.3 GAP — Risk Management belum menganut alur lengkap

1. **Tidak ada tanda risiko positif/negatif** pada `erkap_risk_identifications` meskipun FORM 1 mensyaratkan "Identifikasi Risiko: Risiko Positif (+) / Negatif (-)".
2. **Analisis Risiko tidak auto-calc**:
   - `RiskAnalysisController.store/update` hanya menyimpan data yang lolos validasi; skor & level dipilih user (via AJAX `get-score-level` untuk keperluan tampilan), bukan hasil komputasi Prob × Dampak.
   - `erkap_risk_score_value_id` menunjuk master `erkap_risk_score_levels` yang langsung memberikan `score` dan `level`, sehingga sebenarnya nilai risiko justru *di-**baca dari master**, bukan *dihitung*.
   - Skema skor seeder tidak konsisten dengan business rule rancangan (Prob × Dampak). Contoh seeder: (P=1,I=2)→skor 5; (P=3,I=3)→13; (P=5,I=5)→25. Nilai 1-25 benar tapi bukan hasil perkalian — perlu klarifikasi & dibuat konsisten.
3. **Peringkat risiko input manual** (`risk_rankings.ranking`), tidak di-generate dari skor (urutkan otomatis).
4. **Strategi perlakuan free text**, tidak baku `Avoid/Reduce/Transfer/Accept`.
5. **Tidak ada Risk Assessment Bulanan (FORM 6)** untuk Inherent/Current/Residual risk + mitigasi + status progres — seluruh blok Phase 5.5 dan modul 2.6 kosong.

### 4.4 GAP — Budgeting OPEX belum memenuhi spec penuh

- `routine_costs` sudah punya kebutuhan, qty, harga, elemen biaya, 12 bulan, total.
- Yang kurang:
  - **Satuan (`units`)** sebagai kolom (di `work_programs` ada `units`, di `routine_costs` tidak).
  - **Tabel `cost_centers`** + FK (`cost_center_id` sekarang integer tanpa FK).
  - **Sub-total/agregasi per elemen biaya, per program kerja, per satuan kerja** (modul 2.4 "Auto-subtotal") — tidak ada query tampilan agregat.
  - **Validasi total**: tidak dicek `total = Σ bulanan` dan `total = qty × unit_price` di level validasi; `total` dihitung controller saja.
  - **Konsolidasi OPEX per divisi (RKAP SK)** & **agregasi seluruh divisi (RKAP Perusahaan)** (Flow 3.5-3.6, modul 2.8) tidak ada.

### 4.5 GAP — Work Program (FORM 2) tanpa validasi business rule

- `StoreWorkProgramRequest` memperbolehkan semua kolom bulan nullable; tidak ada aturan `year_plan = Σ(bulan)`.
- Tidak ada "auto-populate" informasi Sasaran/rating/peringkat pada baris program; user hanya memilih risk identification via dropdown.
- Tidak ada penegakan "program kerja hanya untuk sasaran rating ≥ A" (business rule 2.2 & 7.1).

### 4.6 GAP — Financial Projection & P&L (Phase 4) absen total

Semua entitas (revenue_plans, expense_plans, profit_loss_statements) dan modul 2.7 (Pendapatan/Beban/Laba Rugi/Margin/Cash Flow/Scenario) tidak ada. Dengan demikian output "RKAP Final + Projection Laba Rugi + Analisis KPI" (Phase 4) belum dapat dihasilkan. Elemen biaya pendapatan (6000-6940) dan beban (7000-9109) sudah di-seed sehingga fondasi datanya siap, tinggal entitas perencanaan & kalkulasi yang belum dibuat.

### 4.7 GAP — Approval Workflow & Status absent

- Tidak ada kolom `status` di `erkap_rkap` dan entitas turunannya → tidak ada lifecycle draft/submitted/approved.
- Tidak ada route/resource approval, tidak ada tabel `approvals`, tidak ada notifikasi.
- Matriks approval 1.4 (Program Kerja, Anggaran Rutin, Anggaran Investasi, RKAP Perusahaan, Risk Register) sama sekali belum diwujudkan.
- Pola approval sederhana yang ada di FormIT (`EnsureUserIsApprover` middleware, `formit_approvals`) bisa dijadikan referensi implementasi.

### 4.8 GAP — Monitoring, Realisasi, Evaluasi (Phase 5) absent

- Tidak ada `budget_realizations`, `program_realizations`, `risk_assessments_monthly`, `performance_scorecards`.
- Tidak ada integrasi data akuntansi untuk realisasi pendapatan/beban.
- Tidak ada BvA, evaluasi KPI, maupun laporan bulanan/triwulan/tahunan.

### 4.9 GAP — Dashboard & Reporting absen

- Dashboard ERKAP hanya statis; seluruh widget di Bagian 4 (Executive, Risk Heat Map, Gantt, BvA, Margin, variance) tidak ada.
- Tidak ada route export Excel/PDF untuk ERKAP (bandingkan HelpDesk `reports.generate-pdf/excel` dan EQTax `equalization.export`).

### 4.10 GAP — Audit Trail & RBAC bisnis

- Tabel tidak punya `created_by`/`updated_by`; tidak ada model history untuk ERKAP.
- Role bisnis sesuai matriks 8.1 (PPK, Controller, Accounting, Auditor, Manajemen Risiko, Direksi) belum di-seed untuk ERKAP; seeder `RolePermissionSeeder` hanya mencantumkan `erkap-cost-owner` (baris 228) dan permissions per entitas.

---

## 5. HAL-HAL YANG SUDAH BENAR / SUDUT POSITIF

Agar analisis seimbang, berikut yang sudah berjalan baik dan selaras rencana:

1. **Urutan input sudah benar** (Program Kerja → Biaya) dengan relasi FK berjenjang (RKAP → Sasaran → Risiko → Program → Biaya).
2. **RBAC granular per entitas + divisi** sudah cukup matang (permission route + `ErkapAccess`).
3. **Master data risiko & CoA lengkap dengan seeder** (A1-C6, prob/impact, score level, rating, investasi, elemen biaya 6000-9109).
4. **ERD konsisten prefix `erkap_`** dan seluruh route terpusat di `routes/routers/erkap.php`.
5. **Form Request terstandar** (Store/Update untuk tiap entitas) memudahkan penambahan validasi & business rule ke depan.
6. **Dasar kalkulasi bulanan sudah ada** (`calcTotal` pada RoutineCostController; kolom Jan-Des pada WorkProgram dan RoutineCost) sehingga cukup diperkuat validasinya.

---

## 6. REKOMENDASI PRIORITAS IMPLEMENTASI

Prioritas diurutkan berdasarkan rantai nilai sesuai alur rencana (Program → Anggaran → Konsolidasi → P&L → Approval → Monitoring → Dashboard).

| # | Prioritas | Item | Effort | Nilai |
|---|---|---|---|---|
| 1 | **Tinggi** | Lengkapi `cost_centers` (tabel + FK + relasi user/divisi) dan kolom `units`/`satuan` pada `routine_costs` | Kecil | Menutup integritas data FORM 3 |
| 2 | **Tinggi** | Penegakan business rule Work Program (SUM bulanan = tahunan, wajib 12 bulan, rating ≥ A, wajib budget) | Kecil-Sedang | Menjamin konsistensi Form 2 |
| 3 | **Tinggi** | Implementasi **CAPEX (FORM 4 & 5)**: tabel investasi + jadwal pembayaran + controller/view, menyambung master `investation_*` | Sedang-Besar | Menutup di atas 30% fungsionalitas |
| 4 | **Tinggi** | Perbaikan risk: (a) kolom status positif/negatif, (b) auto-calc skor/level konsisten Prob×Dampak, (c) ranking otomatis, (d) enum strategi Avoid/Reduce/Transfer/Accept | Sedang | Menjamin FORM 1 akurat |
| 5 | **Sedang** | Konsolidasi RKAP SK & Perusahaan (agregat OPEX + CAPEX per divisi/pendapatan) | Sedang | Output Phase 3 |
| 6 | **Sedang** | Revenue/Expense Plan + P&L (dengan tipe Pendapatan/Beban di CoA) + Margin | Sedang-Besar | Output Phase 4 |
| 7 | **Sedang** | Approval workflow + status/versioning RKAP (mengikuti pola FormIT atau Laravel Workflow) + notifikasi | Besar | Matriks approval 1.4, 2.8, 8.2 |
| 8 | **Sedang** | Risk Assessment Bulanan (FORM 6: inherent/current/residual + mitigasi + status) | Sedang-Besar | Phase 5.5 & Risk Dashboard |
| 9 | **Sedang** | Monitoring & realisasi (BvA, program realization, integrasi akunting) | Besar | Phase 5 |
| 10 | **Rendah-Sedang** | Export/Import Excel & PDF sesuai format Form 1-6 | Sedang | Bagian 9.2 |
| 11 | **Rendah-Sedang** | Dashboard berisi data (Executive, Risk heatmap, Program, Budget, P&L) | Sedang-Besar | Bagian 4 |
| 12 | **Rendah** | Audit trail (`created_by/updated_by`) + role bisnis (PPK/Controller/Auditor/dll) di seeder | Kecil-Sedang | Bagian 8 |
| 13 | **Rendah** | Aligned `chart_of_accounts` (kolom code/name/type) & relasi ke `erkap_cost_elements` | Kecil | Foundation P&L |
| 14 | **Rendah** | Unique constraint `erkap_cost_elements.code`, check constraint non-negatif numerik | Kecil | Integritas |

### Urutan milestone yang disarankan (selaras roadmap rencana)

- **M1 (Foundation sudah ada)**: Master data + RBAC + jalur OPEX (status saat ini).
- **M2 (Lengkapi Planning)**: Poin 1-4 — biaya rutin & program kerja yang "kuat" + CAPEX.
- **M3 (Financial & Approval)**: Poin 5-7 — konsolidasi, P&L, approval versioning.
- **M4 (Monitoring & Risk bulanan)**: Poin 8-9.
- **M5 (Dashboard & Reporting)**: Poin 10-11 + audit trail & role (12-14).

---

## 7. KESIMPULAN

Codebase ERKAP sudah membangun **fondasi master data dan jalur input perencanaan dasar** (Sasaran → Risiko → Program Kerja → Biaya Rutin) dengan arsitektur Laravel+Blade yang rapi, RBAC granular, dan scoping divisi. Namun terhadap rancangan `ARKITEKTUR DAN ALUR BISNIS RKAP.md` masih terdapat **kesenjangan fungsional yang besar**:

1. **CAPEX/Investasi (FORM 4 & 5)** — belum ada implementasi sama sekali.
2. **Financial Projection & P&L (Phase 4)** — belum ada.
3. **Approval Workflow, status/versioning dokumen, notifikasi** — belum ada.
4. **Monitoring/Realisasi/BvA/Risk Assessment Bulanan (Phase 5)** — belum ada.
5. **Dashboard analytics (Phase 6) & export/import Excel/PDF** — belum ada.
6. **Business rules (Section 7)** — belum ditegakkan (rating, sum bulanan, wajib anggaran, auto-calc risiko).
7. **Integritas audit & data** (cost center, chart of accounts, created_by/updated_by, unique constraint) — parsial.

Dengan memperbaiki 7 area di atas sesuai urutan prioritas pada Bagian 6, modul ERKAP dapat mencapai kesesuaian penuh dengan rancangan.