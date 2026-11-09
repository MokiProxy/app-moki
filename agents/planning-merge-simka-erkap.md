# Planning: Penggabungan Modul E-RKAP ke Modul SIMKA

> **Status:** IMPLEMENTED — Fase A, Fase B, dan cleanup Fase C sudah dikerjakan (9 November 2026).
> **Tanggal:** 9 November 2026
> **Modul tujuan:** SIMKA
> **Modul yang digabung:** E-RKAP
> **Ruang lingkup:** menu, navigasi, layout, route, permission, dan strategi database. Bukan rewrite logika bisnis.

---

## 0. Ringkasan Eksekutif

Penggabungan E-RKAP ke SIMKA pada codebase ini **bukan penggabungan dua database**. Kedua modul sudah memakai database PostgreSQL yang sama (`asset_management_system`) dan hanya dipisahkan oleh:

1. **Prefix URL**: `/simka` vs `/erkap`.
2. **Prefix nama route**: `simka.*` vs `erkap.*`.
3. **Namespace kode**: `App\Http\Controllers\SIMKA` vs `App\Http\Controllers\Erkap`, serta model SIMKA di root `App\Models` vs model E-RKAP di `App\Models\Erkap`.
4. **Prefix tabel**: `simka_*` vs `erkap_*` (ditambah beberapa tabel bersama tanpa prefix).
5. **Layout & sidebar terpisah**: `layouts/Simka.blade.php` vs `layouts/Erkap.blade.php`.

Karena **tidak ada foreign key yang menghubungkan tabel SIMKA ke tabel E-RKAP**, merge yang paling aman dan bernilai adalah **konsolidasi modul di level UI/navigasi/otorisasi**, bukan rename tabel atau rewrite namespace.

Rekomendasi utama:

- **Satu kartu portal:** hanya “SIMKA”.
- **Satu layout:** `layouts.Simka`.
- **Satu sidebar** dengan dua kategori:
  - `Pendapatan: SIMKA`
  - `Pengeluaran: E-RKAP`
- **Pertahankan route name `erkap.*` dan permission `erkap.*`** pada fase awal agar 748 pemanggilan `route('erkap.*')`, 288 route, 1.919 baris test, dan matriks role yang sudah ada tidak rusak.
- **Lakukan dalam 2 tahap wajib + 1 tahap opsional:**
  1. **Tahap A — UI & otorisasi merge** (risiko rendah, dampak langsung terlihat).
  2. **Tahap B — unifikasi URL** `/erkap/*` → `/simka/erkap/*` dengan tetap mempertahankan route name.
  3. **Tahap C — cleanup opsional** (pindah folder view, rename route/permission, dsb.).

---

## 1. Tujuan, Non-Tujuan, dan Prinsip

### 1.1 Tujuan

1. Hanya ada **satu modul** di portal: SIMKA.
2. Seluruh menu dan fitur SIMKA yang sekarang tetap tersedia.
3. Seluruh menu dan fitur E-RKAP tetap tersedia dan dapat diakses dari SIMKA.
4. Sidebar memisahkan dua domain dengan jelas:
   - **Pendapatan: SIMKA**
   - **Pengeluaran: E-RKAP**
5. Hak akses existing tidak hilang.
6. Tidak ada perubahan skema database yang berisiko.
7. Perubahan dapat diuji, di-deploy, dan di-rollback secara bertahap.

### 1.2 Non-Tujuan (fase merge)

1. **Tidak** me-rename tabel `erkap_*` menjadi `simka_*` atau sebaliknya.
2. **Tidak** me-rename namespace `App\Http\Controllers\Erkap` / `App\Models\Erkap`.
3. **Tidak** menulis ulang logika kalkulasi SPPH, RKAP, risiko, CAPEX/OPEX, atau approval.
4. **Tidak** menggabungkan data transaksi lintas modul.
5. **Tidak** me-rename role `erkap-*` pada fase awal.
6. **Tidak** memindahkan folder `resources/views/erkap` pada fase awal.

### 1.3 Prinsip Implementasi

- **Backward compatible:** URL lama tetap bisa diakses minimal sampai masa transisi selesai.
- **Additive first:** tambah permission dan menu baru sebelum menghapus yang lama.
- **No big-bang:** jangan mengubah route prefix, route name, permission name, dan namespace sekaligus.
- **Single source of truth:** setelah merge, hanya ada satu layout dan satu sidebar.
- **Test before delete:** jangan hapus partial/layout lama sebelum seluruh view E-RKAP terbukti memakai layout baru.

---

## 2. Analisis Kondisi Saat Ini

### 2.1 Peta Modul

| Aspek | SIMKA | E-RKAP |
|---|---|---|
| Prefix URL | `/simka` | `/erkap` |
| Prefix route name | `simka.*` | `erkap.*` |
| File route | `routes/routers/simka.php` | `routes/routers/erkap.php` |
| Jumlah route | **119** | **288** |
| Controller | **6** | **48** |
| Model | **20** (`App\Models`) | **45** (`App\Models\Erkap`) |
| Service | 3 kalkulator SPPH | 6+ service E-RKAP |
| File view | **72** | **163** |
| Layout utama | `layouts.SIMKA` | `layouts.Erkap` |
| Permission | **0** `simka.*` | **187** `erkap.*` |
| Kartu portal | Ada | Ada |
| Tabel database | **20** `simka_*` | **43** `erkap_*` + **3** tabel bersama |
| Data master utama | Kategori pendapatan & tarif SPPH | COA, cost center, struktur risiko, RKAP |

Kedua file route di-load dari `routes/web.php`:

```php
require __DIR__.'/routers/erkap.php'; // baris 63
require __DIR__.'/routers/simka.php'; // baris 64
```

Keduanya berada di dalam grup `middleware('auth')`.

---

### 2.2 Portal

`app/Http/Controllers/PortalController.php` mendefinisikan dua kartu terpisah:

- **E-RKAP** (baris 92–99): `permission => 'erkap.menu'`, link `route('erkap.index')`.
- **SIMKA** (baris 100–107): `permission => 'erkap.menu'`, link `route('simka.index')`.

Artinya saat ini:

- SIMKA dan E-RKAP **tampil sebagai dua aplikasi terpisah**.
- SIMKA **tidak punya permission sendiri**; ia menumpang `erkap.menu`.
- `resources/views/portal.blade.php` merender semua kartu; kartu tanpa izin menampilkan dialog “not allowed”, bukan disembunyikan.

**Target:** hapus kartu E-RKAP, sisakan satu kartu SIMKA dengan deskripsi “Pendapatan & Pengeluaran”.

---

### 2.3 Route

#### SIMKA

- File: `routes/routers/simka.php`
- Prefix: `simka`
- Name: `simka.`
- Jumlah: **119 route**
- Middleware: hanya `web` + `Authenticate`
- **Temuan penting:** tidak ada middleware permission `simka.*` sama sekali. Siapa pun yang login dapat membuka konfigurasi, menyimpan tarif, dan menghapus tarif SPPH.
- Struktur route:
  - `simka.index` → dashboard (saat ini view kosong).
  - `simka.spph773.*` → index, konfigurasi, kalkulasi, submenu tarif, simpan/hapus/kalkulasi.
  - `simka.spph2287.*` → index, konfigurasi, kalkulasi, 9 submenu.
  - `simka.spph2899.*` → index, konfigurasi, kalkulasi (view-only).
  - `simka.spph16368.*` → index, konfigurasi, kalkulasi, 7 submenu.

#### E-RKAP

- File: `routes/routers/erkap.php`
- Prefix: `erkap`
- Name: `erkap.`
- Jumlah: **288 route**
- Middleware: hampir semua route memakai `permission:erkap.<resource>.<action>`.
- Resource permission didefinisikan di `database/seeders/RolePermissionSeeder.php` (baris 170–254).
- Role yang ada: 11 role `erkap-*`.

#### Ketergantungan Nama Route

| Pola | Jumlah baris | Dampak jika route name diubah |
|---|---:|---|
| `route('erkap.*')` di `resources/views`, `app`, `routes` | ±748 | Sangat besar |
| `route('simka.*')` | ±70 | Sedang |
| Referensi `erkap` di folder `tests` | ±1.919 baris | Sangat besar |

**Kesimpulan:** ubah **prefix URL** terlebih dahulu, jangan ubah **route name**.

#### Hardcoded URL

Ada **33 titik** di Blade E-RKAP yang memakai `url('erkap/...')` langsung, bukan `route()`. Contoh:

- `resources/views/erkap/dashboard/analytics.blade.php` baris 288:
  `action="{{ url('erkap/dashboard/drilldown') }}"`
- 32 file index lain memakai pola:
  `form.attr('action', "{{ url('erkap/...') }}/" + id)`

Jika prefix URL diubah, 33 titik ini **wajib** diubah pada commit yang sama. File `public/js/erkap-cascade.js` aman karena base URL-nya di-set dari `route('erkap.coa-options.index')` di Blade.

---

### 2.4 Controller, Model, dan Service

| Komponen | SIMKA | E-RKAP |
|---|---|---|
| Controller | `app/Http/Controllers/SIMKA/` | `app/Http/Controllers/Erkap/` |
| Model | `app/Models/Spph*.php`, `KategoriPendapatan.php`, `KategoriTarif.php` | `app/Models/Erkap/` |
| Service | `app/Services/Simka/Spph*TarifCalculator.php` | `app/Services/Erkap/*`, `app/Services/Reporting/*`, `app/Services/Form1ImportExportService.php`, dll. |
| Export | — | `app/Exports/Erkap/*` |
| Command | — | `erkap:consolidate-budget-opex`, `erkap:mark-overdue-risk-assessments`, `erkap:generate-scheduled-reports`, `erkap:zbb-snapshot`, `erkap:seed-simulasi` |

