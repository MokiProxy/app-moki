# Planning Implementasi: Budgeting OPEX (Cost Centers, Validasi, Agregasi)

## 1. Deskripsi Gap

**Status Saat Ini**:
- Tabel `cost_centers` tidak ada
- Kolom `cost_center_id` di `routine_costs` ada tapi tanpa FK constraint
- Kolom `satuan/units` tidak ada di `routine_costs`
- Validasi `total = qty × unit_price` tidak ada
- Auto-subtotal per elemen/program/SK tidak ada

**Yang Diperlukan**:
- Tabel `cost_centers` dengan kode, nama, pemilik
- Kolom `units` di `routine_costs`
- Validasi total = qty × unit_price
- Auto-subtotal per elemen, per program kerja, per satuan kerja

**Dampak**: Budgeting OPEX tidak lengkap, tidak ada konsolidasi per divisi

## 2. Solusi yang Direkomendasikan

### 2.1 Tabel Baru: `cost_centers`

```sql
CREATE TABLE cost_centers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    owner VARCHAR(255) NOT NULL,
    division_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (division_id) REFERENCES divisions(id)
);
```

### 2.2 Tambah Kolom di `routine_costs`

```sql
ALTER TABLE erkap_routine_costs 
ADD COLUMN units VARCHAR(50) NOT NULL AFTER qty,
ADD FOREIGN KEY (cost_center_id) REFERENCES cost_centers(id);
```

### 2.3 Validasi Total

```php
// Di RoutineCostController
public function store(StoreRoutineCostRequest $request)
{
    // Validasi total = qty × unit_price
    $expectedTotal = $request->qty * $request->unit_price;
    if (abs($expectedTotal - $request->total) > 0.01) {
        return back()->withErrors(['total' => 'Total harus sama dengan qty × harga satuan']);
    }
    
    // Validasi total = sum(bulanan)
    $monthlyTotal = collect($request->only(['jan_cost', 'feb_cost', ...]))->sum();
    if (abs($monthlyTotal - $request->total) > 0.01) {
        return back()->withErrors(['total' => 'Total harus sama dengan jumlah bulanan']);
    }
    
    // Simpan
    $routineCost = RoutineCost::create($request->validated());
    
    return redirect()->back()->with('success', 'Biaya rutin berhasil disimpan');
}
```

### 2.4 Auto-Subtotal

```php
// Di RoutineCostController
public function index(Request $request)
{
    $workProgramId = $request->work_program_id;
    
    // Subtotal per elemen biaya
    $subtotalByElement = RoutineCost::where('erkap_work_program_id', $workProgramId)
        ->select('erkap_cost_element_id', DB::raw('SUM(total) as subtotal'))
        ->with('costElement')
        ->groupBy('erkap_cost_element_id')
        ->get();
    
    // Subtotal per program kerja
    $subtotalByProgram = RoutineCost::where('erkap_work_program_id', $workProgramId)
        ->sum('total');
    
    // Total per SK (division)
    $totalByDivision = RoutineCost::whereHas('workProgram.riskIdentification.departmentTarget', function($q) use ($divisionId) {
        $q->where('division_id', $divisionId);
    })->sum('total');
    
    return view('erkap.routine-costs.index', compact('subtotalByElement', 'subtotalByProgram', 'totalByDivision'));
}
```

## 3. Langkah-langkah Implementasi

### Phase 1: Migration & Seeder (1-2 hari)

1. **Buat Migration `2026_09_11_000010_create_cost_centers_table.php`**
2. **Buat Migration `2026_09_11_000011_add_units_to_routine_costs_table.php`**
3. **Buat Migration `2026_09_11_000012_add_fk_to_routine_costs_table.php`**
   - Tambah FK constraint untuk `cost_center_id`

4. **Buat Seeder `CostCentersSeeder.php`**
   - Seed contoh cost centers per divisi

### Phase 2: Model & Relasi (1 hari)

1. **Buat Model `CostCenter.php`**
   ```php
   class CostCenter extends Model
   {
       protected $table = 'cost_centers';
       
       public function division()
       {
           return $this->belongsTo(Division::class);
       }
       
       public function routineCosts()
       {
           return $this->hasMany(RoutineCost::class, 'cost_center_id');
       }
   }
   ```

2. **Update Model `RoutineCost.php`**
   ```php
   public function costCenter()
   {
       return $this->belongsTo(CostCenter::class);
   }
   
   public function workProgram()
   {
       return $this->belongsTo(WorkProgram::class, 'erkap_work_program_id');
   }
   ```

### Phase 3: Controller & Validasi (2 hari)

1. **Buat Controller `CostCenterController.php`**
   - CRUD standar

2. **Update Controller `RoutineCostController.php`**
   - Tambah validasi total
   - Implementasi auto-subtotal
   - Tambah query agregasi

