# Phase 5: Integration & Technical Architecture

**Priority**: 🟢 Low (Phase 2+)  
**Timeline**: Week 7-10 (15-20 days)  
**Dependencies**: Phase 1-4 (core functionality must be stable)

---

## 5.1 API Layer for RKAP Modules

### 5.1.1 API Structure
**Routes**: `routes/api/rkap.php` (prefix: `/api/v1/rkap`)

| Module | Endpoints | Auth |
|--------|-----------|------|
| Master Data | GET/POST `/master/*` | Sanctum |
| Company Targets | CRUD `/company-targets` | Sanctum |
| Department Targets | CRUD `/department-targets` | Sanctum |
| Risk Identifications | CRUD `/risks` | Sanctum |
| Risk Analyses | CRUD `/risks/{id}/analysis` | Sanctum |
| Department Strategies | CRUD `/risks/{id}/strategies` | Sanctum |
| Work Programs | CRUD `/work-programs` | Sanctum |
| Work Schedules | CRUD `/work-schedules` | Sanctum |
| Routine Costs | CRUD `/routine-costs` | Sanctum |
| Investment Plans | CRUD `/investment-plans` | Sanctum |
| Budget Consolidation | GET `/consolidation` | Sanctum |
| P&L | GET `/pnl` | Sanctum |
| Budget Realization | CRUD `/realization` | Sanctum |
| Risk Assessments | CRUD `/risk-assessments` | Sanctum |
| Approvals | POST `/approvals/{type}/{id}/submit` | Sanctum |
| Reports | GET `/reports/{type}` | Sanctum |

### 5.1.2 API Resources (Transformers)
**Directory**: `app/Http/Resources/Api/V1/Rkap/`

```php
// Example: WorkProgramResource.php
class WorkProgramResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'year_plan' => $this->year_plan,
            'monthly' => [
                'jan' => $this->jan_plan,
                // ... dec
            ],
            'department_target' => DepartmentTargetResource::make($this->whenLoaded('departmentTarget')),
            'risk_identification' => RiskIdentificationResource::make($this->whenLoaded('riskIdentification')),
            'has_budget' => $this->hasBudget(),
            'approval_status' => $this->approval_status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
```

### 5.1.3 API Controllers
**Base**: `app/Http/Controllers/Api/V1/Rkap/BaseApiController.php`
- Standardized response format
- Pagination, filtering, sorting
- Authorization via policies

**Example**: `WorkProgramApiController.php` extends `BaseApiController`

### 5.1.4 API Documentation
**Tool**: `knuckleswtf/scribe` (auto-generates from code)
- Run: `php artisan scribe:generate`
- Output: `public/docs/api.html`

---

## 5.2 External System Integrations

### 5.2.1 Accounting System Integration (Priority 1)
**Requirement**: Section 9.1 - "Integration untuk realisasi pendapatan dan beban (API / Database link)"

**Integration Pattern**: 
- **Pull**: Scheduled job fetches GL transactions from accounting API
- **Push**: RKAP sends budget data to accounting for budget control
- **Sync**: BudgetRealization ↔ Accounting actuals

**Implementation**:
- **Service**: `app/Services/Integration/AccountingIntegrationService.php`
- **Config**: `config/integration/accounting.php`
- **Models**: `AccountingTransaction`, `AccountingAccount` (local cache)
- **Jobs**: 
  - `FetchAccountingTransactionsJob` (daily, 02:00)
  - `SyncBudgetToAccountingJob` (on budget approval)
- **Queue**: `integration` (dedicated worker)

**Data Mapping**:
| RKAP Field | Accounting Field |
|------------|------------------|
| BudgetRealization.amount | GL Entry debit/credit |
| CostElement.chart_of_account_id | Accounting Account Code |
| CostCenter.code | Cost Center Code |
| Division.code | Department Code |

### 5.2.2 HR System Integration (Priority 2)
**Requirement**: Section 9.1 - "Data karyawan untuk assignment cost owner"

**Implementation**:
- **Service**: `app/Services/Integration/HrIntegrationService.php`
- **Sync**: Employee data → CostCenter.owner_id, WorkProgram.pic_id
- **Frequency**: Daily sync (03:00)
- **Fields**: Employee ID, Name, Email, Department, Position, Manager

