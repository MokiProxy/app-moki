# ARSITEKTUR & ALUR BISNIS APLIKASI RKAP (Rencana Kerja dan Anggaran Perusahaan)

## Analisis File Excel
File `Format RKAP Improvement.xlsx` mengungkapkan struktur RKAP yang terdiri dari 5 form utama saling terhubung:

1. **FORM 1 - Sasaran & Asesmen Risiko**: Sasaran perusahaan, sasaran satuan kerja, rating (AAA/AA/A/BB/B), identifikasi risiko (positif/negatif), tipe risiko, taksonomi, probabilitas, dampak, nilai risiko, peringkat, strategi, dan program kerja.
2. **FORM 2 - Jadwal Rencana Pelaksanaan Program Kerja**: Target pelaksanaan program kerja bulanan (Jan-Des) yang terhubung dengan Form 1 dan Form 3.
3. **FORM 3 - Penyusunan Biaya Rutin**: Detail barang/jasa, cost center, pemilik, qty, satuan, harga satuan, elemen biaya, dan distribusi biaya bulanan.
4. **FORM 4 - Penyusunan Biaya Investasi**: Kriteria investasi (A-E), kategori (SDU/PSN/OTH), jenis investasi, jadwal pembayaran bulanan.
5. **FORM 5 - Anggaran Investasi**: Ringkasan nilai investasi dan distribusi pembayaran.
6. **Risk Assessment Bulanan**: Inherent risk, current risk, residual risk, mitigasi, dan realisasi monitoring.

**Data pendukung**: Chart of Accounts (Pendapatan 6000-6940, Beban 7000-9109), Kriteria Risiko, Heat Map, dan Risk Taksonom.

---

## 1. BUSINESS PROCESS FLOW

