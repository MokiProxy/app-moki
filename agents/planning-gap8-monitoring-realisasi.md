# Planning Implementasi: Monitoring & Realisasi

## 1. Deskripsi Gap

**Status Saat Ini**:
- Tidak ada tabel `budget_realizations`
- Tidak ada tabel `program_realizations`
- Tidak ada tabel `risk_assessments_monthly`
- Tidak ada tabel `performance_scorecards`
- Tidak ada integrasi data akuntansi untuk realisasi pendapatan/beban
- Tidak ada BvA (Budget vs Actual)
- Tidak ada monitoring % penyelesaian program kerja
- Tidak ada laporan bulanan/triwulan/tahunan

**Yang Diperlukan**:
- Tabel realisasi anggaran per bulan
- Tabel realisasi program kerja (% penyelesaian)
- Tabel risk assessment bulanan
- Tabel performance scorecard (KPI)
- Integrasi data akuntansi
- BvA (Budget vs Actual) per divisi/akun
- Laporan bulanan/triwulan/tahunan

**Dampak**: Phase 5 rancangan (Monitoring & Realisasi) tidak dapat dijalankan

## 2. Solusi yang Direkomendasikan

### 2.1 Tabel Baru: `budget_realizations`

```sql
CREATE TABLE erkap_budget_realizations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    erkap_rkap_id BIGINT UNSIGNED NOT NULL,
    erkap_routine_cost_id BIGINT UNSIGNED NULL,
    erkap_investment_plan_id BIGINT UNSIGNED NULL,
    month TINYINT NOT NULL,
    year SMALLINT NOT NULL,
    budgeted DECIMAL(15,2) DEFAULT 0,
    realized DECIMAL(15,2) DEFAULT 0,
    variance DECIMAL(15,2) DEFAULT 0,
    variance_percent DECIMAL(5,2) DEFAULT 0,
    source VARCHAR(50) DEFAULT 'manual',
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (erkap_rkap_id) REFERENCES erkap_rkap(id),
    FOREIGN KEY (erkap_routine_cost_id) REFERENCES erkap_routine_costs(id),
    FOREIGN KEY (erkap_investment_plan_id) REFERENCES erkap_investment_plans(id),
    UNIQUE KEY unique_realization (erkap_routine_cost_id, erkap_investment_plan_id, month, year)
);
```

### 2.2 Tabel Baru: `program_realizations`

```sql
CREATE TABLE erkap_program_realizations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    erkap_work_program_id BIGINT UNSIGNED NOT NULL,
    month TINYINT NOT NULL,
    year SMALLINT NOT NULL,
    target DECIMAL(15,2) DEFAULT 0,
    realized DECIMAL(15,2) DEFAULT 0,
    percent_complete DECIMAL(5,2) DEFAULT 0,
    status ENUM('on_progress', 'done', 'overdue') DEFAULT 'on_progress',
    notes TEXT NULL,
    evidence_url VARCHAR(500) NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (erkap_work_program_id) REFERENCES erkap_work_programs(id),
    UNIQUE KEY unique_program_realization (erkap_work_program_id, month, year)
);
```

### 2.3 Tabel Baru: `risk_assessments_monthly`

```sql
CREATE TABLE erkap_risk_assessments_monthly (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    erkap_risk_identification_id BIGINT UNSIGNED NOT NULL,
    month TINYINT NOT NULL,
    year SMALLINT NOT NULL,
    inherent_probability TINYINT NOT NULL,
    inherent_impact TINYINT NOT NULL,
    inherent_score INT NOT NULL,
    current_probability TINYINT NULL,
    current_impact TINYINT NULL,
    current_score INT NULL,
    residual_probability TINYINT NULL,
    residual_impact TINYINT NULL,
    residual_score INT NULL,
    mitigation_plan TEXT NULL,
    mitigation_status ENUM('on_progress', 'done', 'overdue') DEFAULT 'on_progress',
    risk_owner VARCHAR(255) NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (erkap_risk_identification_id) REFERENCES erkap_risk_identifications(id),
    UNIQUE KEY unique_risk_assessment (erkap_risk_identification_id, month, year)
);
```

### 2.4 Tabel Baru: `performance_scorecards`

```sql
CREATE TABLE erkap_performance_scorecards (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    erkap_rkap_id BIGINT UNSIGNED NOT NULL,
    erkap_department_target_id BIGINT UNSIGNED NOT NULL,
    quarter TINYINT NOT NULL,
    year SMALLINT NOT NULL,
    kpi_name VARCHAR(255) NOT NULL,
    kpi_target DECIMAL(15,2) DEFAULT 0,
    kpi_actual DECIMAL(15,2) DEFAULT 0,
    kpi_score DECIMAL(5,2) DEFAULT 0,
    weight DECIMAL(5,2) DEFAULT 0,
    weighted_score DECIMAL(5,2) DEFAULT 0,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (erkap_rkap_id) REFERENCES erkap_rkap(id),
    FOREIGN KEY (erkap_department_target_id) REFERENCES erkap_department_targets(id)
);
```

## 3. Langkah-langkah Implementasi

