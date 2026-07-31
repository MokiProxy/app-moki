# Planning: Refactor Nama Permission & Implementasi Permission Dokter

## 1. Tujuan
- Merapikan penamaan permission dengan prefix module: `ams.`, `helpdesk.`, `it-admin.`, `dokter.`
- Mengupdate seluruh implementasi (routes, controllers, blade) sesuai nama permission baru
- Membuat seeder permission untuk module Dokter
- Mengimplementasikan permission Dokter ke dalam codebase

---

## 2. Mapping Nama Permission Lama → Baru

### 2.1. Portal & Menu (tetap, tidak diubah)
| Nama Lama | Nama Baru | Keterangan |
|---|---|---|
| `portal.access` | `portal.access` | Tidak berubah |
| `ams.menu` | `ams.menu` | Tidak berubah |
| `helpdesk.menu` | `helpdesk.menu` | Tidak berubah |
| `data-pegawai.menu` | `data-pegawai.menu` | Tidak berubah |
| `form-it.menu` | `form-it.menu` | Tidak berubah |
| `sop-it.menu` | `sop-it.menu` | Tidak berubah |

### 2.2. AMS — Prefix `ams.`
| Nama Lama | Nama Baru |
|---|---|
| `ams.dashboard` | `ams.dashboard` (sama) |
| `assets.view` | `ams.assets.view` |
| `assets.create` | `ams.assets.create` |
| `assets.edit` | `ams.assets.edit` |
| `assets.delete` | `ams.assets.delete` |
| `assets.import` | `ams.assets.import` |
| `transactions.view` | `ams.transactions.view` |
| `transactions.create` | `ams.transactions.create` |
| `transactions.delete` | `ams.transactions.delete` |
| `transactions.approve` | `ams.transactions.approve` |
| `transactions.export-pdf` | `ams.transactions.export-pdf` |
| `employees.view` | `ams.employees.view` |
| `employees.manage` | `ams.employees.manage` |
| `employees.import` | `ams.employees.import` |
| `master-data.view` | `ams.master-data.view` |
| `master-data.manage` | `ams.master-data.manage` |
| `assignment.view` | `ams.assignment.view` |
| `assignment.manage` | `ams.assignment.manage` |
| `monitoring.view` | `ams.monitoring.view` |
| `whatsapp-settings.manage` | `ams.whatsapp-settings.manage` |
| `settings.reset-password` | `ams.settings.reset-password` |
| `settings.approve` | `ams.settings.approve` |

### 2.3. Helpdesk — Prefix `helpdesk.`
| Nama Lama | Nama Baru |
|---|---|
| `helpdesk.dashboard` | `helpdesk.dashboard` (sama) |
| `tickets.view` | `helpdesk.tickets.view` |
| `tickets.view-all` | `helpdesk.tickets.view-all` |
| `tickets.create` | `helpdesk.tickets.create` |
| `tickets.edit` | `helpdesk.tickets.edit` |
| `tickets.delete` | `helpdesk.tickets.delete` |
| `tickets.assign` | `helpdesk.tickets.assign` |
| `tickets.resolve` | `helpdesk.tickets.resolve` |
| `tickets.approve` | `helpdesk.tickets.approve` |
| `tickets.confirm` | `helpdesk.tickets.confirm` |
| `tickets.reopen` | `helpdesk.tickets.reopen` |
| `tickets.comment` | `helpdesk.tickets.comment` |
| `ticket-categories.manage` | `helpdesk.ticket-categories.manage` |
| `ticket-priorities.manage` | `helpdesk.ticket-priorities.manage` |
| `technicians.view` | `helpdesk.technicians.view` |
| `reports.view` | `helpdesk.reports.view` |
| `reports.export` | `helpdesk.reports.export` |

### 2.4. IT Admin — Prefix `it-admin.`
| Nama Lama | Nama Baru |
|---|---|
| `it-admin.access` | `it-admin.access` (sama) |
| `users.manage` | `it-admin.users.manage` |
| `roles.manage` | `it-admin.roles.manage` |
| `permissions.manage` | `it-admin.permissions.manage` |

### 2.5. Dokter — Prefix `dokter.` (BARU)
| Nama Permission | Keterangan |
|---|---|
| `dokter.menu` | Menu portal untuk module Dokter |
| `dokter.dashboard` | Halaman dashboard Dokter |
| `dokter.vendors.view` | Melihat daftar vendor |
| `dokter.vendors.create` | Membuat vendor baru |
| `dokter.vendors.edit` | Mengedit vendor |
| `dokter.vendors.delete` | Menghapus vendor |
| `dokter.document-types.view` | Melihat daftar jenis dokumen |
| `dokter.document-types.create` | Membuat jenis dokumen baru |
| `dokter.document-types.edit` | Mengedit jenis dokumen |
| `dokter.document-types.delete` | Menghapus jenis dokumen |
| `dokter.file-managements.view` | Melihat file management & viewer PDF |
| `dokter.file-managements.download` | Mendownload file |

---

## 3. Daftar File yang Perlu Diubah

