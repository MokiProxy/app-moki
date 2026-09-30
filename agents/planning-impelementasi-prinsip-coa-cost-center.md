# Planning Implementasi — Prinsip COA 15 Digit & Cost Center (Segmentasi a–e)

> **Scope:** Modul ERKAP — Chart of Account (COA) & Cost Center
> **Status:** Implementasi selesai (F1–F10 selesai; 415 test hijau)
> **Keputusan K-1 (sudah final):** jumlah segmen a–e = **15 karakter**, bukan 16.
> Konstanta tunggal: `App\Support\CoaCode::LENGTH = 15`, `COST_CENTER_LENGTH = 11`.
> **Referensi dokumentasi terkait:**
> - `agents/recap-pedoman-rkap/planning/G6-coa-16-digit.md`
> - `agents/planning-gap2-chart-of-accounts.md`
> - `agents/recap-pedoman-rkap/hasil-analisis-gap.md` (G6, baris 31, 67, 77, 121–124, 162)

---

## 1. Pertanyaan Awal: "Risk manager 1 orang atau bisa lebih?"

**Jawaban berdasarkan analisis kode (bukan asumsi):**

Saat ini **dirancang untuk 1 orang per level approval**, dibuktikan di:

- `app/Services/ApprovalService.php:42-44` — matriks approval `risk_register` hanya **1 level** (`1 => 'erkap-risk-manager'`).
- `app/Services/ApprovalService.php:302-321` — `getApproverByRole()` memakai `->first()`, artinya dari semua user ber-role `erkap-risk-manager` hanya **1 user pertama** yang ditetapkan sebagai approver.
- `app/Services/ApprovalService.php:280-300` — `requireTurn()` memeriksa `approver_id` **persis milik satu user**; user lain dengan role sama akan menerima pesan "Bukan giliran Anda".
- `database/migrations/2026_09_21_000017_create_erkap_approvals_table.php:18` — kolom `approver_id` single FK ke `users`.
- Field `risk_owner` pada asesmen risiko bulanan (`erkap_risk_assessments_monthly.risk_owner`) adalah **satu string bebas** (`varchar(255)`), bukan relasi user, dan tidak divalidasi terhadap user mana pun. (`database/migrations/2026_09_11_000020_create_risk_assessments_monthly_table.php:32`, view `risk-assessments-monthly/create.blade.php:121-123`).

**Kesimpulan:** Secara desain = **1 orang**. Mendukung "lebih dari satu" membutuhkan perubahan nyata (pivot approver / multiple approval rows / `risk_owner` jadi relasi user). **Diluar scope utama dokumen ini** — akan dipisahkan sebagai sub-bab kecil di akhir (Lampiran A). Fokus utama dokumen ini adalah COA & Cost Center.

---

## 2. Analisis Codebase & Struktur Database Saat Ini (ERKAP)

### 2.1 Tabel & Migrasi Terkait COA / Cost Center

| Entitas | Tabel | Migrasi |
|---|---|---|
| Chart of Account | `chart_of_accounts` | `2026_09_07_125216`, `2026_09_11_000004`, `2026_09_26_000013` |
| Kategori Elemen Biaya | `erkap_cost_element_categories` | `2026_09_07_125807` |
| Elemen Biaya | `erkap_cost_elements` | `2026_09_07_125823`, `2026_09_11_000005` |
| Cost Center | `cost_centers` | `2026_09_11_000006`, `2026_09_18_000001`, `2026_09_26_000018` |

### 2.2 Baseline Sebelum Implementasi (sudah disuperceded oleh K-1)

> ⚠️ Bagian di bawah ini mendeskripsikan kondisi **sebelum** implementasi dan dipakai
> hanya sebagai rujukan historis/analisis gap. Sebagian besar sudah berubah:
> `code` COA kini **15 karakter** (bukan 16), tidak lagi memakai `rpad`, dan
> `chart_of_accounts` kini punya FK `cost_center_id` + `cost_element_id`.

**ChartOfAccount (`app/Models/ChartOfAccount.php`)**
- Kolom: `code varchar(16)`, `name`, `type` enum `revenue|expense`, `description`.
- `code` telah di-expand ke 16 digit (`2026_09_26_000013`) dengan backfill `rpad(code,16,'0')` → data legacy 4 digit disimpan sebagai prefix, mis. `6000` → `6000000000000000`.
- Helper `CoaCode` (`app/Support/CoaCode.php`): `pad()`, `valid()`, `format()` (XXXX-XXXX-XXXX-XXXX).
- Validasi `Coa16Digits` (`app/Rules/Coa16Digits.php`) → **hanya numerik 16 digit**, tidak menerima huruf.

**Cost Element (`app/Models/Erkap/CostElement.php`)**
- Kolom: `code` (4-digit legacy, unik), `name`, `erkap_cost_element_category_id`, `chart_of_account_id`.
- `coaSuggestion()`: mencocokkan `chart_of_accounts.code` dengan `CoaCode::pad(costElement.code)`.

**Cost Center (`app/Models/Erkap/CostCenter.php`, tabel `cost_centers`)**
- Kolom saat ini: `code varchar(50) unique`, `name`, `owner`, `division_id`, `is_swakelola`, `is_centralized`, `coordinating_division_id`, timestamps + audit.
- Format kode saat ini: `[A-Za-z]\d{14}` (15 karakter) mis. `F0120215109100` =
  - `F` → BU (informal),
  - `01` → lokasi (informal, hardcode),
  - `20215` → 5-digit **division_id** (bukan "Manajemen Area"),
  - `510`/`110` → aktivitas/swakelola (hardcode),
  - `9100` → 4 digit elemen biaya.
- Helper string-slicing: `costElementCode()` (4 char terakhir), `costCenterCode()` (3 char di posisi −7), `isSwakelola()`, `isCentralized()`.
- **Masalah:** segment MU/Manajemen Area saat ini **dicuri dari division_id**; tidak berdiri sendiri; `is_swakelola` ditentukan dari substring; lokasi & BU di-hardcode di seeder.

**Seeder yang relevan**
- `ChartOfAccountsSeeder.php` — 168 akun 4-digit legacy (6000–9109), di-pad saat insert.
- `CostElementsSeeder.php` — 167 elemen biaya, mapping COA ke `chart_of_account_id` **berpotensi null** (COA di-lookup pakai kode 4 digit vs tersimpan 16 digit).
- `CostCentersSeeder.php` — membuat 2 cost center per division dengan kode `F + 01 + division_id + 510/110 + element`.
- `Erkap/Support/RkapSimulasi.php` — membuat `CostCenter` `'CC-SIM-01'` (tidak mengikuti pola segment).

### 2.3 Konsumen COA / Cost Center (yang harus ikut berubah)

**Controller**
- `ChartOfAccountController.php` (index/create/store/show/sync)
- `CostCenterController.php` (CRUD)
- `CostElementController.php` (CRUD)
- `RoutineCostController.php` (`create`, `edit`, `groupedCostCenters()` di baris 400, `resolveChartOfAccountId()` di 387)
- `InvestmentPlanController.php`, `RevenuePlanController.php`, `ExpensePlanController.php`
- `DashboardController`, `DashboardWidgetsController`, `DashboardDrilldownController`
- `BudgetCapexController.php` (consolidate/summary)

**Service**
- `BudgetOpexConsolidationService.php` (grup `division_id-cost_center_id-chart_of_account_id`, baris 20–80)
- `Erkap/ZBBReviewService.php` (grup routine cost by cost element, investment by cost center, revenue by COA)
- `Erkap/CentralizedCostService.php` (is_centralized / coordinator)
- `ErkapAccess.php` (scoping divisi — perlu dipetakan ulang bila cost center tidak lagi bergantung pada division_id)
- `Dashboard/CostCenterHeatmapService.php`, `ExpenseBreakdownService.php`, `RevenueBreakdownService.php`
- `Dashboard/DrilldownService.php` (`pnlDetail`, `routineCostDetail`, dsb)
- `Reporting/ReportGenerator.php` (report finansial mengambil `RevenuePlan.chartOfAccount` & `ExpensePlan.chartOfAccount`)
- `ApprovalService.php` (hanya terkait risk-manager, Lampiran A)

