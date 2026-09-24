# Planning Implementasi: Financial Projection & P&L

## 1. Deskripsi Gap

**Status Saat Ini**:
- Tidak ada tabel `revenue_plans`
- Tidak ada tabel `expense_plans`
- Tidak ada tabel `profit_loss_statements`
- Tidak ada kalkulasi laba rugi, margin, cash flow
- Tidak ada simulasi skenario (best/base/worst)

**Yang Diperlukan**:
- Tabel `revenue_plans` untuk pendapatan per CoA per bulan
- Tabel `expense_plans` untuk beban per CoA per bulan
- Tabel `profit_loss_statements` untuk laporan laba rugi
- Kalkulasi otomatis: Laba Rugi = Pendapatan - Beban
- Margin = Laba / Pendapatan × 100%
- Simulasi skenario

**Dampak**: Phase 4 rancangan (Financial Projection & P&L) tidak dapat dijalankan

## 2. Solusi yang Direkomendasikan

### 2.1 Tabel Baru: `revenue_plans`

```sql
CREATE TABLE erkap_revenue_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    erkap_rkap_id BIGINT UNSIGNED NOT NULL,
    division_id BIGINT UNSIGNED NOT NULL,
    chart_of_account_id BIGINT UNSIGNED NOT NULL,
    description TEXT NULL,
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
    total DECIMAL(15,2) NOT NULL,
    status ENUM('draft', 'submitted', 'approved', 'rejected') DEFAULT 'draft',
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (erkap_rkap_id) REFERENCES erkap_rkap(id),
    FOREIGN KEY (division_id) REFERENCES divisions(id),
    FOREIGN KEY (chart_of_account_id) REFERENCES chart_of_accounts(id)
);
```

### 2.2 Tabel Baru: `expense_plans`

```sql
CREATE TABLE erkap_expense_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    erkap_rkap_id BIGINT UNSIGNED NOT NULL,
    division_id BIGINT UNSIGNED NOT NULL,
    chart_of_account_id BIGINT UNSIGNED NOT NULL,
    description TEXT NULL,
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
    total DECIMAL(15,2) NOT NULL,
    status ENUM('draft', 'submitted', 'approved', 'rejected') DEFAULT 'draft',
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (erkap_rkap_id) REFERENCES erkap_rkap(id),
    FOREIGN KEY (division_id) REFERENCES divisions(id),
    FOREIGN KEY (chart_of_account_id) REFERENCES chart_of_accounts(id)
);
```

### 2.3 Tabel Baru: `profit_loss_statements`

```sql
CREATE TABLE erkap_profit_loss_statements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    erkap_rkap_id BIGINT UNSIGNED NOT NULL,
    division_id BIGINT UNSIGNED NULL,
    period ENUM('monthly', 'quarterly', 'yearly') NOT NULL,
    month TINYINT NULL,
    quarter TINYINT NULL,
    total_revenue DECIMAL(15,2) DEFAULT 0,
    total_expense DECIMAL(15,2) DEFAULT 0,
    gross_profit DECIMAL(15,2) DEFAULT 0,
    operating_expense DECIMAL(15,2) DEFAULT 0,
    operating_profit DECIMAL(15,2) DEFAULT 0,
    other_income DECIMAL(15,2) DEFAULT 0,
    other_expense DECIMAL(15,2) DEFAULT 0,
    profit_before_tax DECIMAL(15,2) DEFAULT 0,
    tax DECIMAL(15,2) DEFAULT 0,
    net_profit DECIMAL(15,2) DEFAULT 0,
    margin DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (erkap_rkap_id) REFERENCES erkap_rkap(id),
    FOREIGN KEY (division_id) REFERENCES divisions(id)
);
```

## 3. Langkah-langkah Implementasi

### Phase 1: Migration & Seeder (1-2 hari)

1. **Buat Migration `2026_09_11_000013_create_revenue_plans_table.php`**
2. **Buat Migration `2026_09_11_000014_create_expense_plans_table.php`**
3. **Buat Migration `2026_09_11_000015_create_profit_loss_statements_table.php`**

### Phase 2: Model & Relasi (1-2 hari)

1. **Buat Model `RevenuePlan.php`**
   ```php
   class RevenuePlan extends Model
   {
       protected $table = 'erkap_revenue_plans';
       
       public function rkap()
       {
           return $this->belongsTo(RKAP::class, 'erkap_rkap_id');
       }
       
       public function division()
       {
           return $this->belongsTo(Division::class);
       }
       
       public function chartOfAccount()
       {
           return $this->belongsTo(ChartOfAccount::class);
       }
       
       public function scopeMonthly($query, $month)
       {
           return $query->whereNotNull("{$month}_plan");
       }
   }
   ```

2. **Buat Model `ExpensePlan.php`**
   - Sama seperti RevenuePlan

3. **Buat Model `ProfitLossStatement.php`**
   ```php
   class ProfitLossStatement extends Model
   {
       protected $table = 'erkap_profit_loss_statements';
       
       public function calculateMargin()
       {
           if ($this->total_revenue > 0) {
               $this->margin = ($this->net_profit / $this->total_revenue) * 100;
           } else {
               $this->margin = 0;
           }
           $this->save();
       }
   }
   ```

### Phase 3: Controller & Logic (2-3 hari)

1. **Buat Controller `RevenuePlanController.php`**
   - CRUD standar
   - Validasi total = sum(bulanan)

2. **Buat Controller `ExpensePlanController.php`**
   - CRUD standar
   - Validasi total = sum(bulanan)

3. **Buat Controller `ProfitLossController.php`**
   - `index()` - daftar P&L per periode
   - `show()` - detail P&L
   - `generate()` - generate P&L dari revenue & expense plans
   - `calculate()` - kalkulasi laba rugi

