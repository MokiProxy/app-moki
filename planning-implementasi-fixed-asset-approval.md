# Planning Implementasi Fitur Fixed Asset Approval

## 1. Overview

### Tujuan
Membuat fitur peminjaman fixed asset IT dengan alur:
1. **User/Pemohon** mengisi form pengajuan peminjaman
2. Form tersimpan dengan status `menunggu approval`
3. **Approver** (dengan role/permission tertentu) melakukan approval dan melengkapi data penyerahan

### Alur Sistem
```
Pemohon Submit → Status: pending → Approver Review → Input Data Penyerahkan → Status: approved/rejected
```

---

## 2. Analisis Kodebase Saat Ini

### Struktur Database yang Ada
| Table | Deskripsi |
|-------|-----------|
| `assets` | Data master aset |
| `asset_assignments` | Penugasan aset ke karyawan |
| `formit_software_installations` | Pengajuan install software |
| `formit_approvals` | Record approval untuk form IT |
| `employees` | Data karyawan |
| `users` | User login (Spatie Permission) |

### Struktur Approval yang Sudah Ada (Software Installation)
- Model: `SoftwareInstallation` & `FormitApproval`
- Controller: `FormIT\FormController` & `FormIT\ApprovalController`
- Middleware: `EnsureUserIsApprover` (cek apakah user ada di table `formit_approvals`)
- Routes: `routes/routers/form-it.php`

### Permission & Role yang Sudah Ada
- **Roles:** super-admin, approver, staff, teknisi, admin
- **Form-IT Permissions:**
  - `form-it.dashboard`
  - `form-it.forms.view`
  - `form-it.forms.create`
  - `form-it.approval.view`
  - `form-it.approval.process`

---

## 3. Database Design

### 3.1 Table Baru: `formit_fixed_asset_borrowings`

```php
Schema::create('formit_fixed_asset_borrowings', function (Blueprint $table) {
    $table->id();

    // Data Pemohon (otomatis dari auth user)
    $table->string('pemohon_id')->index(); // employee_id pemohon
    $table->string('pemohon_name');
    $table->string('pemohon_jabatan');
    $table->string('pemohon_departemen');
    $table->string('pemohon_area');

    // Data Pengajuan
    $table->date('date_start'); // Tanggal mulai peminjaman
    $table->date('date_end'); // Tanggal akhir peminjaman
    $table->string('tujuan_lokasi'); // Tujuan lokasi
    $table->text('keperluan'); // Keperluan/keterangan
    $table->string('tipe_perangkat'); // Tipe perangkat yang dipinjam

    // Data Penyerahan (diisi oleh Approver)
    $table->string('penyerahkan_name')->nullable();
    $table->string('penyerahkan_jabatan')->nullable();
    $table->string('penyerahkan_departemen')->nullable();
    $table->string('penyerahkan_area')->nullable();

    // Status & Approval
    $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
    $table->string('rejected_by')->nullable();
    $table->text('rejection_reason')->nullable();
    $table->timestamp('approved_at')->nullable();
    $table->timestamp('rejected_at')->nullable();

    // Approver info
    $table->string('approver_id')->nullable()->index(); // employee_id approver

    $table->timestamps();
});
```

### 3.2 Relasi
- `pemohon_id` → `employees.employee_id`
- `approver_id` → `employees.employee_id`

---

## 4. Permission & Role Baru

### 4.1 Permission Baru
```php
// Ditambahkan ke RolePermissionSeeder
['name' => 'form-it.fixed-asset.view', 'guard_name' => 'web'],
['name' => 'form-it.fixed-asset.create', 'guard_name' => 'web'],
['name' => 'form-it.fixed-asset.approve', 'guard_name' => 'web'],
```

### 4.2 Role yang Diberikan Permission

| Role | Permission |
|------|------------|
| `admin` | Semua permission form-it (termasuk fixed-asset) |
| `approver` | `form-it.fixed-asset.view`, `form-it.fixed-asset.approve` |
| `staff` | `form-it.fixed-asset.view`, `form-it.fixed-asset.create` |