### 1.1 Macro Process Overview
```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        LIFECYCLE APLIKASI RKAP                               │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  [1] STRATEGIC PLANNING          [2] RISK MANAGEMENT                         │
│       ↓                                ↓                                     │
│  [3] PROGRAM PLANNING              [4] BUDGETING                              │
│       ↓                                ↓                                     │
│  [5] INVESTMENT PLANNING           [6] APPROVAL WORKFLOW                     │
│       ↓                                ↓                                     │
│  [7] MONITORING & REPORTING        [8] FINANCIAL CONSOLIDATION                │
│       ↓                                ↓                                     │
│  [9] PROFIT & LOSS ANALYSIS        [10] DASHBOARD & ANALYTICS                │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 1.2 Detail Business Process Flow (BPMN-style)

#### PHASE 1: STRATEGIC ALIGNMENT & COMPANY GOALS SETTING

**Aktor**: Direksi, Divisi Strategi, Divisi Keuangan, Satuan Kerja (SK)

| Step ID | Process Step | Input | Output | System Action |
|---------|-------------|-------|--------|---------------|
| 1.1 | Penetapan Visi & Misi Perusahaan | RJP (Rencana Jangka Panjang) | Sasaran Perusahaan | - |
| 1.2 | Penyusunan Sasaran Perusahaan (Corporate OKR) | RJP, Analisis SWOT | Daftar Sasaran Perusahaan | Module: Strategic Planning |
| 1.3 | Penyusunan Sasaran Satuan Kerja | Sasaran Perusahaan | Daftar Sasaran SK | Module: Organizational Management |
| 1.4 | Penilaian Rating Sasaran | Kriteria Rating (AAA/AA/A/BB/B) | Rating per Sasaran | Auto-calculate berdasarkan kontribusi |
| 1.5 | Validasi Sasaran | Draft Sasaran | Sasaran Disetujui | Workflow Approval: Direksi |

**Output Phase 1**: Master Data Sasaran Perusahaan & SK dengan Rating.

---

#### PHASE 2: RISK MANAGEMENT & RISK-BASED PROGRAMMING

**Aktor**: Risk Owner, Satuan Kerja, Manajemen Risiko, Divisi Keuangan

| Step ID | Process Step | Input | Output | System Action |
|---------|-------------|-------|--------|---------------|
| 2.1 | Identifikasi Risiko (Risk Identification) | Sasaran SK, Data Internal/External | Daftar Risiko (Positif/Negatif) | Module: Risk Register |
| 2.2 | Klasifikasi Risiko | Risk Taksonomi | Tipe Risiko + Taksonomi | Dropdown: A1-A7, B1-B15, C1-C6 |
| 2.3 | Analisis Risiko (Risk Analysis) | Identifikasi Risiko | Probabilitas (1-5) + Dampak (1-5) | Module: Risk Calculator |
| 2.4 | Penilaian Inherent Risk | Matriks Risiko | Nilai Risiko (1-25) + Risk Level (VL/L/M/H/VH) | Auto-calc: Prob x Dampak |
| 2.5 | Perencanaan Strategi Perlakuan Risiko | Risk Level | Strategi (Avoid/Reduce/Transfer/Accept) | Module: Risk Treatment |
| 2.6 | Penyusunan Program Kerja (Risk-based) | Strategi + Sasaran | Program Kerja | **INI INPUT PERTAMA SEBELUM RKAP** |
| 2.7 | Penjadwalan Program Kerja | Program Kerja | Rencana 1 tahun + Bulanan | Module: Work Schedule (Jadwal Kerja) |
| 2.8 | Approval Program Kerja | Draft Program + Jadwal | Program Disetujui | Workflow Approval: Atasan Langsung + Manajemen Risiko |

**Output Phase 2**: Program Kerja Terintegrasi dengan Jadwal Pelaksanaan.

---

#### PHASE 3: COST PLANNING (RUTIN & INVESTASI)

**Aktor**: Cost Owner, Satuan Kerja, Divisi Keuangan, Accounting

| Step ID | Process Step | Input | Output | System Action |
|---------|-------------|-------|--------|---------------|
| 3.1 | Penyusunan Biaya Rutin (FORM 3) | Program Kerja (Form 2) | Barang/Jasa + Cost Center + Qty + Harga Satuan + Elemen Biaya | Module: Budget Planning - OPEX |
| 3.2 | Distribusi Biaya Rutin per Bulan | Total Biaya | Breakdown Jan-Des | Auto-proportion dari Form 2 |
| 3.3 | Penyusunan Biaya Investasi (FORM 4) | Program Kerja (Form 2) | Kriteria Investasi + Kategori + Jenis + Nilai + Jadwal Pembayaran | Module: Budget Planning - CAPEX |
| 3.4 | Approval Biaya | Draft Anggaran | Anggaran Disetujui | Workflow Approval: PPK + Direksi Keuangan |
| 3.5 | Konsolidasi Anggaran Satuan Kerja | Anggaran Disetujui | RKAP Satuan Kerja | Module: Budget Consolidation |
| 3.6 | Konsolidasi RKAP Perusahaan | RKAP SK + Pendapatan Perusahaan | RKAP Perusahaan Lengkap | Module: Corporate Budget |

**Output Phase 3**: RKAP Perusahaan (Pendapatan + Beban Rutin + Investasi).

---

#### PHASE 4: FINANCIAL PROJECTION & PROFIT/LOSS MODELING

**Aktor**: Accounting, FP&A, Divisi Keuangan, Manajemen

| Step ID | Process Step | Input | Output | System Action |
|---------|-------------|-------|--------|---------------|
| 4.1 | Penyusunan Forecast Pendapatan | RKAP + Elemen Biaya Pendapatan (6000-6940) | Forecast Pendapatan Bulanan | Module: Revenue Planning |
| 4.2 | Penyusunan Rencana Beban | RKAP + Elemen Biaya Beban (7000-9109) | Rencana Beban Bulanan | Module: Expense Planning |
| 4.3 | Kalkulasi Laba Rugi | Pendapatan - Beban | Laporan Laba Rugi Rencana | Module: P&L Calculator |
| 4.4 | Analisis Margin & KPI | P&L | Gross Margin, EBITDA, Net Margin | Module: Financial Analytics |
| 4.5 | Simulasi Skenario | Variabel Biaya/Pendapatan | Best Case / Base Case / Worst Case | Module: Scenario Planning |
| 4.6 | Approval RKAP Final | RKAP Lengkap | RKAP Perusahaan Disetujui | Workflow Approval: Dewan Komisaris / Direksi |

**Output Phase 4**: RKAP Final + Projection Laba Rugi + Analisis KPI.

---

#### PHASE 5: IMPLEMENTATION & MONITORING

**Aktor**: Project Manager, Cost Owner, Accounting, Controller

| Step ID | Process Step | Input | Output | System Action |
|---------|-------------|-------|--------|---------------|
| 5.1 | Realisasi Pendapatan Bulanan | Data Akuntansi | Pendapatan Realisasi | Module: Revenue Tracking |
| 5.2 | Realisasi Beban Bulanan | Data Akuntansi | Beban Realisasi | Module: Expense Tracking |
| 5.3 | Monitoring Program Kerja | Realisasi Fisik | % Penyelesaian Program | Module: Program Monitoring |
| 5.4 | Monitoring Anggaran | Realisasi Keuangan | Budget vs Actual (BvA) | Module: Budget Monitoring |
| 5.5 | Risk Assessment Bulanan (Form 6) | Data Realisasi | Inherent/Current/Residual Risk | Module: Risk Assessment Monthly |
| 5.6 | Evaluasi Kinerja | BvA + Risk + Program | KPI Scorecard | Module: Performance Management |
| 5.7 | Laporan Bulanan/Triwulan/Tahunan | Data Realisasi | Laporan RKAP, Laporan Keuangan, Risk Report | Module: Reporting Engine |

**Output Phase 5**: Realisasi Bulanan + Monitoring + Evaluasi.

---

#### PHASE 6: DASHBOARD & ANALYTICS

**Aktor**: Manajemen, Divisi Keuangan, Risk Management, Satuan Kerja

| Step ID | Process Step | Input | Output |
|---------|-------------|-------|--------|
| 6.1 | Executive Summary Dashboard | RKAP + Realisasi | Ringkasan Kinerja (Pendapatan, Laba, Cash Flow) |
| 6.2 | Risk Dashboard | Risk Assessment | Heat Map, Risk Register, Risk Appetite |
| 6.3 | Program Dashboard | Jadwal Kerja | Timeline, Milestone, Status Penyelesaian |
| 6.4 | Budget Dashboard | Anggaran vs Realisasi | BvA per Cost Center, Elemen Biaya |
| 6.5 | Profit & Loss Dashboard | P&L Rencana vs Realisasi | Variance Analysis, Margin Analysis |
| 6.6 | Drill-down Analytics | Data Transaksi | Detail ke level transaksi akuntansi |

---

### 1.3 Detail Process Flow: INPUT RKAP (Sequence)

```
[INPUT PROGRAM KERJA] → [INPUT BIAYA] → [KONSOLIDASI] → [ANALISIS LABA RUGI] → [APPROVAL]
        ↓                      ↓                ↓                    ↓                  ↓
   Form 2 (Jadwal)       Form 3 (Rutin)    RKAP SK           P&L Calculation       RKAP Final
   Form 4 (Investasi)   Form 5 (Anggaran)  RKAP Corp         Forecast              RKAP Disetujui
