# Planning G10 — Biaya Tersentralisasi (Coordinating Owner: Gaji→HR, IT→Dept IT)

> **Gap:** G10 (Parsial) — Bagian 3
> **Prioritas:** P2 — Perbaikan Kualitas
> **Status:** Parsial ⚠️
> **Referensi:** `agents/recap-pedoman-rkap/hasil-analisis-gap.md` baris 35, 68, 138–139, 176–177.

---

## 1. Latar Belakang

Pedoman mewajibkan **biaya tersentralisasi**: biaya tertentu (mis. gaji → HR, TI → Departemen IT)
**dikoordinir oleh satu departemen** sebagai pemilik, meski dinikmati banyak divisi. Fakta saat ini:

- `cost_centers` hanya punya flag `is_swakelola` (migration `2026_09_18_000001_add_is_swakelola...`)
  dan helper `CostCenter::isSwakelola()` (segment kode `'510'`) —
  `app/Models/Erkap/CostCenter.php:39-42`.
- Tidak ada alur "dikoordinir oleh departemen X", tidak ada ownership eksplisit untuk
  gaji (HR) / IT.
- `cost_centers.owner` (string) & `division_id` ada namun tidak dipakai untuk membatasi
  multi-departemen.

---

## 2. Tujuan

1. Memiliki mekanisme **centralized cost** pada Cost Center: penanda `is_centralized` + **divisi/owner kordinasi**.
2. Jika suatu cost element (biaya) terpusat, hanya **departemen koordinator** yang dapat menginput
  anggarannya di module OPEX (pembatasan multi-departemen).
3. Mapping siap pakai: Gaji → HR, TI/IT → Departemen IT (via seeder/konfigurasi cost element).
4. Tampilan pelaporan mengelompokkan biaya terpusat agar tidak dobel antar divisi.

---

## 3. Desain Solusi

### 3.1 Migration

Migration `2026_09_26_000010_add_centralized_columns_to_cost_centers_table.php`:

| Kolom | Tipe | Keterangan |
|---|---|---|
| `is_centralized` | boolean default false | biaya terpusat |
| `coordinating_division_id` | FK `divisions` nullable | departemen koordinator |

(Juga pertimbangkan `cost_element_level`/`cost_center` branding: `erkap_cost_elements` boleh diberi
`is_centralized` agar penegakan di level elemen biaya. Default: penegakan di level cost center +
konsistensi ke cost element.)

### 3.2 Model

`App\Models\Erkap\CostCenter`:
- `$fillable` + `is_centralized`,
  `coordinating_division_id`;
- `casts` (`is_centralized => boolean`);
- relasi `coordinatingDivision()` → `belongsTo(Division::class, 'coordinating_division_id')`;
- helper `isCentralized(): bool` → `$this->is_centralized ?? false`.

`App\Models\Erkap\CostElement` (opsional):
- `is_centralized` + `coordinator_division_id` bila penegakan di elemen biaya dipilih.

### 3.3 Service & Penegakan (Enforcement)

Baru `App\Services\Erkap\CentralizedCostService`:

```php
public static function allowedToUse(CostCenter $cc, ?int $divisionId): bool
// cost center non-centralized -> boleh semua divisi
// centralized -> hanya jika $divisionId == $cc->coordinating_division_id

public static function assertCanInput(CostCenter $cc, ?int $divisionId): void
// throw ValidationException bila tidak diizinkan

public static function mapDefaultCoordinator(): array
// ['gaji' => division HR id, 'it' => division IT id] — dari seeder konfigurasi

public static function groupCentralized(Collection $rows): array
// pisahkan row terpusat ke grup 'Terpusat (koordinator)' untuk laporan
```

### 3.4 Routing Integrasi

- `App\Http\Controllers\Erkap\CostCenterController::store/update`:
  validasi `is_centralized` + `coordinating_division_id` (**wajib** bila `is_centralized=true`).
- `App\Http\Requests\StoreCostCenterRequest`:
  ```php
  'is_centralized' => ['boolean'],
  'coordinating_division_id' => [
      'nullable', 'exists:divisions,id',
      'required_if:is_centralized,1',
  ],
  ```
- `App\Http\Controllers\Erkap\RoutineCostController::store/update`:
  sebelum simpan, `CentralizedCostService::assertCanInput($costCenter, divisionIdOfWorkProgram)`.
- Ekspor budget & dashboard: gunakan `groupCentralized()` saat baris rincian OPEX; menandai
  "Cost Center Terpusat" (badge).

### 3.5 Seeder Konfigurasi

- Update `database/seeders/CostCentersSeeder.php`: flags contoh (`is_centralized=true`,
  `coordinating_division_id` = divisi HR/IT) untuk cost center "Gaji (HR)" dan "TI (IT)".
- Cost element mapping: peta kode elemen `gaji|salary|it` ke koordinator (bisa table konfig baru
  `erkap_centralized_cost_mappings` bila perlu generalisasi).

### 3.6 Form & View

- `resources/views/erkap/cost-center/form.blade.php` (atau create/edit): tambah checkbox
  "Biaya Tersentralisasi" + dropdown "Departemen Koordinator" (dependent → tampil bila dicentang).
- `resources/views/erkap/routine-cost/...`: badge "Terpusat: <Divisi Koordinator>" pada baris.
- Dashboard `DashboardController::budgetData` / OPEX export: kelompokkan baris terpusat.

### 3.7 Tests

- Unit: `CentralizedCostServiceTest` — izin divisi, tolak divisi non-koordinator, mapping default.
- Feature: `CentralizedCostFeatureTest` — create cost center terpusat; input routine cost oleh divisi
  lain ditolak; grouping laporan.

---

## 4. Urutan Implementasi

1. Migration kolom.
2. Model + casts/relasi/helper.
3. Service `CentralizedCostService`.
4. Request & controller CostCenter validation.
5. Enforcement di RoutineCost store/update.
6. Seeder + mapping default.
7. Views & reporting (grouping).
8. Tests.

---

## 5. Kriteria Penerimaan

- [ ] Cost center dapat ditandai terpusat + memiliki koordinator wajib.
- [ ] Routine cost di cost center terpusat hanya bisa dibuat oleh divisi koordinator.
- [ ] Laporan/budget mengelompokkan biaya terpusat (tanpa duplikasi di divisi lain).
- [ ] Seeder menyediakan mapping gaji→HR, IT→Dept IT.
- [ ] Semua test terkait lulus.

---

## 6. Risiko & Dependensi

- **Risiko:** penentuan "divisi" pemilik input dari `WorkProgram → DepartmentTarget.division_id`
  (jalur `divisionIdOf` pada `BudgetRealizationController::223`) — pastikan konsisten di
  `RoutineCostController`.
- **Risiko:** usia data — cost center lama tanpa `coordinating_division_id` namun `is_centralized`
  harus dimigrasikan/diwajibkan; buat migration data untuk sinkronisasi flag swakelola→terpusat bila perlu.
- **Dependensi:** dashboard/export memakai `erkap_budget_opex` — pengelompokan terpusat ideal di
  layer konsolidasi agar seragam (lihat catatan kualitas #2 single source).
- **Keputusan bisnis:** definisi segmen kode centralized (selain `510`) perlu konfirmasi Finance.