### 4.3 Middleware
- Menggunakan middleware `approver` yang sudah ada (cek di `formit_approvals`)
- Atau buat middleware baru khusus untuk fixed asset approval

---

## 5. Implementation Plan

### Step 1: Database Migration
**File:** `database/migrations/xxxx_xx_xx_create_formit_fixed_asset_borrowings_table.php`

Buat migration baru untuk table `formit_fixed_asset_borrowings`.

### Step 2: Model
**File:** `app/Models/FixedAssetBorrowing.php`

```php
class FixedAssetBorrowing extends Model
{
    protected $table = 'formit_fixed_asset_borrowings';

    protected $fillable = [
        'pemohon_id', 'pemohon_name', 'pemohon_jabatan',
        'pemohon_departemen', 'pemohon_area',
        'date_start', 'date_end', 'tujuan_lokasi',
        'keperluan', 'tipe_perangkat',
        'penyerahkan_name', 'penyerahkan_jabatan',
        'penyerahkan_departemen', 'penyerahkan_area',
        'status', 'rejected_by', 'rejection_reason',
        'approved_at', 'rejected_at', 'approver_id',
    ];

    protected $casts = [
        'date_start' => 'date',
        'date_end' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    // Relasi
    public function pemohon(): BelongsTo { ... }
    public function approver(): BelongsTo { ... }
}
```

### Step 3: Seeder Permission
**File:** `database/seeders/RolePermissionSeeder.php` (update)

Tambahkan permission baru:
```php
'form-it.fixed-asset.view',
'form-it.fixed-asset.create',
'form-it.fixed-asset.approve',
```

### Step 4: Routes
**File:** `routes/routers/form-it.php` (update)

```php
// PEMINJAMAN FIXED ASSET
Route::prefix("fixed-asset")->name("fixed-asset.")->group(function() {
    // Form pengajuan (Pemohon)
    Route::get("create", [FormController::class, "fixedAssetCreate"])
        ->name("create")
        ->middleware('permission:form-it.fixed-asset.create');

    Route::post("store", [FormController::class, "fixedAssetStore"])
        ->name("store")
        ->middleware('permission:form-it.fixed-asset.create');

    // Detail pengajuan
    Route::get("{id}", [FormController::class, "fixedAssetShow"])
        ->name("show")
        ->middleware('permission:form-it.fixed-asset.view');

    // My submissions
    Route::get("my-submissions", [FormController::class, "fixedAssetMySubmissions"])
        ->name("my-submissions")
        ->middleware('permission:form-it.fixed-asset.view');
});

// APPROVAL FIXED ASSET (untuk Approver)
Route::prefix("approval/fixed-asset")->name("approval.fixed-asset.")->middleware(['approver'])->group(function() {
    Route::get("/", [ApprovalController::class, "fixedAssetIndex"])
        ->name("index")
        ->middleware('permission:form-it.fixed-asset.approve');

    Route::get("/{id}", [ApprovalController::class, "fixedAssetShow"])
        ->name("show")
        ->middleware('permission:form-it.fixed-asset.approve');

    Route::post("/{id}/process", [ApprovalController::class, "fixedAssetProcess"])
        ->name("process")
        ->middleware('permission:form-it.fixed-asset.approve');
});
```

### Step 5: Controller - FormController
**File:** `app/Http/Controllers/FormIT/FormController.php` (update)

Tambahkan method baru:

```php
// Form Create
public function fixedAssetCreate()
{
    abort_unless(auth()->user()->hasPermissionTo('form-it.fixed-asset.create'), 403);

    $pageName = 'Form Peminjaman Fixed Asset IT';
    $pemohon = Employee::with(['division', 'regional'])
        ->where('employee_id', auth()->user()->employee_id)
        ->first();

    return view('form-it.forms.fixed-asset-create', compact('pageName', 'pemohon'));
}

// Store
public function fixedAssetStore(Request $request)
{
    abort_unless(auth()->user()->hasPermissionTo('form-it.fixed-asset.create'), 403);

    $validated = $request->validate([
        'date_start' => 'required|date',
        'date_end' => 'required|date|after_or_equal:date_start',
        'tujuan_lokasi' => 'required|string|max:255',
        'keperluan' => 'required|string|max:1000',
        'tipe_perangkat' => 'required|string|max:255',
    ]);

    $pemohon = Employee::with(['division', 'regional'])
        ->where('employee_id', auth()->user()->employee_id)
        ->first();

    // Cari approver (bisa dari hirarki atau role tertentu)
    $approver = $this->findFixedAssetApprover($pemohon);

    DB::beginTransaction();
    try {
        $borrowing = FixedAssetBorrowing::create([
            'pemohon_id' => $pemohon->employee_id,
            'pemohon_name' => $pemohon->name,
            'pemohon_jabatan' => $pemohon->jabatan,
            'pemohon_departemen' => $pemohon->division->name ?? null,
            'pemohon_area' => $pemohon->regional->name ?? null,
            'date_start' => $validated['date_start'],
            'date_end' => $validated['date_end'],
            'tujuan_lokasi' => $validated['tujuan_lokasi'],
            'keperluan' => $validated['keperluan'],
            'tipe_perangkat' => $validated['tipe_perangkat'],
            'status' => 'pending',
            'approver_id' => $approver?->employee_id,
        ]);

        DB::commit();

        return redirect()
            ->route('form-it.fixed-asset.my-submissions')
            ->with('success', 'Pengajuan peminjaman fixed asset berhasil dibuat! Menunggu approval.');
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->withInput()->with('error', 'Gagal membuat pengajuan: ' . $e->getMessage());
    }
}

// My Submissions
public function fixedAssetMySubmissions()
{
    abort_unless(auth()->user()->hasPermissionTo('form-it.fixed-asset.view'), 403);

    $employeeId = auth()->user()->employee_id;
    $submissions = FixedAssetBorrowing::with(['pemohon', 'approver'])
        ->where('pemohon_id', $employeeId)
        ->latest()
        ->get();

    $pageName = 'Pengajuan Peminjaman Fixed Asset Saya';
    return view('form-it.forms.fixed-asset-my-submissions', compact('pageName', 'submissions'));
}

// Show Detail
public function fixedAssetShow($id)
{
    abort_unless(auth()->user()->hasPermissionTo('form-it.fixed-asset.view'), 403);

    $borrowing = FixedAssetBorrowing::with(['pemohon', 'pemohon.division', 'pemohon.regional', 'approver'])
        ->findOrFail($id);

    $pageName = 'Detail Pengajuan Peminjaman Fixed Asset';
    return view('form-it.forms.fixed-asset-show', compact('pageName', 'borrowing'));
}

// Helper: Find Approver
private function findFixedAssetApprover(Employee $pemohon): ?Employee
{
    // Option 1: Dari hirarki (superior1)
    $superior1 = $pemohon->superior1();
    if ($superior1) {
        return $superior1;
    }

    // Option 2: Dari role approver
    $approverUser = User::whereHas('roles', function ($query) {
        $query->where('name', 'approver');
    })->first();

    return $approverUser?->employee;
}
```

### Step 6: Controller - ApprovalController
**File:** `app/Http/Controllers/FormIT/ApprovalController.php` (update)

Tambahkan method baru:

```php
// Index - List pengajuan menunggu approval
public function fixedAssetIndex()
{
    abort_unless(auth()->user()->hasPermissionTo('form-it.fixed-asset.approve'), 403);

    $employeeId = auth()->user()->employee_id;

    $pendingApprovals = FixedAssetBorrowing::with(['pemohon', 'pemohon.division'])
        ->where('approver_id', $employeeId)
        ->where('status', 'pending')
        ->latest()
        ->get();

    $historyApprovals = FixedAssetBorrowing::with(['pemohon', 'pemohon.division'])
        ->where('approver_id', $employeeId)
        ->where('status', '!=', 'pending')
        ->latest()
        ->get();

    $pageName = 'Approval Peminjaman Fixed Asset';
    return view('form-it.approval.fixed-asset-index', compact('pageName', 'pendingApprovals', 'historyApprovals'));
}

// Show - Detail untuk approval
public function fixedAssetShow($id)
{
    abort_unless(auth()->user()->hasPermissionTo('form-it.fixed-asset.approve'), 403);

    $borrowing = FixedAssetBorrowing::with(['pemohon', 'pemohon.division', 'pemohon.regional', 'approver'])
        ->findOrFail($id);

    $pageName = 'Review Pengajuan Peminjaman Fixed Asset';
    return view('form-it.approval.fixed-asset-show', compact('pageName', 'borrowing'));
}

// Process - Approve/Reject
public function fixedAssetProcess(Request $request, $id)
{
    abort_unless(auth()->user()->hasPermissionTo('form-it.fixed-asset.approve'), 403);

    $validated = $request->validate([
        'action' => 'required|in:approve,reject',
        'notes' => 'nullable|string|max:500',
        // Data penyerahan (wajib saat approve)
        'penyerahkan_name' => 'required_if:action,approve|string|max:255',
        'penyerahkan_jabatan' => 'required_if:action,approve|string|max:255',
        'penyerahkan_departemen' => 'required_if:action,approve|string|max:255',
        'penyerahkan_area' => 'required_if:action,approve|string|max:255',
    ]);

    $employeeId = auth()->user()->employee_id;
    $borrowing = FixedAssetBorrowing::findOrFail($id);

    // Cek apakah user adalah approver yang ditunjuk
    if ($borrowing->approver_id !== $employeeId) {
        return back()->with('error', 'Anda tidak memiliki akses untuk melakukan approval ini.');
    }

    if ($borrowing->status !== 'pending') {
        return back()->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
    }

    DB::beginTransaction();
    try {
        if ($validated['action'] === 'approve') {
            $borrowing->update([
                'status' => 'approved',
                'approved_at' => now(),
                'penyerahkan_name' => $validated['penyerahkan_name'],
                'penyerahkan_jabatan' => $validated['penyerahkan_jabatan'],
                'penyerahkan_departemen' => $validated['penyerahkan_departemen'],
                'penyerahkan_area' => $validated['penyerahkan_area'],
            ]);

            $message = 'Pengajuan peminjaman fixed asset berhasil disetujui!';
        } else {
            $borrowing->update([
                'status' => 'rejected',
                'rejected_by' => $employeeId,
                'rejection_reason' => $validated['notes'] ?? null,
                'rejected_at' => now(),
            ]);

            $message = 'Pengajuan peminjaman fixed asset berhasil ditolak.';
        }

        DB::commit();
        return back()->with('success', $message);
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Gagal memproses approval: ' . $e->getMessage());
    }
}
```

### Step 7: Views

#### 7.1 Form Create (Pemohon)
**File:** `resources/views/form-it/forms/fixed-asset-create.blade.php`

Fields yang ditampilkan:
- Tanggal Pengajuan (Dari & Sampai)
- Tujuan Lokasi
- Keperluan
- Tipe Perangkat
- Nama Pemohon (readonly, otomatis dari auth)
- Jabatan Pemohon (readonly)
- Departemen Pemohon (readonly)
- Area Pemohon (readonly)

**TIDAK menampilkan** fields "Data Yang Menyerahkan" (akan diisi oleh approver).

#### 7.2 My Submissions (Pemohon)
**File:** `resources/views/form-it/forms/fixed-asset-my-submissions.blade.php`

Tabel list pengajuan dengan kolom:
- No
- Tanggal Pengajuan
- Tipe Perangkat
- Tujuan Lokasi
- Status
- Aksi (Detail, PDF jika approved)

#### 7.3 Detail Show (Pemohon)
**File:** `resources/views/form-it/forms/fixed-asset-show.blade.php`

Tampilan detail pengajuan + status approval.

#### 7.4 Approval Index (Approver)
**File:** `resources/views/form-it/approval/fixed-asset-index.blade.php`

Tabel list pengajuan menunggu approval dengan tab:
- Menunggu Persetujuan
- Riwayat Approval

