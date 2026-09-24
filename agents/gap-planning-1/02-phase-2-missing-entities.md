# Phase 2: Missing Entities & Relationships

**Priority**: 🔴 High  
**Timeline**: Week 2-3 (3-5 days)  
**Dependencies**: Phase 1 (constraints should exist first)

---

## 2.1 New Entities to Create

### 2.1.1 `risk_treatments` Table (ISO 31000 Alignment)
**Purpose**: Separate risk treatment tracking from DepartmentRiskStrategy  
**Reference**: Section 3.1 ERD, Section 3.2 Master Data

**Migration**: `create_risk_treatments_table.php`

```php
Schema::create('risk_treatments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('risk_identification_id')->constrained()->cascadeOnDelete();
    $table->foreignId('department_risk_strategy_id')->nullable()->constrained()->nullOnDelete();
    $table->string('treatment_type'); // avoid, mitigate, transfer, accept
    $table->text('description');
    $table->string('responsible_party');
    $table->date('target_date');
    $table->enum('status', ['planned', 'in_progress', 'completed', 'cancelled'])->default('planned');
    $table->text('result')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['risk_identification_id', 'status']);
});
```

**Model**: `app/Models/RiskTreatment.php`  
**Relationships**:
- `belongsTo(RiskIdentification::class)`
- `belongsTo(DepartmentRiskStrategy::class)`
- `hasMany(RiskTreatmentAction::class)` - future

---

### 2.1.2 `budget_opex` Consolidation Table
**Purpose**: Consolidation layer between RoutineCost and RKAP consolidation  
**Reference**: Section 3.2 Financial ERD

**Migration**: `create_budget_opex_table.php`

```php
Schema::create('budget_opex', function (Blueprint $table) {
    $table->id();
    $table->foreignId('year')->constrained('budget_years');
    $table->foreignId('division_id')->constrained('divisions');
    $table->foreignId('cost_center_id')->constrained('cost_centers');
    $table->foreignId('chart_of_account_id')->constrained('chart_of_accounts');
    $table->decimal('budget_amount', 20, 2)->default(0);
    $table->decimal('realization_amount', 20, 2)->default(0);
    $table->decimal('variance', 20, 2)->default(0);
    $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
    $table->timestamps();
    $table->softDeletes();
    
    $table->unique(['year', 'division_id', 'cost_center_id', 'chart_of_account_id'], 'budget_opex_unique');
    $table->index(['year', 'division_id', 'status']);
});
```

**Model**: `app/Models/BudgetOpex.php`  
**Service**: `app/Services/BudgetOpexConsolidationService.php` - Aggregates from RoutineCost

---

### 2.1.3 `work_schedules` Table (Separate from WorkProgram)
**Purpose**: Form 2 is schedule, Form 1 is program - currently merged  
**Reference**: Section 3.1 ERD

**Migration**: `create_work_schedules_table.php`

```php
Schema::create('work_schedules', function (Blueprint $table) {
    $table->id();
    $table->foreignId('work_program_id')->constrained()->cascadeOnDelete();
    $table->string('activity_name');
    $table->text('description')->nullable();
    $table->integer('year_plan')->default(0);
    $table->integer('jan_plan')->default(0);
    $table->integer('feb_plan')->default(0);
    $table->integer('mar_plan')->default(0);
    $table->integer('apr_plan')->default(0);
    $table->integer('may_plan')->default(0);
    $table->integer('jun_plan')->default(0);
    $table->integer('jul_plan')->default(0);
    $table->integer('aug_plan')->default(0);
    $table->integer('sep_plan')->default(0);
    $table->integer('oct_plan')->default(0);
    $table->integer('nov_plan')->default(0);
    $table->integer('dec_plan')->default(0);
    $table->enum('status', ['draft', 'submitted', 'approved'])->default('draft');
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['work_program_id', 'status']);
});
```

**Model**: `app/Models/WorkSchedule.php`  
**Migration note**: Move monthly fields from `work_programs` to `work_schedules` (data migration needed)

---

### 2.1.4 `companies` Table (Multi-Company Support)
**Purpose**: Multi-company/group solution per business doc  
**Reference**: Section 3.2 Master Data

**Migration**: `create_companies_table.php`

```php
Schema::create('companies', function (Blueprint $table) {
    $table->id();
    $table->string('code', 10)->unique();
    $table->string('name');
    $table->string('short_name')->nullable();
    $table->text('address')->nullable();
    $table->string('npwp')->nullable();
    $table->string('logo_path')->nullable();
    $table->boolean('is_active')->default(true);
    $table->boolean('is_parent')->default(false);
    $table->foreignId('parent_company_id')->nullable()->constrained('companies')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();
});
```

**Model**: `app/Models/Company.php`  
**Relationships**: `hasMany(Division::class)`, `hasMany(CompanyTarget::class)`

**Updates needed**:
- `divisions` table: add `company_id` FK
- `company_targets` table: add `company_id` FK (currently exists?)
- `budget_years` table: add `company_id` FK

---

## 2.2 Data Migration Scripts

### 2.2.1 WorkProgram → WorkSchedule Split
**File**: `database/migrations/xxxx_split_work_programs_to_schedules.php`
- Create WorkSchedule records from existing WorkProgram monthly data
- Keep WorkProgram as high-level program (target, description, risk linkage)
- WorkSchedule becomes detailed monthly activity breakdown

### 2.2.2 RoutineCost → BudgetOpex Consolidation
**File**: `app/Console/Commands/ConsolidateBudgetOpex.php`
- Aggregate RoutineCost by year, division, cost_center, coa
- Populate BudgetOpex table
- Schedule as recurring job

---

## 2.3 Model Relationship Updates

| Model | New Relationships |
|-------|-------------------|
| `RiskIdentification` | `hasMany(RiskTreatment::class)` |
| `DepartmentRiskStrategy` | `hasMany(RiskTreatment::class)` |
| `WorkProgram` | `hasMany(WorkSchedule::class)` |
| `Division` | `belongsTo(Company::class)` |
| `CompanyTarget` | `belongsTo(Company::class)` |
| `BudgetYear` | `belongsTo(Company::class)` |

---

## 2.4 Implementation Tasks Checklist

- [ ] Create `risk_treatments` migration + model + factory + seeder
- [ ] Create `budget_opex` migration + model + consolidation service
- [ ] Create `work_schedules` migration + model + data migration from WorkProgram
- [ ] Create `companies` migration + model + update related tables
- [ ] Update Division, CompanyTarget, BudgetYear with company_id
- [ ] Write data migration scripts
- [ ] Add FK constraints for new relationships
- [ ] Update existing queries to use new entities
- [ ] Write tests for new entities and relationships

---

## 2.5 Testing Strategy

### Unit Tests
- `RiskTreatmentTest` - CRUD, status transitions, ISO 31000 types
- `BudgetOpexTest` - Consolidation accuracy, unique constraint
- `WorkScheduleTest` - Monthly validation, link to WorkProgram
- `CompanyTest` - Hierarchy, multi-company isolation

### Integration Tests
- Full risk flow: Identification → Strategy → Treatment
- Budget consolidation: RoutineCost → BudgetOpex → RKAP
- Multi-company data isolation