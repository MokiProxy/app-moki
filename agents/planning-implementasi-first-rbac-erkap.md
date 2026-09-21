# Planning: Implementasi RBAC Pertama di Modul E-RKAP (Role `erkap-cost-owner` & Autofill Divisi)

## Overview

Fitur ini menambahkan sistem **Role-Based Access Control (RBAC) layer pertama untuk modul E-RKAP**:

1. **Perlindungan fitur input** untuk menu **"Sasaran Asesmen Risiko" → "Jadwal Kerja" → "Biaya Umum"**
   (10 resource) agar hanya user dengan role **`erkap-cost-owner`** (beserta `admin` / `super-admin`)
   yang bisa mengakses & melakukan input (create/update/delete).
2. **Autofill field "Divisi"** pada form **Sasaran Departemen** (`erkap_department_targets`) yang
   diisi otomatis sesuai divisi yang berelasi dengan user yang login
   (`users.employee_id → employees.division_id → divisions.id`).

Role `erkap-cost-owner` dibuat baru; permission mengikuti konvensi granular yang sudah dipakai
proyek (`<modul>.<entity>.<action>`, seperti `ams.assets.view`, `helpdesk.tickets.create`, dst).

---

## 1. Analisis Kondisi Saat Ini

### 1.1 Modul E-RKAP — Route & Struktur Menu

Route modul E-RKAP didefinisikan di `routes/routers/erkap.php` (di-load dari `routes/web.php` line 63 di
dalam grup `middleware('auth')`). **Tidak ada middleware permission** — hanya butuh login.

Struktur hierarki data (semua tabel berprefix `erkap_`):

```
erkap_rkap (periode/tahun RKAP)
  └─ erkap_company_targets            → "Sasaran Perusahaan"     [company-targets]
      └─ erkap_department_targets     → "Sasaran Departemen"     [department-targets]  ← field division_id → divisions
          └─ erkap_risk_identifications → "Identifikasi Risiko"  [risk-identifications]
              ├─ erkap_risk_identification_reasons   → "Alasan"  [risk-identification-reasons]
              ├─ erkap_risk_identification_impacts   → "Dampak"  [risk-identification-impacts]
              ├─ erkap_risk_analysis                  → "Analisis Risiko" [risk-analysis]
              ├─ erkap_risk_rankings                  → "Peringkat"      [risk-rankings]
              ├─ erkap_department_risk_strategies     → "Strategi"       [department-risk-strategies]
              └─ erkap_work_programs  → "Program Kerja" (Jadwal Kerja)    [work-programs]
                  └─ erkap_routine_costs → "Biaya Rutin" (Biaya Umum)      [routine-costs]
```

Menu sidebar `resources/views/layouts/partials/erkap/app-sidebar.blade.php` membagi menjadi:

| Menu grup (sidebar) | Items (route prefix) |
|---|---|
| **Master Data** | cost-element-categories, cost-elements, risk-appetites, risk-taxonomies, risk-types, rating-criterias, risk-scales, risk-probabilities, risk-impacts, risk-score-levels, investation-types, investation-criterias, investattion-categories, rkap |
| **Sasaran Asesmen Risiko** | company-targets, department-targets, risk-identifications, risk-identification-reasons, risk-identification-impacts, risk-analysis, risk-rankings, department-risk-strategies |
| **Jadwal Kerja** | work-programs |
| **Biaya Umum** | company-targets (link "Barang / Jasa" — placeholder, lihat catatan), routine-costs |

> Observasi: item sidebar "Barang / Jasa" di grup "Biaya Umum" (line 173) memakai link
> `erkap.company-targets.index` — kemungkinan placeholder/bug karena reuse halaman Sasaran Perusahaan.
> Di luar scope fitur ini; dicatat untuk ditindaklanjuti terpisah.

### 1.2 Sistem Role/Permission Saat Ini

- Paket **`spatie/laravel-permission` ^6.25** (`composer.json` line 23). Tabel: `roles`, `permissions`,
  `model_has_roles`, `role_has_permissions`, `model_has_permissions`.
- `app/Models/User.php` menggunakan trait `HasRoles`.
- **`super-admin` bypass semua** lewat `Gate::before` di `app/Providers/AppServiceProvider.php`
  (role `super-admin` → `return true`).
