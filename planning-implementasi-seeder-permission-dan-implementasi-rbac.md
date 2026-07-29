# Planning Implementasi Seeder Permission & RBAC

## Ringkasan

Dua tujuan utama:
1. **Seeder permission** — buat semua permission records yang dibutuhkan, assign ke role
2. **Implementasi RBAC** — ganti semua hardcoded role check dengan permission check

---

## 1. Analisis Kondisi Saat Ini

### Database Permission: **KOSONG**
Tabel `permissions` tidak memiliki satupun record. Semua otorisasi dilakukan dengan:
- Hardcoded role names di middleware: `role:super-admin|admin`
- Hardcoded role names di Blade: `@role('super-admin|staff')`
- Hardcoded role names di Controller: `$user->hasRole('staff')`
- Hardcoded string comparison: `$authUserRoleId == 'super-admin'`

### Total Role Check: ~61 lokasi
| Tipe | Jumlah | Contoh |
|------|--------|--------|
| Middleware `role:` | 8 | `role:super-admin\|admin` |
| Controller `hasRole()`/`hasAnyRole()` | 8 | `$user->hasRole('teknisi')` |
| Blade `@role()` | 7 | `@role('super-admin')` |
| Blade `@if hasRole()` | 7 | `@if(!$user->hasRole('approver'))` |
| Hardcoded `== 'role'` | 11 | `$authUserRoleId == 'staff'` |
| `User::role()` scope | 4 | `User::role('teknisi')->get()` |
| `getRoleNames()` | 9 | `getRoleNames()->first()` |
| `syncRoles()` | 2 | `syncRoles([$request->role_name])` |
| `assignRole()` | 2 | `assignRole('staff')` |

| Role | Frekuensi Check |
|------|----------------|
| `super-admin` | ~22 |
| `admin` | ~12 |
| `staff` | ~20 |
| `teknisi` | ~14 |
| `approver` | ~5 |

---

## 2. Daftar Permission Lengkap

### 2.1 Portal & Menu
| Permission | Deskripsi | Super Admin | Admin | Approver | Staff | Teknisi |
|-----------|-----------|:-----------:|:-----:|:--------:|:-----:|:-------:|
| `portal.access` | Akses portal utama | ✓ | ✓ | ✓ | ✓ | ✓ |
| `ams.menu` | Lihat menu AMS | ✓ | ✓ | ✓ | ✓ | - |
| `helpdesk.menu` | Lihat menu Helpdesk | ✓ | ✓ | - | ✓ | ✓ |
| `data-pegawai.menu` | Lihat menu Data Pegawai | ✓ | ✓ | ✓ | - | - |
| `form-it.menu` | Lihat menu Form IT | ✓ | ✓ | - | - | - |
| `sop-it.menu` | Lihat menu SOP IT | ✓ | ✓ | - | - | - |

### 2.2 AMS (Asset Management System)
| Permission | Deskripsi | Super Admin | Admin | Approver | Staff | Teknisi |
|-----------|-----------|:-----------:|:-----:|:--------:|:-----:|:-------:|
| `ams.dashboard` | Dashboard AMS | ✓ | ✓ | ✓ | ✓ | - |
| `assets.view` | Lihat daftar aset | ✓ | ✓ | ✓ | ✓ | - |
| `assets.create` | Tambah aset baru | ✓ | ✓ | - | - | - |
| `assets.edit` | Edit aset | ✓ | ✓ | - | - | - |
| `assets.delete` | Hapus aset | ✓ | ✓ | - | - | - |
| `assets.import` | Import aset dari file | ✓ | ✓ | - | - | - |
| `transactions.view` | Lihat transaksi | ✓ | ✓ | ✓ | ✓ | - |
| `transactions.create` | Buat transaksi | ✓ | ✓ | - | ✓ | - |
| `transactions.delete` | Hapus transaksi | ✓ | ✓ | - | - | - |
| `transactions.approve` | Approve/reject transaksi | ✓ | ✓ | ✓ | - | - |
| `transactions.export-pdf` | Export PDF transaksi | ✓ | ✓ | ✓ | ✓ | - |
| `employees.view` | Lihat data pegawai | ✓ | ✓ | ✓ | ✓ | - |
| `employees.manage` | CRUD pegawai | ✓ | ✓ | - | - | - |
| `employees.import` | Import pegawai | ✓ | ✓ | - | - | - |
| `master-data.view` | Lihat master data | ✓ | ✓ | ✓ | ✓ | - |
| `master-data.manage` | CRUD master data | ✓ | ✓ | - | - | - |
| `assignment.view` | Lihat assignment aset | ✓ | ✓ | ✓ | ✓ | - |
| `assignment.manage` | CRUD assignment aset | ✓ | ✓ | - | - | - |
| `monitoring.view` | Lihat monitoring | ✓ | ✓ | ✓ | ✓ | - |
| `whatsapp-settings.manage` | Atur setting WhatsApp | ✓ | ✓ | - | - | - |
| `settings.reset-password` | Ubah password sendiri | ✓ | ✓ | ✓ | ✓ | ✓ |
| `settings.approve` | Halaman approval settings | ✓ | ✓ | ✓ | - | - |