Tidak ada controller/model E-RKAP yang di-import oleh controller SIMKA, dan sebaliknya. Titik temu hanya di:
- layout/sidebar,
- portal,
- permission/role,
- tabel `users`, `companies`, `divisions`,
- report AMS yang membaca field `erkap_*` (mis. `resources/views/reports/realization.blade.php`).

**Rekomendasi:** pertahankan namespace dan struktur folder pada fase merge. Rename namespace hanya menambah risiko tanpa nilai bisnis langsung.

---

### 2.5 Layout, Header, dan Sidebar

`layouts/Simka.blade.php` dan `layouts/Erkap.blade.php` hampir identik. Perbedaannya praktis hanya pada partial yang di-include dan judul brand.

| Bagian | SIMKA | E-RKAP |
|---|---|---|
| Head | `layouts.partials.simka.app-head` | `layouts.partials.erkap.app-head` (isinya identik) |
| Header | `...simka.app-header` | `...erkap.app-header` |
| Sidebar | `...simka.app-sidebar` | `...erkap.app-sidebar` |
| Footer | `...simka.app-footer` | `...erkap.app-footer` (isinya identik) |
| Plugin | `...simka.app-plugin` | `...erkap.app-plugin` (isinya identik) |

Temuan pada header/footer:

- Header SIMKA menampilkan `SIMKA` tanpa subtitle.
- Header E-RKAP menampilkan `E-RKAP` + subtitle “Manajemen RKAP Digital”.
- Footer **kedua partial** masih menulis `E-RKAP SBS`, termasuk footer SIMKA. Ini harus diubah menjadi `SIMKA SBS`.

Temuan pada sidebar SIMKA (`resources/views/layouts/partials/simka/app-sidebar.blade.php`):

- Dashboard masih mengarah ke `route('erkap.index')`, bukan `route('simka.index')`.
- Empat link SPPH dibungkus `@can('erkap.approvals.view')`, padahal SPPH adalah fitur Pendapatan SIMKA.
- Tidak ada menu Pendapatan/Pengeluaran yang eksplisit.
- Query `pendingApprovalCount` sudah mengambil model `App\Models\Erkap\Approval`; ini bisa dipertahankan di sidebar gabungan.

Temuan pada sidebar E-RKAP (`.../erkap/app-sidebar.blade.php`):

- 372 baris, 12 grup menu, sudah lengkap.
- Sudah memakai `@can` per resource.
- Sudah ada badge jumlah approval pending.
- Merupakan sumber utama untuk kategori “Pengeluaran: E-RKAP”.

#### Masalah Case-Sensitivity

File layout bernama `Simka.blade.php`, tetapi 43 view SIMKA menulis:

```blade
@extends('layouts.SIMKA')
```

Di Windows ini berjalan karena filesystem case-insensitive. Di Linux/macOS produksi, ini berpotensi gagal karena `layouts/SIMKA.blade.php` tidak ada. Saat merge, **standardisasi menjadi `layouts.Simka`** atau `layouts.simka` dan update semua referensi.

---

### 2.6 Permission dan Role

- Permission E-RKAP: **187** baris di tabel `permissions`, format `erkap.<resource>.<action>`.
- Permission SIMKA: **0**.
- Role E-RKAP: `erkap-cost-owner`, `erkap-admin`, `erkap-ppk`, `erkap-controller`, `erkap-direksi-keuangan`, `erkap-direksi`, `erkap-komisaris`, `erkap-accounting`, `erkap-risk-manager`, `erkap-auditor`, `erkap-manajemen-aset`.
- `super-admin` bypass semua lewat `Gate::before` di `app/Providers/AppServiceProvider.php`.
- Helper test: `tests/Concerns/ActsAsErkapRole.php`, `ActsAsSuperAdmin.php`.

**Temuan kritis:**

1. Portal SIMKA memakai `erkap.menu`. Jika `erkap.menu` dicabut dari suatu role, akses SIMKA ikut hilang.
2. Route SIMKA tidak punya permission, sehingga penambahan `simka.menu` harus disertai update test SIMKA yang saat ini memakai user biasa tanpa role.
3. Belum ada pemisahan izin baca vs tulis untuk SPPH.

---

### 2.7 Struktur Database

#### Koneksi

`.env` saat ini:

```env
DB_CONNECTION=pgsql
DB_DATABASE=asset_management_system
```

Tidak ada koneksi database terpisah untuk E-RKAP. **Keduanya sudah satu database.**

#### Inventaris Tabel SIMKA (20 tabel)

| Kelompok | Tabel | Baris saat ini |
|---|---|---:|
| Master | `simka_kategori_pendapatan` | 6 |
| Master | `simka_kategori_tarif` | 9 |
| SPPH 773 | `simka_spph773_bobot` | 5 |
| SPPH 773 | `simka_spph773_komponen_tarif` | 2 |
| SPPH 773 | `simka_spph773_asumsi_dasar` | 3 |
| SPPH 773 | `simka_spph773_adjustment_value` | 0 |
| SPPH 773 | `simka_spph773_tarif` | 0 |
| SPPH 773 | `simka_spph773_tarif_total` | 0 |
| SPPH 2287 | `simka_spph2287_bobot` | 2 |
| SPPH 2287 | `simka_spph2287_asumsi_dasar` | 2 |
| SPPH 2287 | `simka_spph2287_adjustment_value` | 0 |
| SPPH 2287 | `simka_spph2287_tarif` | 0 |
| SPPH 2287 | `simka_spph2287_tarif_total` | 0 |
| SPPH 16368 | `simka_spph16368_bobot` | 0 |
| SPPH 16368 | `simka_spph16368_komponen_tarif` | 0 |
| SPPH 16368 | `simka_spph16368_asumsi_dasar` | 0 |
| SPPH 16368 | `simka_spph16368_kategori_tarif` | 0 |
| SPPH 16368 | `simka_spph16368_tarif` | 0 |
| SPPH 16368 | `simka_spph16368_tarif_total` | 0 |
| SPPH 16368 | `simka_spph16368_adjustment_value` | 0 |

Catatan:
- **SPPH 2899 tidak punya tabel sendiri.** Controller-nya view-only.
- Tabel `simka_spph*_tarif_total` tidak memiliki foreign key; datanya berdiri sendiri.
- Relasi utama:

```text
simka_kategori_pendapatan
 ├─ simka_kategori_tarif
 │   └─ simka_spph773_tarif
 │   └─ simka_spph2287_tarif
 │   └─ simka_spph16368_tarif
 ├─ simka_spph773_bobot
 ├─ simka_spph773_komponen_tarif
 ├─ simka_spph16368_bobot
 ├─ simka_spph16368_komponen_tarif
 └─ simka_spph16368_kategori_tarif
```

#### Inventaris Tabel E-RKAP (43 tabel `erkap_*` + 3 tabel bersama)

Tabel dengan data saat ini:

| Tabel | Baris | Keterangan |
|---|---:|---|
| `chart_of_accounts` | **16.700** | Master COA; dipakai E-RKAP |
| `cost_centers` | **100** | Pusat biaya; dipakai E-RKAP |
| `erkap_activities` | 147 | Struktur aktivitas |
| `erkap_management_areas` | 49 | Struktur manajemen area |
| `erkap_locations` | 12 | Struktur lokasi |
| `erkap_business_units` | 2 | Struktur bisnis unit |
| `erkap_cost_elements` | 167 | Elemen biaya |
| `erkap_cost_element_categories` | 28 | Kategori elemen biaya |
| `erkap_risk_types` | 35 | Master risiko |
| `erkap_risk_scales` | 25 | Matriks risiko |
| `erkap_risk_score_levels` | 25 | Skor risiko |
| `erkap_risk_taxonomies` | 8 | Master risiko |
| `erkap_risk_appetites` | 4 | Master risiko |
| `erkap_risk_probabilities` | 5 | Matriks risiko |
| `erkap_risk_impacts` | 5 | Matriks risiko |
| `erkap_rating_criterias` | 5 | Rating |
| `erkap_investation_types` | 6 | Master investasi |
| `erkap_investation_criterias` | 5 | Master investasi |
| `erkap_investattion_categories` | 3 | Master investasi |
| `erkap_audit_logs` | 631 | Audit trail |

Tabel transaksi/perencanaan yang saat ini **kosong** (0 baris) antara lain:

```text
erkap_rkap
erkap_company_targets
erkap_department_targets
erkap_risk_identifications
erkap_risk_identification_reasons
erkap_risk_identification_impacts
erkap_risk_analysis
erkap_department_risk_strategies
erkap_work_programs
erkap_routine_costs
erkap_investment_plans
erkap_investment_stage_gates
erkap_budget_opex
erkap_budget_capex
erkap_revenue_plans
erkap_expense_plans
erkap_profit_loss_statements
erkap_budget_realizations
erkap_program_realizations
erkap_risk_assessments_monthly
erkap_performance_scorecards
erkap_zbb_reviews
erkap_approvals
erkap_report_items
risk_business_processes
```

#### Relasi Inti E-RKAP

