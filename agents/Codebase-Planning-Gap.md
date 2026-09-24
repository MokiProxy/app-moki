# Codebase vs Business Requirements Gap Analysis

## Executive Summary

The current codebase implements **approximately 85-90%** of the business requirements defined in "ARKITEKTUR DAN ALUR BISNIS RKAP.md". The core modules are well-structured with Laravel 11, proper relationships, approval workflows, and RBAC. However, several critical gaps exist in business rule enforcement, data integrity, integration points, and advanced features.

---

## 1. IMPLEMENTED MODULES (✅ Aligned with Requirements)

| Module | Business Doc Section | Implementation Status |
|--------|---------------------|----------------------|
| Master Data (Organization, CoA, Risk Taxonomy, Rating, Matrix, Investment Criteria) | 2.1, 3.2 | ✅ Complete |
| Strategic Planning - Company/Department Targets (Form 1) | 2.2, 1.2 Phase 1 | ✅ Complete |
| Risk Identification & Analysis | 2.2, 1.2 Phase 2 | ✅ Complete |
| Risk Treatment / Department Risk Strategies | 2.2, 1.2 Phase 2 Step 2.5-2.6 | ✅ Complete |
| Work Programs (Form 2) with Monthly Schedule | 2.3, 1.2 Phase 2 Step 2.7 | ✅ Complete |
| Budget OPEX - Routine Costs (Form 3) | 2.4, 1.2 Phase 3 Step 3.1-3.2 | ✅ Complete |
| Budget CAPEX - Investment Plans (Form 4) | 2.5, 1.2 Phase 3 Step 3.3 | ✅ Complete |
| Budget Consolidation (RKAP) | 2.5, 1.2 Phase 3 Step 3.5-3.6 | ✅ Complete |
| Revenue & Expense Planning | 2.7, 1.2 Phase 4 Step 4.1-4.2 | ✅ Complete |
| Profit & Loss Calculation & Scenario Planning | 2.7, 1.2 Phase 4 Step 4.3-4.5 | ✅ Complete |
| Approval Workflow Engine | 2.8, 1.4 | ✅ Complete |
| Risk Assessment Monthly (Form 6) | 2.6, 1.2 Phase 5 Step 5.5 | ✅ Complete |
| Program & Budget Realization | 1.2 Phase 5 Step 5.1-5.4 | ✅ Complete |
| Performance Scorecards | 1.2 Phase 5 Step 5.6 | ✅ Complete |
| Dashboard (Executive, Risk, Program, Budget, P&L) | 4.1-4.5, 1.2 Phase 6 | ✅ Complete |
| Audit Trail | 8.2 | ✅ Complete |
| Export Excel/PDF | 9.2 | ✅ Complete |

---

## 2. CRITICAL GAPS (🔴 High Priority)

### 2.1 Missing Business Rule Enforcement

| Gap | Business Rule (Doc Reference) | Current State | Impact |
|-----|------------------------------|---------------|--------|
| **Program Kerja requires Rating A+** | 2.2 Business Rule: "Program Kerja hanya bisa dibuat dari Sasaran yang memiliki Rating A ke atas" | Partially enforced in `WorkProgramController::checkRating()` but only checks AAA/AA/A. Rating "A" should be minimum. Also not enforced at database/model level. | Data integrity risk - programs can be created for BB/B rated targets via API bypass |
| **Every Risk must have Strategy + Program Kerja** | 2.2 Business Rule: "Setiap Risiko wajib memiliki Strategi dan Program Kerja" | Not enforced. RiskIdentification can exist without DepartmentRiskStrategy or WorkProgram | Incomplete risk management cycle |
| **Form 2 Program requires Budget (Form 3/4)** | 2.3 Business Rule: "Jika Program Kerja ada di Form 2, wajib ada anggaran di Form 3/Form 4" | Not enforced. WorkProgram can be approved without RoutineCost or InvestmentPlan | Budget planning gap |
| **Monthly breakdown validation** | 2.3 Business Rule: "Satuan Kerja wajib mengisi 12 bulan" & "SUM(bulanan) = target tahunan" | Partially validated in RoutineCostController but not for WorkProgram monthly fields | Data inconsistency |
| **Cost Element mapping to CoA mandatory** | 7.3: "Setiap item anggaran wajib memilih elemen biaya dari CoA" | CostElement has chart_of_account_id but not required at DB level | Chart of Accounts mapping gaps |
| **Investment Total = Qty × Harga** | 2.5: "Total Investasi = Qty x Harga Satuan" | Calculated in controller but not enforced at model level | Calculation drift risk |
| **Payment Schedule = Total Payment** | 2.5: "Total Pembayaran = SUM(rencana pembayaran bulanan)" | Not validated | CAPEX cash flow planning errors |

