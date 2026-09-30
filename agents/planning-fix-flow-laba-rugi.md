# Planning: Perubahan Alur Perhitungan Laba Rugi

## 1. Problem Statement

Saat ini, **Rencana Pendapatan** dan **Rencana Beban** diinput secara manual oleh user. Padahal data yang sama sudah tersedia di **Biaya Umum (RoutineCost)** dan **Biaya Investasi (InvestmentPlan)**. Hal ini menyebabkan:

- **Duplikasi data** — user input data yang sama di dua tempat
- **Inkonsistensi** — total di P&L bisa berbeda dengan total di anggaran operasional
- **Inefisiensi** — user harus input ulang data yang sudah ada

## 2. Objective

Mengubah alur perhitungan laba rugi agar:
1. **Rencana Pendapatan** tidak lagi diinput manual, tapi **diambil dari RoutineCost dan InvestmentPlan** yang elemen biayanya termasuk kategori **pendapatan** (COA type = `revenue`)
2. **Rencana Beban** tidak lagi diinput manual, tapi **diambil dari RoutineCost dan InvestmentPlan** yang elemen biayanya termasuk kategori **beban** (COA type = `expense`)

## 3. Current State Analysis

### 3.1 Database Tables

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `erkap_revenue_plans` | Manual revenue plans | `erkap_rkap_id`, `division_id`, `chart_of_account_id`, `jan_plan`..`dec_plan`, `total` |
| `erkap_expense_plans` | Manual expense plans | `erkap_rkap_id`, `division_id`, `chart_of_account_id`, `jan_plan`..`dec_plan`, `total` |
| `erkap_routine_costs` | Operational costs (OPEX) | `erkap_work_program_id`, `erkap_cost_element_id`, `chart_of_account_id`, `jan_cost`..`des_cost`, `total` |
| `erkap_investment_plans` | Investment plans (CAPEX) | `erkap_work_program_id`, `erkap_cost_element_id`, `chart_of_account_id`, `jan_plan`..`dec_plan`, `total` |
| `erkap_profit_loss_statements` | P&L results | `erkap_rkap_id`, `division_id`, `total_revenue`, `total_expense`, `gross_profit`, `net_profit`, `margin` |
| `erkap_cost_elements` | Cost element master | `code`, `name`, `erkap_cost_element_category_id`, `chart_of_account_id` |
| `chart_of_accounts` | COA master | `code`, `cost_center_id`, `cost_element_id`, `type` (`revenue`/`expense`) |

### 3.2 Current Flow

```
User Input Manual
    ↓
RevenuePlan (manual entry) ──→ ProfitLossController::generate() ──→ ProfitLossStatement
ExpensePlan (manual entry) ──→ ProfitLossController::generate() ──→ ProfitLossStatement
```

### 3.3 How Revenue vs Expense is Determined

Sistem menggunakan **COA `type` field** untuk membedakan pendapatan vs beban:
- `ChartOfAccount::scopeRevenue()` → `where('type', 'revenue')`
- `ChartOfAccount::scopeExpense()` → `where('type', 'expense')`

Cost element dikategorikan berdasarkan COA type-nya:
- Code starting with "6" → revenue (e.g., 6050 Pendapatan Penjualan Briket Super)
- Code starting with "7", "8", "9" → expense (e.g., 7000 Biaya Konsultan, 8006 Tunjangan Pegawai)

### 3.4 Key Models & Relationships

```
RoutineCost
  ├── belongsTo CostElement (erkap_cost_element_id)
  ├── belongsTo ChartOfAccount (chart_of_account_id)
  └── belongsTo WorkProgram → RiskIdentification → DepartmentTarget → Division

InvestmentPlan
  ├── belongsTo CostElement (erkap_cost_element_id)
  ├── belongsTo ChartOfAccount (chart_of_account_id)
  └── belongsTo WorkProgram → RiskIdentification → DepartmentTarget → Division

RevenuePlan
  ├── belongsTo ChartOfAccount (chart_of_account_id)
  └── belongsTo Division

ExpensePlan
  ├── belongsTo ChartOfAccount (chart_of_account_id)
  └── belongsTo Division
```

## 4. Proposed Solution

### 4.1 New Flow

```
RoutineCost (OPEX) ──┐
                     ├──→ Aggregate by COA type ──→ ProfitLossStatement
InvestmentPlan (CAPEX) ┘
```

### 4.2 Implementation Plan

#### Phase 1: Service Layer — `ProfitLossService`

