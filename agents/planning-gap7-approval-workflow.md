# Planning Implementasi: Approval Workflow & Status Dokumen

## 1. Deskripsi Gap

**Status Saat Ini**:
- Tidak ada kolom `status` di `erkap_rkap` dan entitas turunannya
- Tidak ada tabel `approvals`
- Tidak ada route approval
- Tidak ada lifecycle draft/submitted/approved/rejected
- Tidak ada notifikasi approval
- Tidak ada role bisnis (PPK, Controller, Direksi, dll)

**Yang Diperlukan**:
- Kolom `status` pada semua dokumen ERKAP
- Tabel `erkap_approvals` untuk approval history
- Lifecycle: draft → submitted → approved/rejected
- Multi-level approval sesuai matriks
- Notifikasi saat status berubah
- Role bisnis: PPK, Controller, Accounting, Manajemen Risiko, Direksi, Auditor

**Dampak**: Tidak ada mekanisme approval sama sekali untuk dokumen ERKAP

## 2. Solusi yang Direkomendasikan

### 2.1 Kolom Status di Semua Dokumen

```sql
-- Tambah kolom status ke tabel yang relevan
ALTER TABLE erkap_rkap ADD COLUMN status ENUM('draft', 'submitted', 'approved', 'rejected', 'revised') DEFAULT 'draft';
ALTER TABLE erkap_work_programs ADD COLUMN status ENUM('draft', 'submitted', 'approved', 'rejected') DEFAULT 'draft';
ALTER TABLE erkap_routine_costs ADD COLUMN status ENUM('draft', 'submitted', 'approved', 'rejected') DEFAULT 'draft';
ALTER TABLE erkap_investment_plans ADD COLUMN status ENUM('draft', 'submitted', 'approved', 'rejected') DEFAULT 'draft';
ALTER TABLE erkap_budget_capex ADD COLUMN status ENUM('draft', 'submitted', 'approved', 'rejected') DEFAULT 'draft';
```

### 2.2 Tabel Baru: `erkap_approvals`

```sql
CREATE TABLE erkap_approvals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    approvalable_type VARCHAR(255) NOT NULL,
    approvalable_id BIGINT UNSIGNED NOT NULL,
    level TINYINT NOT NULL DEFAULT 1,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approver_id BIGINT UNSIGNED NOT NULL,
    notes TEXT NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX (approvalable_type, approvalable_id),
    FOREIGN KEY (approver_id) REFERENCES users(id)
);
```

### 2.3 Matriks Approval

```php
// Di ApprovalService
public static function getApprovalMatrix()
{
    return [
        'work_program' => [
            1 => 'erkap-cost-owner',           // Level 1: Cost Owner
            2 => 'erkap-ppk',                  // Level 2: PPK
            3 => 'erkap-controller',           // Level 3: Controller
        ],
        'routine_cost' => [
            1 => 'erkap-cost-owner',
            2 => 'erkap-ppk',
            3 => 'erkap-controller',
        ],
        'investment_plan' => [
            1 => 'erkap-cost-owner',
            2 => 'erkap-ppk',
            3 => 'erkap-direksi-keuangan',
        ],
        'rkap' => [
            1 => 'erkap-controller',
            2 => 'erkap-direksi',
            3 => 'erkap-komisaris',
        ],
    ];
}
```

## 3. Langkah-langkah Implementasi

### Phase 1: Migration & Seeder (1-2 hari)

1. **Buat Migration `2026_09_11_000016_add_status_columns_to_erkap_tables.php`**
   - Tambah kolom `status` ke tabel yang relevan

2. **Buat Migration `2026_09_11_000017_create_erkap_approvals_table.php`**

3. **Update Seeder `RolePermissionSeeder.php`**
   - Tambah role: `erkap-ppk`, `erkap-controller`, `erkap-accounting`, `erkap-risk-manager`, `erkap-direksi`, `erkap-auditor`
   - Tambah permissions: `erkap.*.approve`, `erkap.*.reject`

### Phase 2: Model & Service (2 hari)

1. **Buat Model `Approval.php`**
   ```php
   class Approval extends Model
   {
       protected $table = 'erkap_approvals';
       
       public function approvalable()
       {
           return $this->morphTo();
       }
       
       public function approver()
       {
           return $this->belongsTo(User::class);
       }
       
       public function approve($notes = null)
       {
           $this->update([
               'status' => 'approved',
               'notes' => $notes,
               'approved_at' => now(),
           ]);
           
           // Cek apakah semua level sudah approve
           $this->checkCompletion();
       }
       
       public function reject($notes = null)
       {
           $this->update([
               'status' => 'rejected',
               'notes' => $notes,
               'approved_at' => now(),
           ]);
           
           // Update status parent
           $this->approvalable()->update(['status' => 'rejected']);
       }
       
       private function checkCompletion()
       {
           $totalLevels = self::where('approvalable_type', $this->approvalable_type)
               ->where('approvalable_id', $this->approvalable_id)
               ->count();
           
           $approvedLevels = self::where('approvalable_type', $this->approvalable_type)
               ->where('approvalable_id', $this->approvalable_id)
               ->where('status', 'approved')
               ->count();
           
           if ($approvedLevels === $totalLevels) {
               $this->approvalable()->update(['status' => 'approved']);
           }
       }
   }
   ```