### 5.2.3 Procurement Integration (Priority 3)
**Requirement**: Section 9.1 - "Data kontrak dan PO untuk realisasi investasi"

**Implementation**:
- **Service**: `app/Services/Integration/ProcurementIntegrationService.php`
- **Data**: Purchase Orders, Contracts, Goods Receipts
- **Linkage**: InvestmentPlan ↔ PO/Contract
- **Realization**: Auto-create BudgetRealization from GR/Invoice

### 5.2.4 Asset Management Integration (Priority 4)
**Requirement**: Section 9.1 - "Data aset untuk depreciation dan investasi"

**Implementation**:
- **Service**: `app/Services/Integration/AssetIntegrationService.php`
- **Data**: Asset register, depreciation schedules
- **Linkage**: InvestmentPlan (capitalized) → Asset
- **Use Case**: Capex → Asset registration → Depreciation → P&L

---

## 5.3 Queue Workers & Background Jobs

### 5.3.1 Queue Configuration
**File**: `config/queue.php`

```php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => null,
    ],
    'integration' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'integration',
        'retry_after' => 300,
    ],
    'exports' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'exports',
        'retry_after' => 600,
    ],
    'calculations' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'calculations',
        'retry_after' => 180,
    ],
],
```

### 5.3.2 Required Workers (Supervisor Config)
**File**: `/etc/supervisor/conf.d/laravel-worker.conf`

```ini
[program:laravel-worker-default]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work redis --queue=default --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2

[program:laravel-worker-integration]
command=php /path/to/artisan queue:work redis --queue=integration --sleep=5 --tries=3 --max-time=3600
numprocs=2

[program:laravel-worker-exports]
command=php /path/to/artisan queue:work redis --queue=exports --sleep=10 --tries=2 --max-time=7200
numprocs=1

[program:laravel-worker-calculations]
command=php /path/to/artisan queue:work redis --queue=calculations --sleep=3 --tries=3 --max-time=3600
numprocs=2

[program:laravel-scheduler]
command=php /path/to/artisan schedule:work
numprocs=1
```

### 5.3.3 Key Background Jobs

| Job | Queue | Trigger | Timeout |
|-----|-------|---------|---------|
| `CalculateRiskScoresJob` | calculations | RiskAnalysis created/updated | 60s |
| `ConsolidateBudgetOpexJob` | calculations | RoutineCost saved | 120s |
| `GeneratePnlScenariosJob` | calculations | P&L parameters changed | 180s |
| `ExportRkapReportJob` | exports | User requests export | 600s |
| `ImportMasterDataJob` | default | User uploads Excel | 300s |
| `FetchAccountingTransactionsJob` | integration | Scheduled (daily) | 300s |
| `SyncBudgetToAccountingJob` | integration | Budget approved | 120s |
| `SyncHrDataJob` | integration | Scheduled (daily) | 180s |
| `GenerateScheduledReportsJob` | exports | Scheduled (monthly/quarterly) | 600s |
| `SendApprovalNotificationsJob` | default | Approval requested | 30s |

---

## 5.4 Redis Caching Strategy

### 5.4.1 Cache Configuration
**File**: `config/cache.php` - Add Redis store

### 5.4.2 Cache Keys & TTL

| Data | Key Pattern | TTL | Invalidation |
|------|-------------|-----|--------------|
| Dashboard Executive Summary | `dashboard:executive:{year}:{division?}` | 15 min | On budget/realization save |
| Risk Dashboard Aggregates | `dashboard:risk:{year}:{division?}` | 15 min | On risk/assessment save |
| Program Dashboard Data | `dashboard:program:{year}:{division?}` | 15 min | On work_program/schedule save |
| Budget Dashboard Data | `dashboard:budget:{year}:{division?}` | 15 min | On routine_cost/investment save |
| P&L Dashboard Data | `dashboard:pnl:{year}:{scenario?}` | 30 min | On P&L parameter change |
| User Permissions | `permissions:{user_id}` | 1 hour | On role/permission change |
| Master Data (CoA, Cost Centers) | `master:{type}` | 24 hours | On master data change |