### 2.2 Missing Core Entities (Per Data Model Section 3.2)

| Missing Entity | Business Doc Reference | Required For |
|----------------|----------------------|--------------|
| `companies` | 3.2 Master Data | Multi-company support (Doc: "multi-company/group solution") |
| `departments` (as Satuan Kerja master) | 3.2 Master Data | Currently using Division model but not fully aligned |
| `risk_treatments` (separate from DepartmentRiskStrategy) | 3.1 ERD: Risk Treatment entity | Proper risk treatment tracking per ISO 31000 |
| `budget_opex` table | 3.2 Financial: `budget_opex` linked to work_schedules | Currently using RoutineCost directly, missing consolidation layer |
| `work_schedules` as separate from work_programs | 3.1 ERD: Work Schedule (Form 2) separate entity | Form 2 is schedule, Form 1 is program - currently merged |

### 2.3 Approval Workflow Gaps

| Gap | Business Doc Reference | Current State |
|-----|----------------------|---------------|
| **Multi-level threshold-based approval** | 2.8: "Multi-level approval dengan configurable threshold" | Fixed matrix in ApprovalService, not configurable |
| **RKAP approval: Komisaris → Direksi Utama** | 1.4 Matrix: RKAP approved by Komisaris/Direksi Utama | Current matrix: Controller → Direksi → Komisaris (order differs) |
| **Risk Register approval: Komite Manajemen Risiko** | 1.4 Matrix | Not implemented - RiskIdentification has no approval workflow |
| **Versioning (draft, submitted, approved, rejected, revised)** | 2.8: "Versioning RKAP" | Only status field, no version history |
| **Email/in-app notifications** | 2.8: "Notifikasi email/in-app" | Only database notifications via Laravel, no email channel configured |

### 2.4 Financial Integration Gaps

| Gap | Business Doc Reference | Current State |
|-----|----------------------|---------------|
| **Accounting System Integration** | 9.1: "Integration untuk realisasi pendapatan dan beban (API / Database link)" | BudgetRealization is manual entry only, no API integration |
| **HR System Integration** | 9.1: "Data karyawan untuk assignment cost owner" | CostCenter has owner but no HR sync |
| **Procurement Integration** | 9.1: "Data kontrak dan PO untuk realisasi investasi" | Not implemented |
| **Asset Management Integration** | 9.1: "Data aset untuk depreciation dan investasi" | Not implemented |

---

## 3. FUNCTIONAL GAPS (🟡 Medium Priority)

### 3.1 Form 1 (Sasaran & Asesmen Risiko) - Missing Fields

| Missing Field | Excel Column | Current Model |
|--------------|--------------|---------------|
| **Sasaran Perusahaan** | Column B | CompanyTarget only has `target` text |
| **Sasaran Satuan Kerja** | Column C | DepartmentTarget only has `target` text |
| **Rating (AAA/AA/A/BB/B)** | Column D | RatingCriteria exists but not fully linked |
| **Identifikasi Risiko** | Column E | RiskIdentification.risk (✅) |
| **Positif/Negatif** | Column F | RiskIdentification.risk_direction (✅) |
| **Tipe Risiko** | Column G | RiskIdentification.risk_type (✅) |
| **Taksonomi** | Column H | RiskIdentification.risk_taxonomy (✅) |
| **Penyebab** | Column I | RiskIdentificationReason (✅ separate table) |
| **Dampak** | Column J | RiskIdentificationImpact (✅ separate table) |
| **Probabilitas (1-5)** | Column K | RiskAnalysis → RiskProbability (✅) |
| **Dampak (1-5)** | Column L | RiskAnalysis → RiskImpact (✅) |
| **Nilai Risiko (auto)** | Column M | RiskAnalysis → RiskScoreLevel (✅ auto-calc) |
| **Peringkat (VL/L/M/H/VH)** | Column N | RiskAnalysis → RiskScoreLevel.level (✅) |
| **Strategi** | Column O | DepartmentRiskStrategy (✅) |
| **Program Kerja** | Column P | WorkProgram (✅ linked) |