- Role & permission didefinisikan di `database/seeders/RolePermissionSeeder.php`
  (14 role, 103 permission, convention `module.entity.action`).
- **Belum ada permission `erkap.*` sama sekali.** Kartu portal "E-RKAP" di
  `app/Http/Controllers/PortalController.php` (line 92–99) memakai permission `eqtax.menu` (salah/dipinjam).
- Middleware tersedia: `permission` (Spatie), `role` (Spatie), `can` (Laravel) — terdaftar di
  `app/Http/Kernel.php`. Route-level `permission:` dipakai luas di modul lain; middleware Spatie
  mendukung OR antar-permission via `|`.
- Tidak ada Policy class; autorisasi seluruhnya via Spatie (route middleware + `@can` + `hasPermissionTo`).
- Penugasan role ke user sudah tersedia melalui **IT Admin → Users**
  (`it-admin/users`, permission `it-admin.users.manage`) tanpa perlu UI baru.

### 1.3 Relasi User → Divisi (untuk Autofill)

Tidak ada pivot. Rantai relasi:

```
users.employee_id ──→ employees.employee_id ──→ employees.division_id ──→ divisions.id
```

- `User::employee()` — BelongsTo ke `Employee` via `employee_id` (`app/Models/User.php:60-67`).
- `Employee::division()` — BelongsTo ke `Division` via `division_id` (`app/Models/Employee.php:24-27`).
- Akses cukup: `auth()->user()->employee?->division` atau `->division_id`.

### 1.4 Kondisi Form Sasaran Departemen Saat Ini

- Controller: `app/Http/Controllers/Erkap/DepartmentTargetController.php` — `create()`/`edit()` me-load
  SEMUA `Division` (`Division::all()`) lalu pass `$divisions` ke view.
- View `resources/views/erkap/department-target/create.blade.php` (dan `edit.blade.php`): field `division_id`
  berupa `<select>` bebas ("Pilih Divisi") yang bisa dipilih user.
- Request `app/Http/Requests/StoreDepartmentTargetRequest.php` (dan Update): `division_id` required,
  `exists:divisions,id`. `authorize()` mengembalikan `true`.
- Index hanya filter pagination; **tidak ada isolasi per divisi**.

---

## 2. Ruang Lingkup Fitur yang Dikunci (10 Resource)

Lima menu/area berikut (dari sidebar) hanya boleh diinput user ber-role `erkap-cost-owner`:

| # | Resource group (route prefix) | Label | Permission prefix |
|---|---|---|---|
| 1 | `company-targets` | Sasaran Perusahaan | `erkap.company-targets` |
| 2 | `department-targets` | Sasaran Departemen | `erkap.department-targets` |
| 3 | `risk-identifications` | Identifikasi Risiko | `erkap.risk-identifications` |
| 4 | `risk-identification-reasons` | Alasan Identifikasi | `erkap.risk-identification-reasons` |
| 5 | `risk-identification-impacts` | Dampak Identifikasi | `erkap.risk-identification-impacts` |
| 6 | `risk-analysis` | Analisis Risiko | `erkap.risk-analysis` |
| 7 | `risk-rankings` | Peringkat Risiko | `erkap.risk-rankings` |
| 8 | `department-risk-strategies` | Strategi Risiko Departemen | `erkap.department-risk-strategies` |
| 9 | `work-programs` | Program Kerja (Jadwal Kerja) | `erkap.work-programs` |
| 10 | `routine-costs` | Biaya Rutin (Biaya Umum) | `erkap.routine-costs` |

> **Di luar scope:** grup **Master Data** (14 resource) tetap dapat diakses semua user ber-login
> (perilaku saat ini), atau alternatif ikut dibatasi ke `admin`/`super-admin` — perlu konfirmasi (lihat
> Bagian 5). Kartu portal E-RKAP juga dapat diberi permission khusus `erkap.menu` (opsional).

---

## 3. Desain Solusi

### 3.1 Role Baru `erkap-cost-owner` + Permission Baru

Tambahkan **40 permission baru** di `RolePermissionSeeder.php` (konvensi `module.entity.action`,
guard `web`), + opsional `erkap.menu`:

```
erkap.company-targets.{view,create,edit,delete}
erkap.department-targets.{view,create,edit,delete}
erkap.risk-identifications.{view,create,edit,delete}
erkap.risk-identification-reasons.{view,create,edit,delete}
erkap.risk-identification-impacts.{view,create,edit,delete}
erkap.risk-analysis.{view,create,edit,delete}
erkap.risk-rankings.{view,create,edit,delete}
erkap.department-risk-strategies.{view,create,edit,delete}
erkap.work-programs.{view,create,edit,delete}
erkap.routine-costs.{view,create,edit,delete}

(opsional) erkap.menu
```

**Role `erkap-cost-owner`** diberi seluruh permission di atas (40 + `erkap.menu`).

**Role `admin`** — sesuai pola "hampir semua permission", tambahkan ke daftar `$admin->givePermissionTo([...])`
semua permission `erkap.*` agar admin tetap bisa mengelola.

**Role `super-admin`** otomatis dapat semua via `syncPermissions($allPermissions)` (sudah ada).

> Catatan: parameter "delete" bisa dipisahkan jika user `erkap-cost-owner` TIDAK boleh menghapus data
> (hanya input/create-edit). Lihat Bagian 5 (Keputusan).

Setelah seeding, jalankan:
```
php artisan db:seed --class=RolePermissionSeeder
php artisan permission:cache-reset
```

### 3.2 Proteksi Route (`permission:` middleware)

Di `routes/routers/erkap.php`, tambahkan middleware per-route untuk 10 resource di atas:

```
index            → middleware('permission:erkap.<prefix>.view')
create, store    → middleware('permission:erkap.<prefix>.create')
edit, update     → middleware('permission:erkap.<prefix>.edit')
destroy          → middleware('permission:erkap.<prefix>.delete')
```

Khusus route AJAX `get-score-level/{probabilityId}/{impactId}` (dipakai form Analisis Risiko):
`middleware('permission:erkap.risk-analysis.view|erkap.risk-analysis.create|erkap.risk-analysis.edit')`
(Spatie mendukung OR via `|`).

Form Request tetap ada; autorisasi ditangani middleware route (pola yang sama dengan modul lain).
Request `authorize()` boleh ditambah check `hasPermissionTo(...)` sebagai defense-in-depth (opsional).

### 3.3 Batasi Tampilan Menu Sidebar

Di `resources/views/layouts/partials/erkap/app-sidebar.blade.php`, sembunyikan menu
**Sasaran Asesmen Risiko**, **Jadwal Kerja**, **Biaya Umum** bila user tidak punya permission terkait:

- Bungkus menu **Sasaran Asesmen Risiko** dengan
  `@can('erkap.company-targets.view')` (atau gabungan `@can`/`@canany`).
- Menu **Jadwal Kerja** → `@can('erkap.work-programs.view')`.
- Menu **Biaya Umum** → `@can('erkap.routine-costs.view')` (dan `company-targets.view` utk "Barang/Jasa").
- Master Data tetap tampil (perilaku saat ini).

`@can` secara otomatis mempertimbangkan `Gate::before` super-admin → admin/super-admin tetap melihat.

### 3.4 Autofill Divisi di Sasaran Departemen

**Tujuan:** field `division_id` di form `department-targets` diisi otomatis dari divisi user login.

**Perilaku per role:**

- User dengan role **`erkap-cost-owner`** → divisi **di-lock** = divisinya sendiri; tidak bisa diganti manual.
- User **`admin` / `super-admin`** → tetap tampil dropdown pilih divisi (bebas), agar bisa mengelola semua divisi.
  (Pengecekan berbasis role `hasRole('erkap-cost-owner')` — proporsional dengan kebutuhan divisi otomatis.)

**Implementasi:**

1. Bantu helper untuk mengambil divisi user — tambahkan method di `app/Models/User.php`:
   ```php
   public function division()
   {
       return $this->employee?->division;
   }
   ```
   (atau akses langsung `auth()->user()->employee->division_id`; usul helper untuk dipakai konsisten.)

2. `DepartmentTargetController::create()`:
   - Jika `auth()->user()->hasRole('erkap-cost-owner')` → ambil `$userDivisionId = auth()->user()->employee->division_id`,
     kirim ke view (mis. `$userDivision`), dan jangan kirim semua `$divisions` (atau kirim tapi view render readonly/hidden).
   - User tanpa record employee (divisi null) → tampilkan pesan error/info & blokir akses form.