Buat service baru `app/Services/Erkap/ProfitLossService.php` untuk memisahkan logika bisnis dari controller.

**Method utama:**

```php
class ProfitLossService
{
    /**
     * Hitung P&L dari RoutineCost + InvestmentPlan
     */
    public static function calculateFromOperationalBudget(RKAP $rkap, ?int $divisionId = null): array
    {
        // 1. Ambil semua RoutineCost untuk RKAP ini
        // 2. Ambil semall InvestmentPlan (status approved) untuk RKAP ini
        // 3. Join ke ChartOfAccount untuk dapatkan type (revenue/expense)
        // 4. Group by: division_id + chart_of_account_id + type
        // 5. Sum monthly amounts
        // 6. Return structured data untuk P&L statement
    }

    /**
     * Ambil data pendapatan dari RoutineCost + InvestmentPlan
     * Filter: COA type = 'revenue'
     */
    public static function fetchRevenueData(RKAP $rkap, ?int $divisionId = null): Collection
    {
        // Query RoutineCost + InvestmentPlan
        // Join ChartOfAccount where type = 'revenue'
        // Group by division_id + chart_of_account_id
        // Return monthly sums
    }

    /**
     * Ambil data beban dari RoutineCost + InvestmentPlan
     * Filter: COA type = 'expense'
     */
    public static function fetchExpenseData(RKAP $rkap, ?int $divisionId = null): Collection
    {
        // Query RoutineCost + InvestmentPlan
        // Join ChartOfAccount where type = 'expense'
        // Group by division_id + chart_of_account_id
        // Return monthly sums
    }
}
```

#### Phase 2: Modify `ProfitLossController`

**Method `generate()`:**
- Ganti `planFor($data, 'revenue')` → `ProfitLossService::fetchRevenueData($rkap, $divisionId)`
- Ganti `planFor($data, 'expense')` → `ProfitLossService::fetchExpenseData($rkap, $divisionId)`
- Logic perhitungan tetap sama (sum monthly, calculate profit, save to ProfitLossStatement)

**Method `show()`:**
- Tampilkan data dari RoutineCost + InvestmentPlan, bukan dari RevenuePlan/ExpensePlan
- Atau tampilkan RevenuePlan/ExpensePlan yang sudah auto-generated (jika diperlukan untuk backward compatibility)

**Method `simulate()`:**
- Tetap gunakan multiplier (1.1x, 1.0x, 0.9x) pada data yang sudah di-fetch dari operational budget

#### Phase 3: Auto-Generate RevenuePlan & ExpensePlan (Optional)

Untuk backward compatibility dan reporting, bisa dibuat auto-generation dari RevenuePlan/ExpensePlan:

```php
public static function syncPlansFromOperationalBudget(RKAP $rkap, ?int $divisionId = null): void
{
    // 1. Calculate revenue data
    // 2. Upsert RevenuePlan records (auto-generated, bukan manual)
    // 3. Calculate expense data
    // 4. Upsert ExpensePlan records (auto-generated, bukan manual)
}
```

Atau **hapus total** RevenuePlan/ExpensePlan dan murni hitung dari RoutineCost + InvestmentPlan.

#### Phase 4: UI Changes

**Opsi A — Hapus menu RevenuePlan & ExpensePlan:**
- Hapus route & controller untuk RevenuePlan & ExpensePlan
- P&L dihitung murni dari operational budget
- User tidak perlu input manual lagi

**Opsi B — Jadikan RevenuePlan & ExpensePlan read-only:**
- Tampilkan data yang sudah di-auto-generate dari operational budget
- User tidak bisa create/edit/delete
- Hanya bisa view

**Opsi C — Pertahankan manual + tambah tombol "Sync from Budget":**
- RevenuePlan & ExpensePlan tetap ada
- Tambah tombol "Sync dari Anggaran" untuk auto-generate dari RoutineCost + InvestmentPlan
- User bisa pilih: input manual atau sync otomatis

**Rekomendasi: Opsi A** — paling clean, menghilangkan duplikasi data.

#### Phase 5: Database Migration (Jika Opsi A)

Jika memilih Opsi A (hapus RevenuePlan & ExpensePlan):

```php
// Migration: drop revenue_plans dan expense_plans tables
// Atau: rename ke _archived untuk historical data
```

Jika memilih Opsi B/C, tidak perlu migration.

## 5. Detailed Implementation Steps

### Step 1: Buat ProfitLossService

**File:** `app/Services/Erkap/ProfitLossService.php`

