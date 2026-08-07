# Planning: Implementasi Permission Pembuatan Pengajuan & Approval Form IT

## Status: Draft
## Tanggal: 2026-08-06

---

## 1. Ringkasan Masalah

Modul Form IT saat ini memiliki beberapa masalah terkait permission:

1. **Semua route Form IT menggunakan `helpdesk.dashboard`** sebagai permission middleware — ini salah karena seharusnya Form IT punya permission sendiri yang terpisah dari Helpdesk.

2. **Tidak ada permission granular** untuk pembuatan pengajuan (`form-it.forms.create`) dan approval (`form-it.approval.process`). Semua user yang punya akses ke Form IT bisa melihat semua menu.

3. **Approver role tidak punya akses ke Form IT** — di `RolePermissionSeeder`, role `approver` tidak diberikan permission `form-it.menu` atau `helpdesk.dashboard`, sehingga user dengan role `approver` saja tidak bisa mengakses halaman approval.

4. **`EnsureUserIsApprover` middleware menggunakan query database langsung** ke `formit_approvals` table untuk cek apakah user adalah approver. Ini adalah **business logic** yang valid (siapa yang boleh approve某specific form), BUKAN role-based permission. Jadi middleware ini **TIDAK perlu diganti** dengan Spatie.

5. **Sidebar dan Dashboard controller juga query langsung** ke `formit_approvals` untuk cek apakah user adalah approver — ini juga business logic yang valid.

---

## 2. Analisis Permission Saat Ini

### 2.1 Permission yang Sudah Ada

| Permission | Digunakan Oleh | Keterangan |
|-----------|---------------|------------|
| `form-it.menu` | Seeder, Portal view | Hanya untuk visibilitas menu di portal, BUKAN di route |
| `helpdesk.dashboard` | **Semua route Form IT** | Permission helpdesk yang dipakai untuk akses Form IT |

### 2.2 Yang Perlu Dibuat

| Permission Baru | Deskripsi | Digunakan Di |
|----------------|-----------|-------------|
| `form-it.dashboard` | Akses dashboard Form IT | Route `form-it.index` |
| `form-it.forms.view` | Melihat daftar pengajuan & detail | Route `form-it.forms.my-submissions`, `form-it.forms.software-installation.show`, `form-it.forms.software-installation.pdf` |
| `form-it.forms.create` | Membuat pengajuan baru | Route `form-it.forms.software-installation` (GET + POST) |
| `form-it.approval.view` | Melihat daftar & detail approval | Route `form-it.approval.index`, `form-it.approval.show` |
| `form-it.approval.process` | Melakukan approve/reject | Route `form-it.approval.process` |

### 2.3 Permission yang TIDAK Perlu Diubah

| Permission/Kode | Alasan |
|----------------|--------|
| `EnsureUserIsApprover` middleware | Business logic — cek apakah user adalah approver untuk specific form, bukan role-based |
| `FormitApproval::where('approver_id')` di Dashboard/Sidebar | Business logic — menampilkan menu approval hanya untuk user yang memang ditunjuk sebagai approver |

---

## 3. Role-Permission Mapping

### 3.1 Role `admin`
Mendapat **semua** permission Form IT:
```
form-it.menu, form-it.dashboard, form-it.forms.view, form-it.forms.create,
form-it.approval.view, form-it.approval.process
```

### 3.2 Role `staff`
Permission pembuatan pengajuan:
```
form-it.menu, form-it.dashboard, form-it.forms.view, form-it.forms.create
```
> Staff bisa membuat pengajuan, melihat pengajuan sendiri, tapi TIDAK bisa akses approval.

### 3.3 Role `approver`
Permission approval (tetap juga bisa melihat dashboard):
```
form-it.menu, form-it.dashboard, form-it.forms.view,
form-it.approval.view, form-it.approval.process
```
> Approver bisa melihat approval queue, tapi TIDAK bisa membuat pengajuan baru.