3. View `create.blade.php` & `edit.blade.php`:
   - Mode cost-owner: render `division_id` sebagai `<input type="hidden">` + teks readonly nama divisi
     (mis. badge "Divisi: IT — otomatis dari user Anda").
   - Mode admin/super-admin: pertahankan `<select>` seperti sekarang.

4. `DepartmentTargetController::store()` / `update()` — **selalu override server-side** agar tidak bisa
   di-tamper client:
   ```php
   $data = $request->validated();
   if (auth()->user()->hasRole('erkap-cost-owner')) {
       $data['division_id'] = auth()->user()->employee->division_id;
   }
   DepartmentTarget::create($data); // atau $departmentTarget->update($data)
   ```
   Validation `division_id` di Request tetap ada (dihasilkan dari hidden input), sehingga tidak perlu ubah
   aturan validasi.

### 3.5 (Opsi, Direkomendasikan) Isolasi Data per Divisi di Index

Agar user cost-owner hanya melihat datanya sendiri:
- `DepartmentTargetController::index()`: jika cost-owner,
  filter `DepartmentTarget::where('division_id', $userDivisionId)`.
- Optional memperdalam ke resource anak (risk-identification → hanya department target milik divisinya;
  per-tampilan create/edit dropdown identifikasi/strategi/program/biaya dibatasi data divisi user).
- `RiskIdentificationController`, `WorkProgramController`, `RoutineCostController`, dll. menyesuaikan data
  dropdown jika isolasi ini diterapkan. **Konfirmasi dulu** (lihat Bagian 5) karena memperluas scope.

### 3.6 Portal Card E-RKAP (opsional)

Ganti permission `eqtax.menu` → `erkap.menu` untuk kartu E-RKAP di `PortalController.php`,
lalu tambahkan `erkap.menu` ke role `erkap-cost-owner` dan `admin`. Ini mencegah user lain melihat/membuka
modul E-RKAP sama sekali.

---

## 4. Files yang Perlu Diubah

| No | File | Tipe | Perubahan |
|----|------|------|-----------|
| 1 | `database/seeders/RolePermissionSeeder.php` | Ubah | Tambah role `erkap-cost-owner`; tambah 40+1 permission `erkap.*`; assign ke role baru, tambahkan ke daftar `$admin->givePermissionTo(...)` |
| 2 | `routes/routers/erkap.php` | Ubah | Tambahkan `permission:` middleware pada 10 grup resource (view/create/edit/delete) + route `get-score-level` (OR permission) |
| 3 | `app/Models/User.php` | Ubah | (opsional) tambah method `division()` helper |
| 4 | `app/Http/Controllers/Erkap/DepartmentTargetController.php` | Ubah | `create/edit`: ambil `$userDivision` saat cost-owner; `store/update`: override `division_id`; (opsional) `index`: filter divisi saat cost-owner |
| 5 | `resources/views/erkap/department-target/create.blade.php` | Ubah | Field `division_id` → hidden + teks readonly saat cost-owner; select saat admin |
| 6 | `resources/views/erkap/department-target/edit.blade.php` | Ubah | Sama seperti create |
| 7 | `resources/views/layouts/partials/erkap/app-sidebar.blade.php` | Ubah | Bungkus menu Sasaran/Jadwal/Biaya dengan `@can` |
| 8 | `app/Http/Controllers/PortalController.php` | Ubah (opsional) | Permission kartu E-RKAP `eqtax.menu` → `erkap.menu` |
| 9 | `database/seeders/UserSeeder.php` | Ubah (opsional) | Beri role `erkap-cost-owner` pada minimal 1 test user agar mudah diuji |

**Tidak perlu diubah:** tabel DB (tidak ada kolom/migration baru; divisi sudah ada FK
`erkap_department_targets.division_id`); model `DepartmentTarget`; seluruh controller erkap lain
(kecuali isolasi data opsional di Bagian 3.5); `config/permission.php`.

---

## 5. Keputusan yang Perlu Dikonfirmasi

1. **Hak `delete` untuk cost-owner?** User spesifik menyebut "diinput" (input). Opsi:
   - (a) Role diberi view + create + edit saja; delete hanya admin/super-admin. *(Rekomendasi)*
   - (b) Role diberi full termasuk delete.