**Gap**: The Excel Form 1 has a **flat structure** but the codebase uses **normalized relations**. Import/Export needs to handle this transformation.

### 3.2 Form 2 (Jadwal Rencana Pelaksanaan) - Missing Features

| Feature | Business Doc | Current State |
|---------|-------------|---------------|
| **Auto-populate from Form 1** | 2.3: "Auto-populate dari Form 1: Sasaran, Sasaran SK, Risiko, Peringkat, Program Kerja" | Manual selection only |
| **Target tahunan (col 9)** | 2.3 | WorkProgram.year_plan (✅) |
| **Breakdown bulanan (col 10-21)** | 2.3 | WorkProgram jan_plan...dec_plan (✅) |
| **Referensi biaya ke Form 3/4** | 2.3: "Referensi biaya ke Form 3 (biaya rutin) dan Form 4 (biaya investasi)" | WorkProgram.hasBudget() method exists but no UI linkage |
| **Validasi total: SUM(bulanan) = target tahunan** | 2.3 Business Rule | Not enforced |

### 3.3 Form 3 (Penyusunan Biaya Rutin) - Missing Features

| Feature | Business Doc | Current State |
|---------|-------------|---------------|
| **Auto-subtotal per elemen biaya** | 2.4: "Auto-subtotal per elemen biaya, per program kerja, per satuan kerja" | Dashboard shows subtotals but not in Form view |
| **Auto-subtotal per program kerja** | 2.4 | Missing in create/edit views |
| **Auto-subtotal per satuan kerja** | 2.4 | Missing in create/edit views |
| **Budget vs Actual integration** | 2.4 Business Rule | BudgetRealization exists but no automated sync |

### 3.4 Form 4/5 (Biaya Investasi & Anggaran Investasi) - Missing Features

| Feature | Business Doc | Current State |
|---------|-------------|---------------|
| **Kriteria Investasi (A-E)** | 2.5: InvestationCriteria (✅) |
| **Kategori (SDU/PSN/OTH)** | 2.5: InvestattionCategory (✅) |
| **Jenis Investasi (1-6)** | 2.5: InvestationType (✅) |
| **Rencana Pembayaran bulanan** | 2.5: InvestmentPlan jan_plan...dec_plan (✅) |
| **Form 5: Ringkasan nilai investasi** | 2.5: BudgetCapex exists but limited to division totals |
| **Form 5: Distribusi pembayaran** | 2.5 | Not implemented as separate view |

### 3.5 Risk Assessment Monthly (Form 6) - Gaps

| Feature | Business Doc | Current State |
|---------|-------------|---------------|
| **Business Process mapping** | 2.6: "Business Process mapping" | Not implemented |
| **Status: On Progress / Done / Overdue** | 2.6 | mitigation_status exists but no enum enforcement |
| **Risk Appetite visualization** | 4.2 Widget 3 | RiskAppetite model exists but not connected to assessments |

### 3.6 Dashboard Gaps (Section 4)

| Dashboard | Missing Widgets |
|-----------|----------------|
| **Executive Summary (4.1)** | Cash Flow Position (Widget 6) - not implemented |
| **Risk Dashboard (4.2)** | Risk Appetite Meter (Widget 3), Risk Trend (Widget 5) |
| **Program Dashboard (4.3)** | Gantt Chart (Widget 1) - basic list only, Dependency Map (Widget 4) |
| **Budget Dashboard (4.4)** | Cost Center Heatmap (Widget 2), Variance Analysis Top 10 (Widget 5) |
| **P&L Dashboard (4.5)** | Revenue Stream breakdown (Widget 3), Expense Category breakdown (Widget 4), Scenario Comparison (Widget 5) - simulation exists but not in dashboard |
| **Drill-down Analytics (4.6)** | 6.6: "Detail ke level transaksi akuntansi" - not implemented |

### 3.7 Reporting Engine (Phase 5 Step 5.7)