```

**Catatan Kritis**: Sesuai permintaan pengguna, urutan input adalah:
1. **Pertama**: Input Program Kerja (Form 1 & Form 2)
2. **Kedua**: Input Biaya Rutin & Investasi (Form 3, Form 4, Form 5)
3. **Ketiga**: Konsolidasi RKAP dan Analisis Laba Rugi
4. **Keempat**: Approval RKAP

---

### 1.4 Approval Workflow Matrix

| Dokumen | Pembuat | Pemeriksa | Disetujui |
|---------|---------|-----------|-----------|
| Program Kerja | Satuan Kerja | Manajemen Risiko | PPK / Direksi |
| Anggaran Rutin | Cost Owner | Controller | VP Keuangan |
| Anggaran Investasi | Satuan Kerja | Manajemen Investasi | Direktur |
| RKAP Perusahaan | Divisi Keuangan | Komisaris | Direktur Utama |
| Risk Register | Risk Owner | Manajemen Risiko | Komite Manajemen Risiko |

---

## 2. MODULE BREAKDOWN & FUNCTIONAL SPECIFICATION

### 2.1 Master Data Module

**Sub-modules**:
- **Organization Management**: Satuan Kerja, Cost Center, Pemilik Cost Center, User & Role
- **Chart of Accounts**: Elemen Biaya Pendapatan (6000-6940), Elemen Biaya Beban (7000-9109)
- **Risk Taxonomy**: Tipe Risiko (A1-C6), Taksonomi, Risk Appetite
- **Rating Criteria**: Kriteria Penetapan Rating (AAA-BB)
- **Risk Matrix**: Kriteria Probabilitas (1-5), Dampak (1-5), Nilai Risiko, Peringkat
- **Investment Criteria**: Kriteria Investasi (A-E), Kategori (SDU/PSN/OTH), Jenis Investasi (1-6)

### 2.2 Strategic Planning Module (FORM 1)

**Fitur**:
- CRUD Sasaran Perusahaan dengan Rating (AAA/AA/A/BB/B)
- CRUD Sasaran Satuan Kerja dengan referensi ke Sasaran Perusahaan
- Identifikasi Risiko: Risiko Positif (+) / Negatif (-)
- Tipe Risiko, Taksonomi, Penyebab, Dampak
- Analisis Risiko: Probabilitas (1-5), Dampak (1-5), Nilai Risiko (auto-calc)
- Peringkat Risiko: VL/L/M/H/VH (auto-calc berdasarkan kriteria)
- Strategi Perlakuan Risiko
- Program Kerja: linked ke Form 2

**Business Rule**:
- Program Kerja hanya bisa dibuat dari Sasaran yang memiliki Rating A ke atas
- Setiap Risiko wajib memiliki Strategi dan Program Kerja
- Nilai Risiko = Probabilitas x Dampak (range 1-25)

### 2.3 Work Schedule Module (FORM 2)

**Fitur**:
- Program Kerja dengan target bulanan (Jan-Des)
- Auto-populate dari Form 1: Sasaran, Sasaran SK, Risiko, Peringkat, Program Kerja
- Target tahunan (col 9) dan breakdown bulanan (col 10-21)
- Referensi biaya ke Form 3 (biaya rutin) dan Form 4 (biaya investasi)
- Validasi total: SUM(bulanan) = target tahunan

**Business Rule**:
- Jika Program Kerja ada di Form 2, wajib ada anggaran di Form 3/Form 4
- Satuan Kerja wajib mengisi 12 bulan

### 2.4 Budget Planning - OPEX Module (FORM 3)

**Fitur**:
- Detail Barang/Jasa dengan referensi ke Program Kerja (Form 2)
- Cost Center (Kode + Pemilik)
- QTY, Satuan, Harga Satuan
- Elemen Biaya (dropdown dari CoA)
- Breakdown biaya per bulan (Jan-Des) + TOTAL
- Auto-subtotal per elemen biaya, per program kerja, per satuan kerja

**Business Rule**:
- Total biaya per elemen biaya = SUM(bulanan)
- Total biaya per satuan kerja = SUM(semua item)
- Budget vs Actual integration ke akuntansi

### 2.5 Budget Planning - CAPEX Module (FORM 4 & 5)

**Fitur**:
- Kriteria Investasi: A (Pendapatan/Laba), B (Penugasan), C (Non-core), D (Efisiensi), E (Operasional)
- Kategori Investasi: SDU, PSN, OTH
- Jenis Investasi: Tanah, Bangunan, Mesin, Kendaraan, Inventaris, Aktiva dalam Penyelesaian
- Rencana Pembayaran bulanan (Jan-Des)
- Total Investasi = Qty x Harga Satuan
- Total Pembayaran = SUM(rencana pembayaran bulanan)

### 2.6 Risk Assessment Monthly Module (FORM 6)

**Fitur**:
- Inherent Risk: Probabilitas + Dampak awal
- Current Risk: Probabilitas + Dampak setelah mitigasi
- Residual/Expected Risk: Probabilitas + Dampak setelah kontrol
- Mitigasi Plan + Realisasi Mitigasi
- Business Process mapping
- Status: On Progress / Done / Overdue

### 2.7 Financial Projection & P&L Module

**Fitur**:
- **Pendapatan**: Breakdown per elemen biaya pendapatan (6000-6940)
- **Beban**: Breakdown per elemen biaya beban (7000-9109)
- **Laba Rugi**: Pendapatan - Beban = Laba/ Rugi
- **Margin Analysis**: Gross Margin, Operating Margin, Net Margin
- **Cash Flow**: Operating, Investing, Financing
- **Scenario Planning**: Best Case, Base Case, Worst Case

**Business Rule**:
- Setiap elemen biaya di RKAP harus mapping ke Chart of Accounts
- P&L dihitung otomatis dari anggaran yang disetujui
- Realisasi P&L di-calculate dari data akuntansi

### 2.8 Approval & Workflow Engine

**Fitur**:
- Multi-level approval dengan configurable threshold
- Notifikasi email/in-app untuk approval
- Audit trail (siapa, kapan, apa yang diubah)
- Versioning RKAP (draft, submitted, approved, rejected, revised)

### 2.9 Reporting & Dashboard Module

**Fitur**:
- Real-time dashboard dengan visualisasi data
- Export Excel/PDF sesuai format existing
- Drill-down dari summary ke detail transaksi
- Mobile-responsive design

---

## 3. DATA MODEL (Entity Relationship)

### 3.1 Core Entities

```
┌─────────────────┐       ┌─────────────────┐       ┌─────────────────┐
│  Sasaran        │1     N│  Risk Register  │1     N│  Program Kerja  │
│  Perusahaan     │───────│                 │───────│                 │
└─────────────────┘       └─────────────────┘       └─────────────────┘
        │1                         │1                        │1
        │ N                       │ N                        │ N