2. **Isolasi data per divisi (Bagian 3.5)?** Apakah user cost-owner hanya boleh melihat data divisi
   sendiri di index & dropdown terkait, atau cukup autofill divisi saja?
   - (a) Autofill saja *(minimal, sesuai request)*
   - (b) Autofill + filter index per divisi
   - (c) Autofill + filter index + pembatasan dropdown data anak (scope terluas)
3. **Master Data E-RKAP** tetap terbuka untuk semua user ber-login, atau ikut dibatasi
   (`admin`/`super-admin`)? *(Di luar scope request; perilaku sekarang: terbuka.)*
4. **Admin** pada form Sasaran Departemen: dropdown bebas (seperti sekarang) atau ikut di-lock ke divisinya?
   *(Rekomendasi: admin tetap dropdown bebas.)*
5. **Portal card / akses masuk modul**: terapkan `erkap.menu` (opsional) atau biarkan semua user ber-login
   tetap bisa buka modul (mis. untuk lihat Master Data).
6. **"Barang / Jasa"** di sidebar menunjuk ke `company-targets.index` — tetap dibiarkan (perilaku saat ini)
   atau dibuat halaman khusus di luar scope fitur ini?

---

## 6. Edge Cases

| Skenario | Penanganan |
|----------|------------|
| User cost-owner tanpa relasi `employees` (employee_id null atau divisi null) | Blokir akses form create/edit dengan pesan jelas; store/update tolak (validasi `division_id` tidak terisi / pesan khusus) |
| User ber-role cost-owner sekaligus `admin` | Pengecekan divisi `hasRole('erkap-cost-owner')` → tetap autofill (paling spesifik) — atau diprioritaskan admin. Konfirmasi prioritas role |
| Data lama dengan divisi berbeda saat cost-owner edit | Form menampilkan divisi auto user; `update()` override memindahkan record ke divisi user (perlu konfirmasi: izinkan pindah divisi via edit, atau blokir edit data lintas divisi) |
| Supervisor/super-admin bypass | `Gate::before` super-admin tetap lolos semua `@can` & `permission:` (by design) |
| User tanpa role apa pun / staff | Tidak punya `erkap.*` permission → route 403, menu tersembunyi |
| AJAX `get-score-level` dipanggil user dengan hak edit tapi belum create | OR permission `view|create|edit` mengakomodasi |
| Cache permission setelah seeder | `php artisan permission:cache-reset` / `optimize:clear` wajib dijalankan agar role baru dikenali |

---

## 7. Checklist Implementasi

- [ ] Tambah role `erkap-cost-owner` + 40/41 permission `erkap.*` di `RolePermissionSeeder.php`
- [ ] Assign permission ke role baru & tambahkan ke role `admin`
- [ ] Tambahkan `permission:` middleware pada 10 resource group di `routes/routers/erkap.php`
  (+ route `get-score-level` dengan OR permission)
- [ ] (Opsional) Tambah method `division()` di `User` model
- [ ] Ubah `DepartmentTargetController`: autofill `division_id` pada create/edit/store/update utk cost-owner
- [ ] Ubah view `create`/`edit` department-target (hidden input + teks readonly saat cost-owner)
- [ ] (Opsional per konfirmasi) Filter index & dropdown data anak per divisi
- [ ] Bungkus menu sidebar Sasaran Asesmen Risiko / Jadwal Kerja / Biaya Umum dengan `@can`
- [ ] (Opsional) Ganti permission kartu E-RKAP portal → `erkap.menu`
- [ ] (Opsional) Assign role `erkap-cost-owner` ke test user di `UserSeeder`
- [ ] Jalankan `db:seed --class=RolePermissionSeeder` + `permission:cache-reset`
- [ ] Uji: login sbg cost-owner → akses menu Sasaran..Biaya OK, master data tetap tampil
- [ ] Uji: login sbg user tanpa role → route 403 / menu tersembunyi
- [ ] Uji: form Sasaran Departemen sbg cost-owner → divisi terkunci sesuai divisi user
- [ ] Uji: store/update divisi tidak bisa di-tamper (override server)
- [ ] Uji: admin/super-admin tetap bisa pilih divisi & akses semua fitur