| Report | Business Doc | Current State |
|--------|-------------|---------------|
| **Laporan RKAP** | 5.7 | Export Excel/PDF for individual forms only |
| **Laporan Keuangan** | 5.7 | P&L show view only, no formatted report |
| **Risk Report** | 5.7 | Risk Identification export only, no assessment report |
| **Bulanan/Triwulan/Tahunan** | 5.7 | Monthly only, no quarterly/annual aggregation |

---

## 4. TECHNICAL ARCHITECTURE GAPS

### 4.1 Technology Stack Alignment (Section 5.1)

| Component | Recommended | Current | Gap |
|-----------|-------------|---------|-----|
| **Frontend** | React.js / Next.js with TypeScript | Blade templates + jQuery/DataTables | Major - not SPA, limited interactivity |
| **Charts** | Chart.js / Recharts | None visible in views | Missing visualizations |
| **UI Library** | Ant Design / Material UI | Custom Bootstrap-like CSS | Inconsistent UI |
| **Data Fetching** | React Query | Server-side rendering | No client-side caching |
| **Queue Workers** | For batch processes (export, calculation) | Not configured | Export/import may timeout |

### 4.2 Application Architecture (Section 5.2)

| Layer | Specified | Current | Gap |
|-------|-----------|---------|-----|
| **API Gateway/Router** | Laravel Sanctum Auth | Web routes only, no API routes for RKAP | No API layer |
| **Shared Services** | Workflow Engine, Approval Engine, Notification, Export/Import, Audit Trail, Cache | ApprovalService exists, others partial | Notification Service incomplete, Cache not used |
| **Data Layer** | MySQL + Redis + File Storage | MySQL only | No Redis caching, no file storage abstraction |

### 4.3 Database Design Gaps

| Issue | Current State | Required |
|-------|--------------|----------|
| **Foreign Keys** | Some missing (e.g., RoutineCost.cost_center_id nullable) | All relations should have FK constraints (8.3) |
| **Check Constraints** | None for Probabilitas 1-5, Dampak 1-5 | Required per 8.3 |
| **Unique Constraints** | Missing for Cost Center code, Elemen Biaya code, Program Kerja | Required per 8.3 |
| **Soft Deletes** | Not used on Erkap models | Needed for audit trail |
| **Indexes** | Missing on frequently queried columns (status, year, division_id) | Performance |

---

## 5. SECURITY & COMPLIANCE GAPS (Section 8)

### 5.1 RBAC Role Matrix (Section 8.1)

| Role | Required Access | Current Implementation |
|------|----------------|------------------------|
| **Super Admin** | Full access | Likely via `super-admin` role |
| **Direksi** | View all, Approval RKAP, Executive Dashboard | No specific role, uses existing permissions |
| **Manajemen Risiko** | Manage Risk Register, Risk Assessment, Risk Dashboard | `erkap-risk-manager` role? Not defined |
| **PPK** | Approval Program Kerja & Anggaran | `erkap-ppk` role used in ApprovalService |
| **Cost Owner / Satuan Kerja** | Input Program Kerja, Input Anggaran, View own data | `erkap-cost-owner` role (✅ via ErkapAccess) |
| **Controller** | Review & Validasi Anggaran, Monitoring BvA | `erkap-controller` role (✅ in ApprovalService) |
| **Accounting** | Input Realisasi Keuangan, P&L Data | Not defined |
| **Auditor** | View only, Audit Trail access | Auditor module exists but separate |

**Gap**: Role definitions not documented in code, permission names inconsistent (e.g., `erkap.menu` vs `erkap.risk-identifications.view`)

### 5.2 Audit Trail Gaps

| Requirement | Current State |
|-------------|---------------|
| **Versioning untuk RKAP** | Only status field, no version snapshots |
| **Approval history with timestamp, user, comment** | Approval model has this (✅) |
| **All changes logged (created_by, updated_by, created_at, updated_at)** | HasAuditTrail trait (✅) but not on all models |

---

## 6. USER EXPERIENCE & WORKFLOW GAPS

### 6.1 Input Sequence Enforcement (Section 1.3)

**Business Requirement**: Strict sequence: Program Kerja → Biaya → Konsolidasi → P&L → Approval

**Current State**: All modules accessible independently via sidebar. No workflow wizard to enforce sequence.

### 6.2 Monthly Cycle Support (Section 7.4)