┌─────────────────┐       ┌─────────────────┐       ┌─────────────────┐
│  Sasaran SK     │       │  Work Schedule  │       │  Budget Detail  │
│                 │       │  (Form 2)       │       │  (Form 3/4)     │
└─────────────────┘       └─────────────────┘       └─────────────────┘
                                                           │
                                              ┌────────────┴────────────┐
                                              │                         │
                                        ┌─────┴─────┐           ┌──────┴──────┐
                                        │  OPEX     │           │  CAPEX      │
                                        │  (Form 3) │           │  (Form 4)   │
                                        └───────────┘           └─────────────┘
                                                           │
                                                    ┌────────┴────────┐
                                                    │                 │
                                              ┌─────┴─────┐     ┌────┴─────┐
                                              │  Elemen   │     │  Jenis   │
                                              │  Biaya    │     │ Investasi│
                                              └───────────┘     └──────────┘
                                                           │
                                                    ┌────────┴────────┐
                                                    │                 │
                                              ┌─────┴─────┐     ┌────┴─────┐
                                              │  Realisasi│     │  RKAP    │
                                              │  Bulanan   │     │  Final   │
                                              └───────────┘     └──────────┘
```

### 3.2 Key Tables

**Master Data**:
- `companies` (Perusahaan)
- `departments` (Satuan Kerja)
- `cost_centers` (Kode Cost Center + Pemilik)
- `chart_of_accounts` (Elemen Biaya: Kode, Deskripsi, Tipe [Pendapatan/Beban])
- `risk_taxonomies` (Tipe Risiko, Taksonomi, Risk Appetite)
- `rating_criteria` (AAA, AA, A, BB, B)
- `risk_matrix` (Probabilitas 1-5, Dampak 1-5, Nilai Risiko, Risk Level)
- `investment_criteria` (A-E, SDU/PSN/OTH, Jenis 1-6)

**Planning**:
- `company_goals` (Sasaran Perusahaan)
- `department_goals` (Sasaran SK + Rating + Referensi Sasaran Perusahaan)
- `risk_registers` (Risiko: Tipe, Taksonomi, Penyebab, Dampak, Probabilitas, Dampak, Nilai Risiko, Risk Level)
- `risk_treatments` (Strategi + Program Kerja)
- `work_schedules` (Program Kerja + Target 1 tahun + Breakdown bulanan)
- `budget_details` (Barang/Jasa, Qty, Satuan, Harga, Elemen Biaya, Breakdown bulanan)

**Financial**:
- `budget_opex` (Biaya Rutin - linked ke work_schedules)
- `budget_capex` (Biaya Investasi - linked ke work_schedules)
- `revenue_plans` (Forecast Pendapatan per elemen biaya)
- `expense_plans` (Forecast Beban per elemen biaya)
- `profit_loss_statements` (Pendapatan, Beban, Laba/Rugi)
- `budget_realizations` (Realisasi bulanan per elemen biaya)

**Monitoring**:
- `risk_assessments_monthly` (Inherent, Current, Residual Risk per bulan)
- `program_realizations` (% Penyelesaian Program Kerja)
- `performance_scorecards` (KPI hasil evaluasi)

---

## 4. DASHBOARD DESIGN

### 4.1 Executive Summary Dashboard

**Widgets**:
1. **RKAP Achievement Score**: Ringkasan % capai RKAP (Realisasi vs Anggaran)
2. **Profit & Loss Trend**: Grafik tren pendapatan, beban, dan laba (Rencana vs Realisasi)
3. **Budget vs Actual**: BvA summary per divisi/satuan kerja
4. **Top 5 Risks**: Heat map dengan 5 risiko tertinggi
5. **Program Progress**: % penyelesaian program kerja (milestone Gantt chart)
6. **Cash Flow Position**: Arus kas operasional, investasi, pendanaan

### 4.2 Risk Dashboard

**Widgets**:
1. **Risk Heat Map**: Matriks probabilitas vs dampak (Inherent, Current, Residual)
2. **Risk Register Table**: Daftar risiko dengan filter by tipe, taksonomi, risk level
3. **Risk Appetite Meter**: Visualisasi posisi risiko relatif terhadap risk appetite
4. **Mitigation Status**: % mitigasi done vs on progress vs overdue
5. **Risk Trend**: Tren jumlah risiko per kategori (High/Medium/Low) bulanan

### 4.3 Program Dashboard

**Widgets**:
1. **Gantt Chart**: Timeline program kerja dengan milestone
2. **Program Status**: % On Track, Delayed, Completed
3. **Budget per Program**: Anggaran vs Realisasi per program kerja
4. **Dependency Map**: Ketergantungan antar program kerja

### 4.4 Budget Dashboard

**Widgets**:
1. **OPEX vs CAPEX**: Perbandingan biaya rutin vs investasi
2. **Cost Center Heatmap**: Realisasi vs Anggaran per cost center
3. **Expense Breakdown**: Pie chart per elemen biaya utama
4. **Monthly Trend**: Tren pengeluaran bulanan (Rencana vs Realisasi)
5. **Variance Analysis**: Top 10 variances (terbesar positif/negatif)

### 4.5 Profit & Loss Dashboard

**Widgets**:
1. **P&L Statement**: Pendapatan - Beban = Laba (Rencana vs Realisasi vs Variance)
2. **Margin Analysis**: Gross Margin %, Operating Margin %, Net Margin %
3. **Revenue Stream**: Breakdown pendapatan per elemen biaya
4. **Expense Category**: Breakdown beban per elemen biaya
5. **Scenario Comparison**: Best Case vs Base Case vs Worst Case

---

## 5. TECHNICAL ARCHITECTURE

### 5.1 Technology Stack (Recommended)

**Frontend**:
- React.js / Next.js dengan TypeScript
- Chart.js / Recharts untuk visualisasi dashboard
- Ant Design / Material UI untuk komponen UI
- React Query untuk data fetching dan caching

**Backend**:
- Laravel 11 (PHP 8.2+) - sesuai stack yang ada di lingkungan
- Laravel Sanctum untuk authentication
- Laravel Workflow untuk approval engine
- Laravel Excel untuk import/export Excel

**Database**:
- MySQL 8.0 / PostgreSQL 14+
- Redis untuk caching dan session

**Infrastructure**:
- Docker untuk containerization
- Nginx sebagai reverse proxy
- Queue Worker untuk proses batch (export, calculation)

### 5.2 Application Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                         CLIENT LAYER                                │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐              │
│  │   Web App    │  │  Dashboard   │  │  Mobile App  │              │
│  │  (React)     │  │  (React)     │  │  (React)     │              │
│  └──────────────┘  └──────────────┘  └──────────────┘              │
└─────────────────────────────────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────┐
│                       API GATEWAY / ROUTER                           │
│                    (Laravel Sanctum Auth)                            │
└─────────────────────────────────────────────────────────────────────┘
                                 │
        ┌────────────────────────┼────────────────────────┐
        ▼                        ▼                        ▼
┌──────────────┐        ┌──────────────┐        ┌──────────────┐
│   Planning   │        │   Financial  │        │   Reporting  │
│  Module      │        │   Module     │        │   Module     │
│  (RKAP)      │        │  (P&L)       │        │  (Dashboard) │
└──────────────┘        └──────────────┘        └──────────────┘
        │                        │                        │
        └────────────────────────┼────────────────────────┘
                                 ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    SHARED SERVICES                                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐              │
│  │  Workflow    │  │   Approval   │  │  Notification│              │
│  │  Engine      │  │   Engine     │  │  Service     │              │
│  └──────────────┘  └──────────────┘  └──────────────┘              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐              │
│  │  Export/     │  │   Audit      │  │   Cache      │              │
│  │  Import      │  │   Trail      │  │   Service    │              │
│  └──────────────┘  └──────────────┘  └──────────────┘              │
└─────────────────────────────────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────┐
│                       DATA LAYER                                    │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐              │
│  │   MySQL      │  │    Redis     │  │  File Storage│              │
│  │  (Primary)   │  │   (Cache)    │  │  (Export)    │              │
│  └──────────────┘  └──────────────┘  └──────────────┘              │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 6. IMPLEMENTATION ROADMAP

### Phase 1: Foundation (Month 1-2)
- Setup project (Laravel + React)
- Master Data Module (Organization, CoA, Risk Taxonomy, Rating, Matrix)
- Authentication & Authorization (RBAC)
- Database schema design & migration

### Phase 2: Core RKAP Planning (Month 3-4)
- Strategic Planning Module (FORM 1: Sasaran, Risk Register, Program Kerja)
- Work Schedule Module (FORM 2: Jadwal Kerja)
- Budget Planning - OPEX (FORM 3: Biaya Rutin)
- Budget Planning - CAPEX (FORM 4/5: Biaya Investasi)

### Phase 3: Financial Integration (Month 5-6)
- P&L Calculation Module
- Revenue & Expense Planning
- Approval Workflow Engine
- Export/Import Excel (compatibility dengan format existing)

### Phase 4: Monitoring & Risk (Month 7-8)
- Budget Monitoring (BvA)
- Program Monitoring
- Risk Assessment Monthly (FORM 6)
- Realisasi bulanan integration

### Phase 5: Dashboard & Analytics (Month 9-10)
- Executive Dashboard
- Risk Dashboard
- Program Dashboard
- Budget Dashboard
- P&L Dashboard
- Drill-down Analytics

### Phase 6: Testing & Deployment (Month 11-12)
- UAT (User Acceptance Testing)
- Data Migration dari Excel
- Training User
- Production Deployment
- Post-go-live support

---

## 7. KEY BUSINESS RULES SUMMARY

### 7.1 Hierarki Input
```
Sasaran Perusahaan (Rating)
    ↓
