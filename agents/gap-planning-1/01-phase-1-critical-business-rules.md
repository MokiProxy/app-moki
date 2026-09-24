# Phase 1: Critical Business Rules Enforcement

**Priority**: 🔴 High  
**Timeline**: Week 1-2 (5-7 days)  
**Dependencies**: None (foundational)

---

## 1.1 Database Constraints Implementation

### Check Constraints
| Table | Column | Constraint | Business Rule |
|-------|--------|------------|---------------|
| `risk_analyses` | `probability` | `CHECK (probability BETWEEN 1 AND 5)` | Section 8.3 |
| `risk_analyses` | `impact` | `CHECK (impact BETWEEN 1 AND 5)` | Section 8.3 |
| `investment_plans` | `quantity` | `CHECK (quantity > 0)` | Qty must be positive |
| `investment_plans` | `unit_price` | `CHECK (unit_price >= 0)` | Price non-negative |
| `investment_plans` | `total_investment` | `CHECK (total_investment = quantity * unit_price)` | Section 2.5 |
| `investment_plans` | `total_payment` | `CHECK (total_payment = jan_plan + feb_plan + ... + dec_plan)` | Section 2.5 |

### Unique Constraints
| Table | Columns | Business Rule |
|-------|---------|---------------|
| `cost_centers` | `code` | Section 8.3 |
| `chart_of_accounts` | `code` | Section 8.3 |
| `work_programs` | `code` | Section 8.3 |
| `cost_elements` | `code` | Section 8.3 |

### Foreign Key Constraints (Missing)
| Child Table | Column | Parent Table | Parent Column |
|-------------|--------|--------------|---------------|
| `routine_costs` | `cost_center_id` | `cost_centers` | `id` |
| `investment_plans` | `cost_center_id` | `cost_centers` | `id` |
| `work_programs` | `department_target_id` | `department_targets` | `id` |
| `department_risk_strategies` | `risk_identification_id` | `risk_identifications` | `id` |

---

## 1.2 Model-Level Business Rule Enforcement

### 1.2.1 Program Kerja Requires Rating A+ (Minimum A)
**File**: `app/Models/WorkProgram.php`  
**Method**: Add `booted()` with `creating` observer  
**Validation**: `departmentTarget->rating->min_level >= 'A'`  
**Error**: "Program Kerja hanya bisa dibuat untuk Sasaran dengan Rating A ke atas"

### 1.2.2 Every Risk Must Have Strategy + Program Kerja
**File**: `app/Models/RiskIdentification.php`  
**Method**: `booted()` with `deleted` observer + validation in `DepartmentRiskStrategy`  
**Validation**: On RiskIdentification delete → check for strategies/programs  
**On Strategy create**: Verify risk exists  
**On WorkProgram create**: Verify linked risk has strategy

### 1.2.3 WorkProgram Requires Budget (Form 3/4) Before Approval
**File**: `app/Models/WorkProgram.php`  
**Method**: `canSubmitForApproval()` / `approve()`  
**Validation**: `hasBudget()` must return true (has RoutineCost OR InvestmentPlan)  
**Error**: "Program Kerja wajib memiliki anggaran (Form 3 atau Form 4) sebelum disetujui"

### 1.2.4 Monthly Breakdown Validation
**Files**: `WorkProgram.php`, `RoutineCost.php`, `InvestmentPlan.php`  
**Method**: `validateMonthlyBreakdown()`  
**Rule**: `SUM(jan_plan...dec_plan) == year_plan`  
**Error**: "Total bulanan harus sama dengan target tahunan"

---

## 1.3 Approval Matrix Fix

### 1.3.1 RKAP Approval Order Correction
**Current**: Controller → Direksi → Komisaris  
**Required**: Komisaris → Direksi Utama (per Section 1.4)

**Files to modify**:
- `app/Services/ApprovalService.php` - Update matrix for `rkap` type
- `database/seeders/ApprovalMatrixSeeder.php` - Fix seed data

### 1.3.2 Risk Register Approval Workflow
**New**: Add approval workflow for `RiskIdentification`  
**Approver**: Komite Manajemen Risiko (per Section 1.4)  
**Files**:
- `app/Models/RiskIdentification.php` - Add `HasApproval` trait
- `app/Services/ApprovalService.php` - Add risk register matrix
- Migration: Add `approval_status`, `approved_by`, `approved_at` to `risk_identifications`

---

## 1.4 Implementation Tasks Checklist

- [ ] Create migration for check constraints
- [ ] Create migration for unique constraints  
- [ ] Create migration for missing foreign keys
- [ ] Add model observers for business rules (WorkProgram, RiskIdentification)
- [ ] Add validation methods to models
- [ ] Update ApprovalService matrix for RKAP
- [ ] Add RiskIdentification approval workflow
- [ ] Write tests for all business rule validations
- [ ] Run migrations and verify constraints
- [ ] Update seeders with correct approval matrix

---

## 1.5 Testing Strategy

### Unit Tests
- `WorkProgramBusinessRulesTest` - Rating validation, budget requirement
- `RiskIdentificationBusinessRulesTest` - Strategy/program requirement
- `MonthlyBreakdownValidationTest` - All three models
- `ApprovalMatrixTest` - RKAP order, Risk Register flow

### Integration Tests
- Full approval flow for RKAP with corrected matrix
- Risk register approval with Komite Manajemen Risiko
- API bypass attempts for business rules (should fail)