```text
erkap_rkap
 └─ erkap_company_targets
     └─ erkap_department_targets (division_id → divisions)
         └─ erkap_risk_identifications
             ├─ erkap_risk_identification_reasons
             ├─ erkap_risk_identification_impacts
             ├─ erkap_risk_analysis
             ├─ erkap_department_risk_strategies
             └─ erkap_work_programs
                 ├─ erkap_routine_costs
                 │   ├─ cost_centers
                 │   ├─ erkap_cost_elements
                 │   └─ chart_of_accounts
                 ├─ erkap_investment_plans
                 │   ├─ cost_centers
                 │   ├─ erkap_cost_elements
                 │   └─ chart_of_accounts
                 └─ erkap_program_realizations
```

```text
erkap_rkap
 ├─ erkap_budget_opex
 ├─ erkap_budget_capex
 ├─ erkap_revenue_plans
 ├─ erkap_expense_plans
 ├─ erkap_profit_loss_statements
 ├─ erkap_budget_realizations
 ├─ erkap_performance_scorecards
 └─ erkap_zbb_reviews
```

```text
erkap_approvals  → polymorphic ke model yang di-approve
erkap_audit_logs → polymorphic ke model yang diaudit
```

#### Tabel Bersama / Tanpa Prefix

Tiga tabel berikut adalah domain E-RKAP tetapi tidak memakai prefix `erkap_`:

- `chart_of_accounts`
- `cost_centers`
- `risk_business_processes`

Jangan rename pada fase merge. Alasan:
- Dipakai oleh 45 model dan banyak FK.
- `chart_of_accounts` sudah berisi 16.700 baris.
- Rename akan memutus migrasi, seeder, test, report, dan kemungkinan integrasi lain.

#### Kesimpulan Database

1. **Tidak ada foreign key silang antara tabel `simka_*` dan `erkap_*`.**
2. Tidak ada tabel yang perlu dipindahkan atau digabung untuk kebutuhan menu.
3. Merge modul cukup dilakukan di level aplikasi.
4. Jika ke depan ingin integrasi Pendapatan vs Pengeluaran, buat relasi baru secara eksplisit (mis. `erkap_revenue_plans.simka_kategori_pendapatan_id`) dan jangan rename tabel existing.

---

### 2.8 Inventaris Fitur

#### SIMKA

| Fitur | Route utama | Keterangan |
|---|---|---|
| Dashboard SIMKA | `simka.index` | View masih kosong |
| SPPH 773 | `simka.spph773.index` | Konfigurasi, kalkulasi, tarif tanah/batubara |
| SPPH 2287 | `simka.spph2287.index` | Konfigurasi, kalkulasi, 9 submenu |
| SPPH 2899 | `simka.spph2899.index` | View-only |
| SPPH 16368 | `simka.spph16368.index` | Konfigurasi, kalkulasi, 7 submenu |
| CRUD tarif | `*.simpan`, `*.kalkulasi`, `*.hapus` | Per submenu SPPH |

#### E-RKAP

| Grup menu | Fitur / route |
|---|---|
| Dashboard | `erkap.index`, `erkap.dashboard.widgets` |
| Lifecycle RKAP | `erkap.rkap.*` |
| Master Data | COA, kategori & elemen biaya, risk taxonomy, rating criteria, risk matrix, investment criteria, periode RKAP, cost center |
| Struktur Pusat Biaya | business units, locations, management areas, activities |
| Form 1 | company targets, department targets, risk identifications, reasons, impacts, risk analysis, department risk strategies, form1 import/export |
| Form 2 | `erkap.work-programs.*` |
| Form 3 | `erkap.routine-costs.*` |
| Form 4 | investment plans, budget capex, summary, payment distribution |
| ZBB | `erkap.zbb-reviews.*` |
| Financial Projection | profit-loss, simulate |
| Monitoring & Realisasi | budget realizations (BvA), program realizations, risk assessment monthly, performance scorecard |
| Approval | approvals, stage gate review |
| Report Center | reports |
| Audit Trail | audit logs |
| API internal | `erkap.coa-options.*` |

Fitur E-RKAP yang **belum ada di sidebar**:
- `erkap.revenue-plans.*`
- `erkap.expense-plans.*`

Keduanya perlu keputusan bisnis: tetap tersembunyi atau dimunculkan di grup perencanaan.

---

### 2.9 Titik Overlap dan Peluang Integrasi

Tidak ada duplikasi tabel, tetapi ada overlap konseptual:

| Domain | SIMKA | E-RKAP | Catatan |
|---|---|---|---|
| Pendapatan | Tarif SPPH, kategori pendapatan | `erkap_revenue_plans` | Belum terhubung |
| Pengeluaran | — | routine cost, budget opex, budget capex, expense plan | Domain E-RKAP |
| Dashboard | Kosong | Dashboard RKAP lengkap | Bisa dijadikan landing gabungan |
| Approval | Belum ada | `erkap_approvals` | Bisa dipakai untuk SPPH di masa depan |
| Report | Belum ada | Report Center | Bisa menampung laporan pendapatan |
| Master data | `simka_kategori_*` | COA, cost center, cost element | Belum terhubung |

Peluang fase lanjutan (bukan scope merge awal):
- Dashboard gabungan: total pendapatan SPPH vs total pengeluaran RKAP.
- Report Center gabungan dengan filter Pendapatan/Pengeluaran.
- Menghubungkan `erkap_revenue_plans` ke `simka_kategori_pendapatan` atau `chart_of_accounts`.
- Approval workflow untuk konfigurasi/tarif SPPH.

---

## 3. Target State

### 3.1 Information Architecture Sidebar

Struktur yang direkomendasikan:

```text
SIMKA
├─ Dashboard SIMKA                         → simka.index
│
├─ Pendapatan: SIMKA
│  ├─ SPPH 773                             → simka.spph773.index
│  ├─ SPPH 2287                            → simka.spph2287.index
│  ├─ SPPH 2899                            → simka.spph2899.index
│  └─ SPPH 16368                           → simka.spph16368.index
│
├─ Pengeluaran: E-RKAP
│  ├─ Dashboard E-RKAP                     → erkap.index
│  ├─ Analytics & Widgets                  → erkap.dashboard.widgets
│  ├─ Lifecycle RKAP                       → erkap.rkap.index
│  ├─ Perencanaan & Penganggaran
│  │  ├─ Form 1: Sasaran & Asesmen Risiko
│  │  ├─ Form 2: Program Kerja
│  │  ├─ Form 3: Biaya Rutin
│  │  ├─ Form 4: Investasi & Anggaran
│  │  └─ Zero Based Budgeting
│  ├─ Master Data & Struktur
│  │  ├─ Chart of Accounts
│  │  ├─ Kategori & Elemen Biaya
│  │  ├─ Risk Taxonomy / Matrix / Rating
│  │  ├─ Investment Criteria
│  │  ├─ Periode RKAP
│  │  ├─ Pusat Biaya
│  │  └─ Struktur a–d
│  ├─ Monitoring & Realisasi
│  ├─ Financial Projection
│  ├─ Approval & Stage Gate
│  ├─ Report Center
│  └─ Audit Trail
│
└─ Back to Portal                          → portal.index
```

Implementasi visual:
- Gunakan `menu-title` untuk `Pendapatan: SIMKA` dan `Pengeluaran: E-RKAP`.
- Sub-grup E-RKAP tetap memakai `has-arrow` seperti sidebar existing.
- Badge `pendingApprovalCount` tetap di menu Approval E-RKAP.

### 3.2 Landing Page

Fase A cukup membuat `/simka` sebagai landing sederhana:
- Kartu besar **Pendapatan (SIMKA)** → empat SPPH.
- Kartu besar **Pengeluaran (E-RKAP)** → Dashboard E-RKAP.
- Ringkasan angka bisa ditambahkan pada fase lanjutan.

### 3.3 Target Teknis

| Komponen | Target |
|---|---|
| Layout | Satu layout: `layouts.Simka` |
| Header | Brand `SIMKA`, subtitle `Sistem Informasi Manajemen Keuangan & Anggaran` |
| Footer | `SIMKA SBS` |
| Sidebar | Satu file: `layouts/partials/simka/app-sidebar.blade.php` |
| Portal | Satu kartu: SIMKA |
| Route E-RKAP | Tetap nama `erkap.*`; URL dapat dipindah ke `/simka/erkap` |
| Permission E-RKAP | Tetap `erkap.*` |
| Permission SIMKA | Tambah `simka.menu` dan `simka.spph.manage` |
| Database | Tidak ada perubahan skema |
| Namespace | Tidak diubah |

---

## 4. Opsi Strategi dan Rekomendasi

| Opsi | Isi | Risiko | Rekomendasi |
|---|---|---|---|
| **A. UI-only merge** | Satu portal, satu layout, satu sidebar; URL tetap `/erkap` dan `/simka` | Rendah | **Lakukan lebih dulu** |
| **B. UI merge + URL unification** | E-RKAP dipindah ke `/simka/erkap`, route name tetap | Sedang | **Lakukan setelah A stabil** |
| **C. Full rename** | Rename route name, permission, namespace, folder view | Sangat tinggi | **Jangan pada fase ini** |
| **D. Physical DB merge** | Rename/gabung tabel `erkap_*` dan `simka_*` | Sangat tinggi, tanpa manfaat langsung | **Tidak disarankan** |

**Rekomendasi final:** A → B → cleanup opsional. C dan D hanya jika ada kebutuhan strategis yang sangat kuat dan disertai alokasi waktu khusus.

---

## 5. Rencana Implementasi Bertahap

## Fase 0 — Baseline dan Pengamanan

