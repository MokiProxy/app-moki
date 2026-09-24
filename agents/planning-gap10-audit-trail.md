# Planning Implementasi: Audit Trail & RBAC Bisnis

## 1. Deskripsi Gap

**Status Saat Ini**:
- Tidak ada kolom `created_by`/`updated_by` di tabel ERKAP
- Tidak ada model history/audit log untuk ERKAP
- Role bisnis (PPK, Controller, Accounting, Manajemen Risiko, Direksi, Auditor) belum di-seed
- Tidak ada unique constraint untuk beberapa kolom
- Tidak ada check constraint non-negatif numerik

**Yang Diperlukan**:
- Kolom `created_by`/`updated_by` di semua tabel ERKAP
- Tabel `erkap_audit_logs` untuk audit trail lengkap
- Role bisnis sesuai matriks 8.1
- Unique constraint untuk kode (cost center, elemen biaya)
- Check constraint non-negatif untuk kolom numerik

**Dampak**: Tidak ada jejak audit, tidak ada role bisnis untuk approval

## 2. Solusi yang Direkomendasikan

### 2.1 Kolom `created_by`/`updated_by`

```sql
-- Tambah kolom ke semua tabel ERKAP
ALTER TABLE erkap_rkap 
ADD COLUMN created_by BIGINT UNSIGNED NULL,
ADD COLUMN updated_by BIGINT UNSIGNED NULL,
ADD FOREIGN KEY (created_by) REFERENCES users(id),
ADD FOREIGN KEY (updated_by) REFERENCES users(id);

-- Ulangi untuk semua tabel ERKAP lainnya
```

### 2.2 Tabel Baru: `erkap_audit_logs`

