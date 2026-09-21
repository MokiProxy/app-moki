# Planning Implementasi: Chart of Accounts (CoA)

## 1. Deskripsi Gap

**Status Saat Ini**: 
- Tabel `chart_of_accounts` ada tapi hanya kolom `id` + `timestamps` (kosong)
- Data akun 6000-9109 di-seed ke `erkap_cost_elements` tanpa relasi ke `chart_of_accounts`
- Tidak ada pemisahan tipe `Pendapatan | Beban` pada elemen biaya
- Model `ChartOfAccount` milik EQTax, tidak terhubung ERKAP

**Yang Diperlukan**:
- Tabel `chart_of_accounts` dengan kolom `code`, `name`, `type` (Pendapatan/Beban)
- Relasi antara `erkap_cost_elements` ke `chart_of_accounts`
- Pemisahan tipe pendapatan (6000-6940) vs beban (7000-9109)

**Dampak**: P&L (Phase 4) tidak bisa menghitung pendapatan vs beban secara otomatis

## 2. Solusi yang Direkomendasikan

### 2.1 Ubah Struktur Tabel `chart_of_accounts`

```sql
ALTER TABLE chart_of_accounts 
ADD COLUMN code VARCHAR(10) NOT NULL AFTER id,
ADD COLUMN name VARCHAR(255) NOT NULL AFTER code,
ADD COLUMN type ENUM('revenue', 'expense') NOT NULL AFTER name,
ADD COLUMN description TEXT NULL AFTER type,
ADD UNIQUE KEY unique_code (code);
```

### 2.2 Tambah Kolom di `erkap_cost_elements`

```sql
ALTER TABLE erkap_cost_elements 
ADD COLUMN chart_of_account_id BIGINT UNSIGNED NULL AFTER erkap_cost_element_category_id,
ADD FOREIGN KEY (chart_of_account_id) REFERENCES chart_of_accounts(id);
```

### 2.3 Isi Data `chart_of_accounts` dari Seeder

```php
// Revenue accounts (6000-6940)
$revenueAccounts = [
    ['code' => '6000', 'name' => 'Pendapatan Usaha', 'type' => 'revenue'],
    ['code' => '6010', 'name' => 'Pendapatan Penjualan', 'type' => 'revenue'],
    // ... sampai 6940
];

// Expense accounts (7000-9109)
$expenseAccounts = [
    ['code' => '7000', 'name' => 'Beban Pokok Penjualan', 'type' => 'expense'],
    ['code' => '7010', 'name' => 'Beban Bahan', 'type' => 'expense'],
    // ... sampai 9109
];
```

## 3. Langkah-langkah Implementasi

### Phase 1: Migration & Seeder (1 hari)

1. **Buat Migration `2026_09_11_000004_add_columns_to_chart_of_accounts_table.php`**
   - Tambah kolom `code`, `name`, `type`, `description`
   - Tambah unique constraint

2. **Buat Migration `2026_09_11_000005_add_chart_of_account_id_to_cost_elements_table.php`**
   - Tambah kolom `chart_of_account_id` nullable
   - Tambah foreign key

3. **Update Seeder `CostElementsSeeder.php`**
   - Seed data ke `chart_of_accounts` dulu
   - Lalu seed `erkap_cost_elements` dengan `chart_of_account_id`

4. **Buat Seeder `ChartOfAccountsSeeder.php`**
   - Seed semua akun 6000-9109

### Phase 2: Model & Relasi (1 hari)

1. **Update Model `ChartOfAccount.php`**
   ```php
   public function costElements()
   {
       return $this->hasMany(CostElement::class, 'chart_of_account_id');
   }
   
   public function scopeRevenue($query)
   {
       return $query->where('type', 'revenue');
   }
   
   public function scopeExpense($query)
   {
       return $query->where('type', 'expense');
   }
   ```

2. **Update Model `CostElement.php`**
   ```php
   public function chartOfAccount()
   {
       return $this->belongsTo(ChartOfAccount::class);
   }
   ```

### Phase 3: Controller & View (2 hari)

1. **Buat Controller `ChartOfAccountController.php`**
   - `index()` - daftar CoA dengan filter type
   - `show()` - detail CoA + relasi cost elements
   - `sync()` - sinkronisasi CoA dengan cost elements

2. **Buat View `resources/views/erkap/chart-of-accounts/index.blade.php`**
   - Tabel daftar CoA
   - Filter berdasarkan type (revenue/expense)
   - Kolom: code, name, type, jumlah elemen biaya

3. **Update View `resources/views/erkap/cost-elements/index.blade.php`**
   - Tampilkan kolom "Akun CoA" dari relasi

### Phase 4: Business Rules (1 hari)

1. **Validasi Unik Code**
   ```php
   // Di StoreCostElementRequest
   'code' => 'required|string|max:10|unique:erkap_cost_elements,code'
   ```

2. **Relasi Wajib**
   - Saat create/update cost element, user harus pilih CoA
   - Tampilkan dropdown berdasarkan type (revenue/expense)

3. **Query untuk P&L**
   ```php
   // Revenue
   $revenue = CostElement::whereHas('chartOfAccount', function($q) {
       $q->where('type', 'revenue');
   })->get();
   
   // Expense
   $expense = CostElement::whereHas('chartOfAccount', function($q) {
       $q->where('type', 'expense');
   })->get();
   ```

## 4. File yang Perlu Dibuat/Diubah

### File Baru:
- `database/migrations/2026_09_11_000004_add_columns_to_chart_of_accounts_table.php`
- `database/migrations/2026_09_11_000005_add_chart_of_account_id_to_cost_elements_table.php`
- `database/seeders/ChartOfAccountsSeeder.php`
- `app/Http/Controllers/Erkap/ChartOfAccountController.php`
- `resources/views/erkap/chart-of-accounts/index.blade.php`
- `resources/views/erkap/chart-of-accounts/show.blade.php`

### File yang Diubah:
- `database/migrations/2026_09_07_125216_create_chart_of_accounts_table.php` - tambah kolom
- `database/migrations/2026_09_07_125823_create_cost_elements_table.php` - tambah FK
- `app/Models/ChartOfAccount.php` - tambah relasi & scope
- `app/Models/Erkap/CostElement.php` - tambah relasi
- `database/seeders/CostElementsSeeder.php` - update logic
- `routes/routers/erkap.php` - tambah route
- `resources/views/erkap/cost-elements/index.blade.php` - tambah kolom
- `resources/views/erkap/cost-elements/create.blade.php` - tambah dropdown CoA
- `resources/views/erkap/cost-elements/edit.blade.php` - tambah dropdown CoA

## 5. Estimasi Effort

| Task | Effort (hari) |
|------|--------------|
| Migration & Seeder | 1 |
| Model & Relasi | 1 |
| Controller & View | 2 |
| Business Rules | 1 |
| Testing | 1 |
| **Total** | **6** |

## 6. Dependency

- Tabel `chart_of_accounts` sudah ada (perlu alter)
- Tabel `erkap_cost_elements` sudah ada (perlu tambah FK)
- Seeder `CostElementsSeeder` sudah ada (perlu update)

## 7. Risk & Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| Data tidak sinkron antara CoA dan cost elements | Query P&L salah | Validasi referential integrity |
| Performa query relasi | Lambat | Gunakan eager loading |
| Konflik dengan modul EQTax | Error | Pisahkan namespace atau gunakan tabel baru `erkap_chart_of_accounts`