**Tujuan:** memastikan kondisi awal tercatat dan dapat dibandingkan setelah perubahan.

Task:
1. Simpan baseline route:
   ```bash
   php artisan route:list --json > storage/app/baseline-routes.json
   ```
2. Simpan baseline test:
   ```bash
   php artisan test
   ```
3. Catat jumlah route per modul dan daftar route name.
4. Backup database (minimal schema + data tabel `permissions`, `roles`, `model_has_roles`, `role_has_permissions`).
5. Catat role yang memiliki `erkap.menu`.
6. Pastikan environment test memakai database test, bukan database produksi.

**Output:** baseline route, baseline test, daftar role.

---

## Fase A — Merge UI, Menu, dan Otorisasi Dasar

**Tujuan:** pengguna melihat satu modul SIMKA dengan dua kategori, tanpa mengubah URL dan route name E-RKAP.

### A1. Tambah permission SIMKA

File:
- `database/seeders/RolePermissionSeeder.php` atau seeder baru `MergeSimkaPermissionSeeder.php`.

Permission baru:
```php
simka.menu
simka.spph.manage
```

Aturan pemberian:
- `simka.menu` diberikan ke **semua role yang saat ini memiliki `erkap.menu`**.
- `simka.spph.manage` diberikan ke `super-admin` dan `erkap-admin` terlebih dahulu.
- `super-admin` otomatis mendapat semua permission lewat `syncPermissions($allPermissions)`.
- Jangan hapus `erkap.menu`; biarkan sebagai compatibility sampai Fase C.

Contoh logika seeder:
```php
Permission::firstOrCreate(['name' => 'simka.menu', 'guard_name' => 'web']);
Permission::firstOrCreate(['name' => 'simka.spph.manage', 'guard_name' => 'web']);

foreach (Role::permission('erkap.menu')->get() as $role) {
    $role->givePermissionTo('simka.menu');
}

$erkapAdmin->givePermissionTo('simka.spph.manage');
```

Verifikasi:
```bash
php artisan permission:show
php artisan test --filter=ErkapRolePermissionMatrixTest
```

### A2. Ubah Portal

File: `app/Http/Controllers/PortalController.php`

Task:
1. Hapus entri menu **E-RKAP** (baris 92–99).
2. Ubah kartu SIMKA:
   - `permission` → `simka.menu`
   - `sub` → `Pendapatan & Pengeluaran`
   - `link` → `route('simka.index')`
3. Pastikan urutan kartu tetap rapi.

Verifikasi:
- Login sebagai role yang punya `erkap.menu`.
- Portal hanya menampilkan satu kartu SIMKA.
- Role tanpa `simka.menu` tidak dapat membuka SIMKA.

### A3. Tambah Middleware Permission di Route SIMKA

File: `routes/routers/simka.php`

Strategi sederhana:
- Bungkus grup `simka` dengan `middleware('permission:simka.menu')`.
- Untuk route POST/DELETE, tambahkan `middleware('permission:simka.spph.manage')`.

Contoh:
```php
Route::prefix('simka')->name('simka.')
    ->middleware('permission:simka.menu')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');

        Route::prefix('spph-773')->name('spph773.')->group(function () {
            // route GET ...
            Route::post('/konfigurasi-data', ...)
                ->middleware('permission:simka.spph.manage')
                ->name('konfigurasi.simpan');
            // route POST/DELETE lainnya juga diberi middleware manage
        });
    });
```

Catatan:
- Jika nanti butuh granular per SPPH, pecah menjadi `simka.spph773.view`, `simka.spph773.manage`, dst.
- Fase A cukup dua permission agar cepat dan aman.

Dampak test:
- `tests/Feature/Simka/Spph2287TarifCrudTest.php` dan `SpphTarifTotalViewTest.php` saat ini memakai user tanpa role. Setelah middleware aktif, test harus:
  - memakai super-admin, atau
  - memberi user permission `simka.menu` + `simka.spph.manage`.

### A4. Ganti Layout E-RKAP ke Layout SIMKA

File yang diubah:
- 128 file Blade E-RKAP yang saat ini memakai `@extends('layouts.Erkap')`.

Perubahan:
```blade
@extends('layouts.Simka')
```

Langkah aman:
1. Pastikan `layouts/Simka.blade.php` sudah menjadi layout final.
2. Lakukan replace bertahap per folder.
3. Jalankan test render semua halaman E-RKAP.
4. Setelah semua lolos, hapus atau jadikan alias `layouts/Erkap.blade.php`.

Catatan:
- Jangan mengubah `@section`, `@yield`, atau nama section. Kedua layout kompatibel.
- Pastikan nama layout konsisten `layouts.Simka` (bukan `layouts.SIMKA`) agar aman di Linux.

### A5. Konsolidasi Header, Footer, dan Sidebar

File:
- `resources/views/layouts/partials/simka/app-header.blade.php`
- `resources/views/layouts/partials/simka/app-footer.blade.php`
- `resources/views/layouts/partials/simka/app-sidebar.blade.php`
- `resources/views/layouts/partials/erkap/*` (nanti dihapus/di-deprecate)

Perubahan header:
- Brand: `SIMKA`
- Subtitle: `Sistem Informasi Manajemen Keuangan & Anggaran`
- Ikon tetap `mdi-cash` agar konsisten dengan portal.

Perubahan footer:
- `E-RKAP SBS` → `SIMKA SBS`.

Perubahan sidebar:
- Dashboard link → `route('simka.index')`.
- Tambahkan `menu-title` `Pendapatan: SIMKA`.
- Tambahkan empat link SPPH.
- Tambahkan `menu-title` `Pengeluaran: E-RKAP`.
- Pindahkan seluruh grup menu E-RKAP existing ke bawah kategori Pengeluaran.
- Ganti gating SPPH dari `@can('erkap.approvals.view')` menjadi `@can('simka.menu')`.
- Pertahankan `@can` per resource E-RKAP.
- Pertahankan badge approval pending.

Contoh kerangka:
```blade
<li class="menu-title" key="t-pendapatan">Pendapatan: SIMKA</li>
@can('simka.menu')
    <li><a href="{{ route('simka.spph773.index') }}">SPPH 773</a></li>
    <li><a href="{{ route('simka.spph2287.index') }}">SPPH 2287</a></li>
    <li><a href="{{ route('simka.spph2899.index') }}">SPPH 2899</a></li>
    <li><a href="{{ route('simka.spph16368.index') }}">SPPH 16368</a></li>
@endcan

<li class="menu-title" key="t-pengeluaran">Pengeluaran: E-RKAP</li>
@can('erkap.menu')
    {{-- seluruh menu E-RKAP existing --}}
@endcan
```

### A6. Landing Page SIMKA

File:
- `app/Http/Controllers/SIMKA/DashboardController.php`
- `resources/views/simka/dashboard/index.blade.php`

Task minimal:
- Tampilkan dua kartu:
  - Pendapatan: SIMKA → empat SPPH.
  - Pengeluaran: E-RKAP → `route('erkap.index')`.
- Tampilkan nama user dan ringkasan kecil bila memungkinkan.
- Jangan memanggil query berat di fase A.

### A7. Test dan QA Fase A

Test yang wajib:
```bash
php artisan test --filter=Simka
php artisan test --filter=Erkap
```

Test baru yang disarankan:
1. Portal hanya memiliki satu kartu SIMKA.
2. User dengan `erkap.menu` tetapi belum `simka.menu` → setelah seeder, mendapat akses.
3. User tanpa `simka.menu` → 403 saat membuka `/simka`.
4. User dengan `simka.menu` tanpa `simka.spph.manage` → boleh GET, 403 saat POST/DELETE.
5. Semua view E-RKAP berhasil render dengan layout baru.

Manual QA:
- Login sebagai `erkap-admin`: buka semua menu Pengeluaran.
- Login sebagai `erkap-cost-owner`: pastikan menu yang tidak diizinkan tetap tersembunyi.
- Buka SPPH 773/2287/2899/16368 dari sidebar.
- Buka Portal: hanya satu kartu.

**Kriteria selesai Fase A:**
- Satu kartu portal.
- Satu layout.
- Sidebar dua kategori.
- Semua route E-RKAP dan SIMKA tetap berfungsi.
- URL masih `/erkap` dan `/simka`.
- Test existing hijau.

---

## Fase B — Unifikasi URL ke `/simka/erkap`

**Tujuan:** satu modul secara URL, tanpa mengubah route name.

### B1. Ubah Prefix Route E-RKAP

File: `routes/routers/erkap.php`

Perubahan:
```php
// sebelum
Route::prefix("erkap")->name("erkap.")->group(function () {
```

```php
// sesudah
Route::prefix("simka/erkap")->name("erkap.")->group(function () {
```

Hasil:
- `route('erkap.index')` otomatis menghasilkan `/simka/erkap`.
- 748 pemanggilan `route('erkap.*')` **tidak perlu diubah**.
- Test yang memakai `route('erkap.*')` tetap lolos.
- Test yang memakai path hardcoded `/erkap/...` harus diubah.

### B2. Ubah 33 Hardcoded URL di Blade

Cari:
```bash
rg "url\('erkap" resources/views
```

Ganti pola:
```blade
{{ url('erkap/cost-elements') }}
```
menjadi salah satu:
```blade
{{ route('erkap.cost-elements.index') }}
```
atau jika butuh base URL dinamis:
```blade
{{ url('simka/erkap/cost-elements') }}
```

Rekomendasi: ubah ke `route()` agar tidak ada hardcoded prefix lagi.