**Request & Validasi**
- `Store/UpdateCostCenterRequest.php` — `code` regex `/^[A-Za-z]\d{14}$/` + closure cek suffix 4 digit ke `erkap_cost_elements`.
- `StoreChartOfAccountRequest.php` — `code` + `Coa16Digits` + unique.
- `Store/UpdateRoutineCostRequest.php`, `Store/UpdateInvestmentPlanRequest.php`, `Store/UpdateRevenuePlanRequest.php`, `Store/UpdateExpensePlanRequest.php` — referensi `cost_center_id` / `chart_of_account_id` (exists).

**View**
- `erkap/cost-center/{create,edit,index}.blade.php`
- `erkap/chart-of-account/{create,index,show}.blade.php`
- `erkap/cost-element/{create,edit,index}.blade.php`
- `erkap/routine-cost/{create,edit,index}.blade.php` — ada cascading `data-coa-id`, `data-owner`, `data-cost-element` (client-side)
- `erkap/investment-plan/{create,edit,index}.blade.php`
- `erkap/revenue-plan/{create,edit}.blade.php`, `erkap/expense-plan/{create,edit}.blade.php`

**Export/Import**
- `Exports/Erkap/RoutineCostExport.php`, `InvestmentPlanExport.php`, `BudgetConsolidationExport.php`, `ConsolidatedRkapExport.php`
- `Imports/Erkap/Form1Import.php` (risk, bukan COA — tidak terpengaruh langsung)

**Tests & Factory**
- `tests/Unit/Erkap/CoaCodeTest.php` (mengunci `pad()` → 16 digit)
- `tests/Feature/Erkap/RoutineCostCoaFeatureTest.php`, `CentralizedCostFeatureTest.php`
- `tests/Concerns/BuildsErkapChain.php`, `database/factories/Erkap/CostCenterFactory.php, ChartOfAccountFactory.php`

### 2.4 Gap Utama (Kondisi Sekarang vs Prinsip Baru)

| # | Gap |
|---|---|
| G1 | Konsep "Manajemen Area (c)" tidak ada sebagai entitas; saat ini 5 digit = `division_id` |
| G2 | Konsep "Lokasi (b)" & "Bisnis Unit (a)" hanya di-hardcode string dalam seeder, bukan master data |
| G3 | Konsep "Aktivitas (d)" tidak ada; `is_swakelola` diturunkan dari substring kode |
| G4 | `code` cost center 15 karakter vs COA 16 digit — **tidak komposisional** (CC + elemen ≠ COA) |
| G5 | Semua relasi berbasis string-slicing & `CoaCode::pad` yang rapuh |
| G6 | Belum ada dropdown **cascading parent→child** yang generik (hanya pola `data-*` manual di routine-cost) |

---

## 3. Definisi Prinsip Baru (Target)

### 3.1 Struktur COA — 15 karakter (alfanumerik karena a = huruf)

```
X(a) - XX(b) - XXXXX(c) - XXX(d) - XXXX(e)
```

Total: `1 + 2 + 5 + 3 + 4` = **15 karakter**.

| Segmen | Panjang | Nama | Contoh |
|---|---|---|---|
| a | 1 | **Bisnis Unit** | `F` = Penambangan, `G` = Rental |
| b | 2 | **Lokasi** | `01` Umum, `02` Banko, `03` TAL, `04` PELTAR, `05` Peranap, `06` BTU |
| c | 5 | **Manajemen Area** | `20200` Senior Manajer Keuangan/Umum/SDM, `30100` Senior Manajer Komersial |
| d | 3 | **Aktivitas** | `110` Penambangan Swakelola, `120` Penambangan Non-Swakelola, `210` Rental Swakelola |
| e | 4 | **Elemen/Jenis Biaya** | `6000` Pendapatan Jasa Kupas Tanah, `6001` Pendapatan Jasa Penambangan, `6002` Pendapatan Sewa Peralatan, `8104` Biaya Material & Suku Cadang |

### 3.2 Aturan Komposisi (kunci utama perubahan)

- **Cost Center = segmen a–d** (11 karakter: `a(1) + b(2) + c(5) + d(3)`).
- **COA = Cost Center + elemen e** (`a–d + e`).
- Artinya setiap Cost Center bisa dikombinasikan dengan banyak elemen biaya (e) menjadi banyak COA.

> ✅ **KEPUTUSAN K-1 (FINAL):** jumlah segmen a–e = **15 karakter**, bukan 16.
> - `Cost Center` = segmen a–d = **11 karakter**.
> - `COA` = segmen a–e = **15 karakter** (`cost_center.code` + `cost_element.code`).
> - Kolom `chart_of_accounts.code` disetel ke `varchar(15)`; `cost_centers.code` ke `varchar(11)`.
> - Panjang diatur oleh konstanta tunggal sehingga perubahan hanya menyentuh satu tempat:
>   `App\Support\CoaCode::LENGTH`, `CoaCode::COST_CENTER_LENGTH`, dan
>   `CoaCode::SEGMENT_LENGTHS` (default `[a=1, b=2, c=5, d=3, e=4]`).
> - Validasi memakai `App\Rules\CoaCodeFormat` & `App\Rules\CostCenterCodeFormat`
>   (pengganti `Coa16Digits`), dengan pattern
>   `CoaCode::PATTERN_COST_CENTER = /^[A-Za-z0-9]\d{10}$/` dan
>   `CoaCode::PATTERN_COA = /^[A-Za-z0-9]\d{14}$/`.
> - **Tanpa** `legacy_code` dan **tanpa** backfill suffix; data di-reset & di-seed ulang.

### 3.3 Prinsip Fleksibilitas (Dropdown Child → Parent otomatis)

- Setiap segmen menjadi **master data** dengan relasi parent–child eksplisit (FK), bukan string-slicing.
- Memilih **anak** (mis. cost element) → dropdown **parent** (aktivitas → manajemen area → lokasi → BU) **otomatis terset** dari relasi (reverse lookup).
- Memilih **parent** → dropdown **anak difilter** sesuai parent terpilih.
- Chain penuh: `BU (a) → Lokasi (b) → Manajemen Area (c) → Aktivitas (d) → Elemen Biaya (e) → COA`.

---

## 4. Desain Struktur Database (Target)

### 4.1 Tabel Master Baru

Semua tabel baru memakai pola yang sama (memungkinkan cascading generik + ekspansi masa depan):