#### 7.5 Approval Show (Approver)
**File:** `resources/views/form-it/approval/fixed-asset-show.blade.php`

Tampilan detail dengan form approval:
- Data Pemohon (readonly)
- Form Input Data Penyerahan (wajib diisi saat approve):
  - Nama Yang Menyerahkan
  - Jabatan Yang Menyerahkan
  - Departemen Yang Menyerahkan
  - Area Yang Menyerahkan
- Tombol Approve/Reject

### Step 8: Update Layout/Sidebar
**File:** `resources/views/layouts/FormIT.blade.php` atau sidebar

Tambahkan menu:
```php
@if(auth()->user()->hasPermissionTo('form-it.fixed-asset.create'))
    <a href="{{ route('form-it.fixed-asset.create') }}">Peminjaman Fixed Asset</a>
@endif

@if(auth()->user()->hasPermissionTo('form-it.fixed-asset.view'))
    <a href="{{ route('form-it.fixed-asset.my-submissions') }}">Pengajuan Saya</a>
@endif

@if(auth()->user()->hasPermissionTo('form-it.fixed-asset.approve'))
    <a href="{{ route('form-it.approval.fixed-asset.index') }}">Approval Fixed Asset</a>
@endif
```

### Step 9: PDF Template (Opsional)
**File:** `resources/views/form-it/templates/fixed-asset.blade.php` (update)

Update template PDF sesuai dengan field yang ada di database.

---

## 6. File yang Perlu Dibuat/Update

### File Baru
| No | File | Deskripsi |
|----|------|-----------|
| 1 | `database/migrations/xxxx_create_formit_fixed_asset_borrowings_table.php` | Migration |
| 2 | `app/Models/FixedAssetBorrowing.php` | Model |
| 3 | `resources/views/form-it/forms/fixed-asset-create.blade.php` | Form Create |
| 4 | `resources/views/form-it/forms/fixed-asset-my-submissions.blade.php` | My Submissions |
| 5 | `resources/views/form-it/forms/fixed-asset-show.blade.php` | Detail Show |
| 6 | `resources/views/form-it/approval/fixed-asset-index.blade.php` | Approval Index |
| 7 | `resources/views/form-it/approval/fixed-asset-show.blade.php` | Approval Show + Form |

### File yang Perlu Diupdate
| No | File | Deskripsi |
|----|------|-----------|
| 1 | `routes/routers/form-it.php` | Tambah routes |
| 2 | `app/Http/Controllers/FormIT/FormController.php` | Tambah methods |
| 3 | `app/Http/Controllers/FormIT/ApprovalController.php` | Tambah methods |
| 4 | `database/seeders/RolePermissionSeeder.php` | Tambah permissions |
| 5 | `resources/views/layouts/FormIT.blade.php` | Update sidebar menu |

---

## 7. Status Flow

```
pending → approved (lengkap data penyerahkan)
pending → rejected (dengan alasan)
```

---

## 8. Testing Checklist

- [ ] Pemohon bisa submit form pengajuan
- [ ] Data tersimpan dengan status `pending`
- [ ] Approver bisa melihat list pengajuan
- [ ] Approver bisa input data penyerahkan saat approve
- [ ] Status berubah ke `approved` setelah approve
- [ ] Approver bisa reject dengan alasan
- [ ] Status berubah ke `rejected` setelah reject
- [ ] Pemohon bisa melihat status pengajuan
- [ ] PDF bisa di-generate setelah approved
- [ ] Permission dan role berfungsi dengan benar

---

## 9. Estimasi Waktu

| No | Task | Estimasi |
|----|------|----------|
| 1 | Database Migration | 0.5 jam |
| 2 | Model & Relations | 0.5 jam |
| 3 | Seeder Permission | 0.5 jam |
| 4 | Routes | 0.5 jam |
| 5 | Controller Form | 1.5 jam |
| 6 | Controller Approval | 1.5 jam |
| 7 | Views (5 files) | 3 jam |
| 8 | Sidebar/Menu | 0.5 jam |
| 9 | Testing | 1.5 jam |
| **Total** | | **10 jam** |
