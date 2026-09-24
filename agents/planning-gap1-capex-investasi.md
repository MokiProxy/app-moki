# Planning Implementasi: CAPEX/Investasi (FORM 4 & 5)

## 1. Deskripsi Gap

**Status Saat Ini**: Hanya ada 3 tabel master data investasi (`erkap_investation_types`, `erkap_investation_criterias`, `erkap_investattion_categories`) yang tidak direferensikan oleh entitas planning manapun.

**Yang Diperlukan**: 
- Tabel transaksi investasi (barang, qty, harga satuan, jadwal pembayaran Jan-Des, total)
- Controller/route/view untuk input biaya investasi
- Tabel ringkasan "Anggaran Investasi" (FORM 5)
- Relasi ke Work Program atau RKAP

**Dampak**: Jalur CAPEX pada Flow 3.1-3.3, modul 2.5, dan dashboard budget (OPEX vs CAPEX) tidak dapat berfungsi.

## 2. Solusi yang Direkomendasikan

### 2.1 Tabel Baru: `erkap_investment_plans` (Transaksi Investasi)

```sql
CREATE TABLE erkap_investment_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    erkap_work_program_id BIGINT UNSIGNED NOT NULL,
    erkap_investattion_category_id BIGINT UNSIGNED NOT NULL,
    erkap_investation_type_id BIGINT UNSIGNED NOT NULL,
    erkap_investation_criteria_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    unit VARCHAR(50) NOT NULL,
    qty DECIMAL(15,2) NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    total DECIMAL(15,2) NOT NULL,
    jan_plan DECIMAL(15,2) DEFAULT 0,
    feb_plan DECIMAL(15,2) DEFAULT 0,
    mar_plan DECIMAL(15,2) DEFAULT 0,
    apr_plan DECIMAL(15,2) DEFAULT 0,
    may_plan DECIMAL(15,2) DEFAULT 0,
    jun_plan DECIMAL(15,2) DEFAULT 0,
    jul_plan DECIMAL(15,2) DEFAULT 0,
    aug_plan DECIMAL(15,2) DEFAULT 0,
    sep_plan DECIMAL(15,2) DEFAULT 0,
    oct_plan DECIMAL(15,2) DEFAULT 0,
    nov_plan DECIMAL(15,2) DEFAULT 0,
    dec_plan DECIMAL(15,2) DEFAULT 0,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (erkap_work_program_id) REFERENCES erkap_work_programs(id),
    FOREIGN KEY (erkap_investattion_category_id) REFERENCES erkap_investattion_categories(id),
    FOREIGN KEY (erkap_investation_type_id) REFERENCES erkap_investation_types(id),
    FOREIGN KEY (erkap_investation_criteria_id) REFERENCES erkap_investation_criterias(id)
);
```

### 2.2 Tabel Baru: `erkap_budget_capex` (Ringkasan Anggaran Investasi per SK)

```sql
CREATE TABLE erkap_budget_capex (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    erkap_rkap_id BIGINT UNSIGNED NOT NULL,
    division_id BIGINT UNSIGNED NOT NULL,
    total_investment DECIMAL(15,2) DEFAULT 0,
    status ENUM('draft', 'submitted', 'approved', 'rejected') DEFAULT 'draft',
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (erkap_rkap_id) REFERENCES erkap_rkap(id),
    FOREIGN KEY (division_id) REFERENCES divisions(id)
);
```

## 3. Langkah-langkah Implementasi

### Phase 1: Model & Migration (1-2 hari)

1. **Buat Migration `2026_09_11_000002_create_investment_plans_table.php`**
2. **Buat Migration `2026_09_11_000003_create_budget_capex_table.php`**
3. **Buat Model `InvestmentPlan.php`**
4. **Buat Model `BudgetCapex.php`**
5. **Update Model `WorkProgram.php`** - tambahkan relasi `investmentPlans()`
6. **Update Model `InvestattionCategory.php`** - tambahkan relasi `investmentPlans()`
7. **Update Model `InvestationType.php`** - tambahkan relasi `investmentPlans()`
8. **Update Model `InvestationCriteria.php`** - tambahkan relasi `investmentPlans()`

### Phase 2: Controller & Route (2-3 hari)

1. **Buat Controller `InvestmentPlanController.php`**
   - `index()` - daftar investasi per work program
   - `create()` - form input investasi
   - `store()` - simpan investasi + validasi
   - `edit()` - form edit
   - `update()` - update investasi
   - `destroy()` - hapus investasi
   - `calcTotal()` - hitung total = qty × unit_price
   - `calcMonthlyTotal()` - hitung total per bulan

2. **Buat Controller `BudgetCapexController.php`**
   - `index()` - daftar anggaran investasi per SK
   - `show()` - detail anggaran investasi
   - `update()` - update status (draft/submitted/approved/rejected)
   - `consolidate()` - konsolidasi dari semua investment plan

3. **Update Route `routes/routers/erkap.php`**
   - Tambah resource `investment-plans`
   - Tambah resource `budget-capex`
   - Tambah route `budget-capex.consolidate`

