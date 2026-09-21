# Phase 3: Form Features & UX Improvements

**Priority**: 🟡 Medium  
**Timeline**: Week 3-5 (10-15 days)  
**Dependencies**: Phase 1 (business rules), Phase 2 (entities)

---

## 3.1 Form 1 (Sasaran & Asesmen Risiko) - Excel Import/Export

### 3.1.1 Flat Structure Import/Export Service
**File**: `app/Services/Form1ImportExportService.php`

**Import Flow**:
1. Read Excel (Form 1 template with flat columns A-P)
2. Transform flat rows → normalized entities:
   - CompanyTarget / DepartmentTarget
   - RiskIdentification + Reasons + Impacts
   - RiskAnalysis (probability, impact, score, level)
   - DepartmentRiskStrategy
   - WorkProgram
3. Validate business rules during import
4. Transactional save with rollback on error

**Export Flow**:
1. Query normalized data with all relationships
2. Flatten to single-row-per-risk format matching Excel template
3. Generate Excel with proper formatting (headers, validation lists)

**Columns Mapping**:
| Excel Col | Field | Source |
|-----------|-------|--------|
| A | No | Auto |
| B | Sasaran Perusahaan | CompanyTarget.target |
| C | Sasaran Satuan Kerja | DepartmentTarget.target |
| D | Rating (AAA/AA/A/BB/B) | RatingCriteria.code |
| E | Identifikasi Risiko | RiskIdentification.risk |
| F | Positif/Negatif | RiskIdentification.risk_direction |
| G | Tipe Risiko | RiskIdentification.risk_type |
| H | Taksonomi | RiskIdentification.risk_taxonomy_id → name |
| I | Penyebab | RiskIdentificationReason.reason (concatenated) |
| J | Dampak | RiskIdentificationImpact.impact (concatenated) |
| K | Probabilitas (1-5) | RiskAnalysis.probability_id → value |
| L | Dampak (1-5) | RiskAnalysis.impact_id → value |
| M | Nilai Risiko (auto) | RiskAnalysis.risk_score |
| N | Peringkat (VL/L/M/H/VH) | RiskScoreLevel.level |
| O | Strategi | DepartmentRiskStrategy.strategy |
| P | Program Kerja | WorkProgram.name |

---

## 3.2 Form 2 (Jadwal Rencana Pelaksanaan) - Auto-Populate & Validation

### 3.2.1 Auto-Populate from Form 1
**File**: `app/Http/Controllers/WorkScheduleController.php` (new controller for WorkSchedule)

**Method**: `create()` / `edit()`
- Load DepartmentTarget with RiskIdentifications → Strategies → WorkPrograms
- Pre-select: Sasaran SK, Risiko, Peringkat, Program Kerja
- User only fills: Activity name, monthly breakdown

### 3.2.2 Monthly Validation (SUM = Target)
**Frontend**: JavaScript validation on blur/change
**Backend**: `WorkSchedule::validateMonthlyBreakdown()`
**UI**: Show running total, highlight mismatch in red

### 3.2.3 Budget Reference Linkage
**UI**: Add "Budget Reference" column/section
- Link to RoutineCost (Form 3) via cost_center + coa
- Link to InvestmentPlan (Form 4) via cost_center
- Show "Has Budget" badge (green/red)

---

## 3.3 Form 3 (Penyusunan Biaya Rutin) - Subtotals in UI

### 3.3.1 Subtotal Display in Create/Edit Views
**File**: `resources/views/erkap/routine-costs/form.blade.php`

**Subtotal Levels**:
1. **Per Elemen Biaya** (CoA): SUM of all cost_centers for same CoA
2. **Per Program Kerja**: SUM via WorkProgram → RoutineCost linkage
3. **Per Satuan Kerja** (Division/CostCenter): SUM for same cost_center

**Implementation**: 
- Vue/Alpine.js component for reactive subtotals
- Or server-side computed in controller, passed to view
- AJAX recalculation on row add/edit/delete

### 3.3.2 Budget vs Actual Integration Preview
**UI**: Show BudgetRealization alongside budget in form (read-only)
- Column: "Realisasi YTD"
- Column: "Variance (Budget - Actual)"
- Color coding: Green (under), Red (over)