Daftar file yang terdampak (33 titik):
- `resources/views/erkap/activity/*`
- `resources/views/erkap/budget-realizations/index.blade.php`
- `resources/views/erkap/company-target/index.blade.php`
- `resources/views/erkap/cost-center/index.blade.php`
- `resources/views/erkap/cost-element/index.blade.php`
- `resources/views/erkap/cost-element-category/index.blade.php`
- `resources/views/erkap/dashboard/analytics.blade.php`
- `resources/views/erkap/department-risk-strategy/index.blade.php`
- `resources/views/erkap/department-target/index.blade.php`
- `resources/views/erkap/expense-plan/index.blade.php`
- `resources/views/erkap/investation-criteria/index.blade.php`
- `resources/views/erkap/investation-type/index.blade.php`
- `resources/views/erkap/investattion-category/index.blade.php`
- `resources/views/erkap/investment-plan/index.blade.php`
- `resources/views/erkap/performance-scorecards/index.blade.php`
- `resources/views/erkap/program-realizations/index.blade.php`
- `resources/views/erkap/rating-criteria/index.blade.php`
- `resources/views/erkap/revenue-plan/index.blade.php`
- `resources/views/erkap/risk-analysis/index.blade.php`
- `resources/views/erkap/risk-appetite/index.blade.php`
- `resources/views/erkap/risk-assessment-monthly/index.blade.php`
- `resources/views/erkap/risk-identification/index.blade.php`
- `resources/views/erkap/risk-identification-impact/index.blade.php`
- `resources/views/erkap/risk-identification-reason/index.blade.php`
- `resources/views/erkap/risk-impact/index.blade.php`
- `resources/views/erkap/risk-probability/index.blade.php`
- `resources/views/erkap/risk-scale/index.blade.php`
- `resources/views/erkap/risk-score-level/index.blade.php`
- `resources/views/erkap/risk-taxonomy/index.blade.php`
- `resources/views/erkap/risk-type/index.blade.php`
- `resources/views/erkap/rkap/index.blade.php`
- `resources/views/erkap/routine-cost/index.blade.php`
- `resources/views/erkap/work-program/index.blade.php`

### B3. Update Test dengan Path Hardcoded

File utama:
- `tests/Feature/Erkap/ErkapE2ETest.php` (banyak path `/erkap/...`)

Strategi:
- Ganti ke `route('erkap.*')` bila memungkinkan.
- Jika harus path literal, ubah ke `/simka/erkap/...`.

Cari:
```bash
rg "'/erkap|\"/erkap" tests
```

### B4. Tambah Redirect Backward Compatibility

Di `routes/web.php`, tambahkan sebelum grup auth atau di dalamnya:

```php
Route::permanentRedirect('/erkap/{any?}', '/simka/erkap/{any?}')
    ->where('any', '.*');
```

Catatan:
- Redirect ini menangani GET (bookmark lama).
- Form POST baru selalu di-generate dari halaman baru, jadi tidak perlu redirect POST.
- Jika ada integrasi eksternal yang POST ke `/erkap/...`, koordinasikan sebelum deploy.

### B5. Verifikasi Fase B

```bash
php artisan route:clear
php artisan view:clear
php artisan route:list --json | Select-String "simka/erkap"
php artisan test
```

Manual:
- Buka `/erkap` → redirect ke `/simka/erkap`.
- Buka semua menu E-RKAP dari sidebar → URL baru.
- Simpan form E-RKAP → POST ke URL baru.
- Buka dashboard analytics dan filter drilldown → form action baru.
- Buka halaman cascade COA → API `/simka/erkap/coa-options/...`.

**Kriteria selesai Fase B:**
- Semua URL E-RKAP berada di `/simka/erkap/...`.
- Route name tetap `erkap.*`.
- Tidak ada link ke `/erkap/...` yang tersisa di Blade.
- Test hijau.
- Redirect lama berfungsi.

---

## Fase C — Cleanup dan Konsolidasi Opsional

**Tujuan:** menghilangkan duplikasi dan menyatukan konvensi.

Task:
1. Hapus atau jadikan alias `layouts/Erkap.blade.php`.
2. Hapus `layouts/partials/erkap/*` setelah tidak ada referensi.
3. Standardisasi nama layout:
   - `layouts.Simka` untuk semua view.
   - Hindari `layouts.SIMKA`.
4. Opsional: pindahkan `resources/views/erkap` → `resources/views/simka/erkap`.
   - Update semua `view('erkap.` di 48 controller.
   - Keuntungan: struktur folder satu modul.
   - Risiko: banyak perubahan mekanis; lakukan hanya jika ada waktu.
5. Opsional: pindahkan `app/Http/Controllers/Erkap` → `app/Http/Controllers/Simka/Erkap`.
   - Tidak direkomendasikan dalam waktu dekat.
6. Opsional: rename route name `erkap.*` → `simka.erkap.*`.
   - Baru lakukan setelah Fase B stabil dan semua test memakai route name.
7. Opsional: rename permission `erkap.*` → `simka.pengeluaran.*`.
   - Perlu migrasi permission + update seluruh middleware + update matriks role.
8. Update dokumentasi:
   - `agents/tutorial-pengisian-erkap*.md`
   - `agents/matriks_hak_akses_rkap_2027.md`
   - README modul.

---

## Fase D — Integrasi Lanjutan (Opsional, Bukan Scope Merge)

> **Status 9 November 2026:** butir 1, 2, 4, dan 5 sudah diimplementasikan.
> Butir 3 (Approval SPPH) ditunda sampai aturan approver ditetapkan.

1. **Dashboard gabungan**
   - Total pendapatan SPPH.
   - Total biaya RKAP (opex/capex).
   - Selisih pendapatan vs pengeluaran.
2. **Report Center gabungan**
   - Filter domain: Pendapatan / Pengeluaran.
   - Export Excel/PDF.
3. **Approval SPPH**
   - Gunakan `erkap_approvals` atau buat approval SIMKA.
4. **Relasi pendapatan–pengeluaran**
   - Hubungkan `erkap_revenue_plans` ke `simka_kategori_pendapatan` atau `chart_of_accounts`.
5. **Master data bersama**
   - Gunakan `chart_of_accounts` dan `cost_centers` untuk klasifikasi pendapatan.

---

## 6. Menu Mapping Detail

### 6.1 Pendapatan: SIMKA

| Menu | Route | Permission fase A | Catatan |
|---|---|---|---|
| Dashboard SIMKA | `simka.index` | `simka.menu` | Landing gabungan |
| SPPH 773 | `simka.spph773.index` | `simka.menu` | CRUD tarif butuh `simka.spph.manage` |
| SPPH 2287 | `simka.spph2287.index` | `simka.menu` | idem |
| SPPH 2899 | `simka.spph2899.index` | `simka.menu` | view-only |
| SPPH 16368 | `simka.spph16368.index` | `simka.menu` | idem |

### 6.2 Pengeluaran: E-RKAP