```sql
CREATE TABLE erkap_audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    auditable_type VARCHAR(255) NOT NULL,
    auditable_id BIGINT UNSIGNED NOT NULL,
    action ENUM('create', 'update', 'delete') NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at TIMESTAMP NULL,
    INDEX (auditable_type, auditable_id),
    INDEX (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### 2.3 Role Bisnis

```php
// Di RolePermissionSeeder
$roles = [
    'erkap-ppk' => [
        'erkap.work_program.view',
        'erkap.work_program.approve',
        'erkap.routine_cost.view',
        'erkap.routine_cost.approve',
        'erkap.investment_plan.view',
        'erkap.investment_plan.approve',
    ],
    'erkap-controller' => [
        'erkap.work_program.view',
        'erkap.work_program.approve',
        'erkap.routine_cost.view',
        'erkap.routine_cost.approve',
        'erkap.budget_capex.view',
        'erkap.budget_capex.approve',
        'erkap.profit_loss.view',
    ],
    'erkap-accounting' => [
        'erkap.routine_cost.view',
        'erkap.investment_plan.view',
        'erkap.budget_realization.view',
        'erkap.profit_loss.view',
    ],
    'erkap-risk-manager' => [
        'erkap.risk_identification.view',
        'erkap.risk_analysis.view',
        'erkap.risk_assessment_monthly.view',
    ],
    'erkap-direksi' => [
        'erkap.rkap.view',
        'erkap.rkap.approve',
        'erkap.profit_loss.view',
        'erkap.dashboard.view',
    ],
    'erkap-auditor' => [
        'erkap.*.view',
        'erkap.audit_log.view',
    ],
];
```

## 3. Langkah-langkah Implementasi

### Phase 1: Migration (2-3 hari)

1. **Buat Migration `2026_09_11_000022_add_audit_columns_to_erkap_tables.php`**
   ```php
   public function up()
   {
       $tables = [
           'erkap_rkap',
           'erkap_company_targets',
           'erkap_department_targets',
           'erkap_risk_identifications',
           'erkap_risk_identification_reasons',
           'erkap_risk_identification_impacts',
           'erkap_risk_analysis',
           'erkap_risk_rankings',
           'erkap_department_risk_strategies',
           'erkap_work_programs',
           'erkap_routine_costs',
           'erkap_cost_elements',
           'erkap_cost_element_categories',
           'erkap_risk_appetites',
           'erkap_risk_taxonomies',
           'erkap_risk_types',
           'erkap_risk_scales',
           'erkap_risk_probabilities',
           'erkap_risk_impacts',
           'erkap_risk_score_levels',
           'erkap_rating_criterias',
           'erkap_investattion_categories',
           'erkap_investation_types',
           'erkap_investation_criterias',
       ];
       
       foreach ($tables as $table) {
           Schema::table($table, function (Blueprint $table) {
               $table->unsignedBigInteger('created_by')->nullable()->after('id');
               $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
               $table->foreign('created_by')->references('id')->on('users');
               $table->foreign('updated_by')->references('id')->on('users');
           });
       }
   }
   ```

2. **Buat Migration `2026_09_11_000023_create_erkap_audit_logs_table.php`**

3. **Buat Migration `2026_09_11_000024_add_constraints_to_erkap_tables.php`**
   ```php
   public function up()
   {
       // Unique constraint untuk kode
       Schema::table('erkap_cost_elements', function (Blueprint $table) {
           $table->unique('code');
       });
       
       Schema::table('erkap_investation_types', function (Blueprint $table) {
           $table->unique('code');
       });
       
       Schema::table('erkap_investation_criterias', function (Blueprint $table) {
           $table->unique('code');
       });
       
       Schema::table('erkap_investattion_categories', function (Blueprint $table) {
           $table->unique('code');
       });
       
       // Check constraint non-negatif (MySQL tidak support check constraint, skip)
       // Alternatif: gunakan validasi di application level
   }
   ```

### Phase 2: Model & Trait (2 hari)

1. **Buat Trait `HasAuditTrail.php`**
   ```php
   trait HasAuditTrail
   {
       public static function bootHasAuditTrail()
       {
           static::created(function ($model) {
               $model->update(['created_by' => auth()->id()]);
               $model->logAudit('create', null, $model->toArray());
           });
           
           static::updated(function ($model) {
               $model->update(['updated_by' => auth()->id()]);
               $model->logAudit('update', $model->getOriginal(), $model->toArray());
           });
           
           static::deleted(function ($model) {
               $model->logAudit('delete', $model->toArray(), null);
           });
       }
       
       public function logAudit($action, $oldValues, $newValues)
       {
           AuditLog::create([
               'user_id' => auth()->id(),
               'auditable_type' => get_class($this),
               'auditable_id' => $this->id,
               'action' => $action,
               'old_values' => $oldValues,
               'new_values' => $newValues,
               'ip_address' => request()->ip(),
               'user_agent' => request()->userAgent(),
           ]);
       }
       
       public function audits()
       {
           return $this->morphMany(AuditLog::class, 'auditable');
       }
   }
   ```

2. **Buat Model `AuditLog.php`**
   ```php
   class AuditLog extends Model
   {
       protected $table = 'erkap_audit_logs';
       
       protected $casts = [
           'old_values' => 'json',
           'new_values' => 'json',
       ];
       
       public function auditable()
       {
           return $this->morphTo();
       }
       
       public function user()
       {
           return $this->belongsTo(User::class);
       }
   }
   ```

3. **Update Semua Model ERKAP**
   - Tambah `use HasAuditTrail;`
   - Tambah `$fillable` termasuk `created_by`, `updated_by`

### Phase 3: Controller & Service (1-2 hari)

1. **Buat Controller `AuditLogController.php`**
   ```php
   class AuditLogController extends Controller
   {
       public function index(Request $request)
       {
           $query = AuditLog::with('user');
           
           if ($request->has('auditable_type')) {
               $query->where('auditable_type', $request->auditable_type);
           }
           
           if ($request->has('user_id')) {
               $query->where('user_id', $request->user_id);
           }
           
           if ($request->has('action')) {
               $query->where('action', $request->action);
           }
           
           $logs = $query->latest()->paginate(50);
           
           return view('erkap.audit-logs.index', compact('logs'));
       }
       
       public function show($id)
       {
           $log = AuditLog::with('user', 'auditable')->findOrFail($id);
           
           return view('erkap.audit-logs.show', compact('log'));
       }
   }
   ```

2. **Buat Service `AuditService.php`**
   ```php
   class AuditService
   {
       public function getHistory($model)
       {
           return $model->audits()
               ->with('user')
               ->latest()
               ->get();
       }
       
       public function diff($oldValues, $newValues)
       {
           $changes = [];
           
           foreach ($newValues as $key => $value) {
               if (isset($oldValues[$key]) && $oldValues[$key] !== $value) {
                   $changes[$key] = [
                       'old' => $oldValues[$key],
                       'new' => $value,
                   ];
               }
           }
           
           return $changes;
       }
   }
   ```

### Phase 4: View/Blade (2 hari)

1. **Buat `resources/views/erkap/audit-logs/index.blade.php`**
   - Tabel daftar audit log
   - Filter berdasarkan user, action, type
   - Kolom: Waktu, User, Action, Model, Changes

2. **Buat `resources/views/erkap/audit-logs/show.blade.php`**
   - Detail audit log
   - Perbandingan old vs new values
   - Info user (IP, user agent)

3. **Update View yang Sudah Ada**
   - Tampilkan info "Dibuat oleh: {user}" di footer
   - Tampilkan info "Diubah oleh: {user}" saat ada update

### Phase 5: Seeder Role Bisnis (1 hari)

1. **Update Seeder `RolePermissionSeeder.php`**
   - Tambah semua role bisnis
   - Tambah permissions untuk approval

2. **Buat Seeder `UserRoleSeeder.php`**
   - Assign user ke role bisnis untuk testing

## 4. File yang Perlu Dibuat/Diubah

### File Baru:
- `database/migrations/2026_09_11_000022_add_audit_columns_to_erkap_tables.php`
- `database/migrations/2026_09_11_000023_create_erkap_audit_logs_table.php`
- `database/migrations/2026_09_11_000024_add_constraints_to_erkap_tables.php`
- `app/Models/Erkap/AuditLog.php`
- `app/Traits/HasAuditTrail.php`
- `app/Services/AuditService.php`
- `app/Http/Controllers/Erkap/AuditLogController.php`
- `app/Http/Requests/Erkap/FilterAuditLogRequest.php`
- `database/seeders/UserRoleSeeder.php`
- `resources/views/erkap/audit-logs/index.blade.php`
- `resources/views/erkap/audit-logs/show.blade.php`

### File yang Diubah:
- Semua model di `app/Models/Erkap/` - tambah trait `HasAuditTrail`
- `database/seeders/RolePermissionSeeder.php` - tambah role bisnis
- `routes/routers/erkap.php` - tambah route audit log
- Semua view ERKAP - tambah info created_by/updated_by

## 5. Estimasi Effort

| Task | Effort (hari) |
|------|--------------|
| Migration | 2-3 |
| Model & Trait | 2 |
| Controller & Service | 1-2 |
| View/Blade | 2 |
| Seeder Role Bisnis | 1 |
| Testing | 1-2 |
| **Total** | **9-12** |

## 6. Dependency

- Semua tabel ERKAP sudah ada
- Tabel `users` sudah ada
- Spatie Permission sudah terinstall

## 7. Risk & Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| Data existing tidak punya created_by | Query salah | Jalankan script backfill |
| Performance audit log | Lambat | Gunakan index & caching |
| Audit log terlalu besar | Storage penuh | Implementasi archival |
| Role tidak lengkap | Approval gagal | Seed role lengkap |