### Phase 1: Migration & Seeder (1-2 hari)

1. **Buat Migration `2026_09_11_000018_create_budget_realizations_table.php`**
2. **Buat Migration `2026_09_11_000019_create_program_realizations_table.php`**
3. **Buat Migration `2026_09_11_000020_create_risk_assessments_monthly_table.php`**
4. **Buat Migration `2026_09_11_000021_create_performance_scorecards_table.php`**

### Phase 2: Model & Relasi (2 hari)

1. **Buat Model `BudgetRealization.php`**
   ```php
   class BudgetRealization extends Model
   {
       protected $table = 'erkap_budget_realizations';
       
       public function rkap()
       {
           return $this->belongsTo(RKAP::class, 'erkap_rkap_id');
       }
       
       public function routineCost()
       {
           return $this->belongsTo(RoutineCost::class, 'erkap_routine_cost_id');
       }
       
       public function investmentPlan()
       {
           return $this->belongsTo(InvestmentPlan::class, 'erkap_investment_plan_id');
       }
       
       public function calculateVariance()
       {
           $this->variance = $this->realized - $this->budgeted;
           $this->variance_percent = $this->budgeted > 0 
               ? (($this->realized - $this->budgeted) / $this->budgeted) * 100 
               : 0;
           $this->save();
       }
   }
   ```

2. **Buat Model `ProgramRealization.php`**
   ```php
   class ProgramRealization extends Model
   {
       protected $table = 'erkap_program_realizations';
       
       public function workProgram()
       {
           return $this->belongsTo(WorkProgram::class, 'erkap_work_program_id');
       }
       
       public function calculatePercentComplete()
       {
           $this->percent_complete = $this->target > 0 
               ? ($this->realized / $this->target) * 100 
               : 0;
           
           $this->status = match(true) {
               $this->percent_complete >= 100 => 'done',
               $this->percent_complete > 0 => 'on_progress',
               default => 'on_progress',
           };
           
           $this->save();
       }
   }
   ```

3. **Buat Model `RiskAssessmentMonthly.php`**
   ```php
   class RiskAssessmentMonthly extends Model
   {
       protected $table = 'erkap_risk_assessments_monthly';
       
       public function riskIdentification()
       {
           return $this->belongsTo(RiskIdentification::class, 'erkap_risk_identification_id');
       }
       
       public function calculateScores()
       {
           $this->inherent_score = $this->inherent_probability * $this->inherent_impact;
           $this->current_score = $this->current_probability * $this->current_impact;
           $this->residual_score = $this->residual_probability * $this->residual_impact;
           $this->save();
       }
   }
   ```

4. **Buat Model `PerformanceScorecard.php`**
   ```php
   class PerformanceScorecard extends Model
   {
       protected $table = 'erkap_performance_scorecards';
       
       public function rkap()
       {
           return $this->belongsTo(RKAP::class, 'erkap_rkap_id');
       }
       
       public function departmentTarget()
       {
           return $this->belongsTo(DepartmentTarget::class, 'erkap_department_target_id');
       }
       
       public function calculateWeightedScore()
       {
           $this->kpi_score = $this->kpi_target > 0 
               ? ($this->kpi_actual / $this->kpi_target) * 100 
               : 0;
           $this->weighted_score = $this->kpi_score * ($this->weight / 100);
           $this->save();
       }
   }
   ```

### Phase 3: Controller & Logic (3-4 hari)

1. **Buat Controller `BudgetRealizationController.php`**
   - `index()` - daftar realisasi anggaran
   - `store()` - input realisasi
   - `calculateBvA()` - hitung BvA (Budget vs Actual)
   - `import()` - import dari sistem akuntansi

2. **Buat Controller `ProgramRealizationController.php`**
   - `index()` - daftar realisasi program
   - `store()` - input realisasi
   - `calculateProgress()` - hitung % penyelesaian

3. **Buat Controller `RiskAssessmentMonthlyController.php`**
   - `index()` - daftar risk assessment bulanan
   - `store()` - input risk assessment
   - `calculateScores()` - hitung inherent/current/residual score

4. **Buat Controller `PerformanceScorecardController.php`**
   - `index()` - daftar KPI scorecard
   - `store()` - input KPI
   - `calculateWeightedScore()` - hitung weighted score

5. **Logic BvA (Budget vs Actual)**
   ```php
   public function calculateBvA($erkapRkapId, $divisionId, $month)
   {
       $budget = RoutineCost::whereHas('workProgram.riskIdentification.departmentTarget', function($q) use ($divisionId) {
           $q->where('division_id', $divisionId);
       })->sum("{$this->getMonthName($month)}_cost");
       
       $actual = BudgetRealization::where('erkap_rkap_id', $erkapRkapId)
           ->whereHas('routineCost.workProgram.riskIdentification.departmentTarget', function($q) use ($divisionId) {
               $q->where('division_id', $divisionId);
           })
           ->where('month', $month)
           ->sum('realized');
       
       return [
           'budget' => $budget,
           'actual' => $actual,
           'variance' => $actual - $budget,
           'variance_percent' => $budget > 0 ? (($actual - $budget) / $budget) * 100 : 0,
       ];
   }
   ```