| Grup | Menu | Route | Permission |
|---|---|---|---|
| Dashboard | Dashboard E-RKAP | `erkap.index` | `erkap.menu` |
| Dashboard | Analytics & Widgets | `erkap.dashboard.widgets` | `erkap.menu` |
| Lifecycle | Lifecycle RKAP | `erkap.rkap.index` | `erkap.rkap.view` |
| Master | Chart of Accounts | `erkap.chart-of-accounts.index` | `erkap.chart-of-accounts.view` |
| Master | Kategori Elemen Biaya | `erkap.cost-element-categories.index` | `erkap.cost-element-categories.view` |
| Master | Elemen Biaya | `erkap.cost-elements.index` | `erkap.cost-elements.view` |
| Master | Risk Appetites | `erkap.risk-appetites.index` | `erkap.risk-appetites.view` |
| Master | Risk Taxonomies | `erkap.risk-taxonomies.index` | `erkap.risk-taxonomies.view` |
| Master | Risk Types | `erkap.risk-types.index` | `erkap.risk-types.view` |
| Master | Rating Criteria | `erkap.rating-criterias.index` | `erkap.rating-criterias.view` |
| Master | Risk Scales | `erkap.risk-scales.index` | `erkap.risk-scales.view` |
| Master | Risk Probabilities | `erkap.risk-probabilities.index` | `erkap.risk-probabilities.view` |
| Master | Risk Impacts | `erkap.risk-impacts.index` | `erkap.risk-impacts.view` |
| Master | Risk Score & Levels | `erkap.risk-score-levels.index` | `erkap.risk-score-levels.view` |
| Master | Investation Types | `erkap.investation-types.index` | `erkap.investation-types.view` |
| Master | Investation Criterias | `erkap.investation-criterias.index` | `erkap.investation-criterias.view` |
| Master | Investattion Categories | `erkap.investattion-categories.index` | `erkap.investattion-categories.view` |
| Master | Periode RKAP | `erkap.rkap.index` | `erkap.rkap.view` |
| Master | Pusat Biaya | `erkap.cost-centers.index` | `erkap.cost-centers.view` |
| Struktur | Bisnis Unit (a) | `erkap.business-units.index` | `erkap.business-units.view` |
| Struktur | Lokasi (b) | `erkap.locations.index` | `erkap.locations.view` |
| Struktur | Manajemen Area (c) | `erkap.management-areas.index` | `erkap.management-areas.view` |
| Struktur | Aktivitas (d) | `erkap.activities.index` | `erkap.activities.view` |
| Form 1 | Sasaran Perusahaan | `erkap.company-targets.index` | `erkap.company-targets.view` |
| Form 1 | Sasaran Departemen | `erkap.department-targets.index` | `erkap.department-targets.view` |
| Form 1 | Identifikasi Risiko | `erkap.risk-identifications.index` | `erkap.risk-identifications.view` |
| Form 1 | Penyebab Identifikasi | `erkap.risk-identification-reasons.index` | `erkap.risk-identification-reasons.view` |
| Form 1 | Dampak Identifikasi | `erkap.risk-identification-impacts.index` | `erkap.risk-identification-impacts.view` |
| Form 1 | Form 1 Import/Export | `erkap.form1.index` | `erkap.risk-identifications.view` |
| Form 1 | Analisis Risiko | `erkap.risk-analysis.index` | `erkap.risk-analysis.view` |
| Form 1 | Strategi Risiko Departemen | `erkap.department-risk-strategies.index` | `erkap.department-risk-strategies.view` |
| Form 2 | Program Kerja | `erkap.work-programs.index` | `erkap.work-programs.view` |
| Form 3 | Biaya Rutin | `erkap.routine-costs.index` | `erkap.routine-costs.view` |
| Form 4 | Rencana Investasi | `erkap.investment-plans.index` | `erkap.investment-plans.view` |
| Form 4 | Anggaran Investasi | `erkap.budget-capex.index` | `erkap.budget-capex.view` |
| Form 4 | Ringkasan Nilai Investasi | `erkap.budget-capex.summary` | `erkap.budget-capex.view` |
| Form 4 | Distribusi Pembayaran | `erkap.budget-capex.payment-distribution` | `erkap.budget-capex.view` |
| ZBB | Review ZBB | `erkap.zbb-reviews.index` | `erkap.zbb-reviews.view` |
| Financial | Laba Rugi (P&L) | `erkap.profit-loss.index` | `erkap.profit-loss.view` |
| Financial | Simulasi Skenario | `erkap.profit-loss.simulate` | `erkap.profit-loss.view` |
| Monitoring | Realisasi Anggaran (BvA) | `erkap.budget-realizations.index` | `erkap.budget-realizations.view` |
| Monitoring | Realisasi Program Kerja | `erkap.program-realizations.index` | `erkap.program-realizations.view` |
| Monitoring | Risk Assessment Bulanan | `erkap.risk-assessments-monthly.index` | `erkap.risk-assessments-monthly.view` |
| Monitoring | Performance Scorecard | `erkap.performance-scorecards.index` | `erkap.performance-scorecards.view` |
| Approval | Approval | `erkap.approvals.index` | `erkap.approvals.view` |
| Approval | Stage Gate Review | `erkap.investment-gates.index` | `erkap.investment-gates.view` |
| Report | Report Center | `erkap.reports.index` | `erkap.reports.view` |
| Audit | Audit Trail | `erkap.audit-logs.index` | `erkap.audit-logs.view` |

Menu yang belum ditampilkan:
- `erkap.revenue-plans.index`
- `erkap.expense-plans.index`

Keputusan yang disarankan untuk fase A: **tetap tersembunyi**, karena tidak ada di sidebar existing dan belum jelas posisinya di kategori Pendapatan/Pengeluaran.

---

## 7. Dampak File dan Perubahan yang Diperlukan

| File / Folder | Perubahan | Fase |
|---|---|---|
| `app/Http/Controllers/PortalController.php` | Hapus kartu E-RKAP, ubah permission SIMKA | A |
| `database/seeders/RolePermissionSeeder.php` | Tambah `simka.menu`, `simka.spph.manage`, grant ke role | A |
| `routes/routers/simka.php` | Tambah middleware permission | A |
| `routes/routers/erkap.php` | Ubah prefix ke `simka/erkap` | B |
| `routes/web.php` | Tambah redirect `/erkap/*` | B |
| `resources/views/layouts/partials/simka/app-sidebar.blade.php` | Sidebar gabungan dua kategori | A |
| `resources/views/layouts/partials/simka/app-header.blade.php` | Brand SIMKA + subtitle | A |
| `resources/views/layouts/partials/simka/app-footer.blade.php` | `SIMKA SBS` | A |
| `resources/views/layouts/Simka.blade.php` | Layout tunggal | A |
| `resources/views/layouts/Erkap.blade.php` | Deprecate / alias | C |
| `resources/views/layouts/partials/erkap/*` | Hapus setelah tidak dipakai | C |
| 128 file `resources/views/erkap/**/*.blade.php` | `@extends('layouts.Simka')` | A |
| 43 file `resources/views/simka/**/*.blade.php` | Normalisasi `layouts.Simka` | A |
| 33 file Blade E-RKAP | Ganti `url('erkap/...')` ke `route()` | B |
| `tests/Feature/Simka/*` | Tambah permission user | A |
| `tests/Feature/Erkap/ErkapE2ETest.php` | Sesuaikan path hardcoded | B |
| `public/js/erkap-cascade.js` | Hanya komentar; base URL dari route() | B |
| `agents/tutorial-pengisian-erkap*.md` | Update navigasi | C |
| `agents/matriks_hak_akses_rkap_2027.md` | Update nama modul/menu | C |

---

## 8. Strategi Permission dan Role

### 8.1 Permission Baru

| Permission | Fungsi |
|---|---|
| `simka.menu` | Akses modul SIMKA dan seluruh menu Pendapatan (read-only) |
| `simka.spph.manage` | Simpan konfigurasi, kalkulasi, dan hapus tarif SPPH |

Opsional fase lanjutan:
- `simka.spph773.view` / `.manage`
- `simka.spph2287.view` / `.manage`
- `simka.spph2899.view`
- `simka.spph16368.view` / `.manage`

### 8.2 Matriks Role Awal

| Role | `simka.menu` | `simka.spph.manage` | Catatan |
|---|---:|---:|---|
| `super-admin` | ✅ | ✅ | Otomatis semua |
| `erkap-admin` | ✅ | ✅ | Admin keuangan |
| `erkap-ppk` | ✅ | ❌ | View |
| `erkap-controller` | ✅ | ❌ | View |
| `erkap-direksi-keuangan` | ✅ | ❌ | View |
| `erkap-direksi` | ✅ | ❌ | View |
| `erkap-komisaris` | ✅ | ❌ | View |
| `erkap-accounting` | ✅ | ❌ | View |
| `erkap-risk-manager` | ✅ | ❌ | View |
| `erkap-auditor` | ✅ | ❌ | View-only |
| `erkap-manajemen-aset` | ✅ | ❌ | View |
| `erkap-cost-owner` | ✅ | ❌ | View |

Jika bisnis ingin cost-owner dapat mengubah tarif SPPH, berikan `simka.spph.manage` secara eksplisit.

### 8.3 Prinsip Kompatibilitas

- Jangan hapus permission `erkap.*`.
- Jangan rename role `erkap-*`.
- Jangan ubah route name pada fase A/B.
- `erkap.menu` tetap dipakai untuk area Pengeluaran.
- `simka.menu` menjadi gerbang modul.

---

## 9. Strategi Database

### 9.1 Keputusan

**Tidak ada perubahan skema database pada fase merge.**

Alasan:
- Satu database, tanpa cross-FK.
- Rename tabel berisiko tinggi dan tidak diperlukan.
- Data E-RKAP masih sedikit (mayoritas tabel transaksi kosong), tetapi struktur dan FK sudah benar.
- `chart_of_accounts` berisi 16.700 baris dan dipakai banyak relasi.

### 9.2 Yang Boleh Dilakukan

- Menambah permission (data, bukan skema).
- Menambah index jika ada query baru (tidak ada di fase merge).
- Menambah kolom relasi di masa depan untuk integrasi pendapatan–pengeluaran.
- Menambahkan dokumentasi ownership tabel.

### 9.3 Yang Tidak Boleh Dilakukan pada Fase Merge

- Rename `erkap_*` → `simka_*`.
- Rename `simka_*` → `erkap_*`.
- Rename `chart_of_accounts`, `cost_centers`, `risk_business_processes`.
- Menghapus tabel atau kolom.
- Menggabungkan data `erkap_revenue_plans` ke `simka_*` tanpa desain relasi.

### 9.4 Opsional: Dokumentasi Ownership

Buat dokumen atau komentar tabel:

```text
simka_*        → domain Pendapatan (SIMKA)
erkap_*        → domain Pengeluaran (E-RKAP)
chart_of_accounts, cost_centers, risk_business_processes → domain E-RKAP (legacy name)
```

---

## 10. Testing Plan

### 10.1 Automated Test

Baseline:
```bash
php artisan test
php artisan test --filter=Simka
php artisan test --filter=Erkap
```

Test baru yang disarankan:

| Test | Tujuan |
|---|---|
| `PortalSingleModuleTest` | Portal hanya menampilkan satu kartu SIMKA |
| `SimkaMenuPermissionTest` | Akses SIMKA membutuhkan `simka.menu` |
| `SimkaSpphWritePermissionTest` | POST/DELETE membutuhkan `simka.spph.manage` |
| `MergedSidebarVisibilityTest` | Kategori Pendapatan/Pengeluaran tampil sesuai permission |
| `ErkapLegacyRedirectTest` | `/erkap/*` redirect ke `/simka/erkap/*` |
| `ErkapRoutesStillResolveTest` | Semua route name `erkap.*` masih menghasilkan URL |

### 10.2 Manual QA Matrix