### 3.1. File Utama (Wajib Diubah)
| No | File | Perubahan |
|---|---|---|
| 1 | `database/seeders/RolePermissionSeeder.php` | Semua nama permission, role assignments, tambah permission dokter |
| 2 | `routes/web.php` | Semua middleware `permission:` yang mengandung prefix AMS |
| 3 | `routes/routers/helpdesk.php` | Semua middleware `permission:` helpdesk |
| 4 | `routes/routers/it-admin.php` | Semua middleware `permission:` it-admin |
| 5 | `routes/routers/dokter.php` | Ubah middleware, tambah permission baru |
| 6 | `app/Http/Controllers/PortalController.php` | Permission keys di menu array |
| 7 | `app/Http/Controllers/TransactionController.php` | `hasPermissionTo()` calls |
| 8 | `app/Http/Controllers/HelpDesk/TicketController.php` | `hasPermissionTo()`, middleware konstruktor |
| 9 | `app/Http/Controllers/HelpDesk/DashboardController.php` | `hasPermissionTo()` calls |
| 10 | `app/Http/Controllers/HelpDesk/ReportController.php` | `hasPermissionTo()` calls |

### 3.2. Blade Views (Wajib Diubah)
| No | File | Perubahan |
|---|---|---|
| 11 | `resources/views/portal.blade.php` | `@can($menu["permission"])` |
| 12 | `resources/views/layouts/partials/app-sidebar.blade.php` | Semua `@can()` |
| 13 | `resources/views/layouts/partials/helpdesk/app-sidebar.blade.php` | Semua `@can()` |
| 14 | `resources/views/components/transaction.blade.php` | `@can()`, `@cannot()`, `hasPermissionTo()` |
| 15 | `resources/views/helpdesk/dashboard/index.blade.php` | `@can()` calls |
| 16 | `resources/views/helpdesk/tickets/index.blade.php` | `hasPermissionTo()` |

### 3.3. File Dokter (Baru/Diubah)
| No | File | Perubahan |
|---|---|---|
| 17 | `routes/routers/dokter.php` | Tambah permission middleware untuk setiap route group |
| 18 | `resources/views/layouts/partials/dokter/app-sidebar.blade.php` | Tambah `@can()` untuk menu |

---

## 4. Rencana Implementasi per Fase

### Fase 1: Update Seeder
- Ubah semua nama permission di array `$permissions`
- Tambah permission Dokter
- Update role assignments dengan nama permission baru
- Role super-admin tidak perlu diubah (Gate before bypass)

### Fase 2: Update Routes
- **web.php**: ganti semua string permission di middleware
- **helpdesk.php**: ganti semua string permission
- **it-admin.php**: ganti semua string permission
- **dokter.php**: tambah middleware permission untuk:
  - `dokter.dashboard` → dashboard & chart-data (jika ada)
  - `dokter.vendors.view` → index vendor
  - `dokter.vendors.create` → create/store vendor
  - `dokter.vendors.edit` → edit/update vendor
  - `dokter.vendors.delete` → destroy vendor
  - `dokter.document-types.view` → index document types
  - `dokter.document-types.create` → create/store document types
  - `dokter.document-types.edit` → edit/update document types
  - `dokter.document-types.delete` → destroy document types
  - `dokter.file-managements.view` → index & view file management
  - `dokter.file-managements.download` → download file

### Fase 3: Update Controllers
- PortalController: update permission keys di menu array (tambah `dokter.menu`)
- TransactionController: update `hasPermissionTo()` strings
- TicketController: update `hasPermissionTo()` & middleware strings
- DashboardController (Helpdesk): update `hasPermissionTo()` strings
- ReportController (Helpdesk): update `hasPermissionTo()` strings

### Fase 4: Update Blade Views
- portal.blade.php: sudah otomatis menggunakan `$menu["permission"]`
- app-sidebar.blade.php: update semua string di `@can()`
- helpdesk/app-sidebar.blade.php: update semua string di `@can()`
- transaction.blade.php: update `@can()`, `@cannot()`, `hasPermissionTo()`
- helpdesk/dashboard/index.blade.php: update `@can()`
- helpdesk/tickets/index.blade.php: update `hasPermissionTo()`
- dokter/app-sidebar.blade.php: tambah menu items dengan `@can()`
- PortalController: tambah menu item untuk Dokter dengan permission `dokter.menu`

### Fase 5: Update Role Assignments
Sesuaikan pemberian permission ke role di seeder:
| Role | Permission Baru |
|---|---|
| admin | Semua permission termasuk dokter.* |
| approver | `ams.*` (read), `helpdesk.*` (read) |
| staff | `ams.*` (read/basic), `helpdesk.*` (basic) |
| teknisi | `helpdesk.*` (tickets) |

---

## 5. Prioritas & Urutan Pengerjaan

| Prioritas | Fase | Estimasi |
|---|---|---|
| P0 | Fase 1 (Seeder) | 15 menit |
| P0 | Fase 2 (Routes) | 30 menit |
| P1 | Fase 3 (Controllers) | 20 menit |
| P1 | Fase 4 (Blade Views) | 25 menit |
| P2 | Fase 5 (Role Assignments) | 10 menit |

**Total estimasi: ~100 menit**

---

## 6. Catatan Penting
1. Setelah semua perubahan, jalankan `php artisan cache:forget spatie.permission.cache` dan `php artisan db:seed --class=RolePermissionSeeder` untuk mengaplikasikan permission baru
2. Permission `portal.access`, `ams.menu`, `helpdesk.menu`, `data-pegawai.menu`, `form-it.menu`, `sop-it.menu` tetap menggunakan nama lama karena merupakan permission menu portal yang sudah standar
3. Module Dokter menggunakan route prefix `dokter` yang sudah ada, hanya ditambahkan middleware permission
4. Dokter route group masih menggunakan `it-admin.access` sebagai base access (karena hanya admin yang bisa akses), ditambah permission spesifik per fitur