2. **Buat Service `ApprovalService.php`**
   ```php
   class ApprovalService
   {
       public function submit($model, $type)
       {
           $model->update(['status' => 'submitted']);
           $this->createApprovals($model, $type);
           $this->sendNotifications($model, $type);
       }
       
       private function createApprovals($model, $type)
       {
           $matrix = ApprovalService::getApprovalMatrix()[$type];
           
           foreach ($matrix as $level => $role) {
               Approval::create([
                   'approvalable_type' => get_class($model),
                   'approvalable_id' => $model->id,
                   'level' => $level,
                   'approver_id' => $this->getApproverByRole($role),
               ]);
           }
       }
       
       private function sendNotifications($model, $type)
       {
           // Kirim notifikasi ke approver level 1
           $approval = Approval::where('approvalable_type', get_class($model))
               ->where('approvalable_id', $model->id)
               ->where('level', 1)
               ->first();
           
           if ($approval) {
               Notification::send($approval->approver, new ApprovalNotification($model, $type));
           }
       }
   }
   ```

3. **Update Model yang Sudah Ada**
   - Tambah relasi `approvals()` morphMany
   - Tambah method `submit()`, `approve()`, `reject()`

### Phase 3: Controller & Route (2-3 hari)

1. **Buat Controller `ApprovalController.php`**
   - `index()` - daftar approval yang menunggu
   - `show()` - detail dokumen + approval history
   - `approve()` - approve dokumen
   - `reject()` - reject dokumen
   - `history()` - history approval

2. **Update Route `routes/routers/erkap.php`**
   ```php
   Route::prefix('approvals')->name('approvals.')->group(function () {
       Route::get('/', [ApprovalController::class, 'index'])->name('index');
       Route::get('/{approvalable_type}/{approvalable_id}', [ApprovalController::class, 'show'])->name('show');
       Route::post('/{approvalable_type}/{approvalable_id}/approve', [ApprovalController::class, 'approve'])->name('approve');
       Route::post('/{approvalable_type}/{approvalable_id}/reject', [ApprovalController::class, 'reject'])->name('reject');
       Route::get('/{approvalable_type}/{approvalable_id}/history', [ApprovalController::class, 'history'])->name('history');
   });
   ```

3. **Update Controller yang Sudah Ada**
   - Tambah method `submit()` untuk submit dokumen
   - Tambah validasi status transition

### Phase 4: View/Blade (2-3 hari)

1. **Buat `resources/views/erkap/approvals/index.blade.php`**
   - Daftar dokumen yang menunggu approval
   - Filter berdasarkan type dan status
   - Tombol approve/reject

2. **Buat `resources/views/erkap/approvals/show.blade.php`**
   - Detail dokumen
   - Approval history (siapa, kapan, status)
   - Form approve/reject dengan notes

3. **Update View yang Sudah Ada**
   - Tambah tombol "Submit" saat status draft
   - Tambah badge status (draft/submitted/approved/rejected)
   - Tambah approval history section

### Phase 5: Notifikasi (1 hari)

1. **Buat Notification `ApprovalNotification.php`**
   ```php
   class ApprovalNotification extends Notification
   {
       public function __construct($model, $type)
       {
           $this->model = $model;
           $this->type = $type;
       }
       
       public function via($notifiable)
       {
           return ['database', 'mail'];
       }
       
       public function toArray($notifiable)
       {
           return [
               'type' => 'approval_required',
               'document_type' => $this->type,
               'document_id' => $this->model->id,
               'message' => "Dokumen {$this->type} #{$this->model->id} membutuhkan approval",
               'url' => route('erkap.approvals.show', [$this->type, $this->model->id]),
           ];
       }
   }
   ```

## 4. File yang Perlu Dibuat/Diubah

### File Baru:
- `database/migrations/2026_09_11_000016_add_status_columns_to_erkap_tables.php`
- `database/migrations/2026_09_11_000017_create_erkap_approvals_table.php`
- `app/Models/Erkap/Approval.php`
- `app/Services/ApprovalService.php`
- `app/Http/Controllers/Erkap/ApprovalController.php`
- `app/Http/Requests/Erkap/StoreApprovalRequest.php`
- `app/Notifications/ApprovalNotification.php`
- `resources/views/erkap/approvals/index.blade.php`
- `resources/views/erkap/approvals/show.blade.php`

### File yang Diubah:
- `database/seeders/RolePermissionSeeder.php` - tambah role & permissions
- `app/Models/Erkap/RKAP.php` - tambah relasi approvals
- `app/Models/Erkap/WorkProgram.php` - tambah relasi approvals
- `app/Models/Erkap/RoutineCost.php` - tambah relasi approvals
- `app/Http/Controllers/Erkap/RKAPController.php` - tambah method submit
- `app/Http/Controllers/Erkap/WorkProgramController.php` - tambah method submit
- `app/Http/Controllers/Erkap/RoutineCostController.php` - tambah method submit
- `resources/views/erkap/rkap/index.blade.php` - tambah tombol submit
- `resources/views/erkap/work-programs/index.blade.php` - tambah tombol submit
- `resources/views/erkap/routine-costs/index.blade.php` - tambah tombol submit
- `routes/routers/erkap.php` - tambah route approvals

## 5. Estimasi Effort

| Task | Effort (hari) |
|------|--------------|
| Migration & Seeder | 1-2 |
| Model & Service | 2 |
| Controller & Route | 2-3 |
| View/Blade | 2-3 |
| Notifikasi | 1 |
| Testing | 1-2 |
| **Total** | **9-13** |

## 6. Dependency

- Semua tabel dokumen ERKAP sudah ada
- Table `users` sudah ada
- Table `roles` sudah ada (Spatie Permission)

## 7. Risk & Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| Role tidak lengkap | Approval gagal | Seed role lengkap |
| Concurrent approval | Race condition | Gunakan locking |
| Notifikasi tidak terkirim | User tidak tahu | Fallback ke email |
| Status tidak konsisten | Error | Gunakan transaksi DB |