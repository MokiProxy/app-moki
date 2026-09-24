# Phase 6: Security & Compliance

**Priority**: 🔴 High (parallel Week 2-3)  
**Timeline**: Week 2-3 (3-5 days)  
**Dependencies**: Phase 1 (some constraints)

---

## 6.1 RBAC Role Matrix Implementation

### 6.1.1 Role Definitions (Per Section 8.1)

| Role | Description | Permissions Prefix |
|------|-------------|-------------------|
| `super_admin` | Full system access | `*` |
| `direksi` | View all, Approve RKAP, Executive Dashboard | `erkap.view.*, erkap.approve.rkap, erkap.dashboard.executive` |
| `manajemen_risiko` | Manage Risk Register, Risk Assessment, Risk Dashboard | `erkap.risk.*, erkap.dashboard.risk` |
| `ppk` | Approve Program Kerja & Anggaran | `erkap.approve.program, erkap.approve.budget` |
| `cost_owner` | Input Program Kerja, Input Anggaran, View own data | `erkap.create.program, erkap.create.budget, erkap.view.own` |
| `controller` | Review & Validasi Anggaran, Monitoring BvA | `erkap.review.budget, erkap.monitor.bva` |
| `accounting` | Input Realisasi Keuangan, P&L Data | `erkap.create.realization, erkap.pnl.*` |
| `auditor` | View only, Audit Trail access | `erkap.view.*, erkap.audit-trail.*` |

### 6.1.2 Permission Granularity

**Resource-Based Permissions** (using spatie/laravel-permission):

```php
// Permission naming convention: {module}.{action}.{resource}
'erkap.view.company-targets'
'erkap.create.company-targets'
'erkap.edit.company-targets'
'erkap.delete.company-targets'
'erkap.approve.company-targets'

'erkap.view.department-targets'
'erkap.create.department-targets'
...

'erkap.view.risk-identifications'
'erkap.create.risk-identifications'
'erkap.approve.risk-identifications'  // Risk Register approval

'erkap.view.work-programs'
'erkap.create.work-programs'
'erkap.approve.work-programs'

'erkap.view.routine-costs'
'erkap.create.routine-costs'
'erkap.approve.routine-costs'

'erkap.view.investment-plans'
'erkap.create.investment-plans'
'erkap.approve.investment-plans'

'erkap.view.budget-consolidation'
'erkap.approve.budget-consolidation'  // RKAP approval

'erkap.view.pnl'
'erkap.create.pnl-scenarios'
'erkap.approve.pnl'

'erkap.create.budget-realization'
'erkap.view.budget-realization'

'erkap.create.risk-assessments'
'erkap.view.risk-assessments'

'erkap.dashboard.executive'
'erkap.dashboard.risk'
'erkap.dashboard.program'
'erkap.dashboard.budget'
'erkap.dashboard.pnl'

'erkap.reports.generate'
'erkap.reports.view'
'erkap.audit-trail.view'
'erkap.import.master-data'
'erkap.export.all'
```

### 6.1.3 Policy Classes
**Directory**: `app/Policies/Rkap/`

| Policy | Model | Key Methods |
|--------|-------|-------------|
| `CompanyTargetPolicy` | CompanyTarget | viewAny, view, create, update, delete, approve |
| `DepartmentTargetPolicy` | DepartmentTarget | viewAny, view, create, update, delete, approve |
| `RiskIdentificationPolicy` | RiskIdentification | viewAny, view, create, update, delete, approve |
| `WorkProgramPolicy` | WorkProgram | viewAny, view, create, update, delete, approve |
| `RoutineCostPolicy` | RoutineCost | viewAny, view, create, update, delete, approve |
| `InvestmentPlanPolicy` | InvestmentPlan | viewAny, view, create, update, delete, approve |
| `BudgetConsolidationPolicy` | BudgetConsolidation | viewAny, view, approve |
| `PnlPolicy` | PnlScenario | viewAny, view, create, update, approve |
| `BudgetRealizationPolicy` | BudgetRealization | viewAny, view, create, update |
| `RiskAssessmentPolicy` | RiskAssessmentMonthly | viewAny, view, create, update |

**Authorization Logic Example**:
```php
// WorkProgramPolicy
public function create(User $user, DepartmentTarget $target): bool {
    // Cost owner can only create for their division
    if ($user->hasRole('cost_owner')) {
        return $user->costCenters()->where('division_id', $target->division_id)->exists();
    }
    // PPK/Controller can create for any
    return $user->hasAnyRole(['ppk', 'controller', 'direksi', 'super_admin']);
}

public function approve(User $user, WorkProgram $program): bool {
    return $user->hasRole('ppk') && $this->isInApprovalChain($user, $program);
}
```

### 6.1.4 Seeder for Roles & Permissions
**File**: `database/seeders/RbacSeeder.php`