### 5.4.3 Cache Implementation
**Trait**: `app/Traits/CachedQueries.php`
```php
trait CachedQueries {
    protected function cached(string $key, int $ttl, Closure $callback) {
        return Cache::store('redis')->remember($key, $ttl, $callback);
    }
    
    protected function invalidate(string $pattern) {
        Cache::store('redis')->flush(); // Or use tags if using Redis tags
    }
}
```

---

## 5.5 Email Notifications for Approvals

### 5.5.1 Notification Classes
**Directory**: `app/Notifications/Rkap/`

| Notification | Trigger | Recipients |
|--------------|---------|------------|
| `ApprovalRequested` | Submission for approval | Next approver(s) |
| `ApprovalApproved` | Approval granted | Submitter + next approver |
| `ApprovalRejected` | Approval rejected | Submitter |
| `ApprovalDelegated` | Delegation | New approver + old approver |
| `ApprovalOverdue` | Daily check (overdue > 3 days) | Approver + escalation |
| `RkapVersionCreated` | New version snapshot | All stakeholders |

### 5.5.2 Email Templates
**Directory**: `resources/views/emails/rkap/approvals/`
- `requested.blade.php`
- `approved.blade.php`
- `rejected.blade.php`
- `overdue.blade.php`

### 5.5.3 Mail Configuration
**File**: `config/mail.php` - Ensure SMTP configured
**Queue**: Notifications use `default` queue (implement `ShouldQueue`)

---

## 5.6 File Storage Abstraction

### 5.6.1 Storage Configuration
**File**: `config/filesystems.php`

```php
'disks' => [
    'local' => [...],
    'public' => [...],
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('AWS_URL'),
        'endpoint' => env('AWS_ENDPOINT'),
        'visibility' => 'private',
    ],
    'reports' => [
        'driver' => 'local',
        'root' => storage_path('app/reports'),
        'visibility' => 'private',
    ],
    'exports' => [
        'driver' => 'local',
        'root' => storage_path('app/exports'),
        'visibility' => 'private',
    ],
    'imports' => [
        'driver' => 'local',
        'root' => storage_path('app/imports'),
        'visibility' => 'private',
    ],
],
```

### 5.6.2 Usage in Services
```php
// ReportGenerator
Storage::disk('reports')->put("{$reportType}/{$filename}", $pdfContent);

// Export
Storage::disk('exports')->put("{$userId}/{$filename}", $excelContent);
```

---

## 5.7 Implementation Tasks Checklist

### API Layer
- [ ] Create `routes/api/rkap.php` with all endpoints
- [ ] Create API Resources for all RKAP models
- [ ] Create BaseApiController + module controllers
- [ ] Add API authentication (Sanctum token)
- [ ] Install and configure Scribe for docs
- [ ] Write API integration tests

### Integrations
- [ ] AccountingIntegrationService + config + jobs
- [ ] HrIntegrationService + config + jobs
- [ ] ProcurementIntegrationService + config + jobs
- [ ] AssetIntegrationService + config + jobs
- [ ] Integration test suite with mock external APIs

### Queue & Jobs
- [ ] Configure Redis queues in config/queue.php
- [ ] Create all job classes listed in 5.3.3
- [ ] Set up Supervisor config for production
- [ ] Test queue processing, retries, failed jobs

### Caching
- [ ] Add Redis cache store config
- [ ] Implement CachedQueries trait
- [ ] Add cache to all dashboard services
- [ ] Add cache invalidation observers on models
- [ ] Load test dashboard with/without cache

### Notifications
- [ ] Create all approval notification classes
- [ ] Create email templates
- [ ] Configure mail (SMTP)
- [ ] Test email delivery in staging

### File Storage
- [ ] Configure disks in filesystems.php
- [ ] Update ReportGenerator to use reports disk
- [ ] Update Export services to use exports disk
- [ ] Add cleanup job for old temp files

---

## 5.8 Testing Strategy

### Integration Tests
- `ApiEndpointsTest` - All CRUD endpoints return 200/201
- `AccountingIntegrationTest` - Mock accounting API, verify sync
- `QueueJobsTest` - Jobs dispatch, process, complete successfully
- `CacheInvalidationTest` - Data change → cache cleared → fresh data

### Load Tests (k6 or Laravel Octane)
- Dashboard API response time < 200ms (with cache)
- Export job completes < 5 min for full RKAP
- Concurrent approval notifications < 1s each