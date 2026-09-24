# Planning Implementasi: Risk Management (Positif/Negatif, Auto-Calc Skor, Ranking, Strategi)

## 1. Deskripsi Gap

**Status Saat Ini**:
- Tidak ada kolom `is_positive`/`is_negative` pada `erkap_risk_identifications`
- Analisis risiko tidak auto-calc (skor dipilih user via AJAX dari master)
- Skema skor di seeder tidak konsisten dengan Prob × Dampak
- Peringkat risiko input manual
- Strategi perlakuan free text

**Yang Diperlukan**:
- Kolom `risk_type` (positive/negative) pada risk_identifications
- Auto-calc skor = Probabilitas × Dampak (1-25)
- Level otomatis berdasarkan skor (VL/L/M/H/VH)
- Ranking otomatis berdasarkan skor
- Enum strategi baku: Avoid/Reduce/Transfer/Accept

**Dampak**: FORM 1 tidak akurat, tidak ada konsistensi perhitungan risiko

## 2. Solusi yang Direkomendasikan

### 2.1 Tambah Kolom di `erkap_risk_identifications`

```sql
ALTER TABLE erkap_risk_identifications 
ADD COLUMN risk_direction ENUM('positive', 'negative') NOT NULL DEFAULT 'negative' AFTER risk;
```

### 2.2 Auto-Calc Skor di Backend

```php
// Di RiskAnalysisController
public function store(StoreRiskAnalysisRequest $request)
{
    $probability = RiskProbability::find($request->probability_id);
    $impact = RiskImpact::find($request->impact_id);
    
    // Auto-calc skor
    $calculatedScore = $probability->point * $impact->point;
    
    // Cari level berdasarkan skor
    $level = $this->getRiskLevel($calculatedScore);
    
    // Simpan
    $analysis = RiskAnalysis::create([
        'risk_identification_id' => $request->risk_identification_id,
        'probability_id' => $request->probability_id,
        'impact_id' => $request->impact_id,
        'calculated_score' => $calculatedScore,
        'risk_level' => $level,
    ]);
    
    // Auto-generate ranking
    $this->generateRanking($request->risk_identification_id, $calculatedScore);
    
    return redirect()->back()->with('success', 'Analisis risiko berhasil disimpan');
}

private function getRiskLevel($score)
{
    if ($score >= 20) return 'VH';      // Very High
    if ($score >= 15) return 'H';       // High
    if ($score >= 10) return 'M';       // Medium
    if ($score >= 5) return 'L';        // Low
    return 'VL';                         // Very Low
}

private function generateRanking($riskIdentificationId, $score)
{
    // Hitung ranking berdasarkan skor tertinggi
    $rank = RiskAnalysis::where('risk_identification_id', $riskIdentificationId)
        ->where('calculated_score', '>=', $score)
        ->count() + 1;
    
    RiskRanking::updateOrCreate(
        ['risk_identification_id' => $riskIdentificationId],
        ['ranking' => $rank, 'score' => $score]
    );
}
```

### 2.3 Enum Strategi Baku

```php
// Di DepartmentRiskStrategy
public static function getStrategies()
{
    return [
        'avoid' => 'Hindari',
        'reduce' => 'Kurangi',
        'transfer' => 'Transfer',
        'accept' => 'Terima',
    ];
}
```

## 3. Langkah-langkah Implementasi

### Phase 1: Migration (1 hari)

1. **Buat Migration `2026_09_11_000006_add_risk_direction_to_risk_identifications_table.php`**
   - Tambah kolom `risk_direction`

2. **Buat Migration `2026_09_11_000007_add_calculated_columns_to_risk_analysis_table.php`**
   - Tambah kolom `calculated_score` (int)
   - Tambah kolom `risk_level` (enum)

3. **Buat Migration `2026_09_11_000008_add_score_to_risk_rankings_table.php`**
   - Tambah kolom `score` (int)

4. **Buat Migration `2026_09_11_000009_add_strategy_enum_to_department_risk_strategies_table.php`**
   - Ubah kolom `strategy` dari text ke enum

### Phase 2: Model & Logic (2 hari)

1. **Update Model `RiskIdentification.php`**
   ```php
   protected $fillable = ['risk', 'department_target_id', 'risk_type_id', 'risk_taxonomy_id', 'risk_direction'];
   
   public function scopePositive($query)
   {
       return $query->where('risk_direction', 'positive');
   }
   
   public function scopeNegative($query)
   {
       return $query->where('risk_direction', 'negative');
   }
   ```