---

## 3.4 Form 4/5 (Biaya Investasi & Anggaran Investasi)

### 3.4.1 Form 5: Ringkasan Nilai Investasi (BudgetCapex Enhancement)
**Current**: Limited to division totals  
**Required**: Full breakdown per Section 2.5

**New View**: `resources/views/erkap/budget-capex/summary.blade.php`

**Columns**:
- Kategori (SDU/PSN/OTH)
- Jenis Investasi (1-6)
- Kriteria (A-E)
- Program Kerja
- Total Investasi
- Sisa Anggaran Tahun Lalu
- Anggaran Tahun Ini
- Total Anggaran

### 3.4.2 Form 5: Distribusi Pembayaran View
**New View**: `resources/views/erkap/budget-capex/payment-distribution.blade.php`

**Matrix**: Rows = Investment Plans, Columns = Jan-Dec
- Monthly payment plans
- Row totals = Total Payment
- Column totals = Monthly cash flow requirement
- Grand total = Total Annual Capex Payment

### 3.4.3 Validation: Payment Schedule = Total Payment
**Model**: `InvestmentPlan::validatePaymentSchedule()`
- SUM(jan_plan...dec_plan) == total_payment
- Block submit if mismatch

---

## 3.5 Form 6 (Risk Assessment Monthly) - Enhancements

### 3.5.1 Business Process Mapping
**New Table**: `risk_business_processes`
```php
$table->id();
$table->foreignId('risk_assessment_monthly_id')->constrained()->cascadeOnDelete();
$table->string('process_name');
$table->text('description')->nullable();
$table->string('owner');
$table->enum('risk_level', ['low', 'medium', 'high', 'critical']);
$table->timestamps();
```

**UI**: Add "Business Process" section in Form 6 create/edit

### 3.5.2 Status Enum Enforcement
**Model**: `RiskAssessmentMonthly`
- Add `mitigation_status` as enum: `on_progress`, `done`, `overdue`
- Migration: Change column to enum or add check constraint
- Default: `on_progress`
- Auto-transition: `overdue` if target_date < today && status != done

### 3.5.3 Risk Appetite Linkage
**Model**: `RiskAssessmentMonthly`
- Add `risk_appetite_id` FK to `risk_appetites`
- UI: Select risk appetite threshold
- Dashboard: Show assessment vs appetite (traffic light)

---

## 3.6 Implementation Tasks Checklist

### Form 1
- [ ] Create Form1ImportExportService
- [ ] Add import route/controller (POST /erkap/form1/import)
- [ ] Add export route/controller (GET /erkap/form1/export)
- [ ] Create Excel template file for download
- [ ] Write import validation tests

### Form 2
- [ ] Create WorkScheduleController
- [ ] Implement auto-populate logic in create/edit
- [ ] Add monthly validation (FE + BE)
- [ ] Add budget reference linkage UI
- [ ] Update routes

### Form 3
- [ ] Add subtotal computation to RoutineCostController
- [ ] Create reactive subtotal component (Alpine.js/Vue)
- [ ] Integrate BudgetRealization preview in form
- [ ] Test with large datasets

### Form 4/5
- [ ] Enhance BudgetCapexController with summary view
- [ ] Create payment distribution view
- [ ] Add payment schedule validation
- [ ] Update InvestmentPlan model

### Form 6
- [ ] Create risk_business_processes migration + model
- [ ] Add mitigation_status enum migration
- [ ] Add risk_appetite_id FK to risk_assessment_monthlies
- [ ] Update Form 6 views with new sections
- [ ] Add overdue scheduler job

---

## 3.7 Testing Strategy

### Feature Tests
- `Form1ImportExportTest` - Round-trip import/export data integrity
- `Form2AutoPopulateTest` - Data flows from Form 1 correctly
- `Form3SubtotalsTest` - All three subtotal levels calculate correctly
- `Form45PaymentDistributionTest` - Matrix totals match
- `Form6BusinessProcessTest` - CRUD + status transitions

### Browser Tests (Laravel Dusk)
- Full Form 1 → Form 2 → Form 3 → Form 4 workflow
- Monthly validation UI feedback
- Subtotal reactive updates