### Phase 3: Form Request & Validasi (1 hari)

1. **Buat `StoreInvestmentPlanRequest.php`**
   - Validasi: required fields, numeric qty/price, sum(bulan) = total
   - Validasi: total = qty × unit_price

2. **Buat `UpdateInvestmentPlanRequest.php`**
   - Validasi: sama dengan store

3. **Buat `UpdateBudgetCapexRequest.php`**
   - Validasi: status enum

### Phase 4: View/Blade (3-4 hari)

1. **Buat `resources/views/erkap/investment-plans/index.blade.php`**
2. **Buat `resources/views/erkap/investment-plans/create.blade.php`**
3. **Buat `resources/views/erkap/investment-plans/edit.blade.php`**
4. **Buat `resources/views/erkap/budget-capex/index.blade.php`**
5. **Buat `resources/views/erkap/budget-capex/show.blade.php`**
6. **Update `resources/views/erkap/work-programs/show.blade.php`**
   - Tampilkan daftar investasi terkait
   - Tombol "Tambah Investasi"

### Phase 5: Business Rules & Validasi (1-2 hari)

1. **Validasi Total Bulanan**
   ```php
   // Di InvestmentPlanController
   $totalMonthly = collect($request->only(['jan_plan', 'feb_plan', ...]))->sum();
   if (abs($totalMonthly - $request->total) > 0.01) {
       return back()->withErrors(['total' => 'Total harus sama dengan jumlah bulanan']);
   }
   ```

2. **Validasi Total = Qty × Harga**
   ```php
   $expectedTotal = $request->qty * $request->unit_price;
   if (abs($expectedTotal - $request->total) > 0.01) {
       return back()->withErrors(['total' => 'Total harus sama dengan qty × harga satuan']);
   }
   ```

3. **Relasi ke Work Program**
   - InvestmentPlan wajib terkait dengan WorkProgram
   - WorkProgram bisa memiliki banyak InvestmentPlan

### Phase 6: Konsolidasi & Agregasi (1-2 hari)

1. **Method `consolidate()` di BudgetCapexController**
   - Aggregate semua InvestmentPlan per WorkProgram
   - Group by WorkProgram → DepartmentTarget → Division
   - Hitung total per divisi

2. **Query Agregasi**
   ```php
   $budgetCapex = InvestmentPlan::select('division_id', DB::raw('SUM(total) as total_investment'))
       ->with('division')
       ->groupBy('division_id')
       ->get();
   ```

## 4. File yang Perlu Dibuat/Diubah

### File Baru:
- `database/migrations/2026_09_11_000002_create_investment_plans_table.php`
- `database/migrations/2026_09_11_000003_create_budget_capex_table.php`
- `app/Models/Erkap/InvestmentPlan.php`
- `app/Models/Erkap/BudgetCapex.php`
- `app/Http/Controllers/Erkap/InvestmentPlanController.php`
- `app/Http/Controllers/Erkap/BudgetCapexController.php`
- `app/Http/Requests/Erkap/StoreInvestmentPlanRequest.php`
- `app/Http/Requests/Erkap/UpdateInvestmentPlanRequest.php`
- `app/Http/Requests/Erkap/UpdateBudgetCapexRequest.php`
- `resources/views/erkap/investment-plans/index.blade.php`
- `resources/views/erkap/investment-plans/create.blade.php`
- `resources/views/erkap/investment-plans/edit.blade.php`
- `resources/views/erkap/budget-capex/index.blade.php`
- `resources/views/erkap/budget-capex/show.blade.php`

### File yang Diubah:
- `routes/routers/erkap.php` - tambah route
- `app/Models/Erkap/WorkProgram.php` - tambah relasi
- `app/Models/Erkap/InvestattionCategory.php` - tambah relasi
- `app/Models/Erkap/InvestationType.php` - tambah relasi
- `app/Models/Erkap/InvestationCriteria.php` - tambah relasi
- `resources/views/erkap/work-programs/show.blade.php` - tambah daftar investasi
- `app/Http/Controllers/Erkap/WorkProgramController.php` - tambah logic investasi
- `database/seeders/DatabaseSeeder.php` - tambah seeder test

## 5. Estimasi Effort

| Task | Effort (hari) |
|------|--------------|
| Model & Migration | 1-2 |
| Controller & Route | 2-3 |
| Form Request & Validasi | 1 |
| View/Blade | 3-4 |
| Business Rules & Validasi | 1-2 |
| Konsolidasi & Agregasi | 1-2 |
| Testing | 1-2 |
| **Total** | **10-16** |

## 6. Dependency

- Tabel `erkap_work_programs` sudah ada
- Master data investasi sudah ada (`erkap_investation_types`, `erkap_investation_criterias`, `erkap_investattion_categories`)
- Tabel `divisions` sudah ada

## 7. Risk & Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| Data master investasi tidak lengkap | Validasi FK gagal | Validasi seeder lengkap |
| Performa query konsolidasi | Lambat untuk banyak data | Gunakan eager loading & caching |
| Konflik dengan modul lain | Error | Gunakan prefix `erkap_` konsisten