### Phase 4: View/Blade (3-4 hari)

1. **Buat `resources/views/erkap/budget-realizations/index.blade.php`**
   - Tabel BvA per divisi
   - Kolom: Budget, Actual, Variance, Variance %
   - Filter per bulan

2. **Buat `resources/views/erkap/budget-realizations/create.blade.php`**
   - Form input realisasi
   - Import Excel

3. **Buat `resources/views/erkap/program-realizations/index.blade.php`**
   - Tabel realisasi program kerja
   - Progress bar % penyelesaian
   - Status badge (on_progress/done/overdue)

4. **Buat `resources/views/erkap/risk-assessments-monthly/index.blade.php`**
   - Tabel risk assessment bulanan
   - Kolom: Inherent, Current, Residual
   - Mitigation status

5. **Buat `resources/views/erkap/performance-scorecards/index.blade.php`**
   - Tabel KPI scorecard
   - Kolom: KPI, Target, Actual, Score, Weight, Weighted Score
   - Total weighted score

### Phase 5: Integrasi Akuntansi (1-2 hari)

1. **Buat Service `AccountingIntegrationService.php`**
   ```php
   class AccountingIntegrationService
   {
       public function importRealization($erkapRkapId, $month, $year)
       {
           // Ambil data dari sistem akuntansi (API/Database)
           $realizations = $this->fetchFromAccounting($month, $year);
           
           foreach ($realizations as $item) {
               BudgetRealization::updateOrCreate(
                   [
                       'erkap_rkap_id' => $erkapRkapId,
                       'erkap_routine_cost_id' => $item['routine_cost_id'],
                       'month' => $month,
                       'year' => $year,
                   ],
                   [
                       'realized' => $item['amount'],
                       'source' => 'accounting',
                   ]
               );
           }
       }
       
       private function fetchFromAccounting($month, $year)
       {
           // Logic untuk mengambil data dari sistem akuntansi
           // Bisa via API, database view, atau file import
       }
   }
   ```

2. **Buat Route Import**
   ```php
   Route::post('/budget-realizations/import', [BudgetRealizationController::class, 'import'])->name('budget-realizations.import');
   ```

## 4. File yang Perlu Dibuat/Diubah

### File Baru:
- `database/migrations/2026_09_11_000018_create_budget_realizations_table.php`
- `database/migrations/2026_09_11_000019_create_program_realizations_table.php`
- `database/migrations/2026_09_11_000020_create_risk_assessments_monthly_table.php`
- `database/migrations/2026_09_11_000021_create_performance_scorecards_table.php`
- `app/Models/Erkap/BudgetRealization.php`
- `app/Models/Erkap/ProgramRealization.php`
- `app/Models/Erkap/RiskAssessmentMonthly.php`
- `app/Models/Erkap/PerformanceScorecard.php`
- `app/Http/Controllers/Erkap/BudgetRealizationController.php`
- `app/Http/Controllers/Erkap/ProgramRealizationController.php`
- `app/Http/Controllers/Erkap/RiskAssessmentMonthlyController.php`
- `app/Http/Controllers/Erkap/PerformanceScorecardController.php`
- `app/Http/Requests/Erkap/StoreBudgetRealizationRequest.php`
- `app/Http/Requests/Erkap/StoreProgramRealizationRequest.php`
- `app/Http/Requests/Erkap/StoreRiskAssessmentMonthlyRequest.php`
- `app/Http/Requests/Erkap/StorePerformanceScorecardRequest.php`
- `app/Services/AccountingIntegrationService.php`
- `resources/views/erkap/budget-realizations/index.blade.php`
- `resources/views/erkap/budget-realizations/create.blade.php`
- `resources/views/erkap/program-realizations/index.blade.php`
- `resources/views/erkap/program-realizations/create.blade.php`
- `resources/views/erkap/risk-assessments-monthly/index.blade.php`
- `resources/views/erkap/risk-assessments-monthly/create.blade.php`
- `resources/views/erkap/performance-scorecards/index.blade.php`
- `resources/views/erkap/performance-scorecards/create.blade.php`

### File yang Diubah:
- `routes/routers/erkap.php` - tambah route
- `database/seeders/DatabaseSeeder.php` - tambah seeder test

## 5. Estimasi Effort

| Task | Effort (hari) |
|------|--------------|
| Migration & Seeder | 1-2 |
| Model & Relasi | 2 |
| Controller & Logic | 3-4 |
| View/Blade | 3-4 |
| Integrasi Akuntansi | 1-2 |
| Testing | 1-2 |
| **Total** | **11-16** |

## 6. Dependency

- Semua tabel dokumen ERKAP sudah ada
- Sistem akuntansi eksternal (untuk integrasi)
- Data budget dari planning gap 4 & 5

## 7. Risk & Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| Integrasi akuntansi sulit | Data tidak real-time | Manual import sebagai fallback |
| Data tidak lengkap | Kalkulasi salah | Validasi data sebelum kalkulasi |
| Performa query monitoring | Lambat | Gunakan caching & summary table |
| Concurrent update | Data corrupt | Gunakan locking |