```sql
-- 1. Bisnis Unit (a)
CREATE TABLE erkap_business_units (
  id          bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  code        varchar(1)  NOT NULL UNIQUE,          -- F, G
  name        varchar(100) NOT NULL,
  is_active   boolean     NOT NULL DEFAULT true,
  sort_order  integer     NOT NULL DEFAULT 0,
  created_by  bigint NULL REFERENCES users(id),
  updated_by  bigint NULL REFERENCES users(id),
  created_at  timestamptz,
  updated_at  timestamptz
);

-- 2. Lokasi (b) — child dari BU
CREATE TABLE erkap_locations (
  id                  bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  code                varchar(2)   NOT NULL,        -- 01..06
  name                varchar(100) NOT NULL,        -- Umum, Banko, TAL, PELTAR, Peranap, BTU
  erkap_business_unit_id bigint REFERENCES erkap_business_units(id),
  is_active           boolean NOT NULL DEFAULT true,
  sort_order          integer NOT NULL DEFAULT 0,
  created_at timestamptz, updated_at timestamptz,
  UNIQUE (code, erkap_business_unit_id)
);

-- 3. Manajemen Area (c) — child dari Lokasi (opsional) + boleh dipetakan ke division
CREATE TABLE erkap_management_areas (
  id                bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  code              varchar(5)   NOT NULL,          -- 20200, 30100
  name              varchar(150) NOT NULL,
  erkap_location_id bigint NULL REFERENCES erkap_locations(id),          -- cascade optional
  division_id       bigint NULL REFERENCES divisions(id),               -- bridge ke org chart
  is_active         boolean NOT NULL DEFAULT true,
  sort_order        integer NOT NULL DEFAULT 0,
  created_at timestamptz, updated_at timestamptz,
  UNIQUE (code, erkap_location_id)
);

-- 4. Aktivitas (d) — child dari Manajemen Area (opsional)
CREATE TABLE erkap_activities (
  id                     bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  code                   varchar(3)   NOT NULL,     -- 110, 120, 210
  name                   varchar(150) NOT NULL,
  erkap_management_area_id bigint NULL REFERENCES erkap_management_areas(id),
  is_swakelola           boolean NOT NULL DEFAULT false,   -- flag turunan aktivitas
  is_active              boolean NOT NULL DEFAULT true,
  sort_order             integer NOT NULL DEFAULT 0,
  created_at timestamptz, updated_at timestamptz,
  UNIQUE (code, erkap_management_area_id)
);
```

> Catatan untuk fleksibilitas: `parent_id` di atas dibuat *spesifik antar-tabel* (FK eksplisit). Bila ingin benar-benar generik (level dinamis), gunakan **satu tabel `erkap_code_segments`** dengan kolom `level` + `parent_id` self-FK + `code` + `name` (skema "adjacency list"). Draft 4.1 mengikuti FK eksplisit agar mudah dibaca & di-query; Lampiran B berisi varian generik.

### 4.2 Perubahan Tabel `cost_centers`

```sql
ALTER TABLE cost_centers ADD COLUMN
  erkap_business_unit_id    bigint REFERENCES erkap_business_units(id),     -- a
  erkap_location_id         bigint REFERENCES erkap_locations(id),          -- b
  erkap_management_area_id  bigint REFERENCES erkap_management_areas(id),   -- c
  erkap_activity_id         bigint REFERENCES erkap_activities(id);        -- d
```

> Catatan implementasi: `legacy_code` **tidak** dipakai. Pendekatan yang dipilih adalah
> reset & seed ulang, sehingga tidak diperlukan kolom cadangan kode lama maupun
> skrip backfill tebakan segmen (lihat §7).
-- 'code' dihitung ulang sebagai komposisi a..d (11 karakter), kolom jadi varchar(11).
-- kolom division_id tetap dipertahankan (untuk scoping akses & laporan) namun
-- di-sinkronkan dari erkap_management_areas.division_id.
```

**Model `CostCenter`:** hapus `costElementCode()/costCenterCode()/isSwakelola()` berbasis substring; ganti dengan relasi `businessUnit()/location()/managementArea()/activity()` + accessor `code` komposisi.

### 4.3 Perubahan Tabel `chart_of_accounts`

```sql
ALTER TABLE chart_of_accounts ADD COLUMN
  cost_center_id   bigint REFERENCES cost_centers(id) ON DELETE RESTRICT,
  cost_element_id  bigint REFERENCES erkap_cost_elements(id) ON DELETE RESTRICT;