### 2.3 Helpdesk
| Permission | Deskripsi | Super Admin | Admin | Approver | Staff | Teknisi |
|-----------|-----------|:-----------:|:-----:|:--------:|:-----:|:-------:|
| `helpdesk.dashboard` | Dashboard helpdesk | ✓ | ✓ | - | ✓ | ✓ |
| `tickets.view` | Lihat tiket (milik sendiri) | ✓ | ✓ | - | ✓ | ✓ |
| `tickets.view-all` | Lihat semua tiket | ✓ | ✓ | - | - | - |
| `tickets.create` | Buat tiket baru | ✓ | ✓ | - | ✓ | - |
| `tickets.edit` | Edit konten tiket | ✓ | ✓ | - | ✓ | - |
| `tickets.delete` | Hapus tiket | ✓ | ✓ | - | ✓ | - |
| `tickets.assign` | Assign tiket ke teknisi | ✓ | ✓ | - | - | - |
| `tickets.resolve` | Resolve tiket (teknisi) | ✓ | - | - | - | ✓ |
| `tickets.approve` | Approve resolusi tiket | ✓ | - | - | - | ✓ |
| `tickets.confirm` | Konfirmasi tiket selesai | ✓ | ✓ | - | ✓ | - |
| `tickets.reopen` | Buka ulang tiket | ✓ | ✓ | - | ✓ | - |
| `tickets.comment` | Komentar di tiket | ✓ | ✓ | - | ✓ | ✓ |
| `ticket-categories.manage` | CRUD kategori tiket | ✓ | ✓ | - | - | - |
| `ticket-priorities.manage` | CRUD prioritas tiket | ✓ | ✓ | - | - | - |
| `technicians.view` | Lihat daftar teknisi | ✓ | ✓ | - | - | - |
| `reports.view` | Lihat laporan helpdesk | ✓ | ✓ | - | - | - |
| `reports.export` | Export laporan (PDF/Excel) | ✓ | ✓ | - | - | - |

### 2.4 IT Admin
| Permission | Deskripsi | Super Admin | Admin | Approver | Staff | Teknisi |
|-----------|-----------|:-----------:|:-----:|:--------:|:-----:|:-------:|
| `it-admin.access` | Akses menu IT Admin | ✓ | - | - | - | - |
| `users.manage` | CRUD user + set password | ✓ | - | - | - | - |
| `roles.manage` | CRUD role | ✓ | - | - | - | - |
| `permissions.manage` | Atur permission role | ✓ | - | - | - | - |

---

## 3. Strategi Implementasi RBAC

### 3.1 Super Admin Bypass

Super admin akan lolos semua pengecekan permission secara otomatis via `Gate::before()`:

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Support\Facades\Gate;

public function boot()
{
    Gate::before(function ($user, $ability) {
        return $user->hasRole('super-admin') ? true : null;
    });
}
```

Seeder tidak perlu assign permission ke super-admin.

### 3.2 Migrasi: Middleware `role:` → `permission:`

#### Rute Helpdesk (`routes/web.php`)
| Route Saat Ini | Roles | Ganti Menjadi |
|---------------|-------|---------------|
| Ln 70: `role:super-admin\|admin` | super-admin, admin | `permission:ticket-categories.manage\|ticket-priorities.manage` |
| Ln 78: `role:super-admin\|admin` | super-admin, admin | `permission:tickets.assign\|reports.view` |
| Ln 89: `role:super-admin\|staff\|teknisi\|admin` | semua kecuali approver | `permission:tickets.comment` |
| Ln 94: `role:teknisi` | teknisi | `permission:tickets.resolve\|tickets.approve` |
| Ln 99: `role:super-admin\|staff` | super-admin, staff | `permission:tickets.confirm\|tickets.reopen\|tickets.edit\|tickets.delete` |

#### Controller Ticket (`app/Http/Controllers/HelpDesk/TicketController.php`)
```php
// Sebelum:
$this->middleware('role:super-admin|admin')->only(['assignTeknisi']);
$this->middleware('role:teknisi')->only(['approve']);