| Skenario | Ekspektasi |
|---|---|
| Login `erkap-admin` | Melihat Pendapatan + Pengeluaran lengkap |
| Login `erkap-cost-owner` | Melihat menu sesuai izin; tidak bisa approve |
| Login `erkap-auditor` | Hanya view; tidak ada tombol create/edit |
| Login user tanpa `simka.menu` | Tidak bisa membuka `/simka` |
| Buka Portal | Hanya kartu SIMKA |
| Buka SPPH 773 | Konfigurasi, kalkulasi, simpan, hapus |
| Buka E-RKAP Dashboard | Tampil normal |
| Buka Form 1–4 | Tampil normal |
| Simpan Form 3 | Redirect dan data tersimpan |
| Buka analytics | Filter dan drilldown berfungsi |
| Buka `/erkap` | Redirect ke `/simka/erkap` (fase B) |

### 10.3 Regression Checklist

- [ ] Semua route `erkap.*` dan `simka.*` terdaftar.
- [ ] Tidak ada error 500 pada 163 view E-RKAP.
- [ ] Tidak ada error 500 pada 72 view SIMKA.
- [ ] Tidak ada link mati di sidebar.
- [ ] Tidak ada hardcoded `/erkap/` yang tersisa (fase B).
- [ ] Permission matrix test hijau.
- [ ] Seeder idempotent saat dijalankan dua kali.
- [ ] Cache route/view dibersihkan setelah deploy.

---

## 11. Rollout dan Rollback

### 11.1 Urutan Deploy Fase A

1. Deploy kode layout/sidebar/portal.
2. Jalankan seeder permission:
   ```bash
   php artisan db:seed --class=RolePermissionSeeder
   ```
3. Bersihkan cache:
   ```bash
   php artisan optimize:clear
   ```
4. Smoke test portal dan sidebar.
5. Pantau log error 403/500.

### 11.2 Urutan Deploy Fase B

1. Pastikan Fase A stabil minimal beberapa hari.
2. Deploy perubahan prefix route + 33 hardcoded URL + test path.
3. Jalankan:
   ```bash
   php artisan route:clear
   php artisan view:clear
   php artisan optimize:clear
   ```
4. Verifikasi route list.
5. Uji redirect `/erkap`.
6. Pantau akses 404.

### 11.3 Rollback

| Perubahan | Rollback |
|---|---|
| Layout/sidebar | Revert file Blade |
| Portal | Revert `PortalController.php` |
| Permission | Permission additive; bisa dibiarkan atau dicabut manual |
| Prefix route | Revert `routes/routers/erkap.php` + hardcoded URL |
| Redirect | Hapus route redirect |

Karena tidak ada perubahan skema, rollback tidak memerlukan restore database.

---

## 12. Risiko dan Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Route name diubah terlalu cepat | 748 referensi rusak | Jangan ubah route name di fase A/B |
| Permission `simka.menu` belum di-seed | Semua user terkunci dari SIMKA | Seed sebelum deploy, sediakan fallback sementara `erkap.menu` |
| Test SIMKA gagal karena middleware baru | CI merah | Update test dengan role/permission |
| 33 hardcoded URL terlewat | Form E-RKAP gagal submit | grep wajib, test E2E |
| Case-sensitive `layouts.SIMKA` | 500 di Linux | Standardisasi `layouts.Simka` |
| Sidebar terlalu panjang | UX buruk | Gunakan sub-grup collapsible, uji di layar kecil |
| Dua layout masih ada | Duplikasi dan drift | Hapus di fase C |
| Rename tabel | Rusak total | Jangan lakukan |
| Permission `erkap.*` dihapus | Role kehilangan akses | Pertahankan sampai fase C |
| Redirect POST tidak bekerja | Integrasi eksternal gagal | Koordinasi sebelum fase B |

---

## 13. Keputusan yang Masih Perlu Dikonfirmasi

> **Update 9 November 2026:** keputusan 1 (Fase B), 2 (role pengelola SPPH),
> 3 (menu tersembunyi), dan 7 (case-sensitivity layout) sudah diputuskan dan
> diimplementasikan. Keputusan 4–6 dan 8 masih terbuka untuk fase lanjutan.

1. **Apakah Fase B (URL `/simka/erkap`) wajib sekarang atau boleh menyusul?**
   - Rekomendasi: menyusul setelah Fase A stabil.
2. **Siapa saja role yang boleh mengubah tarif SPPH?**
   - Rekomendasi awal: `super-admin` + `erkap-admin`.
3. **Apakah menu `erkap.revenue-plans` dan `erkap.expense-plans` perlu ditampilkan?**
   - Rekomendasi: tetap tersembunyi sampai ada kebutuhan bisnis.
4. **Apakah dashboard SIMKA harus langsung menggabungkan KPI Pendapatan dan Pengeluaran?**
   - Rekomendasi: fase A cukup landing dua kartu; KPI gabungan di fase D.
5. **Apakah folder view/controller perlu dipindahkan ke namespace SIMKA?**
   - Rekomendasi: tidak pada fase merge.
6. **Apakah permission `erkap.*` akan di-rename menjadi `simka.pengeluaran.*`?**
   - Rekomendasi: tidak, kecuali ada kebutuhan strategis.
7. **Apakah production berjalan di Linux?**
   - Jika ya, perbaiki `layouts.SIMKA` → `layouts.Simka` sebelum deploy.
8. **Apakah ada integrasi eksternal yang memanggil `/erkap/*`?**
   - Jika ada, redirect GET saja tidak cukup; perlu masa transisi dual-prefix.

---

## 14. Acceptance Criteria

> **Status 9 November 2026:** kriteria Fase A, B, dan C di bawah sudah terpenuhi.
> Fase D sudah dikerjakan sebagian: dashboard KPI gabungan, Report Center
> gabungan (filter domain + export), dan klasifikasi pendapatan berbasis CoA.
> Approval SPPH masih menunggu keputusan bisnis.

### Fase A

- [ ] Portal hanya menampilkan satu kartu: SIMKA.
- [ ] `/simka` dapat dibuka oleh role yang berhak.
- [ ] Sidebar menampilkan kategori `Pendapatan: SIMKA`.
- [ ] Sidebar menampilkan kategori `Pengeluaran: E-RKAP`.
- [ ] Empat SPPH dapat dibuka dari sidebar.
- [ ] Seluruh menu E-RKAP existing dapat dibuka sesuai permission.
- [ ] 128 view E-RKAP memakai layout SIMKA tanpa error.
- [ ] Header menampilkan brand SIMKA.
- [ ] Footer menampilkan SIMKA SBS.
- [ ] Permission `simka.menu` dan `simka.spph.manage` ada.
- [ ] Test `Simka` dan `Erkap` hijau.
- [ ] Tidak ada perubahan skema database.

### Fase B

- [ ] Semua URL E-RKAP berada di `/simka/erkap/...`.
- [ ] Route name `erkap.*` tetap berfungsi.
- [ ] Tidak ada `url('erkap/...')` tersisa di Blade.
- [ ] Redirect `/erkap/*` berfungsi.
- [ ] Test E2E E-RKAP hijau.
- [ ] Tidak ada 404 pada menu sidebar.

### Fase C

- [ ] Hanya ada satu layout dan satu sidebar.
- [ ] Tidak ada partial E-RKAP yang tidak terpakai.
- [ ] Dokumentasi tutorial dan matriks akses diperbarui.
- [ ] Tidak ada referensi `layouts.SIMKA` (case-sensitive).

### Fase D

- [x] Dashboard SIMKA menampilkan KPI Pendapatan, Pengeluaran, Anggaran Opex+Capex, Realisasi, dan Selisih.
- [x] Dashboard dapat memilih periode RKAP.
- [x] Dashboard menampilkan grafik rencana pendapatan vs pengeluaran per bulan.
- [x] Report Center memiliki tipe laporan `combined` (gabungan SIMKA & E-RKAP).
- [x] Report Center dapat difilter domain `all` / `revenue` / `expense`.
- [x] Laporan gabungan dapat diekspor PDF/Excel melalui mekanisme Report Center existing.
- [x] Laporan gabungan menampilkan klasifikasi rencana pendapatan per akun CoA.
- [ ] Approval SPPH — belum, menunggu keputusan level approver dan rule bisnis.
- [x] Relasi pendapatan–pengeluaran menggunakan `chart_of_accounts` (sudah ada di `erkap_revenue_plans`).
- [x] Master data bersama menggunakan `chart_of_accounts` untuk klasifikasi pendapatan.

---

## 15. Lampiran

### 15.1 Perintah Bantu

```bash
# Jumlah route per modul
php artisan route:list --json > storage/app/routes.json

# Cari route E-RKAP
php artisan route:list | Select-String "erkap"

# Cari hardcoded URL
rg "url\('erkap" resources/views

# Cari referensi layout
rg "layouts\.Erkap|layouts\.SIMKA" resources/views

# Jalankan test per modul
php artisan test --filter=Simka
php artisan test --filter=Erkap
```

### 15.2 Referensi File Penting