### 3.4 Role `teknisi`
Tidak ada permission Form IT (kecuali `form-it.menu` jika perlu diakses dari portal).

### 3.5 Role `super-admin`
Bypass semua permission via `Gate::before` — tidak perlu ubahan.

---

## 4. Implementasi

### Step 1: Tambah Permission Baru di Seeder

**File:** `database/seeders/RolePermissionSeeder.php`

Tambahkan 5 permission baru ke array `$permissions`:
```php
['name' => 'form-it.dashboard', 'guard_name' => 'web'],
['name' => 'form-it.forms.view', 'guard_name' => 'web'],
['name' => 'form-it.forms.create', 'guard_name' => 'web'],
['name' => 'form-it.approval.view', 'guard_name' => 'web'],
['name' => 'form-it.approval.process', 'guard_name' => 'web'],
```

Update permission assignments untuk setiap role:
- **admin**: semua permission form-it
- **staff**: `form-it.dashboard`, `form-it.forms.view`, `form-it.forms.create`
- **approver**: `form-it.dashboard`, `form-it.forms.view`, `form-it.approval.view`, `form-it.approval.process`
- **teknisi**: tidak ada (atau `form-it.menu` saja jika perlu)

### Step 2: Update Route Middleware

**File:** `routes/routers/form-it.php`

```php
// SEBELUM:
Route::prefix("form-it")->name("form-it.")->middleware(['permission:helpdesk.dashboard'])->group(function() {

// SESUDAH:
Route::prefix("form-it")->name("form-it.")->group(function() {

    // Dashboard — semua yang punya akses form-it
    Route::get("/", [DashboardController::class, 'index'])
        ->name("index")
        ->middleware('permission:form-it.dashboard');

    // Form Pengajuan
    Route::prefix("forms")->name("forms.")->group(function() {
        Route::get("my-submissions", [FormController::class, "mySubmissions"])
            ->name("my-submissions")
            ->middleware('permission:form-it.forms.view');

        Route::get("software-installation", [FormController::class, "softwareInstallation"])
            ->name("software-installation")
            ->middleware('permission:form-it.forms.create');

        Route::post("software-installation", [FormController::class, "softwareInstallationCreate"])
            ->name("software-installation.create")
            ->middleware('permission:form-it.forms.create');

        Route::get("software-installation/{id}", [FormController::class, "softwareInstallationShow"])
            ->name("software-installation.show")
            ->middleware('permission:form-it.forms.view');

        Route::get("software-installation/{id}/pdf", [FormController::class, "showPdf"])
            ->name("software-installation.pdf")
            ->middleware('permission:form-it.forms.view');
    });

    // Approval
    Route::prefix("approval")->name("approval.")->middleware(['approver'])->group(function() {
        Route::get("/", [ApprovalController::class, "index"])
            ->name("index")
            ->middleware('permission:form-it.approval.view');

        Route::get("/{id}", [ApprovalController::class, "show"])
            ->name("show")
            ->middleware('permission:form-it.approval.view');

        Route::post("/{id}/process", [ApprovalController::class, "process"])
            ->name("process")
            ->middleware('permission:form-it.approval.process');
    });
});
```

> **Catatan:** Middleware `approver` (custom `EnsureUserIsApprover`) tetap dipertahankan di route approval karena ini business logic yang mengecek apakah user memang ditunjuk sebagai approver untuk form tertentu.

### Step 3: Update Sidebar View

**File:** `resources/views/layouts/partials/form-it/app-sidebar.blade.php`

Gunakan `@can` directive Spatie alih-alih query database langsung untuk visibilitas menu:

```blade
@php
$roleColor = "primary-form-it";
@endphp

<div id="sidebar-menu" class="mt-2">
    <ul class="metismenu list-unstyled" id="side-menu">

        <li>
            <a href="{{ route('form-it.index') }}" class="waves-effect">
                <i class="bx bx-home-circle"></i>
                <span key="t-dashboard">Dashboard</span>
            </a>
        </li>

        @can('form-it.forms.create')
        <li>
            <a href="{{ route('form-it.forms.software-installation') }}" class="waves-effect">
                <i class="bx bx-file"></i>
                <span key="t-form-software">Buat Pengajuan</span>
            </a>
        </li>
        @endcan

        @can('form-it.forms.view')
        <li>
            <a href="{{ route('form-it.forms.my-submissions') }}" class="waves-effect">
                <i class="bx bx-list-ul"></i>
                <span key="t-my-submissions">Pengajuan Saya</span>
            </a>
        </li>
        @endcan

        @can('form-it.approval.view')
        <li>
            <a href="{{ route('form-it.approval.index') }}" class="waves-effect">
                <i class="bx bx-check-shield"></i>
                <span key="t-approval">Approval</span>
            </a>
        </li>
        @endcan

        <li>
            <a href="{{ route('portal.index') }}" class="waves-effect text-{{ $roleColor }}">
                <i class="bx bx-grid-alt text-{{ $roleColor }}"></i>
                <span key="t-back-portal" class="fw-bold">Back to Portal</span>
            </a>
        </li>

    </ul>
</div>
```

> **Perubahan:** Menghapus query `FormitApproval::where('approver_id')` langsung dari sidebar dan menggantinya dengan `@can('form-it.approval.view')`. Ini lebih bersih karena permission sudah di-assign ke role yang tepat.

### Step 4: Update Dashboard Controller

**File:** `app/Http/Controllers/FormIT/DashboardController.php`

```php
// SEBELUM:
$isApprover = FormitApproval::where('approver_id', $employeeId)->exists();

// SESUDAH:
$isApprover = auth()->user()->hasPermissionTo('form-it.approval.view');
```

> **Perubahan:** Mengganti query database dengan Spatie permission check. Menu "Approval Pengajuan IT" hanya muncul jika user punya permission `form-it.approval.view`.

### Step 5: Tambah Authorization Check di Controller

**File:** `app/Http/Controllers/FormIT/FormController.php`

Tambahkan `$this->authorize()` atau `$request->user()->hasPermissionTo()` di awal method untuk authorization layer tambahan:

```php
public function softwareInstallationCreate(Request $request)
{
    // Authorization check (redundant dengan middleware, tapi sebagai defense-in-depth)
    if (!auth()->user()->hasPermissionTo('form-it.forms.create')) {
        abort(403);
    }
    // ... logic existing
}
```

**File:** `app/Http/Controllers/FormIT/ApprovalController.php`

```php
public function process(Request $request, $id)
{
    // Authorization check
    if (!auth()->user()->hasPermissionTo('form-it.approval.process')) {
        abort(403);
    }
    // ... logic existing
}
```

> **Catatan:** Check di controller ini adalah **defense-in-depth** (lapisan keamanan tambahan). Middleware di route sudah cukup, tapi controller check berguna jika method dipanggil dari context lain.

### Step 6: Update Portal View (Opsional)

**File:** `resources/views/portal.blade.php`

Pastikan menu Form IT di portal menggunakan permission `form-it.menu` yang sudah ada (sudah benar saat ini).

### Step 7: Cleanup Header

**File:** `resources/views/layouts/partials/form-it/app-header.blade.php`

Hapus baris `$role = session('user_role');` yang sudah tidak digunakan:
```php
// SEBELUM:
$role = session('user_role');
$roleColor = "primary-form-it";

// SESUDAH:
$roleColor = "primary-form-it";
```

---

## 5. Flow Permission Setelah Implementasi