```php
public function run(): void {
    // Create permissions
    $permissions = [
        // ... all permissions from 6.1.2
    ];
    foreach ($permissions as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }
    
    // Create roles with permissions
    $roles = [
        'super_admin' => ['*'],
        'direksi' => [
            'erkap.view.*', 'erkap.approve.rkap', 'erkap.dashboard.executive',
            'erkap.reports.view', 'erkap.audit-trail.view'
        ],
        'manajemen_risiko' => [
            'erkap.risk.*', 'erkap.dashboard.risk', 'erkap.reports.view'
        ],
        'ppk' => [
            'erkap.approve.program', 'erkap.approve.budget',
            'erkap.view.*', 'erkap.create.program', 'erkap.create.budget'
        ],
        'cost_owner' => [
            'erkap.create.program', 'erkap.create.budget',
            'erkap.view.own', 'erkap.dashboard.program'
        ],
        'controller' => [
            'erkap.review.budget', 'erkap.monitor.bva',
            'erkap.view.*', 'erkap.dashboard.budget'
        ],
        'accounting' => [
            'erkap.create.realization', 'erkap.pnl.*',
            'erkap.view.budget-realization'
        ],
        'auditor' => [
            'erkap.view.*', 'erkap.audit-trail.*', 'erkap.reports.view'
        ],
    ];
    
    foreach ($roles as $roleName => $perms) {
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $role->syncPermissions($perms);
    }
}
```

### 6.1.5 Middleware for Own-Data Access
**File**: `app/Http/Middleware/FilterOwnData.php`

```php
public function handle(Request $request, Closure $next): Response {
    if ($request->user()->hasRole('cost_owner')) {
        $divisionIds = $request->user()->costCenters()->pluck('division_id');
        // Apply global scope or modify query
        // Implementation depends on query structure
    }
    return $next($request);
}
```

---

## 6.2 RKAP Versioning (Snapshots)

### 6.2.1 Versioning Requirements (Section 2.8, 8.2)
- **States**: draft, submitted, approved, rejected, revised
- **Trigger**: On status change to submitted/approved/rejected
- **Snapshot**: Full RKAP state (all forms + consolidation + P&L)
- **Storage**: Separate table with JSON snapshot + metadata

### 6.2.2 Migration: `create_rkap_versions_table.php`

```php
Schema::create('rkap_versions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('budget_year_id')->constrained()->cascadeOnDelete();
    $table->string('version_number'); // v1.0, v1.1, v2.0
    $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'revised']);
    $table->json('snapshot'); // Full RKAP data snapshot
    $table->json('changes_summary')->nullable(); // What changed from previous
    $table->foreignId('created_by')->constrained('users');
    $table->foreignId('approved_by')->nullable()->constrained('users');
    $table->timestamp('approved_at')->nullable();
    $table->text('approval_notes')->nullable();
    $table->timestamps();
    
    $table->unique(['budget_year_id', 'version_number']);
    $table->index(['budget_year_id', 'status']);
});
```

### 6.2.3 Versioning Service
**File**: `app/Services/RkapVersioningService.php`

```php
class RkapVersioningService {
    public function createVersion(BudgetYear $year, string $status, User $user, ?string $notes = null): RkapVersion {
        $latest = $year->versions()->latest('version_number')->first();
        $versionNumber = $this->calculateNextVersion($latest, $status);
        
        $snapshot = $this->captureFullSnapshot($year);
        $changes = $latest ? $this->calculateChanges($latest->snapshot, $snapshot) : null;
        
        return RkapVersion::create([
            'budget_year_id' => $year->id,
            'version_number' => $versionNumber,
            'status' => $status,
            'snapshot' => $snapshot,
            'changes_summary' => $changes,
            'created_by' => $user->id,
            'approved_by' => $status === 'approved' ? $user->id : null,
            'approved_at' => $status === 'approved' ? now() : null,
            'approval_notes' => $notes,
        ]);
    }
    
    private function captureFullSnapshot(BudgetYear $year): array {
        return [
            'company_targets' => CompanyTargetResource::collection($year->companyTargets)->resolve(),
            'department_targets' => DepartmentTargetResource::collection($year->departmentTargets)->resolve(),
            'risk_identifications' => RiskIdentificationResource::collection($year->riskIdentifications)->resolve(),
            'work_programs' => WorkProgramResource::collection($year->workPrograms)->resolve(),
            'routine_costs' => RoutineCostResource::collection($year->routineCosts)->resolve(),
            'investment_plans' => InvestmentPlanResource::collection($year->investmentPlans)->resolve(),
            'budget_consolidation' => BudgetConsolidationResource::make($year->consolidation)->resolve(),
            'pnl' => PnlResource::collection($year->pnlScenarios)->resolve(),
            'captured_at' => now()->toISOString(),
        ];
    }
}
```