// Sesudah:
$this->middleware('permission:tickets.assign')->only(['assignTeknisi']);
$this->middleware('permission:tickets.approve')->only(['approve']);
```

### 3.3 Migrasi: Controller `hasRole()` → `hasPermissionTo()`

#### TicketController
| Baris | Sebelum | Sesudah |
|-------|---------|---------|
| 89 | `$user->hasRole('teknisi')` | `$user->hasPermissionTo('tickets.view')` — bedakan query scope |
| 91 | `$user->hasRole('staff')` | `$user->hasPermissionTo('tickets.view')` — staff hanya lihat tiket sendiri |
| 128 | `$user->hasRole('teknisi')` | `$user->hasPermissionTo('tickets.resolve')` |
| 147 | `$user->hasAnyRole(['staff', 'super-admin'])` | `$user->hasPermissionTo('tickets.confirm')` |

**Catatan:** Untuk logic filtering data (misal: staff hanya lihat tiket sendiri, admin lihat semua), permission `tickets.view` vs `tickets.view-all` digunakan:

```php
// Sebelum (hardcoded role):
if ($user->hasRole('teknisi')) {
    $tickets->where('assigned_to', $user->id);
} elseif ($user->hasRole('staff')) {
    $tickets->where('requester_id', $user->id);
}