```
User Login
    │
    ├── role: admin
    │   └── Punya SEMUA permission form-it → Bisa akses semua fitur
    │
    ├── role: staff
    │   ├── form-it.dashboard ✅ → Bisa akses dashboard
    │   ├── form-it.forms.view ✅ → Bisa lihat pengajuan
    │   ├── form-it.forms.create ✅ → Bisa buat pengajuan
    │   ├── form-it.approval.view ❌ → TIDAK bisa lihat approval
    │   └── form-it.approval.process ❌ → TIDAK bisa approve/reject
    │
    ├── role: approver
    │   ├── form-it.dashboard ✅ → Bisa akses dashboard
    │   ├── form-it.forms.view ✅ → Bisa lihat pengajuan
    │   ├── form-it.forms.create ❌ → TIDAK bisa buat pengajuan
    │   ├── form-it.approval.view ✅ → Bisa lihat approval queue
    │   └── form-it.approval.process ✅ → Bisa approve/reject
    │       └── + EnsureUserIsApprover middleware → Hanya form yang ditunjuk
    │
    └── role: teknisi
        └── Tidak ada permission form-it → Tidak bisa akses modul ini
```

---

## 6. File yang Diubah

| No | File | Perubahan |
|----|------|-----------|
| 1 | `database/seeders/RolePermissionSeeder.php` | Tambah 5 permission baru + update role assignments |
| 2 | `routes/routers/form-it.php` | Ganti middleware `permission:helpdesk.dashboard` → permission granular per route |
| 3 | `resources/views/layouts/partials/form-it/app-sidebar.blade.php` | Ganti query database → `@can()` directive |
| 4 | `app/Http/Controllers/FormIT/DashboardController.php` | Ganti query database → `hasPermissionTo()` |
| 5 | `app/Http/Controllers/FormIT/FormController.php` | Tambah authorization check (defense-in-depth) |
| 6 | `app/Http/Controllers/FormIT/ApprovalController.php` | Tambah authorization check (defense-in-depth) |
| 7 | `resources/views/layouts/partials/form-it/app-header.blade.php` | Hapus `$role = session('user_role')` |

---

## 7. Yang TIDAK Diubah

| File/Logic | Alasan |
|-----------|--------|
| `EnsureUserIsApprover` middleware | Ini business logic (cek apakah user ditunjuk sebagai approver), bukan role permission |
| `FormitApproval::where('approver_id')` di ApprovalController | Query untuk filter data approval per user, bukan authorization check |
| `SoftwareInstallation` model & relationships | Tidak ada hubungan dengan permission |
| `FormitApproval` model & relationships | Business logic, bukan authorization |

---

## 8. Testing Checklist

- [ ] User dengan role `admin` bisa akses semua fitur Form IT
- [ ] User dengan role `staff` bisa buat pengajuan, lihat pengajuan sendiri, TIDAK bisa akses approval
- [ ] User dengan role `approver` bisa lihat approval queue, approve/reject, TIDAK bisa buat pengajuan
- [ ] User dengan role `teknisi` TIDAK bisa akses modul Form IT
- [ ] Sidebar hanya menampilkan menu sesuai permission user
- [ ] Dashboard hanya menampilkan menu "Approval" jika user punya permission `form-it.approval.view`
- [ ] Route protection: user tanpa permission yang sesuai mendapat 403
- [ ] `super-admin` bisa akses semua (bypass via Gate::before)
- [ ] Migration ke Spatie tidak menghapus data approval yang sudah ada
- [ ] Seeder bisa di-rollback dan dijalankan ulang tanpa error

---

## 9. Catatan Penting

1. **Middleware `approver` tidak dihapus** — ini adalah custom middleware yang berguna untuk business logic (memastikan user yang mengakses approval route memang ditunjuk sebagai approver untuk form tertentu). Ini berbeda dengan Spatie permission.

2. **Permission `helpdesk.dashboard` tidak dihapus dari role lain** — hanya tidak lagi digunakan di route Form IT. Permission ini masih dibutuhkan untuk modul Helpdesk.

3. **Approver bisa punya permission `form-it.forms.view`** — agar approver bisa melihat detail pengajuan yang perlu di-approve, tapi tidak bisa membuat pengajuan baru.

4. **Jalankan `php artisan db:seed --class=RolePermissionSeeder`** setelah implementasi untuk menambah permission baru.

5. **Jalankan `php artisan permission:cache-reset`** setelahSeeder dijalankan untuk clear cache permission Spatie.
