# Planning Implementasi: Work Program (Validasi Business Rule)

## 1. Deskripsi Gap

**Status Saat Ini**:
- Tidak ada validasi `year_plan = sum(bulan)`
- Tidak ada validasi wajib isi 12 bulan
- Tidak ada guard "program kerja hanya untuk rating >= A"
- Tidak ada guard "setiap risiko wajib punya program kerja"
- Tidak ada auto-populate informasi sasaran/rating

**Yang Diperlukan**:
- Validasi `year_plan = sum(jan_plan..dec_plan)`
- Validasi wajib isi 12 bulan (nullable → required)
- Guard rating >= A untuk membuat program kerja
- Guard setiap risiko wajib punya program kerja
- Auto-populate informasi sasaran/rating pada form

**Dampak**: Work Program tidak konsisten, tidak ada penegakan aturan bisnis

## 2. Solusi yang Direkomendasikan

### 2.1 Validasi Year Plan = Sum Bulanan

```php
// Di StoreWorkProgramRequest
public function rules()
{
    return [
        'risk_identification_id' => 'required|exists:erkap_risk_identifications,id',
        'name' => 'required|string|max:255',
        'units' => 'required|string|max:50',
        'year_plan' => 'required|numeric|min:0',
        'jan_plan' => 'required|numeric|min:0',
        'feb_plan' => 'required|numeric|min:0',
        // ... sampai dec_plan
    ];
}

public function withValidator($validator)
{
    $validator->after(function ($validator) {
        $monthlyTotal = collect($this->only([
            'jan_plan', 'feb_plan', 'mar_plan', 'apr_plan',
            'may_plan', 'jun_plan', 'jul_plan', 'aug_plan',
            'sep_plan', 'oct_plan', 'nov_plan', 'dec_plan'
        ]))->sum();
        
        if (abs($monthlyTotal - $this->year_plan) > 0.01) {
            $validator->errors()->add('year_plan', 'Target tahunan harus sama dengan jumlah bulanan');
        }
    });
}
```

### 2.2 Guard Rating >= A

```php
// Di WorkProgramController
public function create(Request $request)
{
    $riskIdentificationId = $request->risk_identification_id;
    
    if ($riskIdentificationId) {
        $riskIdentification = RiskIdentification::with('departmentTarget.ratingCriteria')
            ->findOrFail($riskIdentificationId);
        
        $rating = $riskIdentification->departmentTarget->ratingCriteria->rating;
        
        // Cek apakah rating >= A
        if (!in_array($rating, ['AAA', 'AA', 'A'])) {
            return back()->withErrors([
                'risk_identification_id' => 'Program kerja hanya bisa dibuat untuk sasaran dengan rating A ke atas'
            ]);
        }
    }
    
    return view('erkap.work-programs.create', compact('riskIdentification'));
}
```

### 2.3 Guard Setiap Risiko Wajib Program Kerja

```php
// Di RiskIdentificationController
public function destroy($id)
{
    $riskIdentification = RiskIdentification::findOrFail($id);
    
    // Cek apakah sudah punya program kerja
    if ($riskIdentification->workPrograms()->count() > 0) {
        return back()->withErrors([
            'error' => 'Risiko yang sudah memiliki program kerja tidak bisa dihapus'
        ]);
    }
    
    $riskIdentification->delete();
    
    return redirect()->route('erkap.risk-identifications.index')
        ->with('success', 'Risiko berhasil dihapus');
}
```

### 2.4 Auto-Populate Informasi

```php
// Di WorkProgramController
public function store(StoreWorkProgramRequest $request)
{
    $riskIdentification = RiskIdentification::with([
        'departmentTarget.companyTarget',
        'departmentTarget.ratingCriteria',
        'riskType',
        'riskTaxonomy'
    ])->findOrFail($request->risk_identification_id);
    
    // Auto-populate field jika kosong
    $data = $request->validated();
    
    if (empty($data['name'])) {
        $data['name'] = "Program Kerja: " . $riskIdentification->risk;
    }
    
    $workProgram = WorkProgram::create($data);
    
    return redirect()->route('erkap.work-programs.show', $workProgram)
        ->with('success', 'Program kerja berhasil disimpan');
}
```

## 3. Langkah-langkah Implementasi