// Sesudah (permission-based):
if (!$user->hasPermissionTo('tickets.view-all')) {
    if ($user->hasPermissionTo('tickets.resolve')) {
        $tickets->where('assigned_to', $user->id);
    } else {
        $tickets->where('requester_id', $user->id);
    }
}
```

#### DashboardController Helpdesk
| Baris | Sebelum | Sesudah |
|-------|---------|---------|
| 21 | `$user->hasRole('staff')` | `!$user->hasPermissionTo('tickets.view-all')` |
| 53 | `$user->hasRole('super-admin')` | `$user->hasPermissionTo('helpdesk.dashboard')` (atau bypass Gate) |
| 71 | `$user->hasRole('staff')` | `!$user->hasPermissionTo('tickets.view-all')` |

#### ReportController
| Baris | Sebelum | Sesudah |
|-------|---------|---------|
| 61 | `$user->hasRole('teknisi')` | `!$user->hasPermissionTo('tickets.view-all')` |
| 63 | `$user->hasRole('staff')` | `!$user->hasPermissionTo('tickets.view-all')` |

#### TransactionController
| Baris | Sebelum | Sesudah |
|-------|---------|---------|
| 95 | `!$user->hasRole('staff')` | `$user->hasPermissionTo('transactions.approve')` |

#### DashboardController (AMS)
Tidak ada role check — hanya perlu permission middleware di route.

### 3.4 Migrasi: Blade `@role()` → `@can()`

#### helpdesk/app-sidebar.blade.php
| Baris | Sebelum | Sesudah |
|-------|---------|---------|
| 24 | `@role('super-admin\|admin')` | `@can('ticket-categories.manage')` |
| 43 | `@role('super-admin\|admin')` | `@can('reports.view')` |
| 47 | `@role('teknisi\|staff')` | `@can('tickets.view')` — atau tanpa gate (semua) |
| 51 | `@role('super-admin\|staff')` | `@can('tickets.confirm')` |
| 57 | `@role('super-admin')` | `@can('users.manage')` atau `@can('it-admin.access')` |

#### helpdesk/dashboard/index.blade.php
| Baris | Sebelum | Sesudah |
|-------|---------|---------|
| 190 | `@role('super-admin')` | `@can('helpdesk.dashboard')` |
| 383 | `@role('super-admin')` | `@can('helpdesk.dashboard')` |

### 3.5 Migrasi: Blade `@if hasRole()` → `@can()`

#### app-sidebar.blade.php (AMS sidebar)
| Baris | Sebelum | Sesudah |
|-------|---------|---------|
| 25 | `@if(!$user->hasRole('approver'))` | `@can('transactions.create')` |
| 52 | `@if(!$user->hasRole('approver'))` | `@can('transactions.approve')` |
| 66 | `@if(!$user->hasRole('approver'))` | `@can('master-data.manage')` |
| 73 | `@if(!$user->hasRole('approver'))` | `@can('whatsapp-settings.manage')` |

#### transaction.blade.php
| Baris | Sebelum | Sesudah |
|-------|---------|---------|
| 5 | `@if(Auth::user()->hasRole('staff'))` | `@can('transactions.create')` |
| 23 | `@if(!Auth::user()->hasRole('staff'))` | `@can('transactions.approve')` |
| 105 | `Auth::user()->hasRole('staff')` | `Auth::user()->hasPermissionTo('transactions.create')` |

### 3.6 Migrasi: `User::role()` Scope → Query Permission

| File | Baris | Sebelum | Sesudah |
|------|-------|---------|---------|
| `TicketController.php` | 31 | `User::role('admin')->first()` | `User::permission('tickets.assign')->first()` atau lewat role |
| `TicketController.php` | 281 | `User::role('teknisi')->get()` | `User::permission('tickets.resolve')->get()` atau `role('teknisi')` tetap |

**Catatan:** Query dengan `User::role()` bisa tetap dipertahankan karena itu query spesifik (cari user dengan role tertentu), bukan otorisasi.

### 3.7 Yang TETAP (Tidak Diubah)

| Lokasi | Alasan |
|--------|--------|
| `app-sidebar.blade.php` (IT Admin & Helpdesk) — styling warna role | Murni UI, bukan otorisasi. Warna berdasarkan role. |
| `app-header.blade.php` (Helpdesk) — styling warna header | Murni UI, bukan otorisasi. |
| `getRoleNames()->first()` di sidebar/header | Untuk display/user greeting, bukan otorisasi. |
| `setting-role.blade.php` — badge warna role | Display role di form manajemen, bukan otorisasi. |
| `RoleController` — `User::role($row->name)->count()` | Logic query, bukan otorisasi. |
| `MicrosoftAuthController` — `assignRole('staff')` | Default role untuk user baru. |
| `UserSeeder` — `assignRole($role)` | Seeder data awal. |

---

## 4. Perubahan File — Ringkasan Lengkap

### 4.1 File Diubah untuk Seeder

| File | Perubahan |
|------|-----------|
| `database/seeders/RolePermissionSeeder.php` | Tambah 40+ permission records, assign ke roles sesuai mapping |
| `app/Providers/AppServiceProvider.php` | Tambah `Gate::before()` untuk super-admin bypass |

### 4.2 File Diubah untuk RBAC Middleware

| File | Perubahan |
|------|-----------|
| `routes/web.php` | 5 route group: `role:` → `permission:` |
| `app/Http/Controllers/HelpDesk/TicketController.php` | 3 middleware: `role:` → `permission:` |

### 4.3 File Diubah untuk Controller hasRole()

| File | Perubahan |
|------|-----------|
| `app/Http/Controllers/HelpDesk/TicketController.php` | 4x `hasRole()` → `hasPermissionTo()` |
| `app/Http/Controllers/HelpDesk/DashboardController.php` | 3x `hasRole()` → `hasPermissionTo()` |
| `app/Http/Controllers/HelpDesk/ReportController.php` | 2x `hasRole()` → `hasPermissionTo()` |
| `app/Http/Controllers/TransactionController.php` | 1x `hasRole()` → `hasPermissionTo()` |

### 4.4 File Diubah untuk Blade @role() / @if hasRole()

| File | Perubahan |
|------|-----------|
| `resources/views/layouts/partials/helpdesk/app-sidebar.blade.php` | 5x `@role()` → `@can()` |
| `resources/views/layouts/partials/app-sidebar.blade.php` | 4x `@if hasRole()` → `@can()` |
| `resources/views/components/transaction.blade.php` | 2x `@if hasRole()` + 1x JS → `@can()` + `hasPermissionTo()` |
| `resources/views/helpdesk/dashboard/index.blade.php` | 2x `@role('super-admin')` → `@can('helpdesk.dashboard')` |
| `resources/views/helpdesk/tickets/index.blade.php` | 1x `hasAnyRole()` → `hasPermissionTo()` |

### 4.5 File Tidak Diubah (Yang ditetapkan)

| File | Alasan |
|------|--------|
| `resources/views/layouts/partials/helpdesk/app-sidebar.blade.php` (Ln 4-8: `==` color logic) | UI styling |
| `resources/views/layouts/partials/helpdesk/app-header.blade.php` (Ln 5-9: `==` color logic) | UI styling |
| `resources/views/components/setting-role.blade.php` (Ln 77-81: badge color) | Display role badges |
| `resources/views/layouts/partials/it-admin/app-sidebar.blade.php` | IT Admin sidebar (sudah pakai permission nanti) |

---

## 5. Catatan Penting

1. **Seeder harus di-run ulang** setelah dibuat — `php artisan db:seed --class=RolePermissionSeeder`
2. **Permission cache** auto-flush via `RefreshesPermissionCache` trait di model Role & Permission
3. **Gate::before()** harus didaftarkan di `AppServiceProvider::boot()` agar super-admin bypass berfungsi
4. **Middleware `permission:`** sudah terdaftar di `Kernel.php` (baris 67)
5. **Untuk Blade**, `@can('permission.name')` akan otomatis memanggil `Gate::before()` — jadi super-admin akan lihat semua menu
6. **User yang sudah login** perlu logout/login ulang agar cache permission refresh (atau cache di-clear)
7. **Urutan implementasi:** 
   - Step 1: Seeder + Gate::before() — ini dulu, agar permission exist
   - Step 2: Middleware route → permission
   - Step 3: Controller hasRole → hasPermissionTo
   - Step 4: Blade @role → @can

---

## 6. Total File Yang Berubah

| Kategori | Jumlah File |
|----------|:-----------:|
| Seeder & Providers | 2 |
| Routes | 1 |
| Controllers | 4 |
| Blade Views | 5 |
| **Total** | **12** |