2. **Update Model `RiskAnalysis.php`**
   ```php
   protected $fillable = ['risk_identification_id', 'probability_id', 'impact_id', 'calculated_score', 'risk_level'];
   
   public function scopeByLevel($query, $level)
   {
       return $query->where('risk_level', $level);
   }
   ```

3. **Update Model `RiskRanking.php`**
   ```php
   protected $fillable = ['risk_identification_id', 'ranking', 'score'];
   ```

4. **Update Model `DepartmentRiskStrategy.php`**
   ```php
   public static function getStrategies()
   {
       return [
           'avoid' => 'Hindari',
           'reduce' => 'Kurangi',
           'transfer' => 'Transfer',
           'accept' => 'Terima',
       ];
   }
   ```

### Phase 3: Controller & Validasi (2 hari)

1. **Update Controller `RiskIdentificationController.php`**
   - Tambah validasi `risk_direction` required

2. **Update Controller `RiskAnalysisController.php`**
   - Implementasi auto-calc skor
   - Implementasi auto-generate ranking
   - Hapus logic AJAX `get-score-level`

3. **Update Controller `DepartmentRiskStrategyController.php`**
   - Gunakan enum strategi

4. **Update Form Request `StoreRiskIdentificationRequest.php`**
   - Tambah `risk_direction` required

5. **Update Form Request `StoreRiskAnalysisRequest.php`**
   - Hapus `risk_score_value_id` (tidak diperlukan lagi)

### Phase 4: View/Blade (2 hari)

1. **Update `resources/views/erkap/risk-identifications/create.blade.php`**
   - Tambah radio button "Positif" / "Negatif"

2. **Update `resources/views/erkap/risk-analyses/create.blade.php`**
   - Tampilkan perhitungan otomatis saat user pilih prob & dampak
   - Hapus dropdown skor
   - Tampilkan preview skor = prob × dampak

3. **Update `resources/views/erkap/department-risk-strategies/create.blade.php`**
   - Ganti free text dengan dropdown enum strategi

4. **Update `resources/views/erkap/risk-rankings/index.blade.php`**
   - Tampilkan ranking otomatis dari skor

## 4. File yang Perlu Dibuat/Diubah

### File Baru:
- `database/migrations/2026_09_11_000006_add_risk_direction_to_risk_identifications_table.php`
- `database/migrations/2026_09_11_000007_add_calculated_columns_to_risk_analysis_table.php`
- `database/migrations/2026_09_11_000008_add_score_to_risk_rankings_table.php`
- `database/migrations/2026_09_11_000009_add_strategy_enum_to_department_risk_strategies_table.php`

### File yang Diubah:
- `app/Models/Erkap/RiskIdentification.php`
- `app/Models/Erkap/RiskAnalysis.php`
- `app/Models/Erkap/RiskRanking.php`
- `app/Models/Erkap/DepartmentRiskStrategy.php`
- `app/Http/Controllers/Erkap/RiskIdentificationController.php`
- `app/Http/Controllers/Erkap/RiskAnalysisController.php`
- `app/Http/Controllers/Erkap/DepartmentRiskStrategyController.php`
- `app/Http/Requests/Erkap/StoreRiskIdentificationRequest.php`
- `app/Http/Requests/Erkap/StoreRiskAnalysisRequest.php`
- `resources/views/erkap/risk-identifications/create.blade.php`
- `resources/views/erkap/risk-analyses/create.blade.php`
- `resources/views/erkap/department-risk-strategies/create.blade.php`
- `resources/views/erkap/risk-rankings/index.blade.php`
- `routes/routers/erkap.php` - hapus route AJAX `get-score-level`

## 5. Estimasi Effort

| Task | Effort (hari) |
|------|--------------|
| Migration | 1 |
| Model & Logic | 2 |
| Controller & Validasi | 2 |
| View/Blade | 2 |
| Testing | 1 |
| **Total** | **8** |

## 6. Dependency

- Tabel `erkap_risk_identifications` sudah ada
- Tabel `erkap_risk_analysis` sudah ada
- Tabel `erkap_risk_rankings` sudah ada
- Tabel `erkap_department_risk_strategies` sudah ada
- Master data `erkap_risk_probabilities` dan `erkap_risk_impacts` sudah ada

## 7. Risk & Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| Data lama tidak punya `risk_direction` | Query salah | Default value 'negative' untuk data existing |
| Skor existing tidak konsisten | Ranking salah | Jalankan script migrasi untuk recalculate |
| User tidak paham enum strategi | Input salah | Tambah tooltip & validasi enum