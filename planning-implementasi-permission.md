# Planning Implementasi Permission ke Codebase

## 1. Ringkasan Kesenjangan (Gap Analysis)

### 1.1. Permission di Seeder Tapi Belum Diimplementasikan

#### A. Modul AMS (Asset Management System)
| Permission | Lokasi Implementasi yang Diperlukan |
|---|---|
| `portal.access` | Middleware di route `/dashboard` atau controller |
| `ams.dashboard` | Route `/ams/analytics` & sidebar menu AMS |
| `assets.view` | Route `/asset` (index, datatable) |
| `assets.create` | Route `/asset/store` |
| `assets.edit` | Route `/asset/edit/{id}` |
| `assets.delete` | Route `/asset/delete/{id}` |
| `assets.import` | Route `/asset/import` & `/asset/template` |
| `transactions.view` | Route `/transaction` (index, datatable, detail) |
| `transactions.approve` | Route `/transaction/update-status/{id}` |
| `employees.view` | Route `/employee` (index, datatable, show) |
| `employees.manage` | Route `/employee/store`, `/employee/update/{id}`, `/employee/delete/{id}` |
| `employees.import` | Route `/employee/import` & `/employee/template` |
| `master-data.view` | Semua route master data (regional, company, division, category, supplier, dll) - akses baca |
| `assignment.view` | Route `/assignment` (index, datatable) |
| `settings.reset-password` | Route `/settings/reset-password` |
| `settings.approve` | Route `/settings/approve` |

#### B. Modul Helpdesk
| Permission | Lokasi Implementasi yang Diperlukan |
|---|---|
| `helpdesk.dashboard` | Route `/helpdesk` (index) & `/helpdesk/dashboard/chart-data` |
| `tickets.view` | Route `/helpdesk/tickets` (hanya tiket milik sendiri) |
| `technicians.view` | Route `/helpdesk/technicians` (saat ini tergabung dengan middleware `tickets.assign|reports.view`) |
| `reports.export` | Route `/helpdesk/reports/generate-pdf` & `/helpdesk/reports/generate-excel` |

#### C. Modul IT Admin
| Permission | Lokasi Implementasi yang Diperlukan |
|---|---|
| `users.manage` | Route `/it-admin/users/*` (index, datatable, store, edit, set-password, delete) |
| `roles.manage` | Route `/it-admin/roles/*` (index, datatable, store, edit, update, delete, permissions, sync-permissions) |
| `permissions.manage` | Route `/it-admin/roles/permissions/{id}` & `/it-admin/roles/sync-permissions/{id}` (sebaiknya dipisah dari `roles.manage`) |

### 1.2. Permission yang Sudah Terimplementasi (Tidak Perlu Diubah)

| Permission | Diimplementasikan di |
|---|---|
| `ams.menu` | PortalController (menu portal) |
| `helpdesk.menu` | PortalController (menu portal) |
| `data-pegawai.menu` | PortalController (menu portal) |
| `form-it.menu` | PortalController (menu portal) |
| `sop-it.menu` | PortalController (menu portal) |
| `it-admin.access` | Route middleware it-admin.php & dokter.php |
| `transactions.create` | Route, controller, blade |
| `transactions.delete` | Route, controller, blade |
| `transactions.export-pdf` | Route, controller, blade |
| `employees.manage` | - |
| `master-data.manage` | Blade sidebar |
| `assignment.manage` | Blade sidebar |
| `monitoring.view` | Route & blade sidebar |
| `whatsapp-settings.manage` | Route & blade sidebar |
| `tickets.view-all` | Controller, blade |
| `tickets.create` | Route, blade sidebar |
| `tickets.edit` | Route middleware & controller |
| `tickets.delete` | Route middleware & controller |
| `tickets.assign` | Route middleware & controller |
| `tickets.resolve` | Route middleware & controller |
| `tickets.approve` | Route middleware & controller |
| `tickets.confirm` | Route middleware & controller |
| `tickets.reopen` | Route middleware & controller |
| `tickets.comment` | Route middleware & controller |
| `ticket-categories.manage` | Route middleware & blade sidebar |
| `ticket-priorities.manage` | Route middleware |
| `reports.view` | Route middleware & blade sidebar |

### 1.3. Permission yang Ada di Codebase Tapi Tidak Ada di Seeder

| Permission | Lokasi |
|---|---|
| `file-management.download` | routes/routers/dokter.php:18 — middleware pada route download |

### 1.4. Masalah Duplikasi di Seeder

- `it-admin.access` didefinisikan 2 kali: baris 80 dan baris 86 (blok "Dokter" yang salah label)

---

## 2. Rencana Implementasi

### Fase 1: Perbaikan Seeder

1. **Hapus duplikasi `it-admin.access`** (baris 86, blok "Dokter" yang salah)
2. **Tambahkan permission `file-management.download`** ke dalam array `$permissions`
3. **Perbaiki label komentar** — blok "Dokter" (baris 85-87) sebenarnya adalah duplikat dari IT Admin, hapus saja

### Fase 2: Implementasi Permission AMS