```php
<?php

namespace App\Services\Erkap;

use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\RoutineCost;
use App\Models\Erkap\RKAP;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProfitLossService
{
    /**
     * Calculate P&L from RoutineCost + InvestmentPlan
     */
    public static function calculate(RKAP $rkap, ?int $divisionId = null): array
    {
        $revenueData = static::fetchRevenueData($rkap, $divisionId);
        $expenseData = static::fetchExpenseData($rkap, $divisionId);

        $monthlyRevenue = static::sumMonthly($revenueData);
        $monthlyExpense = static::sumMonthly($expenseData);

        $totalRevenue = array_sum($monthlyRevenue);
        $totalExpense = array_sum($monthlyExpense);
        $grossProfit = $totalRevenue - $totalExpense;
        $margin = $totalRevenue > 0 ? round(($grossProfit / $totalRevenue) * 100, 2) : 0;

        return [
            'monthly_revenue' => $monthlyRevenue,
            'monthly_expense' => $monthlyExpense,
            'total_revenue' => $totalRevenue,
            'total_expense' => $totalExpense,
            'gross_profit' => $grossProfit,
            'net_profit' => $grossProfit,
            'margin' => $margin,
            'revenue_details' => $revenueData,
            'expense_details' => $expenseData,
        ];
    }

    /**
     * Fetch revenue data from RoutineCost + InvestmentPlan
     * Filter: COA type = 'revenue'
     */
    public static function fetchRevenueData(RKAP $rkap, ?int $divisionId = null): Collection
    {
        return static::fetchOperationalData($rkap, $divisionId, 'revenue');
    }

    /**
     * Fetch expense data from RoutineCost + InvestmentPlan
     * Filter: COA type = 'expense'
     */
    public static function fetchExpenseData(RKAP $rkap, ?int $divisionId = null): Collection
    {
        return static::fetchOperationalData($rkap, $divisionId, 'expense');
    }

    /**
     * Fetch and aggregate operational data by COA type
     */
    protected static function fetchOperationalData(RKAP $rkap, ?int $divisionId, string $type): Collection
    {
        // Implementation:
        // 1. Query RoutineCost with workProgram.riskIdentification.departmentTarget.division
        // 2. Query InvestmentPlan with workProgram.riskIdentification.departmentTarget.division
        // 3. Join ChartOfAccount where type = $type
        // 4. Filter by erkap_rkap_id (through workProgram chain)
        // 5. Filter by division_id if provided
        // 6. Group by division_id + chart_of_account_id
        // 7. Sum monthly amounts
        // 8. Return collection with: division_id, chart_of_account_id, chart_of_account_name, monthly sums
    }

    /**
     * Sum monthly amounts from data collection
     */
    protected static function sumMonthly(Collection $data): array
    {
        $result = array_fill(0, 12, 0.0);

        foreach ($data as $item) {
            foreach (static::monthColumns() as $index => $month) {
                $result[$index] += (float) ($item[$month] ?? 0);
            }
        }

        return $result;
    }

    /**
     * Month column names
     */
    public static function monthColumns(): array
    {
        return ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
    }
}
```

### Step 2: Modify ProfitLossController

**File:** `app/Http/Controllers/Erkap/ProfitLossController.php`

```php
public function generate(Request $request)
{
    $data = $request->validate([
        'erkap_rkap_id' => ['required', 'integer', 'exists:erkap_rkap,id'],
        'division_id' => ['nullable', 'integer', 'exists:divisions,id'],
    ]);

    $rkap = RKAP::findOrFail($data['erkap_rkap_id']);
    $divisionId = $data['division_id'] ?? null;

    // Calculate from operational budget
    $result = ProfitLossService::calculate($rkap, $divisionId);

    // Save to ProfitLossStatement
    ProfitLossStatement::updateOrCreate(
        [
            'erkap_rkap_id' => $rkap->id,
            'division_id' => $divisionId,
            'period' => 'yearly',
        ],
        [
            'total_revenue' => $result['total_revenue'],
            'total_expense' => $result['total_expense'],
            'gross_profit' => $result['gross_profit'],
            'net_profit' => $result['net_profit'],
            'margin' => $result['margin'],
        ]
    );

    return redirect()->route('erkap.profit-loss.show', $rkap->id)
        ->with('success', 'Laba rugi berhasil dihitung dari anggaran operasional.');
}
```

### Step 3: Update Views

**File:** `resources/views/erkap/profit-loss/show.blade.php`