3. **Update Form Request `StoreRoutineCostRequest.php`**
   - Tambah `units` required
   - Tambah validasi `cost_center_id` exists

### Phase 4: View/Blade (2 hari)

1. **Buat `resources/views/erkap/cost-centers/index.blade.php`**
2. **Buat `resources/views/erkap/cost-centers/create.blade.php`**
3. **Buat `resources/views/erkap/cost-centers/edit.blade.php`**

4. **Update `resources/views/erkap/routine-costs/create.blade.php`**
   - Tambah field `units`
   - Tambah dropdown `cost_center_id`
   - Tampilkan preview total otomatis

5. **Update `resources/views/erkap/routine-costs/index.blade.php`**
   - Tampilkan subtotal per elemen
   - Tampilkan subtotal per program
   - Tampilkan total per SK

### Phase 5: Konsolidasi & Agregasi (1-2 hari)

1. **Method `consolidate()` di RoutineCostController**
   ```php
   public function consolidate($erkapRkapId, $divisionId)
   {
       $routineCosts = RoutineCost::whereHas('workProgram.riskIdentification.departmentTarget', function($q) use ($erkapRkapId, $divisionId) {
           $q->where('division_id', $divisionId)
             ->whereHas('companyTarget', function($q2) use ($erkapRkapId) {
                 $q2->where('erkap_rkap_id', $erkapRkapId);
             });
       })->get();
       
       // Konsolidasi per elemen biaya
       $consolidated = $routineCosts->groupBy(function($item) {
           return $item->cost_element_id;
       })->map(function($items, $elementId) {
           return [
               'cost_element_id' => $elementId,
               'total_need' => $items->sum('need'),
               'total_qty' => $items->sum('qty'),
               'total_cost' => $items->sum('total'),
               'monthly' => [
                   'jan' => $items->sum('jan_cost'),
                   'feb' => $items->sum('feb_cost'),
                   // ... sampai des
               ],
           ];
       });
       
       return $consolidated;
   }
   ```

2. **View Konsolidasi**
   - `resources/views/erkap/routine-costs/consolidate.blade.php`
   - Tabel konsolidasi per elemen biaya
   - Total per divisi
   - Export ke Excel

## 4. File yang Perlu Dibuat/Diubah

### File Baru:
- `database/migrations/2026_09_11_000010_create_cost_centers_table.php`
- `database/migrations/2026_09_11_000011_add_units_to_routine_costs_table.php`
- `database/migrations/2026_09_11_000012_add_fk_to_routine_costs_table.php`
- `database/seeders/CostCentersSeeder.php`
- `app/Models/Erkap/CostCenter.php`
- `app/Http/Controllers/Erkap/CostCenterController.php`
- `app/Http/Requests/Erkap/StoreCostCenterRequest.php`
- `app/Http/Requests/Erkap/UpdateCostCenterRequest.php`
- `resources/views/erkap/cost-centers/index.blade.php`
- `resources/views/erkap/cost-centers/create.blade.php`
- `resources/views/erkap/cost-centers/edit.blade.php`
- `resources/views/erkap/routine-costs/consolidate.blade.php`

### File yang Diubah:
- `database/migrations/2026_09_11_000001_create_routine_costs_table.php` - tambah units
- `app/Models/Erkap/RoutineCost.php` - tambah relasi
- `app/Http/Controllers/Erkap/RoutineCostController.php` - tambah validasi & agregasi
- `app/Http/Requests/Erkap/StoreRoutineCostRequest.php` - tambah units & validasi
- `app/Http/Requests/Erkap/UpdateRoutineCostRequest.php` - tambah units & validasi
- `resources/views/erkap/routine-costs/create.blade.php` - tambah field
- `resources/views/erkap/routine-costs/edit.blade.php` - tambah field
- `resources/views/erkap/routine-costs/index.blade.php` - tambah agregasi
- `routes/routers/erkap.php` - tambah route cost-center & consolidate

## 5. Estimasi Effort

| Task | Effort (hari) |
|------|--------------|
| Migration & Seeder | 1-2 |
| Model & Relasi | 1 |
| Controller & Validasi | 2 |
| View/Blade | 2 |
| Konsolidasi & Agregasi | 1-2 |
| Testing | 1 |
| **Total** | **8-10** |

## 6. Dependency

- Tabel `erkap_routine_costs` sudah ada
- Tabel `divisions` sudah ada
- Tabel `erkap_cost_elements` sudah ada

## 7. Risk & Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| Data lama tidak punya `units` | Query gagal | Default value untuk data existing |
| Cost center tidak lengkap | FK error | Seed data lengkap |
| Performa query agregasi | Lambat | Gunakan caching & index