Sasaran Satuan Kerja (Rating A ke atas wajib ada Program Kerja)
    ↓
Program Kerja (Wajib dijadwalkan di Form 2)
    ↓
Biaya Rutin / Investasi (Wajib ada anggaran)
    ↓
RKAP Satuan Kerja
    ↓
RKAP Perusahaan
    ↓
P&L Analysis & Approval
```

### 7.2 Risk-Based Budgeting
- Setiap Program Kerja berasal dari Sasaran yang memiliki Risiko
- Setiap Risiko memiliki Strategi Perlakuan → menghasilkan Program Kerja
- Budget dialokasikan berdasarkan Program Kerja (bottom-up)

### 7.3 Chart of Accounts Mapping
- Pendapatan: 6000-6940 (dikelompokkan per jenis: Batubara, Listrik, Briket, Jasa, dll)
- Beban: 7000-9109 (dikelompokkan: Konsultan, Gaji, Bahan Bakar, Sewa, Penyusutan, dll)
- Setiap item anggaran wajib memilih elemen biaya dari CoA

### 7.4 Monthly Cycle
```
Bulan N:
├── Input Program Kerja & Anggaran (RKAP Planning)
├── Monitoring Realisasi Bulan N-1
├── Risk Assessment Bulan N-1
├── Evaluasi Kinerja
└── Reporting Bulanan
```

---

## 8. SECURITY & COMPLIANCE

### 8.1 Role-Based Access Control (RBAC)

| Role | Access Level |
|------|-------------|
| **Super Admin** | Full access ke semua modul |
| **Direksi** | View all, Approval RKAP, Executive Dashboard |
| **Manajemen Risiko** | Manage Risk Register, Risk Assessment, Risk Dashboard |
| **PPK (Pejabat Pembuat Komitmen)** | Approval Program Kerja & Anggaran |
| **Cost Owner / Satuan Kerja** | Input Program Kerja, Input Anggaran, View own data |
| **Controller** | Review & Validasi Anggaran, Monitoring BvA |
| **Accounting** | Input Realisasi Keuangan, P&L Data |
| **Auditor** | View only, Audit Trail access |

### 8.2 Audit Trail
- Semua perubahan data dicatat (created_by, updated_by, created_at, updated_at)
- Versioning untuk RKAP (draft, submitted, approved)
- Approval history dengan timestamp, user, dan komentar

### 8.3 Data Integrity
- Foreign key constraints antar tabel
- Unique constraints untuk kode unik (Cost Center, Elemen Biaya, Program Kerja)
- Check constraints untuk nilai numerik (Probabilitas 1-5, Dampak 1-5)

---

## 9. INTEGRATION POINTS

### 9.1 External Systems
- **Accounting System**: Integration untuk realisasi pendapatan dan beban (API / Database link)
- **HR System**: Data karyawan untuk assignment cost owner
- **Procurement System**: Data kontrak dan purchase order untuk realisasi investasi
- **Asset Management**: Data aset untuk depreciation dan investasi

### 9.2 Data Export/Import
- **Export to Excel**: Format sesuai template existing (Form 1-6)
- **Import from Excel**: Upload data massal untuk master data dan planning
- **PDF Report**: Laporan bulanan/triwulan/tahunan

---

## 10. SUCCESS METRICS

| Metric | Target |
|--------|--------|
| Planning Cycle Time | Reduction 50% dari Excel-based |
| Data Accuracy | 100% konsisten antar form |
| Budget vs Actual Variance | < 5% |
| Program Completion Rate | > 90% |
| Risk Mitigation Rate | > 85% |
| Report Generation Time | < 5 menit (vs 3 hari Excel) |
| User Adoption | > 95% user aktif bulanan |

---

## 11. RISK & MITIGATION

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| User resistance to change | High | Medium | Training, phased rollout, Excel migration tool |
| Data migration errors | High | Medium | Dry-run migration, validation scripts |
| Complex approval workflow | Medium | High | Configurable workflow, clear SOP |
| Integration dengan sistem existing | Medium | Medium | API-first design, adapter pattern |
| Performance dengan data besar | Medium | Low | Caching, indexing, pagination |

---

## KESIMPULAN

Aplikasi RKAP ini dirancang sebagai sistem terintegrasi yang mengikuti alur bisnis:
1. **Program Kerja** diinput pertama kali (berbasis risiko)
2. **Biaya** dialokasikan berdasarkan Program Kerja
3. **RKAP** dikonsolidasi menjadi anggaran perusahaan
4. **Laba Rugi** dianalisis dari forecast pendapatan dan beban
5. **Dashboard** menyediakan real-time visibility untuk manajemen

Dengan architecture yang modular dan scalable, aplikasi ini dapat berkembang dari single company menjadi multi-company/group solution.