- Tampilkan sumber data (dari RoutineCost / InvestmentPlan)
- Tampilkan breakdown per COA dengan type badge (Pendapatan/Beban)
- Tampilkan link ke detail RoutineCost / InvestmentPlan

### Step 4: Handle RevenuePlan & ExpensePlan

**Opsi A (Recommended):**
- Hapus menu RevenuePlan & ExpensePlan dari sidebar
- Hapus route & controller
- Atau: keep untuk historical data, tapi tidak digunakan lagi untuk kalkulasi

**Opsi B:**
- Jadikan read-only
- Auto-generate dari operational budget

## 6. Data Mapping

### 6.1 Revenue Data Mapping

| Source | COA Type | Example |
|--------|----------|---------|
| RoutineCost | revenue | 6050 Pendapatan Penjualan Briket Super |
| InvestmentPlan | revenue | 6050 Pendapatan Penjualan Briket Super |

### 6.2 Expense Data Mapping

| Source | COA Type | Example |
|--------|----------|---------|
| RoutineCost | expense | 7000 Biaya Konsultan, 8006 Tunjangan Pegawai |
| InvestmentPlan | expense | 7000 Biaya Konsultan, 8006 Tunjangan Pegawai |

### 6.3 Query Logic

```sql
-- Revenue data
SELECT 
    d.id as division_id,
    coa.id as chart_of_account_id,
    coa.name as chart_of_account_name,
    SUM(rc.jan_cost) as jan,
    SUM(rc.feb_cost) as feb,
    ...
    SUM(rc.total) as total
FROM erkap_routine_costs rc
JOIN erkap_work_programs wp ON rc.erkap_work_program_id = wp.id
JOIN erkap_risk_identifications ri ON wp.erkap_risk_identification_id = ri.id
JOIN erkap_department_targets dt ON ri.erkap_department_target_id = dt.id
JOIN divisions d ON dt.division_id = d.id
JOIN chart_of_accounts coa ON rc.chart_of_account_id = coa.id
WHERE coa.type = 'revenue'
  AND dt.erkap_rkap_id = ?
  AND (? IS NULL OR d.id = ?)
GROUP BY d.id, coa.id, coa.name

UNION ALL

-- Same for InvestmentPlan (status = 'approved')
```

## 7. Testing Plan

### 7.1 Unit Tests

- Test `ProfitLossService::calculate()` with sample data
- Test revenue/expense filtering by COA type
- Test monthly sum calculation
- Test margin calculation

### 7.2 Feature Tests

- Test P&L generation from operational budget
- Test P&L show page displays correct data
- Test simulation (best/base/worst case)
- Test with and without division filter

### 7.3 Integration Tests

- Test that P&L total matches sum of RoutineCost + InvestmentPlan
- Test that revenue only includes COA type = 'revenue'
- Test that expense only includes COA type = 'expense'

## 8. Risk & Mitigation

| Risk | Mitigation |
|------|------------|
| Data inconsistency between old manual plans and new auto-calculation | Run comparison report before/after migration |
| Performance issue with large dataset | Add indexes on `chart_of_account_id`, `erkap_rkap_id` |
| User confusion (where to input data?) | Update user guide, add tooltips in UI |
| Historical data loss | Archive old RevenuePlan/ExpensePlan before migration |

## 9. Rollback Plan

If implementation fails:
1. Revert code changes (git revert)
2. Restore RevenuePlan/ExpensePlan manual entry
3. Restore P&L calculation from RevenuePlan/ExpensePlan

## 10. Timeline Estimate

| Phase | Duration | Description |
|-------|----------|-------------|
| Phase 1 | 2 days | Buat ProfitLossService |
| Phase 2 | 1 day | Modify ProfitLossController |
| Phase 3 | 1 day | Update views |
| Phase 4 | 1 day | Handle RevenuePlan & ExpensePlan |
| Phase 5 | 2 days | Testing & bug fixes |
| **Total** | **7 days** | |

## 11. Open Questions

1. **Apakah RevenuePlan & ExpensePlan dihapus total atau dijadikan read-only?**
   - Rekomendasi: Hapus total (Opsi A)

2. **Bagaimana handle historical data?**
   - Archive ke tabel `_archived` atau biarkan sebagai historical reference

3. **Apakah perlu tombol "Recalculate" di UI?**
   - Ya, untuk allow user trigger recalculation setelah data berubah

4. **Bagaimana handle division filter?**
   - Tetap support filter by division

5. **Apakah perlu migration untuk rename `des_cost` → `dec_cost` di routine_costs?**
   - Ini bug terpisah, tapi bisa sekalian fix