-- code diisi = cost_center.code + elemen (a..e) = 15 karakter.
-- name dibiarkan manual; type tetap revenue|expense (diturunkan dari elemen bila kosong).
```

**Model `ChartOfAccount`:** tambah `costCenter()`, `costElement()`; tambah accessor/scoper untuk mencari COA dari kombinasi `(cost_center_id, cost_element_id)`.

---

## 5. Desain Fitur Dropdown Cascading (Parent → Child → Reverse)

### 5.1 API JSON (Controller baru: `Erkap\CoaOptionController`)

Route baru di `routes/routers/erkap.php` (di bawah prefix `erkap`, permission `erkap.menu` agar terbaca semua user modul):

```
GET /erkap/coa-options/business-units
GET /erkap/coa-options/locations?business_unit_id={id}
GET /erkap/coa-options/management-areas?location_id={id}
GET /erkap/coa-options/activities?management_area_id={id}
GET /erkap/coa-options/cost-elements?activity_id={id}                  # kategori boleh ikut
GET /erkap/coa-options/cost-centers?activity_id=&management_area_id=&location_id=&business_unit_id=
GET /erkap/coa-options/accounts?cost_center_id=&cost_element_id=       # daftar COA hasil komposisi
GET /erkap/coa-options/lookup?code={cost_center_code_or_coa_code}      # reverse: 1 kode → isi semua parent
```

Setiap endpoint mengembalikan:
```json
{ "data": [ { "id": 1, "code": "F", "name": "Penambangan", "label": "F - Penambangan" } ] }
```

**Reverse lookup (`lookup`):** input satu kode cost center/COA → kembalikan seluruh rantai `{business_unit, location, management_area, activity, cost_element, cost_center, chart_of_account}`. Ini yang dipakai untuk "dropdown anak otomatis memilih parent" saat edit / saat memilih entitas utuh dari dropdown tunggal.

### 5.2 UI & JavaScript

- Buat helper jQuery/JS generik `ErkapCascade` (mis. `public/js/erkap-cascade.js` atau `@js` inline di layout) dengan pola markup:

```html
<select class="cascade-parent" data-cascade-target="#lokasi" data-cascade-url="/erkap/coa-options/locations" data-param="business_unit_id" data-placeholder="Pilih Lokasi"></select>
<select class="cascade-child" id="lokasi" data-cascade-url="/erkap/coa-options/management-areas" data-param="location_id" ...></select>
```

- Alur:
  1. `change` parent → fetch child → rebuild `<option>` → **re-init Select2** (`.select2('destroy')` lalu re-init), karena plugin auto-init `layouts/partials/erkap/app-plugin.blade.php:3-23` hanya berjalan saat page load.
  2. Saat edit page: isi dropdown paling bawah (mis. COA) → panggil `lookup` → set berantai parent-parent di atasnya (reverse cascade).
  3. Komponen Reactif untuk kode: tampilkan pratinjau kode `a-b-c-d-e` yang berubah seiring pilihan.

- Halaman yang dipasang: `cost-center/create|edit`, `chart-of-account/create`, `cost-element/create|edit`, `routine-cost/create|edit`, `investment-plan/create|edit`, `revenue-plan/create|edit`, `expense-plan/create|edit`. Mengganti pola `data-*` manual lama di `routine-cost` (baris 74–124, JS 340–382) dengan helper generik.

### 5.3 Validasi Server-Side

- `Store/UpdateCostCenterRequest`: ganti regex string-15-char dengan validasi **komposisi**: `code` dihitung dari `a..d` terpilih; validasi `cost_element` tidak lagi relevan (element bukan bagian CC). Pastikan kombinasi segmen unik.
- `StoreChartOfAccountRequest`: `code` dihitung dari `cost_center_id + cost_element_id`; validasi `CoaCodeFormat` (15 karakter) + `exists:cost_centers` & `exists:erkap_cost_elements`.
- Rule reusable: `App\Rules\CoaCodeFormat` & `App\Rules\CostCenterCodeFormat` (menggantikan `Coa16Digits`), Composition helper di `App\Support\CoaCode` (`composeCostCenter()`, `compose()`, `decompose()`, `formatCostCenter()`, `formatCoa()`).

---

## 6. Dampak & Perubahan per Fitur (Lengkap)

### 6.1 Master Data (sidebar "Master Data" → "Chart of Accounts", `app-sidebar.blade.php:76-155`)

- Tambah menu/submenu baru bila perlu: **"Pusat Biaya"** tetap seperti sekarang (baris 146–153), plus entri master untuk BU/Lokasi/Manajemen Area/Aktivitas (opsional, bisa digabung menjadi page "Struktur COA").
- Permission baru di `RolePermissionSeeder.php` (`$erkapResources`, baris 164-189): `business-units`, `locations`, `management-areas`, `activities` (view/create/edit/delete) — mengikuti pola `erkap.*`.
- CRUD untuk 4 master baru (Model + Controller + Views), pola meniru `CostElementCategoryController` yang ringkas.

### 6.2 Cost Center (`CostCenterController`, views)

- Form create/edit:
  - Dropdown cascading BU → Lokasi → Manajemen Area → Aktivitas (dengan reverse-fill saat edit).
  - Hapus input `code` manual (otomatis terkomposisi); hapus/migrasi flag `is_swakelola`(→ dari aktivitas), `is_centralized`/`coordinating_division_id` tetap (fitur terpusat), tambah kolom `company_id`? (buat direktif K-1).
  - `division_id` dibawa otomatis dari `management_area.division_id` (dapat diedit manual dengan dropdown Divisi seperti sekarang).
- Index: tampilkan kolom kode komposisi + nama segment parent (`badge`), pemilik, tipe.
- `groupedCostCenters()` di `RoutineCostController.php:400-418` — injeksi `cost_element_id` berbasis substring harus diganti dengan relasi eksplisit → tambah method `segments()` atau eager-load.

### 6.3 Chart of Account (`ChartOfAccountController`, views)

- Form create:
  - Cascading: pilih Cost Center (atau 4 segmen) → pilih Elemen Biaya → COA otomatis.
  - Ambil `name`/`type` default dari elemen, boleh diedit.
- Index/Show: tampilkan kode terformat + relasi cost center/element.
- `sync()` (baris 67-105): sesuaikan agar *menemukan* COA berdasarkan `(cost_center_id, cost_element_id)` atau membuatnya bila belum ada — bukan by kode-pad.

### 6.4 Biaya Rutin & CAPEX (Routine Cost / Investment Plan)

- `RoutineCostController.php`:
  - `create`/`edit` (baris ~110-190): ganti cost-center swakelola/non-swakelola manual dengan cascading chain; simpan `cost_center_id` & `chart_of_account_id`.
  - `resolveChartOfAccountId()` (387–398): fallback `costElement->coaSuggestion()` → sesuaikan dengan relasi eksplisit.
  - `groupedCostCenters()` (400–418): ganti `$costCenter->costElementCode()` dengan `$costCenter->activity` / relasi cost element.
- `InvestmentPlanController` + views + `Store/UpdateInvestmentPlanRequest`: sama — dropdown cost center + COA via cascading.

### 6.5 Rencana Pendapatan/Beban (Revenue/Expense Plan) & P&L

- `RevenuePlanController` / `ExpensePlanController` (`create`), views, `Store/UpdateRevenuePlanRequest` (baris 23: `Rule::exists(...)->where('type','revenue')`): fetch COA via cascading (filter type revenue/expense). Fallback `chartOfAccount` tetap.
- `ProfitLossController` (membaca `RevenuePlan`/`ExpensePlan` — tidak berubah logic, hanya bentuk data COA baru).
- `Services/Reporting/ReportGenerator.php` (138-146): relasi ke COA tetap via Eloquent — aman setelah model di-update.

### 6.6 Konsolidasi & Monitoring

- `BudgetOpexConsolidationService.php` (20–80): key grup sekarang `division-cost_center-chart_of_account` (baris 45) — tetap valid karena memakai FK, bukan kode. Pastikan `cost_center_id` & `chart_of_account_id` tersedia. Update query `with()` bila relasi berubah.
- `Erkap/ZBBReviewService.php` (220–333): grup routine cost by `erkap_cost_element_id`, investment by `cost_center_id`, revenue by `chart_of_account_id` — semua berbasis FK, aman; verifikasi eager-load.
- `Dashboard/CostCenterHeatmapService.php` (relasi `routineCost.costCenter`), `ExpenseBreakdownService`, `RevenueBreakdownService`, `DrilldownService` (`pnlDetail`: baris 141-177) — hanya perlu eager-load/relasi yang konsisten; tanpa perubahan logic inti.
- `DashboardController`/`DashboardWidgetsController`: sama.

### 6.7 Exports

- `Exports/Erkap/RoutineCostExport.php`, `InvestmentPlanExport.php` → kolom "Cost Center" & "Chart of Account" membaca `costCenter->code` & `chartOfAccount->formattedCode`; update eager-load bila relasi diganti.
- `BudgetConsolidationExport.php`, `ConsolidatedRkapExport.php` (Form 3) → sama.
- Verifikasi heading/tabel tidak berubah dari sisi bisnis; hanya ruas data.

### 6.8 Audit, Akses & Approval

- `ErkapAccess.php`: scoping divisi via `divisionId()` masih relevan. Perhatikan: cost center tidak lagi selalu ber-`division_id` dari segmen c? (Management Area punya `division_id` bridge). Pastikan `assertCanInput` di `RoutineCostController::371-385` & `CentralizedCostService` tetap konsisten.
- Audit trail `HasAuditTrail` otomatis — tidak perlu diubah.

### 6.9 Seeder, Factory, Test

- `DatabaseSeeder.php` (baris E-RKAP): sisipkan seeder baru `ErkapBusinessUnitSeeder`, `ErkapLocationSeeder`, `ErkapManagementAreaSeeder`, `ErkapActivitySeeder` SEBELUM `CostCentersSeeder`.
- `CostCentersSeeder.php`: bangun cost center dari kombinasi segmen (a..d) alih-alih division+hardcode.
- `ChartOfAccountsSeeder.php`: kombinasi `(cc, element)` → COA.
- `CostElementCategoriesSeeder`/`CostElementsSeeder`: mapping COA elemen — pastikan `chart_of_account_id` sinkron (bug `pluck('id','code')` lama: kode 4 digit vs kode 15 karakter sudah tidak relevan; **0 cost element tanpa default COA** setelah seed).
- Factory: `BusinessUnitFactory` & `CostElementFactory` sudah collision-safe (kode 1 & 4 karakter); `CostCenterFactory`/`ChartOfAccountFactory` wajib memakai komposisi `CoaCode`.
- Test yang perlu di-update: `RoutineCostCoaFeatureTest`, `CentralizedCostFeatureTest`; test baru sudah ada: `CoaOptionApiTest` (F4), `StructureMasterCrudTest` + `StructureMasterPageTest` (F9) — lihat §9.

---

## 7. Rencana Migrasi Data (pendekatan: reset & seed ulang)

> Pendekatan awal "backfill + `legacy_code`" **tidak dipakai** karena data uji/dev masih
> dapat dibangun ulang dari seeder. Ini menghilangkan kebutuhan skrip tebakan segmen,
> kolom `legacy_code`, dan command `--dry-run` untuk pemetaan.

1. **Urutan seeder** di `DatabaseSeeder.php`: `ErkapBusinessUnitSeeder` →
   `ErkapLocationSeeder` → `ErkapManagementAreaSeeder` → `ErkapActivitySeeder` →
   `CostCentersSeeder` → `CostElementsSeeder` → `ChartOfAccountsSeeder`.
   FK antar master harus terpenuhi sebelum cost center & COA dibuat.
2. **Reset & seed ulang** dengan `php artisan migrate:fresh --seed`. Hasil aktual
   (terverifikasi): 2 BU, 12 lokasi, 49 management area, 147 aktivitas,
   100 cost center, 167 cost element, 16.700 COA.
3. **Verifikasi integritas** setelah seed — 12 pemeriksaan lulus:
   panjang COA = 15, panjang cost center = 11, tidak ada COA tanpa
   `cost_center_id`/`cost_element_id`, tidak ada cost element tanpa default COA,
   serta konsistensi segmentasi a–d pada kode hasil komposisi.
4. **Catatan produksi:** bila environment production tidak boleh di-reset, jalur
   backfill perlu dibuat terpisah sebagai proyek tersendiri (di luar scope dokumen ini).

---

## 8. Rencana Implementasi (Urutan & Effort)

| Fase | Isi | Artefak | Estimasi | Status |
|---|---|---|---|---|
| **F1 — Persetujuan keputusan** | Konfirmasi K-1 (**15 karakter**), K-2 (pemisahan master), K-3 (management area ↔ division bridge) | Keputusan tercatat | 0,5 hari | ✅ Selesai |
| **F2 — Skema & seeder master** | 3 migrasi + 4 seeder + 4 model + 4 factory + 2 rule komposisi | Tabel & master a–d | 2–3 hari | ✅ Selesai |
| **F3 — Reset & seed data** | `migrate:fresh` + `db:seed`, verifikasi 12 integrity check | Data bersih | 1–2 hari | ✅ Selesai |
| **F4 — API cascading** | `CoaOptionService` + `CoaOptionController` + 9 route + `erkap-cascade.js` | 1 service, 1 controller, 9 route, 1 js | 2 hari | ✅ Selesai |
| **F5 — CRUD Cost Center baru** | Form cascading, code komposisi, request/validasi, index | Controller+views+requests | 2 hari | ✅ Selesai |
| **F6 — CRUD COA baru** | Form cascading (CC+elemen), sync diperbaiki, type/name default | Controller+views+requests | 1,5 hari | ✅ Selesai |
| **F7 — Update form transaksi** | routine-cost, investment-plan, revenue/expense-plan pakai cascading generik | Controller+views+requests | 2–3 hari | ✅ Selesai |
| **F8 — Konsolidasi, dashboard, export** | Verifikasi/update service & export sesuai relasi baru | Service+export | 1–2 hari | ✅ Selesai |
| **F9 — Permission & menu** | Master baru di sidebar + permission seeder | Seeder+sidebar | 0,5 hari | ✅ Selesai |
| **F10 — Test & acceptance** | Unit + feature + fix factory; jalankan seluruh suite | Tests | 2–3 hari | ✅ Selesai (415 test hijau; audit keamanan & E2E lintas role sudah lewat) |
| | | **Total** | **~15–20 hari kerja** | **10/10 fase selesai** |

### 8.1 Catatan hasil implementasi per fase

- **F1** — K-1 dikunci di 15 karakter. Angka panjang tidak lagi tersebar di banyak tempat;
  semuanya dibaca dari `App\Support\CoaCode`.
- **F2** — Tabel master `erkap_business_units`, `erkap_locations`,
  `erkap_management_areas`, `erkap_activities` dibuat dengan relasi parent eksplisit.
  Migrasi: `2026_09_27_000001_create_erkap_code_segment_tables.php`,
  `..._000002_add_code_segments_to_cost_centers_table.php`,
  `..._000003_compose_chart_of_accounts_from_cost_center_and_element.php`.
- **F3** — Data hasil seed: 2 BU, 12 lokasi, 49 area, 147 aktivitas, 100 cost center,
  167 cost element, dan **16.700 COA**. Semua COA terhubung ke cost center + cost element;
  semua cost element punya default COA.
- **F4** — 9 endpoint JSON (bundle + 7 filter parent + reverse lookup) di bawah
  `permission:erkap.menu`. Helper JS `public/js/erkap-cascade.js` menangani
  cascading, `select2('destroy')` → re-init, pembersihan child, preview kode, dan
  prefill reverse lookup. Scoping divisi via `ErkapAccess` sudah diterapkan di
  semua endpoint opsi pada F10 (lihat 8.1 F10).
- **F9** — Permission master (`business-units`, `locations`, `management-areas`,
  `activities`: view/create/edit/delete) + `erkap.structure.view` / `erkap.structure.edit`.
  Hanya `erkap-admin` boleh menulis; `erkap-auditor` mendapat seluruh 45 view permission.
  Sidebar "Struktur Pusat Biaya" diletakkan **di luar** gate `erkap.rkap.view` agar
  admin/auditor tetap bisa menjelajahinya.
- **F5** — CRUD Pusat Biaya memakai dropdown cascading penuh a → b → c → d
  (`resources/views/erkap/cost-center/_form.blade.php` bersama untuk create & edit).
  Tiga keputusan integrity yang diterapkan:
  1. `code` **tidak pernah** diambil dari client. `ComposesCostCenterCode::prepareForValidation()`
     menyusun ulang kode dari master sebelum validasi, sehingga aturan `unique` dan
     `CostCenterCodeFormat` memeriksa kode kanonik.
  2. Segmen a, b, c diwarisi dari Aktivitas (segmen d) lewat
     `CostCenter::syncInheritedSegments()`, mengikuti pola `Activity` dan `ManagementArea`.
     Kombinasi lintas segmen yang tidak sah (mis. Lokasi dari unit lain) menjadi
     mustahil tersimpan.
  3. `is_swakelola` diturunkan dari Aktivitas; checkbox manual dihapus dari form.
     `is_centralized` + `coordinating_division_id` tetap manual (fitur terpusat).
  Hapus `code` dicegah selama masih ada COA atau Biaya Rutin yang memakainya, agar
  `ON DELETE RESTRICT` tidak memunculkan `QueryException` mentah.
  Test: `CostCenterCompositionTest` (20 test) + `ErkapCascadeSegmentLengthTest` (4 test).
- **F6** — COA kini benar-benar merupakan hasil komposisi, bukan baris dengan kode padding.
  1. `StoreChartOfAccountRequest::prepareForValidation()` menyusun ulang `code` dari
     Pusat Biaya + Elemen Biaya; `code` dari client diabaikan, dan `name`/`type`
     diisi default bila form tidak mengirimkannya (`name` = elemen + pusat biaya,
     `type` = tipe COA tertua yang memakai elemen itu, default `expense`).
  2. `sync()` tidak lagi menyamakan kode, melainkan **membuat** baris untuk setiap kombinasi
     `(cost_center, cost_element)` yang belum ada, lalu menautkan COA default ke
     `erkap_cost_elements.chart_of_account_id` yang masih kosong. Idempoten: dijalankan
     dua kali tidak menambah baris.
  3. Form create memakai cascade Pusat Biaya → Elemen Biaya tanpa input kode manual.
     `ErkapCascade` gaining dua kemampuan baru: preview kode bisa disusun dari kode
     Pusat Biaya terpilih (bukan hanya dari 4 select segmen), dan `config.parentQuery`
     mengizinkan override filter parent agar seluruh Elemen Biaya ditawarkan —
     bukan hanya yang sudah punya COA pada Pusat Biaya tersebut.
  4. Halaman show menampilkan dekomposisi a..e beserta referensinya; halaman index
     menampilkan Pusat Biaya, Elemen Biaya, dan progress bar cakupan kombinasi.
  Tidak ada route edit/update/delete COA: scope F6 hanya create + sync, dan
  `edit`/`delete` pada tabel COA berisiko merusak relasi yang dipakai form transaksi
  (F7) serta `chart_of_account_id` pada elemen biaya.
  Test: `ChartOfAccountCompositionTest` (21 test).
- **F7** — Form transaksi memakai cascading generik. Biaya Rutin menurunkan COA
  server-side dari pasangan `(cost_center, cost_element)`; client tidak lagi
  mengirim id COA jadi, dan pasangan tanpa COA ditolak.
- **F8** — Konsumen laporan/dashboard diaudit terhadap relasi hasil komposisi.
  1. `BudgetOpexConsolidationService` **tidak lagi** memakai
     `CostElement::chart_of_account_id` / `coaSuggestion()` sebagai fallback.
     Kedua-duanya menunjuk satu COA sembarang dari elemen, sehingga
     `erkap_budget_opex` bisa menyimpan COA milik Pusat Biaya lain. Sekarang
     COA di-resolve dari peta pasangan `(cost_center_id, cost_element_id)`,
     di-query per chunk 100 Pusat Biaya agar tidak N+1. Baris tanpa Pusat Biaya
     atau tanpa pasangan COA dilewati sebagai gap sinkronisasi, bukan diganti
     diam-diam dengan COA milik Pusat Biaya lain. `chart_of_account_id` yang
     tersimpan eksplisit tetap dihormati.
  2. Accessor `label` ditambahkan pada `ChartOfAccount`, `CostCenter`, dan
     `CostElement` (`kode tersegmentasi - nama`), lalu dipakai di export Biaya
     Rutin & Rencana Investasi, PDF Biaya Rutin, heatmap pusat biaya, top
     variance, dan drilldown. Semua pembacaan `->chartOfAccount` /
     `->costElement` di jalur laporan dibuat null-safe karena keduanya nullable
     sejak F7 (mis. rencana investasi tingkat perusahaan).
  3. `DrilldownService::pnlDetail()` menormalkan baris RKAO dan RKAP menjadi
     satu skema kolom. Sebelumnya `RevenuePlan` (tanpa `prior_year_amount`)
     digabung baris mentah bersama `ExpensePlan`, sehingga heading export P&L
     bergeser dan label jenis ambigu. Baris sekarang seragam berbentuk
     `jenis, divisi, chart_of_account, keterangan, total` dengan `jenis` =
     Pendapatan/Beban.
  4. `RoutineCostController::index` & `InvestmentPlanController` melakukan eager
     loading relasi label agar tidak lazy-load per baris.
  Test: `BudgetOpexConsolidationCompositionTest` (9 test) +
  `ReportingCoaCompositionTest` (11 test) + `ErkapRkapSimulasiSeederTest` (3 test).
  `ZBBReviewService`, `CentralizedCostService`, `BudgetConsolidationExport`, dan
  `ConsolidatedRkapExport` diaudit dan tidak butuh perubahan inti (relasi FK).
- **F10** — Test & acceptance. Total suite **415 test hijau** (`php artisan test`).
  Audit keamanan dan acceptance yang dikerjakan:
  1. **Scoping divisi endpoint opsi F4** — `CoaOptionService::managementAreas()`
     kini melewati `ErkapAccess::scopeDivision()`, dan `activities()` difilter
     lewat `whereHas('managementArea')` mengikuti divisi pemilik. `lookup()` juga
     dibatasi: kode Pusat Biaya/COA milik divisi lain dijawab sebagai
     `found: false`, **bukan** 403, supaya perbedaan respons tidak ikut
     membocorkan keberadaan data divisi lain. Test: `CoaOptionDivisionScopeTest` (11 test).
  2. **`/portal` anonim** — diaudit, **tidak diubah**. `PortalController` memang
     meng-exclude `index` dari middleware `auth` (landing page), tetapi seluruh
     menu di `resources/views/portal.blade.php` dibungkus `@can($menu['permission'])`
     dan nama user memakai `auth()->user()?->name`, jadi tidak ada data yang bocor
     ke pengunjung anonim.
  3. **Fallback role Microsoft SSO** — `MicrosoftAuthController` tidak lagi
     memberi role `staff` tanpa syarat. `staff` memegang
     `ams.settings.reset-password` (reset password seluruh pengguna), hapus tiket
     helpdesk, dan akses berkas dokter — semuanya di luar lingkup SSO. Role
     default kini kosong dan hanya diisi bila operator menetapkannya lewat
     `MICROSOFT_DEFAULT_ROLE`; `MICROSOFT_AUTO_PROVISION=false` menolak akun baru
     sama sekali. Blok `services.microsoft` juga ditambahkan karena driver
     Socialite `azure` membacanya dan blok itu belum pernah ada.
     **Catatan:** `laravel/socialite` belum ada di `composer.json`, jadi callback
     SSO tidak dapat dijalankan — kebijakan role diuji lewat `ensureRole()`.
     Test: `MicrosoftSsoRoleFallbackTest` (5 test).
  4. **Matriks peran** — `erkap-cost-owner` memang tidak punya
     `erkap.cost-centers.view` (maupun elemen/COA/structure). Karena form Biaya
     Rutin menunjuk ketiganya, role tersebut kini diberi izin baca saja:
     `erkap.structure.view`, `erkap.cost-centers.view`, `erkap.cost-elements.view`,
     `erkap.chart-of-accounts.view`. Quartet create/edit/delete tetap ditolak.
     Test: `ErkapRolePermissionMatrixTest` (7 test) — matriks diuji lewat
     `RolePermissionSeeder` sungguhan, dan ekspektasi approval/gate diturunkan
     dari `ApprovalService::getApprovalMatrix()` serta
     `InvestmentGateReviewService::STAGES` agar tidak menyimpang dari implementasi.
  5. **Acceptance lintas role (input → approval → output)** —
     `ErkapCrossRoleAcceptanceTest` (8 test) menjalankan satu dokumen Biaya Rutin
     melalui cost owner → PPK → controller → accounting → laporan OPEX, memakai
     role & permission hasil seeder sungguhan. Alur ini menemukan dua cacat yang
     kemudian diperbaiki:
     - `RoutineCostController::update()` dan `destroy()` **tidak** mengunci dokumen
       yang sudah `submitted`/`approved`, sehingga pengaju bisa mengubah angka
       setelah disetujui PPK/Controller dan Laporan Laba Rugi jadi berbeda dari
yang ditandatangani. `ErkapEvaluationLock::assertRoutineCostEditable()`
        ditambahkan, mengikuti pola `assertRiskEditable()` yang sudah ada.
     - `erkap-approver` tidak masuk rantai approval RKAP (rantainya
       komisaris → direksi); ekspektasi test pertama sempat salah dan diperbaiki.
  6. **Multi-approver** — **tetap di luar scope.** `ApprovalService` memilih satu
     approver per level (`getApproverByRole()->first()`). Lampiran A menandainya
     sebagai butir terpisah yang memerlukan POK/user decision tersendiri, sehingga
     tidak diubah pada F10. Rantai yang berjalan saat ini sudah 2–3 level dan
     tiap level bergilir.

### 8.2 Bug yang ditemukan & diperbaiki saat F4/F5/F6

| Bug | Dampak | Perbaikan |
|---|---|---|
| Panjang segmen `management_area` di `erkap-cascade.js` hardcoded `3` (seharusnya `5`) | Preview kode tidak pernah menampilkan kode, karena panjang total 9 ≠ 11 | Nilai diambil dari konstanta; `ErkapCascadeSegmentLengthTest` mengunci kesesuaian JS↔PHP |
| `SEGMENT_LENGTH` di JS bisa melenceng dari `CoaCode::SEGMENT_LENGTHS` | Preview diam-diam salah saat K-1 berubah | `ErkapCascadeSegmentLengthTest` gagal bila konstanta berbeda |
| `Rule::unique('cost_centers','code', $this->route('costCenter'))` menerima objek model | Aturan unique membandingkan id dengan objek, bukan skalar | `->ignoreModel($this->route('costCenter'))` |
| Segmen a..c diterima apa adanya dari client | Rantai segmentasi bisa tidak konsisten dengan activity | `CostCenter::syncInheritedSegments()` |
| Menu "Struktur Pusat Biaya" ter-*nest* di dalam `@can('erkap.rkap.view')` | Admin & auditor tidak pernah melihat menu struktur | Dipindahkan keluar gate tersebut |
| `CoaOptionService::costElements($costCenterId)` hanya menampilkan elemen yang sudah punya COA | Form create COA tidak punya opsi sama sekali untuk kombinasi baru | `parentQuery` override di JS; filter hanya dipakai form transaksi yang butuh COA valid |
| `typeForElement()` dipanggil per baris saat `sync()` | 16.700 query untuk 16.700 baris | Tipe di-resolve sekali per elemen (`elementTypes()`) |
| `chart_of_accounts` punya unique partial pada `(cost_center_id, cost_element_id)` | `sync()` aman dari duplikat walau dijalankan bersamaan | Set `existingCombinations()` + insert per 500 baris dalam satu transaksi |
| `erkap_cost_elements` **tidak punya** kolom `is_active` (begitu juga `cost_centers`) | `where('is_active', true)` membuat 500 error di form & `sync()` | Dihapus; hanya master a–d yang punya flag tersebut |
| Halaman show COA menautkan `erkap.cost-centers.show` | Route tersebut tidak pernah ada (F5 hanya membuat index/create/store/update/destroy) | Tautan dihapus, info Pusat Biaya ditampilkan sebagai teks |
| `CoaOptionApiTest::test_business_units_are_ordered_by_sort_order_then_code` hard-code kode `Z` | Flaky ±1/36: `CostCenter::factory()` membuat unit acak yang bisa dapat `Z` lebih dulu | Kode dibaca dari model yang dibuat factory, tidak diketik manual |
| `BusinessUnit::factory()->count(2)->create()` risked bentrok kode | Kedua instance bisa dibangun sebelum salah satu ter-insert, sehingga `unusedCode()` tidak melihatnya | Dibuat satu per satu pada test yang butuh lebih dari satu unit |
| `BudgetOpexConsolidationService` fallback ke `CostElement::chart_of_account_id` lalu `coaSuggestion()` | `erkap_budget_opex` bisa menyimpan COA milik Pusat Biaya lain; indikator anggaran & P&L memakai kode yang tidak sesuai | COA di-resolve dari peta pasangan `(cost_center_id, cost_element_id)`; baris tanpa pasangan dilewati |
| `chart_of_account_id` & `cost_center_id` nullable sejak F7, tapi export/dashboard masih dereference `->chartOfAccount` | Route export/dashboard bisa 500 pada rencana investasi tingkat perusahaan | Semua pembacaan di jalur laporan dibuat null-safe dengan fallback `-` |
| `DrilldownService::pnlDetail()` menggabungkan baris mentah `RevenuePlan` + `ExpensePlan` | Jumlah kolom berbeda (`prior_year_amount` hanya di RKAO) → heading export P&L bergeser | Baris dinormalkan ke satu skema kolom + `jenis` (Pendapatan/Beban) |
| `chart_of_accounts.code` & `cost_centers.code` polos masih dipakai di laporan | Kode 15/11 karakter tampil tanpa segmentasi, menyulitkan pembacaan | Accessor `label` dipakai konsisten di export, PDF, dashboard, drilldown |

---

## 9. Test Plan

**Unit**
- `CoaCodeCompositionTest`: builder kode `a-b-c-d-e`; valid & tolak length; format.
- `CostCenterSegmentsTest`: relasi; derivasi `is_swakelola` dari aktivitas; `code` komposisi.
- `ChartOfAccountCompositionTest`: COA = CC + elemen; unique constraint.
- `CoaOptionServiceTest`: resolve chain & reverse lookup.

**Feature (sudah ada)**
- `CoaOptionApiTest` (F4) — 17 test: chain filter, reverse lookup, permission `erkap.menu`, validasi query. ✅
- `StructureMasterCrudTest` (F9) — 28 test: CRUD 4 master, unique compound, parent exists, guard delete, matriks permission admin write-only & auditor view-only. ✅
- `StructureMasterPageTest` (F9) — 15 test: 12 halaman master render, embed endpoint cascade, preview kode komposisi, sidebar. ✅
- `CostCenterCompositionTest` (F5) — 20 test: komposisi kode 11 karakter, `code` dari client diabaikan, warisan segmen a..c dari aktivitas, duplikat ditolak, guard hapus (COA & Biaya Rutin), halaman create/edit/index, permission. ✅
- `ErkapCascadeSegmentLengthTest` (F4/F5/F6) — 4 test: konstanta panjang segmen JS harus sama dengan `CoaCode::SEGMENT_LENGTHS`, termasuk `COST_ELEMENT_LENGTH`; tidak boleh ada angka polos di JS. ✅
- `ChartOfAccountCompositionTest` (F6) — 21 test: komposisi kode 15 karakter dari CC+elemen, `code` dari client diabaikan, duplikat kombinasi ditolak, elemen sama boleh dipakai di pusat biaya berbeda, default `name`/`type`, `sync()` melengkapi semua kombinasi secara idempoten, tautan COA default elemen, cakupan pada flash message, halaman create/index/show, permission. ✅
- `BudgetOpexConsolidationCompositionTest` (F8) — 9 test: COA diturunkan dari pasangan Pusat Biaya + Elemen Biaya (bukan tautan default elemen yang bisa milik pusat biaya lain), `chart_of_account_id` eksplisit dihormati, baris tanpa pasangan COA atau tanpa Pusat Biaya dilewati, elemen berbeda pada pusat biaya sama dipisah, idempoten saat konsolidasi ulang, laporan mengelompokkan per divisi dan membawa `label` komposisi. ✅
- `ReportingCoaCompositionTest` (F8) — 11 test: export Biaya Rutin & Rencana Investasi menampilkan kode tersegmentasi dan tahan baris tanpa COA/Pusat Biaya, PDF Biaya Rutin tidak gagal pada data kosong, route export Biaya Rutin, drilldown Biaya Rutin menampilkan label komposisi dan null-safe, drilldown P&L menormalkan baris RKAO/RKAP ke satu skema kolom, heatmap & top variance memakai label. ✅
- `ErkapRkapSimulasiSeederTest` (F8) — 3 test: seeder end-to-end memakai Pusat Biaya ber Foreign Key valid + COA hasil komposisi, bukan Pusat Biaya bebas. ✅

**Regresi**
- Jalankan seluruh suite (`php artisan test`) — **status terkini: 384 passed** (172,9 detik).
- Fokus: `RoutineCost*`, `InvestmentPlan*`, `BudgetCapex*`, `ProfitLoss*`,
  `Revenue/ExpensePlan*`, dashboard drilldown/export.

**Manual / E2E (F10)**
- [x] Uji allow/deny tiap role ERKAP terhadap halaman master & form transaksi.
  Dijalankan otomatis lewat seeder sungguhan di `ErkapRolePermissionMatrixTest`
  (matriks izin) dan `ErkapCrossRoleAcceptanceTest` (alur utuh), bukan manual.
- [x] Jalankan skenario approval lintas role hingga output.
  `ErkapCrossRoleAcceptanceTest::test_the_approved_amount_reaches_the_opex_report_with_its_composed_code`.
- [ ] Uji cascade di browser pada seluruh form: select2 re-init, preview kode, dan
  reverse-fill pada halaman edit. **Sisa satu-satunya yang belum terotomasi** —
  memerlukan browser sungguhan; logika segmentasi dan `ErkapCascade.init` sudah
  tercakup `CoaOptionApiTest` (17 test) dan `TransactionCoaIntegrityTest` (29 test).

---

## 10. Kriteria Penerimaan

- [x] Master BU, Lokasi, Manajemen Area, Aktivitas ada & terintegrasi di sidebar ERKAP dengan permission. *(F9)*
- [x] Cost center tersimpan dengan FK segmen; `code` otomatis = a–d (11 karakter); kombinasi unik; tidak ada string-slicing. *(F5)*
- [x] COA tersimpan dengan `cost_center_id + cost_element_id`; `code` = a–e (**15 karakter**). *(F2/F3, 16.700 baris tervalidasi)*
- [x] API cascading + reverse lookup tersedia untuk seluruh rantai parent→child. *(F4, 17 test)*
- [x] Form Pusat Biaya memakai cascading penuh a→b→c→d, tanpa input kode manual. *(F5, 20 test)*
- [x] Form COA memakai cascade Pusat Biaya→Elemen Biaya, tanpa input kode manual; `name`/`type` punya default. *(F6, 21 test)*
- [x] Dropdown cascading berfungsi di form **transaksi** (routine-cost, investment-plan, revenue/expense-plan). *(F7, 29 test)*
  - Biaya Rutin: Pusat Biaya → Elemen Biaya, COA diturunkan server-side dari pasangan dan tidak lagi dikirim client. Pasangan tanpa COA ditolak.
  - Rencana Pendapatan/Beban: Divisi → COA (`ChartOfAccountBelongsToDivision` + filter `type`), tanpa migrasi `cost_center_id`.
  - Rencana Investasi: Pusat Biaya → COA expense, keduanya opsional berpasangan (`required_with` + `ChartOfAccountMatches`).
  - Scoping divisi dipaksa di lapisan query (`ErkapAccess::scopeDivision`), bukan hanya mempercayai parameter `division_id` dari client.
- [x] `ChartOfAccountController::sync` menemukan/membuat COA dari kombinasi `(cost_center, cost_element)`, bukan kode-pad. *(F6, idempoten; 2.000 baris Gap terisi dalam 2,18 detik, 16.700 kombinasi utuh)*
- [x] Konsolidasi OPEX (`BudgetOpexConsolidationService`) menurunkan COA dari pasangan
  `(cost_center, cost_element)`, bukan tautan default elemen; ZBB, dashboard, dan
  export menampilkan kode tersegmentasi & null-safe. *(F8, 9 + 11 + 3 test)*
- [x] Seeder & simulasi RKAP berjalan tanpa error; seluruh test hijau (**415 passed**).
- [x] Audit keamanan: scoping divisi `ErkapAccess` pada endpoint opsi F4, `/portal` anonim, fallback role `staff` di Microsoft SSO, dan multi-approver. *(F10 — 3 celah diperbaiki, `/portal` aman, multi-approver diputuskan keluar scope)*

  | Item | Hasil |
  |---|---|
  | Scoping divisi endpoint opsi F4 | **Celah diperbaiki** — `management-areas`, `activities`, dan `lookup` kini dibatasi `ErkapAccess`. `CoaOptionDivisionScopeTest` (11 test). |
  | `/portal` anonim | **Aman, tidak diubah** — halaman memang publik, tetapi menu di-gate `@can` dan user di-null-safe. |
  | Fallback role `staff` di Microsoft SSO | **Celah diperbaiki** — role default kini kosong & hanya lewat `MICROSOFT_DEFAULT_ROLE`; `staff` memberi `ams.settings.reset-password` dll. `MicrosoftSsoRoleFallbackTest` (5 test). |
  | Multi-approver | **Di luar scope** — memerlukan POK/user decision tersendiri (Lampiran A). Rantai 2–3 level yang berjalan sudah bergilir per level. |

- [x] Review matriks peran: `erkap-cost-owner` **tidak** punya
  `erkap.cost-centers.view`, padahal role itu menginput Biaya Rutin yang
  menunjuk Pusat Biaya. *(F10 — diberi izin baca saja: `structure.view`,
  `cost-centers.view`, `cost-elements.view`, `chart-of-accounts.view`; quartet
  create/edit/delete tetap ditolak. `ErkapRolePermissionMatrixTest`, 7 test.)*
- [x] Acceptance end-to-end lintas role (input → approval → output). *(F10 —
  `ErkapCrossRoleAcceptanceTest`, 8 test: cost owner → PPK → controller →
  accounting → laporan OPEX. Menemukan 2 cacat yang diperbaiki: `update()`/
  `destroy()` Biaya Rutin tidak mengunci dokumen yang sudah `submitted`/
  `approved`, dan ekspektasi rantai approval RKAP yang keliru di test.)*

---

## 11. Risiko & Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| ~~K-1 (16 vs 15 digit) salah asumsi~~ | ~~Ulang seluruh validasi & kolom~~ | ✅ **Selesai** — dikunci 15 karakter; panjang via konstanta `CoaCode::LENGTH` |
| ~~Backfill salah memetakan segmen legacy~~ | ~~Data cost center/COA salah~~ | ✅ **Dihindari** — reset & seed ulang, tanpa `legacy_code` |
| `ErkapAccess` scoping divisi bergeser (CC tidak lagi berbasis division langsung) | Departemen memakai data divisi lain | Bridge `management_area.division_id` + pemetaan ulang `RoutineCostController::assertCentralizedCostInput` & `CentralizedCostService` |
| Banyak konsumen (export/service/dashboard) tidak di-update | Laporan rusak | Daftar impact §6 lengkap; test regresi §9 |
| Select2 tidak ter-reinit setelah option dinamis | Dropdown kosong/tidak interaktif | Helper `ErkapCascade` memanggil `.select2('destroy')` → re-init; uji di semua form |
| Seeder urutan salah (COA sebelum cost center) | FK gagal | Sesuaikan `DatabaseSeeder` urutan: BU→Lokasi→Area→Aktivitas→CostCenter→Element→COA |

---

## Lampiran A — "Risk Manager lebih dari satu orang" (opsional, di luar scope utama)

Jika ingin mendukung **banyak risk manager / banyak approver per level**, desain target:

1. **Approval banyak orang per level (OR / siapa pun bisa approve):**
   - `getApproverByRole()` (`ApprovalService.php:302`) → `->get()` (kumpulan user), simpan **satu baris approval per user** `erkap_approvals` per level, `requireTurn()` (`:280-300`) mengubah pengecekan `approver_id` → `in` daftar approver level tsb.
   - UI: `ApprovalController.php` (baris 21-23, 180-188) filter `approver_id = auth()->id()` → filter `role = current user role`.
2. **Risk owner = relasi user (banyak):**
   - Ganti `risk_owner varchar` dengan `erkap_risk_owners` (pivot `risk_assessment_monthly_id`, `user_id`) atau `jsonb risk_owners`.
   - Update `Store/UpdateRiskAssessmentMonthlyRequest.php:30`, view `create/edit` (121-123) → dropdown multi-user (Select2 multiple) via `options/employees` (pattern `select.employee` di `web.php:198`).

Keduanya **perlu POK user terpisah** — tidak dimasukkan ke fase implementasi COA agar tidak menggabungkan 2 perubahan besar.

---

## Lampiran B — Varian Skema Generik (jika master segmen dinamis)

Bila segmen bisa bertambah/berkurang tanpa migration, gunakan 1 tabel:

```sql
CREATE TABLE erkap_code_segments (
  id          bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  level       smallint NOT NULL,        -- 1=BU 2=Lokasi 3=Area 4=Aktivitas
  code        varchar(5) NOT NULL,
  name        varchar(150) NOT NULL,
  parent_id   bigint NULL REFERENCES erkap_code_segments(id),
  sort_order  integer NOT NULL DEFAULT 0,
  is_active   boolean NOT NULL DEFAULT true,
  created_at timestamptz, updated_at timestamptz,
  UNIQUE (level, code, parent_id)
);
```

Dipakai bila kebutuhan "4 segmen" berubah terus. Trade-off: query & validasi lebih kompleks (rekursif/CTE). Rekomendasi awal: **FK eksplisit (skema §4.1)** — lebih mudah dibaca, dan cukup fleksibel karena tiap tabel sudah punya `is_active`, `sort_order`, dan relasi parent.

---

_End of document._