### 6.2.4 Integration Points
- **ApprovalService**: Call `createVersion()` on status transitions
- **UI**: Version history tab in RKAP dashboard
- **Rollback**: "Revert to version" creates new revised version
- **Comparison**: Diff view between two versions

---

## 6.3 Soft Deletes on All Erkap Models

### 6.3.1 Models Needing Soft Deletes
| Model | Current | Action |
|-------|---------|--------|
| CompanyTarget | ❌ | Add `SoftDeletes` trait |
| DepartmentTarget | ❌ | Add `SoftDeletes` trait |
| RiskIdentification | ❌ | Add `SoftDeletes` trait |
| RiskAnalysis | ❌ | Add `SoftDeletes` trait |
| DepartmentRiskStrategy | ❌ | Add `SoftDeletes` trait |
| WorkProgram | ❌ | Add `SoftDeletes` trait |
| WorkSchedule | ❌ (new) | Add `SoftDeletes` trait |
| RoutineCost | ❌ | Add `SoftDeletes` trait |
| InvestmentPlan | ❌ | Add `SoftDeletes` trait |
| BudgetConsolidation | ❌ | Add `SoftDeletes` trait |
| PnlScenario | ❌ | Add `SoftDeletes` trait |
| BudgetRealization | ❌ | Add `SoftDeletes` trait |
| RiskAssessmentMonthly | ❌ | Add `SoftDeletes` trait |
| PerformanceScorecard | ❌ | Add `SoftDeletes` trait |

### 6.3.2 Migration Pattern
```php
// For each table
Schema::table('table_name', function (Blueprint $table) {
    $table->softDeletes(); // adds deleted_at
    $table->index('deleted_at'); // for performance
});
```

### 6.3.3 Global Scope (Optional)
```php
// In base model or trait
protected static function booted() {
    static::addGlobalScope(new WithoutTrashedScope); // default: exclude deleted
}
```

---

## 6.4 Audit Trail Enhancement

### 6.4.1 Current State
- `HasAuditTrail` trait exists (✅)
- But not on all Erkap models
- Missing: `created_by`, `updated_by` on some models

### 6.4.2 Required Fields on All Erkap Models
```php
$table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
$table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
$table->timestamp('created_at')->useCurrent();
$table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
```

### 6.4.3 Observer for Auto-Fill
**File**: `app/Observers/AuditObserver.php`

```php
class AuditObserver {
    public function creating(Model $model): void {
        if (auth()->check()) {
            $model->created_by = auth()->id();
            $model->updated_by = auth()->id();
        }
    }
    
    public function updating(Model $model): void {
        if (auth()->check()) {
            $model->updated_by = auth()->id();
        }
    }
}
```

**Register in `AppServiceProvider`**:
```php
foreach ([
    CompanyTarget::class, DepartmentTarget::class, RiskIdentification::class,
    // ... all erkap models
] as $model) {
    $model::observe(AuditObserver::class);
}
```

---

## 6.5 Implementation Tasks Checklist

### RBAC
- [ ] Define all permissions in seeder (6.1.2)
- [ ] Create roles with permission mapping (6.1.3)
- [ ] Create Policy classes for all RKAP models (6.1.3)
- [ ] Register policies in `AuthServiceProvider`
- [ ] Apply policies in controllers (`$this->authorize()`)
- [ ] Add FilterOwnData middleware for cost_owner
- [ ] Run RbacSeeder
- [ ] Test all role permission combinations

### Versioning
- [ ] Create rkap_versions migration
- [ ] Create RkapVersion model
- [ ] Create RkapVersioningService
- [ ] Integrate with ApprovalService (on status change)
- [ ] Add version history UI (index, show, compare)
- [ ] Add revert functionality (creates revised version)

### Soft Deletes
- [ ] Create migrations for all 14 models
- [ ] Add SoftDeletes trait to all models
- [ ] Add deleted_at index to all migrations
- [ ] Update queries to handle soft deletes (withTrashed where needed)
- [ ] Add "Restore" and "Force Delete" in admin UI

### Audit Trail
- [ ] Add created_by/updated_by to all Erkap model migrations
- [ ] Create AuditObserver
- [ ] Register observer for all Erkap models
- [ ] Verify HasAuditTrail trait works with new fields
- [ ] Add audit trail view in admin (filter by model, user, date)

---

## 6.6 Testing Strategy

### Feature Tests
- `RbacTest` - Each role can/cannot access expected resources
- `PolicyTest` - All policy methods return correct boolean
- `VersioningTest` - Versions created on approval, snapshots accurate
- `SoftDeletesTest` - Deleted models hidden, restorable, force-deletable
- `AuditTrailTest` - created_by/updated_by auto-filled, HasAuditTrail logs changes

### Security Tests
- `AuthorizationBypassTest` - Attempt API/controller access without permission
- `OwnDataIsolationTest` - cost_owner only sees own division data
- `SqlInjectionTest` - Input validation on all endpoints