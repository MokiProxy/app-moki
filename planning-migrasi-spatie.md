# Planning Migrasi: Manual Role/Permission → Laravel Spatie

## Ringkasan

Codebase saat ini menggunakan sistem role/permission manual dengan:
- Kolom `role_id` di tabel `users` (untuk runtime auth)
- Tabel `user_roles` (untuk management role, lookup via employee)
- Middleware custom `CheckRole` (cek `role_id` numeric)
- Helper methods `isSuperAdmin()`, `isAdmin()`, `isAtasan()` di User model
- 4 Gate definitions di `AppServiceProvider` (TIDAK dipakai di mana pun)
- Session `user_role` disimpan saat login untuk keperluan view
- Role dicek inline di controller (`auth()->user()->role_id`) dan blade (`session('user_role')`)

Target: Migrasi penuh ke `spatie/laravel-permission`, hapus semua kode manual, tidak ada duplikasi.

---

## Perubahan Database

### 1. Install Package & Publish Migration
```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```
Ini akan membuat tabel: `permissions`, `roles`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`.

### 2. Migration Baru: `remove_role_id_from_users_table`
Hapus kolom `role_id` dari tabel `users` — role sekarang disimpan via Spatie di `model_has_roles`.

### 3. Migration Baru: `drop_user_roles_table`
Hapus tabel `user_roles` — semua data role sudah migrasi ke tabel Spatie.

### 4. Migration Baru: `seed_default_roles_and_permissions`
Seeder data awal untuk roles dan permissions (bisa via `RoleSeeder` terpisah).

---

## Perubahan Model

### `app/Models/User.php`
- Hapus konstanta `ROLE_SUPERADMIN`, `ROLE_ADMIN`, `ROLE_ATASAN`, `ROLE_TEKNISI`
- Hapus method `isSuperAdmin()`, `isAdmin()`, `isAtasan()`
- Hapus `role_id` dari `$fillable`
- Tambah trait `HasRoles` dari Spatie

---

## Hapus File yang Tidak Diperlukan

| File | Alasan |
|------|--------|
| `app/Http/Middleware/CheckRole.php` | Digantikan middleware Spatie `role` / `permission` |
| `database/migrations/2026_02_25_171337_create_user_roles_table.php` | Tabel `user_roles` tidak diperlukan lagi |

---

## Perubahan Middleware & Kernel

### `app/Http/Kernel.php`
- Hapus baris `'role' => \App\Http\Middleware\CheckRole::class`
- Tambah middleware Spatie:
  ```php
  'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
  'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
  ```

---

## Perubahan Route (`routes/web.php`)

Ganti semua `middleware('role:1,5')` dan sejenisnya dengan Spatie role names:

| Route | Old Middleware | New Middleware |
|-------|---------------|----------------|
| ticket-categories, ticket-priorities | `role:1,5` | `role:super-admin\|admin` |
| technicians, reports | `role:1,5` | `role:super-admin\|admin` |
| comments | `role:1,3,4,5,6` | `role:super-admin\|staff\|teknisi\|admin` (role 6 dihapus) |
| resolve, approve | `role:4` | `role:teknisi` |
| confirm, reopen, update-content | `role:1,3` | `role:super-admin\|staff` |

Di constructor `TicketController` — sama, ganti dengan nama role Spatie.

---

## Perubahan Controller

### `AuthController.php`
- Hapus logic lookup `user_roles` dan `session(['user_role' => ...])`
- Cukup `Auth::attempt()` — role diambil dari Spatie via relasi
- Logout: hapus `session()->forget('user_role')`

### `UserRoleController.php`
- Rewrite total: CRUD sekarang menggunakan API Spatie (`Role::create()`, `$user->assignRole()`, dll)
- Form tidak lagi menyimpan ke tabel `user_roles` tapi ke tabel Spatie
- Method `setPassword()` tetap dipertahankan (untuk bikin user login), tapi ganti cara set role

### `HelpDesk\TicketController.php`
- Ganti semua `auth()->user()->role_id` dengan `auth()->user()->hasRole(...)`
- Ganti `User::where('role_id', User::ROLE_TEKNISI)` dengan `User::role('teknisi')->get()`
- Ganti `User::where('role_id', "=", 5)` dengan `User::role('admin')->first()`

### `HelpDesk\DashboardController.php`
- Ganti `$user->role_id === User::ROLE_ATASAN` dengan `$user->hasRole('staff')`
- Ganti `$user->role_id === User::ROLE_SUPERADMIN` dengan `$user->hasRole('super-admin')`

### `HelpDesk\ReportController.php`
- Ganti `session('user_role')` dengan `auth()->user()->hasRole(...)`

### `TransactionController.php`
- Ganti `auth()->user()->role_id != 3` dengan `auth()->user()->hasAnyRole(...)` atau `hasRole(...)`

### `HelpDesk\TicketCommentController.php`
- Hapus baris `$userRoleId = auth()->user()->role_id;` (tidak dipakai)

### `MicrosoftAuthController.php`
- Setelah user dibuat/ditemukan, assign role default (misal 'staff') via `$user->assignRole('staff')`
- Simpan `session(['user_role' => ...])` bisa dihapus, gunakan `auth()->user()->hasRole()` langsung

### `AppServiceProvider.php`
- Hapus semua `Gate::define(...)` — tidak dipakai sama sekali di codebase

---

## Perubahan Blade Views

### `resources/views/layouts/partials/app-head.blade.php`
- Hapus `$role = session('user_role')` — tidak perlu

### `resources/views/layouts/partials/helpdesk/app-head.blade.php`
- Hapus `$role = session('user_role')`
- Ganti `$authUserRoleId` dengan `auth()->user()->getRoleNames()->first()` atau `auth()->user()->hasRole(...)`

### `resources/views/layouts/partials/app-sidebar.blade.php`
- Ganti `session('user_role')` dengan `auth()->user()->hasRole(...)` / `@role` directive
  - `@if($role != 2)` → `@role('super-admin|admin|staff|teknisi')` atau pakai permission

### `resources/views/layouts/partials/helpdesk/app-sidebar.blade.php`
- Ganti `in_array($authUserRoleId, [1,5])` dengan `@role('super-admin|admin')`
- Ganti `$authUserRoleId == 1 || $authUserRoleId == 5` dengan directive Spatie

### `resources/views/components/setting-role.blade.php`
- Rewrite form untuk menggunakan Spatie roles (nama role, bukan ID numeric)
- Tampilkan badge dengan nama role Spatie
- Hapus ID numeric dari opsi dropdown, ganti dengan nama role

### `resources/views/components/transaction.blade.php`
- Ganti `Auth::user()->role_id == 3` dengan `@cannot` atau `@role`

### `resources/views/helpdesk/dashboard/index.blade.php`
- Ganti `auth()->user()->role_id === App\Models\User::ROLE_SUPERADMIN` dengan `@role('super-admin')`

### `resources/views/helpdesk/tickets/index.blade.php`
- Ganti `session('user_role')` dengan `auth()->user()->hasRole(...)`

### `resources/views/helpdesk/reports/index.blade.php`
- Ganti `session('user_role')` dengan `auth()->user()->hasRole(...)`

---

## Perubahan Seeder

### `database/seeders/DatabaseSeeder.php`
- Tambah panggilan ke `RolePermissionSeeder`

### `database/seeders/UserSeeder.php`
- Hapus `role_id` dari array data
- Setelah `firstOrCreate`, assign role via `$user->assignRole('super-admin')` dll.

### File baru: `database/seeders/RolePermissionSeeder.php`
- Buat roles: `super-admin`, `admin`, `approver`, `staff`, `teknisi`
- Buat permissions jika diperlukan (optional, bisa dimulai tanpa permission dulu)

---

## Role Mapping

| Old ID | Old Name | New Spatie Role Name |
|--------|----------|---------------------|
| 1 | Super Admin | `super-admin` |
| 2 | Approver | `approver` |
| 3 | Staff/Atasan | `staff` |
| 4 | Teknisi | `teknisi` |
| 5 | Admin Help Desk | `admin` |
| 6 | (referenced in route, no data) | —— |

---

## Urutan Eksekusi

1. `composer require spatie/laravel-permission`
2. `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"`
3. Buat migration: `remove_role_id_from_users_table`
4. Buat migration: `drop_user_roles_table`
5. Buat `RolePermissionSeeder`
6. Update `UserSeeder`
7. Update `DatabaseSeeder`
8. Update `User.php` model
9. Hapus `CheckRole.php`, update `Kernel.php`
10. Hapus Gate definitions di `AppServiceProvider`
11. Update `AuthController.php`, `UserRoleController.php`
12. Update semua controller HelpDesk, Transaction
13. Update `MicrosoftAuthController.php`
14. Update semua blade views
15. Update routes
16. Uji coba: `php artisan migrate:fresh --seed`
17. Verifikasi login dan akses tiap role