4. **Logic Kalkulasi P&L**
   ```php
   public function generate($erkapRkapId, $divisionId = null)
   {
       $query = RevenuePlan::where('erkap_rkap_id', $erkapRkapId);
       if ($divisionId) {
           $query->where('division_id', $divisionId);
       }
       
       $totalRevenue = $query->sum('total');
       
       $expenseQuery = ExpensePlan::where('erkap_rkap_id', $erkapRkapId);
       if ($divisionId) {
           $expenseQuery->where('division_id', $divisionId);
       }
       
       $totalExpense = $expenseQuery->sum('total');
       
       $grossProfit = $totalRevenue - $totalExpense;
       $margin = $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0;
       
       $statement = ProfitLossStatement::updateOrCreate(
           [
               'erkap_rkap_id' => $erkapRkapId,
               'division_id' => $divisionId,
               'period' => 'yearly',
           ],
           [
               'total_revenue' => $totalRevenue,
               'total_expense' => $totalExpense,
               'gross_profit' => $grossProfit,
               'net_profit' => $grossProfit,
               'margin' => $margin,
           ]
       );
       
       return $statement;
   }
   ```

### Phase 4: View/Blade (2-3 hari)

1. **Buat `resources/views/erkap/revenue-plans/index.blade.php`**
2. **Buat `resources/views/erkap/revenue-plans/create.blade.php`**
3. **Buat `resources/views/erkap/revenue-plans/edit.blade.php`**

4. **Buat `resources/views/erkap/expense-plans/index.blade.php`**
5. **Buat `resources/views/erkap/expense-plans/create.blade.php`**
6. **Buat `resources/views/erkap/expense-plans/edit.blade.php`**

7. **Buat `resources/views/erkap/profit-loss/index.blade.php`**
   - Tabel ringkasan P&L
   - Kolom: Pendapatan, Beban, Laba Kotor, Margin

8. **Buat `resources/views/erkap/profit-loss/show.blade.php`**
   - Detail P&L per akun
   - Chart pendapatan vs beban
   - Tren margin per bulan

### Phase 5: Simulasi Skenario (1-2 hari)

1. **Method `simulate()` di ProfitLossController**
   ```php
   public function simulate($erkapRkapId, $scenario)
   {
       $multiplier = match($scenario) {
           'best' => 1.1,    // +10%
           'base' => 1.0,    // 0%
           'worst' => 0.9,   // -10%
       };
       
       $revenue = RevenuePlan::where('erkap_rkap_id', $erkapRkapId)->sum('total') * $multiplier;
       $expense = ExpensePlan::where('erkap_rkap_id', $erkapRkapId)->sum('total') * $multiplier;
       
       $profit = $revenue - $expense;
       $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;
       
       return [
           'scenario' => $scenario,
           'revenue' => $revenue,
           'expense' => $expense,
           'profit' => $profit,
           'margin' => $margin,
       ];
   }
   ```

2. **View Simulasi**
   - `resources/views/erkap/profit-loss/simulate.blade.php`
   - Perbandingan 3 skenario
   - Chart perbandingan

## 4. File yang Perlu Dibuat/Diubah

### File Baru:
- `database/migrations/2026_09_11_000013_create_revenue_plans_table.php`
- `database/migrations/2026_09_11_000014_create_expense_plans_table.php`
- `database/migrations/2026_09_11_000015_create_profit_loss_statements_table.php`
- `app/Models/Erkap/RevenuePlan.php`
- `app/Models/Erkap/ExpensePlan.php`
- `app/Models/Erkap/ProfitLossStatement.php`
- `app/Http/Controllers/Erkap/RevenuePlanController.php`
- `app/Http/Controllers/Erkap/ExpensePlanController.php`
- `app/Http/Controllers/Erkap/ProfitLossController.php`
- `app/Http/Requests/Erkap/StoreRevenuePlanRequest.php`
- `app/Http/Requests/Erkap/UpdateRevenuePlanRequest.php`
- `app/Http/Requests/Erkap/StoreExpensePlanRequest.php`
- `app/Http/Requests/Erkap/UpdateExpensePlanRequest.php`
- `resources/views/erkap/revenue-plans/index.blade.php`
- `resources/views/erkap/revenue-plans/create.blade.php`
- `resources/views/erkap/revenue-plans/edit.blade.php`
- `resources/views/erkap/expense-plans/index.blade.php`
- `resources/views/erkap/expense-plans/create.blade.php`
- `resources/views/erkap/expense-plans/edit.blade.php`
- `resources/views/erkap/profit-loss/index.blade.php`
- `resources/views/erkap/profit-loss/show.blade.php`
- `resources/views/erkap/profit-loss/simulate.blade.php`

### File yang Diubah:
- `routes/routers/erkap.php` - tambah route
- `database/seeders/DatabaseSeeder.php` - tambah seeder test

## 5. Estimasi Effort

| Task | Effort (hari) |
|------|--------------|
| Migration & Seeder | 1-2 |
| Model & Relasi | 1-2 |
| Controller & Logic | 2-3 |
| View/Blade | 2-3 |
| Simulasi Skenario | 1-2 |
| Testing | 1-2 |
| **Total** | **9-14** |

## 6. Dependency

- Tabel `chart_of_accounts` sudah ada (perlu update sesuai planning gap 2)
- Tabel `divisions` sudah ada
- Tabel `erkap_rkap` sudah ada
- Data akun pendapatan (6000-6940) dan beban (7000-9109) sudah di-seed

## 7. Risk & Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| Data CoA tidak lengkap | Kalkulasi salah | Validasi data lengkap |
| Performa query aggregasi | Lambat | Gunakan caching & index |
| Konsistensi data antar tabel | Error | Gunakan transaksi DB |