### Phase 1: Validasi Form Request (1 hari)

1. **Update `StoreWorkProgramRequest.php`**
   - Ubah semua kolom bulan dari `nullable` ke `required`
   - Tambah validasi `year_plan = sum(bulan)`
   - Tambah validasi numeric min:0

2. **Update `UpdateWorkProgramRequest.php`**
   - Sama seperti store

### Phase 2: Controller Logic (1-2 hari)

1. **Update `WorkProgramController.php`**
   - Tambah guard rating >= A di `create()` dan `store()`
   - Tambah method `checkRating()` 
   - Update `store()` untuk auto-populate

2. **Update `RiskIdentificationController.php`**
   - Tambah guard di `destroy()` - cek apakah sudah punya program kerja

### Phase 3: View/Blade (1-2 hari)

1. **Update `resources/views/erkap/work-programs/create.blade.php`**
   - Tampilkan informasi sasaran/rating dari risk identification
   - Tambah validasi client-side untuk year_plan = sum bulanan
   - Tambah tooltip "Program kerja hanya untuk rating A ke atas"

2. **Update `resources/views/erkap/work-programs/edit.blade.php`**
   - Sama seperti create

3. **Update `resources/views/erkap/work-programs/index.blade.php`**
   - Tampilkan badge rating untuk setiap program kerja
   - Highlight program kerja yang belum punya biaya

### Phase 4: Business Rules Tambahan (1 hari)

1. **Guard "Setiap Risiko Wajib Program Kerja"**
   ```php
   // Di RiskIdentification model
   public function hasWorkProgram()
   {
       return $this->workPrograms()->count() > 0;
   }
   
   // Di RiskRankingController atau validation
   public function validateRiskHasWorkProgram($riskIdentificationId)
   {
       $riskIdentification = RiskIdentification::findOrFail($riskIdentificationId);
       if (!$riskIdentification->hasWorkProgram()) {
           return false;
       }
       return true;
   }
   ```

2. **Validasi "Program Kerja Wajib Anggaran"**
   ```php
   // Di WorkProgram model
   public function hasBudget()
   {
       return $this->routineCosts()->count() > 0 || $this->investmentPlans()->count() > 0;
   }
   
   // Di validation saat approve
   public function validateWorkProgramHasBudget($workProgramId)
   {
       $workProgram = WorkProgram::findOrFail($workProgramId);
       return $workProgram->hasBudget();
   }
   ```

## 4. File yang Perlu Dibuat/Diubah

### File Baru:
- Tidak ada file baru

### File yang Diubah:
- `app/Http/Requests/Erkap/StoreWorkProgramRequest.php` - tambah validasi
- `app/Http/Requests/Erkap/UpdateWorkProgramRequest.php` - tambah validasi
- `app/Http/Controllers/Erkap/WorkProgramController.php` - tambah guard & logic
- `app/Http/Controllers/Erkap/RiskIdentificationController.php` - tambah guard destroy
- `app/Models/Erkap/RiskIdentification.php` - tambah method hasWorkProgram
- `app/Models/Erkap/WorkProgram.php` - tambah method hasBudget
- `resources/views/erkap/work-programs/create.blade.php` - tambah info sasaran/rating
- `resources/views/erkap/work-programs/edit.blade.php` - tambah info sasaran/rating
- `resources/views/erkap/work-programs/index.blade.php` - tambah badge rating
- `resources/views/erkap/risk-identifications/index.blade.php` - tambah info program kerja

## 5. Estimasi Effort

| Task | Effort (hari) |
|------|--------------|
| Validasi Form Request | 1 |
| Controller Logic | 1-2 |
| View/Blade | 1-2 |
| Business Rules Tambahan | 1 |
| Testing | 1 |
| **Total** | **5-7** |

## 6. Dependency

- Tabel `erkap_work_programs` sudah ada
- Tabel `erkap_risk_identifications` sudah ada
- Tabel `erkap_rating_criterias` sudah ada
- Relasi antar tabel sudah ada

## 7. Risk & Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| Data existing tidak memenuhi validasi | Insert/update gagal | Jalankan script cleanup |
| User tidak paham aturan bisnis | Error | Tambah validasi client-side & pesan error jelas |
| Rating criteria tidak lengkap | Guard salah | Validasi data rating criteria lengkap |