| Cycle Step | Current Support |
|------------|-----------------|
| Input Program Kerja & Anggaran | ✅ |
| Monitoring Realisasi Bulan N-1 | BudgetRealization manual entry |
| Risk Assessment Bulan N-1 | RiskAssessmentMonthly manual entry |
| Evaluasi Kinerja | PerformanceScorecard manual entry |
| Reporting Bulanan | Dashboard only, no generated report |

### 6.3 Data Import/Export (Section 9.2)

| Format | Current | Required |
|--------|---------|----------|
| **Export to Excel (Form 1-6)** | Individual form exports | Consolidated workbook with all forms |
| **Import from Excel** | Not implemented | Mass upload for master data & planning |
| **PDF Report** | Individual form PDFs | Formatted monthly/quarterly/annual reports |

---

## 7. PRIORITIZED ACTION PLAN

### Phase 1: Critical Business Rules (Week 1-2)
1. Add database constraints: check constraints (probability 1-5, impact 1-5), unique keys, FK constraints
2. Enforce "Program Kerja requires Rating A+" at model level (observer/mutator)
3. Enforce "Risk must have Strategy + Program" validation
4. Enforce "WorkProgram requires Budget" before approval submission
5. Fix approval matrix order per business doc (Komisaris → Direksi Utama for RKAP)

### Phase 2: Missing Entities & Relationships (Week 2-3)
1. Create `risk_treatments` table separate from strategies
2. Create `budget_opex` consolidation table
3. Separate `work_schedules` from `work_programs` if needed
4. Add `companies` table for multi-company support

### Phase 3: Form Features & UX (Week 3-5)
1. Form 1: Flat import/export matching Excel template
2. Form 2: Auto-populate from Form 1, monthly validation
3. Form 3: Subtotals per element/program/satuan kerja in UI
4. Form 4/5: Complete BudgetCapex with payment distribution view
5. Form 6: Business process mapping, status enum, risk appetite link

### Phase 4: Dashboard & Reporting (Week 5-7)
1. Executive Dashboard: Add Cash Flow widget
2. Risk Dashboard: Add Risk Appetite Meter, Risk Trend
3. Program Dashboard: Implement Gantt chart (JS library), Dependency Map
4. Budget Dashboard: Cost Center Heatmap, Top 10 Variance
5. P&L Dashboard: Revenue/Expense breakdown charts, Scenario comparison
6. Reporting Engine: Monthly/Quarterly/Annual PDF reports with formatting

### Phase 5: Integration & Technical (Week 7-10)
1. API layer for RKAP modules
2. Accounting system integration (API design)
3. Queue workers for export/import/calculation jobs
4. Redis caching for dashboard queries
5. Email notifications for approvals
6. Frontend modernization (React/Vue) - optional per roadmap

### Phase 6: Security & Compliance (Week 2-3 parallel)
1. Document and seed RBAC roles/permissions per Section 8.1
2. Implement RKAP versioning (snapshots on status change)
3. Add soft deletes to all Erkap models

---

## 8. ESTIMATED EFFORT SUMMARY

| Category | Estimated Days | Priority |
|----------|---------------|----------|
| Critical Business Rules | 5-7 | 🔴 High |
| Missing Entities/Relationships | 3-5 | 🔴 High |
| Form Features & UX | 10-15 | 🟡 Medium |
| Dashboard & Reporting | 10-15 | 🟡 Medium |
| Integration & Technical | 15-20 | 🟢 Low (Phase 2+) |
| Security & Compliance | 3-5 | 🔴 High |
| **Total** | **46-67 days** | |

---

## 9. CONCLUSION

The codebase has a **solid foundation** with well-designed models, relationships, and core workflows. The primary gaps are:

1. **Business rule enforcement** at the database/model layer (not just controller)
2. **Missing entities** per the conceptual data model
3. **Dashboard visualizations** requiring frontend charting library
4. **Reporting engine** for formatted periodic reports
5. **Integration layer** for external systems
6. **RBAC role definitions** aligned with business matrix

The application follows the correct **risk-based budgeting hierarchy** (Sasaran → Risiko → Program → Biaya → RKAP → P&L) and has the approval workflow engine in place. With the above gaps addressed, it will fully meet the RKAP business requirements.

---

*Generated: 2026-09-21*
*Analysis based on: agents/ARKITEKTUR DAN ALUR BISNIS RKAP.md vs current codebase (Laravel 11)*