| File | Fungsi |
|---|---|
| `routes/web.php` | Load route modul |
| `routes/routers/simka.php` | Route SIMKA |
| `routes/routers/erkap.php` | Route E-RKAP |
| `app/Http/Controllers/PortalController.php` | Kartu portal |
| `app/Services/Simka/DashboardSummaryService.php` | KPI gabungan Pendapatan vs Pengeluaran |
| `app/Services/Reporting/ReportGenerator.php` | Tipe laporan `combined` + filter domain |
| `resources/views/reports/combined.blade.php` | Template laporan gabungan |
| `database/seeders/RolePermissionSeeder.php` | Permission & role |
| `resources/views/layouts/Simka.blade.php` | Layout SIMKA |
| `resources/views/layouts/Erkap.blade.php` | Layout E-RKAP |
| `resources/views/layouts/partials/simka/app-sidebar.blade.php` | Sidebar SIMKA |
| `resources/views/layouts/partials/erkap/app-sidebar.blade.php` | Sidebar E-RKAP |
| `resources/views/portal.blade.php` | Render kartu portal |
| `app/Models/Erkap/` | Model E-RKAP |
| `app/Models/Spph*.php` | Model SPPH |
| `database/migrations/2026_10_16_*.php` s.d. `2026_10_23_*.php` | Migrasi SIMKA |
| `database/migrations/2026_09_07_*.php` s.d. `2026_09_29_*.php` | Migrasi E-RKAP |

### 15.3 Kesimpulan Akhir

Penggabungan E-RKAP ke SIMKA adalah **konsolidasi modul di level UI, navigasi, dan otorisasi**. Database sudah menyatu dan tidak memerlukan migrasi fisik. Kunci keberhasilan:

1. **Jangan ubah route name dan permission E-RKAP pada fase awal.**
2. **Satu layout dan satu sidebar sebagai source of truth.**
3. **Tambah permission SIMKA sebelum mengaktifkan middleware.**
4. **Ubah URL E-RKAP secara terpisah setelah UI stabil.**
5. **Jangan rename tabel atau namespace tanpa alasan kuat.**

Dengan urutan ini, pengguna langsung mendapatkan pengalaman “satu modul SIMKA” tanpa mempertaruhkan 288 route E-RKAP, 187 permission, dan 1.919 baris test yang sudah ada.

---

## 16. Catatan Implementasi — 9 November 2026

### 16.1 Fase A (selesai)

| Item | Perubahan |
|---|---|
| Permission | `simka.menu` + `simka.spph.manage` ditambahkan di `RolePermissionSeeder`. `simka.menu` diberikan otomatis ke semua role yang punya `erkap.menu`; `simka.spph.manage` ke `super-admin`, `erkap-admin`, dan `admin`. |
| Portal | Kartu E-RKAP dihapus. Hanya satu kartu SIMKA (`Pendapatan & Pengeluaran`, permission `simka.menu`). |
| Route SIMKA | Seluruh grup memakai middleware `permission:simka.menu`. Semua route tulis (POST/DELETE) memakai `permission:simka.spph.manage`. |
| Layout | 128 view E-RKAP diubah dari `layouts.Erkap` ke `layouts.Simka`. 43 view SIMKA dinormalisasi dari `layouts.SIMKA` ke `layouts.Simka`. |
| Header/Footer | Brand SIMKA + subtitle “Sistem Informasi Manajemen Keuangan & Anggaran”; footer menjadi `SIMKA SBS`. |
| Sidebar | Satu sidebar gabungan: `Pendapatan: SIMKA` dan `Pengeluaran: E-RKAP`, lengkap dengan badge approval. |
| Landing | Dashboard SIMKA menampilkan dua kartu domain: Pendapatan dan Pengeluaran. |
| Test | `SimkaModuleMergeTest` ditambahkan; test SPPH dan matriks role E-RKAP disesuaikan. |

### 16.2 Fase B (selesai)

| Item | Perubahan |
|---|---|
| Prefix URL | `routes/routers/erkap.php` memakai prefix `simka/erkap`; route name tetap `erkap.*`. |
| Hardcoded URL | 33 titik `url('erkap/...')` di Blade diubah ke `url('simka/erkap/...')`. |
| Test path | 52 path literal `/erkap/...` di `ErkapE2ETest` diubah ke `/simka/erkap/...`. |
| Backward compatibility | Redirect permanen `/erkap/{any?}` → `/simka/erkap/{any?}` di `routes/web.php`. |

### 16.3 Verifikasi

| Perintah | Hasil |
|---|---|
| `php artisan test tests/Feature/Simka` | 19 passed |
| `php artisan test tests/Feature/Erkap/ErkapRolePermissionMatrixTest.php` | 7 passed |
| `php artisan test tests/Feature/Erkap/DashboardAnalyticsFeatureTest.php` | 9 passed |
| Batch Structure / RoutineCost / CoaOption / Form1 / BudgetCapex / ApprovalDetail | 58 passed |
| `php artisan view:cache` | semua Blade berhasil dikompilasi |
| `php artisan db:seed --class=RolePermissionSeeder` (dua kali) | idempoten; 13 role mendapat `simka.menu` |

### 16.4 Fase C — cleanup (selesai)

| Item | Perubahan |
|---|---|
| Layout lama | `resources/views/layouts/Erkap.blade.php` dihapus. |
| Partial lama | `resources/views/layouts/partials/erkap/` (5 file: head, header, sidebar, footer, plugin) dihapus. |
| Verifikasi referensi | `rg "layouts.Erkap|partials.erkap"` → tidak ada referensi tersisa di kode aplikasi. |
| Dokumentasi | Catatan migrasi ditambahkan di `tutorial-pengisian-erkap.md`, `tutorial-pengisian-erkap-end-user.md`, `tutorial-pengisian-erkap-end-user-new.md`, `tutorial-erkap-flow.md`, dan `planning-penyesuaian-role-permission-erkap-tutorial.md`. |

Yang **sengaja tidak dilakukan** pada fase ini (sesuai rekomendasi §4):

- Rename route `erkap.*` → `simka.erkap.*`.
- Rename permission `erkap.*` → `simka.pengeluaran.*`.
- Pemindahan `resources/views/erkap` → `resources/views/simka/erkap`.
- Pemindahan `app/Http/Controllers/Erkap` → `App\Http\Controllers\Simka\Erkap`.

Alasan: seluruh perubahan tersebut bersifat mekanis tetapi berisiko tinggi, tidak
menambah nilai bisnis langsung, dan tidak diperlukan untuk mencapai target “satu modul”.

### 16.5 Fase D — integrasi lanjutan (sebagian selesai)

| Item | Perubahan |
|---|---|
| Dashboard KPI | `DashboardSummaryService` menghitung pendapatan rencana, pengeluaran rencana, anggaran opex+capex, realisasi, selisih, dan serapan. Dashboard SIMKA menampilkan KPI, pemilih periode RKAP, dan grafik bulanan. |
| Report Center gabungan | `ReportGenerator` mendapat tipe `combined` dengan filter domain `all` / `revenue` / `expense`. Tipe ini otomatis tersedia untuk export PDF/Excel di Report Center. |
| Klasifikasi CoA | Laporan gabungan menampilkan rencana pendapatan per akun `chart_of_accounts`. Ini memenuhi relasi pendapatan–pengeluaran dan master data bersama tanpa migrasi baru. |
| Test | `tests/Feature/Simka/DashboardSummaryTest.php` (7 test) mencakup agregasi KPI, default RKAP terbaru, render kartu dashboard, preview gabungan, dan filter domain. |

Catatan angka pendapatan: tabel tarif SPPH menyimpan **tarif satuan** (Rp/unit),
bukan volume produksi, sehingga belum bisa menghasilkan total pendapatan SPPH.
Karena itu KPI pendapatan memakai `erkap_revenue_plans`; statistik SPPH
(jumlah skema, baris tarif, kategori) ditampilkan terpisah sebagai indikator
kelengkapan konfigurasi.

**Verifikasi Fase D:**

| Perintah | Hasil |
|---|---|
| `php artisan test --filter=DashboardSummaryTest` | 7 passed |
| `php artisan test tests/Feature/Erkap/DashboardAnalyticsFeatureTest.php` | 9 passed |
| `php artisan test tests/Feature/Simka/SimkaModuleMergeTest.php` | 9 passed |
| `php artisan test tests/Feature/Erkap/ReportingCoaCompositionTest.php` | 11 passed |
| `php artisan test tests/Feature/Erkap/ScheduledReportsTest.php` | 2 passed |
| `php artisan test tests/Feature/Erkap/RKAPLifecycleFeatureTest.php` | 9 passed |
| `php artisan test tests/Feature/Erkap/ZBBReviewFeatureTest.php` | 10 passed |
| `php artisan test tests/Feature/Erkap/WorkProgramFeatureTest.php` | 9 passed |
| `php artisan test tests/Feature/Erkap/RoutineCostCoaFeatureTest.php` | 7 passed |
| `php artisan view:cache` | semua Blade berhasil dikompilasi |

Catatan regresi terpisah: `TransactionCoaIntegrityTest` masih memiliki 7
kegagalan pada skenario investment plan yang mengirim COA dari pusat biaya lain.
Kegagalan ini berasal dari perilaku validasi investment plan yang sudah ada
(COA diturunkan dari pasangan pusat biaya + elemen biaya) dan tidak menyentuh
file Fase D. Perlu diselaraskan pada pekerjaan terpisah.

**Yang belum dikerjakan pada Fase D:**

- **Approval SPPH** — membutuhkan keputusan bisnis tentang level approver,
  siapa yang boleh menyetujui tiap skema, dan apakah approval berlaku per
  skema atau per baris tarif. Implementasi saat ini sengaja ditunda agar tidak
  membuat workflow spekulatif.

### 16.6 Langkah deploy yang wajib

```bash
php artisan db:seed --class=RolePermissionSeeder
php artisan optimize:clear
```

Seeder harus dijalankan di setiap environment sebelum aplikasi dipakai, karena portal dan seluruh route SIMKA kini bergantung pada `simka.menu`.