#### 2.1. Portal Access
- **Route**: Tambahkan middleware `permission:portal.access` ke route `/dashboard` atau group `/dashboard`
- **Blade**: Tidak perlu (hanya gateway)

#### 2.2. AMS Dashboard
- **Route**: Tambahkan middleware `permission:ams.dashboard` ke route `GET /ams/analytics`

#### 2.3. Asset Management
- **Route `/asset` (index, datatable)** → middleware `permission:assets.view`
- **Route `/asset/store`** → middleware `permission:assets.create`
- **Route `/asset/edit/{id}`** → middleware `permission:assets.edit`
- **Route `/asset/delete/{id}`** → middleware `permission:assets.delete`
- **Route `/asset/import` & `/asset/template`** → middleware `permission:assets.import`
- **Blade**: Sembunyikan tombol Create/Edit/Delete/Import berdasarkan permission

#### 2.4. Transaction
- **Route `/transaction` (index, datatable, detail)** → middleware `permission:transactions.view`
- **Route `/transaction/update-status/{id}`** → middleware `permission:transactions.approve`

#### 2.5. Employee
- **Route `/employee` (index, datatable, show)** → middleware `permission:employees.view`
- **Route `/employee/store`, `/employee/update/{id}`, `/employee/delete/{id}`** → middleware `permission:employees.manage`
- **Route `/employee/import` & `/employee/template`** → middleware `permission:employees.import`

#### 2.6. Master Data (Regional, Company, Division, Satuan Kerja, Posisi, Hirarki, Category, Supplier)
- Semua route GET/index/datatable → middleware `permission:master-data.view`
- Semua route POST/store, PUT/update, DELETE/delete → middleware `permission:master-data.manage`

#### 2.7. Assignment
- **Route `/assignment` (index, datatable)** → middleware `permission:assignment.view`
- **Route `/assignment/store`, `/assignment/update/{id}`, `/assignment/destroy/{id}`** → middleware `permission:assignment.manage` (sudah ada)

#### 2.8. Settings
- **Route `/settings/reset-password`** → middleware `permission:settings.reset-password`
- **Route `/settings/approve`** → middleware `permission:settings.approve`

### Fase 3: Implementasi Permission Helpdesk

#### 3.1. Helpdesk Dashboard
- **Route `/helpdesk` & `/helpdesk/dashboard/chart-data`** → middleware `permission:helpdesk.dashboard`

#### 3.2. Tickets View (milik sendiri)
- **Route `/helpdesk/tickets` (index resource)** → middleware `permission:tickets.view` untuk filter tiket milik sendiri (berbeda dengan `tickets.view-all`)

#### 3.3. Technicians View
- **Route `/helpdesk/technicians`** → Pisahkan dari middleware group `tickets.assign|reports.view`, buat middleware `permission:technicians.view` sendiri

#### 3.4. Reports Export
- **Route `/helpdesk/reports/generate-pdf` & `/helpdesk/reports/generate-excel`** → middleware `permission:reports.export`

### Fase 4: Implementasi Permission IT Admin

#### 4.1. Users Manage
- **Route `/it-admin/users/*`** → middleware `permission:users.manage` (pisahkan dari `it-admin.access`)

#### 4.2. Roles Manage
- **Route `/it-admin/roles/*`** → middleware `permission:roles.manage`

#### 4.3. Permissions Manage
- **Route `/it-admin/roles/permissions/{id}` & `/it-admin/roles/sync-permissions/{id}`** → middleware `permission:permissions.manage`

### Fase 5: Update Role Assignment di Seeder

Setelah semua permission diimplementasikan, update pemberian permission ke role di seeder (baris 98-149) sesuai kebutuhan bisnis:

| Role | Permission Baru yang Perlu Ditambahkan |
|---|---|
| **admin** | Semua permission baru |
| **approver** | `transactions.approve`, `settings.approve`, `master-data.view`, `assignment.view` |
| **staff** | `master-data.view`, `assignment.view`, `assets.view`, `transactions.view` |
| **teknisi** | `tickets.view`, `helpdesk.dashboard`, `technicians.view` |

### Fase 6: Update Blade Views

Tambahkan `@can` / `@cannot` di blade views untuk menyesuaikan tampilan:
- `resources/views/layouts/partials/app-sidebar.blade.php` — untuk menu AMS, master data, assignment, dll
- Blade views asset, employee, transaction — untuk tombol aksi
- Blade views helpdesk — untuk tombol export, view teknisi

---

## 3. Prioritas Implementasi

| Prioritas | Fase | Keterangan |
|---|---|---|
| **P0** | Fase 1 | Perbaikan seeder (duplikasi + missing permission) |
| **P1** | Fase 2 | Permission AMS (asset, transaction, employee, master data) — area inti |
| **P1** | Fase 4 | Permission IT Admin (users, roles, permissions) |
| **P2** | Fase 3 | Permission Helpdesk (dashboard, technicians, export) |
| **P3** | Fase 5 | Update role assignment di seeder |
| **P3** | Fase 6 | Update blade views (sembunyikan tombol yang tidak diizinkan) |
