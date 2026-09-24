# Planning Implementasi: Dashboard & Reporting

## 1. Deskripsi Gap

**Status Saat Ini**:
- Dashboard ERKAP hanya halaman statis "Halo, {nama}"
- Tidak ada widget data
- Tidak ada chart/grafik
- Tidak ada export Excel/PDF
- Tidak ada drill-down data

**Yang Diperlukan**:
- Dashboard dengan widget data real-time
- Risk Heat Map
- Gantt Chart Program Kerja
- Budget Dashboard (OPEX vs CAPEX)
- P&L Dashboard
- Export Excel/PDF sesuai format Form 1-6
- Drill-down dari ringkasan ke detail

**Dampak**: Tidak ada visibilitas data ERKAP, tidak ada reporting

## 2. Solusi yang Direkomendasikan

### 2.1 Widget Dashboard

1. **Executive Summary**
   - Total Anggaran (OPEX + CAPEX)
   - Total Program Kerja
   - Jumlah Risiko (Positif/Negatif)
   - Rata-rata Rating

2. **Risk Heat Map**
   - Peta risiko berdasarkan Prob x Dampak
   - Warna berdasarkan level (VL/L/M/H/VH)
   - Klik untuk drill-down

3. **Budget Dashboard**
   - OPEX vs CAPEX
   - Realisasi vs Budget (BvA)
   - Variance per divisi

4. **Program Dashboard**
   - Jumlah program per status
   - % Penyelesaian rata-rata
   - Gantt chart sederhana

5. **P&L Dashboard**
   - Pendapatan vs Beban
   - Margin per bulan
   - Tren laba rugi

### 2.2 Export Excel/PDF

```php
// Menggunakan Maatwebsite Excel
class WorkProgramExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return WorkProgram::with(['riskIdentification.departmentTarget', 'routineCosts'])
            ->get();
    }
    
    public function headings(): array
    {
        return [
            'No', 'Program Kerja', 'Sasaran', 'Rating',
            'Jan', 'Feb', 'Mar', ..., 'Des', 'Total'
        ];
    }
}

// Menggunakan DomPDF
class RoutineCostPdf extends Pdf implements WithView
{
    public function view(): string
    {
        return 'erkap.exports.routine-cost-pdf';
    }
}
```

## 3. Langkah-langkah Implementasi

### Phase 1: Dashboard Widget (3-4 hari)

1. **Update Controller `DashboardController.php`**
   ```php
   public function index()
   {
       $erkapRkap = RKAP::latest()->first();
       
       $data = [
           'totalBudget' => $this->getTotalBudget($erkapRkap),
           'totalWorkPrograms' => WorkProgram::whereHas('riskIdentification', function($q) use ($erkapRkap) {
               $q->whereHas('departmentTarget.companyTarget', function($q2) use ($erkapRkap) {
                   $q2->where('erkap_rkap_id', $erkapRkap->id);
               });
           })->count(),
           'totalRisks' => RiskIdentification::whereHas('departmentTarget.companyTarget', function($q) use ($erkapRkap) {
               $q->where('erkap_rkap_id', $erkapRkap->id);
           })->count(),
           'averageRating' => $this->getAverageRating($erkapRkap),
           'riskHeatMap' => $this->getRiskHeatMap($erkapRkap),
           'budgetComparison' => $this->getBudgetComparison($erkapRkap),
           'programStatus' => $this->getProgramStatus($erkapRkap),
           'profitLoss' => $this->getProfitLoss($erkapRkap),
       ];
       
       return view('erkap.dashboard.index', $data);
   }
   
   private function getRiskHeatMap($erkapRkap)
   {
       return RiskAnalysis::whereHas('riskIdentification.departmentTarget.companyTarget', function($q) use ($erkapRkap) {
           $q->where('erkap_rkap_id', $erkapRkap->id);
       })->selectRaw('probability_id, impact_id, COUNT(*) as count')
           ->groupBy('probability_id', 'impact_id')
           ->get();
   }
   ```

2. **Buat Widget Components**
   - `resources/views/erkap/dashboard/widgets/executive-summary.blade.php`
   - `resources/views/erkap/dashboard/widgets/risk-heatmap.blade.php`
   - `resources/views/erkap/dashboard/widgets/budget-comparison.blade.php`
   - `resources/views/erkap/dashboard/widgets/program-status.blade.php`
   - `resources/views/erkap/dashboard/widgets/profit-loss.blade.php`

3. **Update View `resources/views/erkap/dashboard/index.blade.php`**
   - Grid layout dengan widget
   - Responsive design

### Phase 2: Chart & Visualisasi (2-3 hari)

1. **Integrasi Chart.js atau Highcharts**
   ```html
   <!-- Risk Heat Map -->
   <div id="riskHeatMap" style="width: 100%; height: 400px;"></div>
   
   <script>
   Highcharts.chart('riskHeatMap', {
       chart: { type: 'heatmap' },
       title: { text: 'Risk Heat Map' },
       xAxis: { categories: ['1', '2', '3', '4', '5'] },
       yAxis: { categories: ['1', '2', '3', '4', '5'] },
       series: [{
           data: @json($riskHeatMap),
           borderWidth: 1
       }]
   });
   </script>
   ```

2. **Buat Chart Components**
   - `resources/views/erkap/dashboard/charts/risk-heatmap.blade.php`
   - `resources/views/erkap/dashboard/charts/budget-bar.blade.php`
   - `resources/views/erkap/dashboard/charts/program-pie.blade.php`
   - `resources/views/erkap/dashboard/charts/profit-loss-line.blade.php`

### Phase 3: Export Excel (2-3 hari)

1. **Buat Export Classes**
   - `app/Exports/Erkap/WorkProgramExport.php`
   - `app/Exports/Erkap/RoutineCostExport.php`
   - `app/Exports/Erkap/RiskIdentificationExport.php`
   - `app/Exports/Erkap/InvestmentPlanExport.php`
   - `app/Exports/Erkap/BudgetConsolidationExport.php`

2. **Update Controller dengan Export**
   ```php
   // Di WorkProgramController
   public function export(Request $request)
   {
       $format = $request->get('format', 'xlsx');
       
       return Excel::download(
           new WorkProgramExport($request->all()),
           "work-programs-{$format}.{$format}"
       );
   }
   ```

3. **Tambah Route Export**
   ```php
   Route::get('/work-programs/export', [WorkProgramController::class, 'export'])->name('work-programs.export');
   Route::get('/routine-costs/export', [RoutineCostController::class, 'export'])->name('routine-costs.export');
   Route::get('/risk-identifications/export', [RiskIdentificationController::class, 'export'])->name('risk-identifications.export');
   ```

### Phase 4: Export PDF (1-2 hari)

1. **Buat PDF Views**
   - `resources/views/erkap/exports/work-program-pdf.blade.php`
   - `resources/views/erkap/exports/routine-cost-pdf.blade.php`
   - `resources/views/erkap/exports/risk-identification-pdf.blade.php`

2. **Buat PDF Classes**
   - `app/PDFs/Erkap/WorkProgramPdf.php`
   - `app/PDFs/Erkap/RoutineCostPdf.php`
   - `app/PDFs/Erkap/RiskIdentificationPdf.php`

3. **Update Controller dengan PDF Export**
   ```php
   // Di WorkProgramController
   public function exportPdf(Request $request)
   {
       $pdf = PDF::loadView('erkap.exports.work-program-pdf', [
           'data' => WorkProgram::with(['riskIdentification.departmentTarget', 'routineCosts'])->get()
       ]);
       
       return $pdf->download('work-programs.pdf');
   }
   ```

### Phase 5: Drill-Down (1-2 hari)

1. **Implementasi AJAX Drill-Down**
   ```javascript
   // Di dashboard.blade.php
   $('#riskHeatMap .point').click(function() {
       const probabilityId = $(this).data('probability');
       const impactId = $(this).data('impact');
       
       $.ajax({
           url: '{{ route("erkap.dashboard.risk-detail") }}',
           data: { probability_id: probabilityId, impact_id: impactId },
           success: function(response) {
               $('#riskDetail').html(response.html);
               $('#riskDetailModal').modal('show');
           }
       });
   });
   ```

2. **Buat Route AJAX**
   ```php
   Route::get('/dashboard/risk-detail', [DashboardController::class, 'riskDetail'])->name('dashboard.risk-detail');
   Route::get('/dashboard/budget-detail', [DashboardController::class, 'budgetDetail'])->name('dashboard.budget-detail');
   ```

3. **Buat View Modal**
   - `resources/views/erkap/dashboard/modals/risk-detail.blade.php`
   - `resources/views/erkap/dashboard/modals/budget-detail.blade.php`

## 4. File yang Perlu Dibuat/Diubah

### File Baru:
- `app/Exports/Erkap/WorkProgramExport.php`
- `app/Exports/Erkap/RoutineCostExport.php`
- `app/Exports/Erkap/RiskIdentificationExport.php`
- `app/Exports/Erkap/InvestmentPlanExport.php`
- `app/Exports/Erkap/BudgetConsolidationExport.php`
- `app/PDFs/Erkap/WorkProgramPdf.php`
- `app/PDFs/Erkap/RoutineCostPdf.php`
- `app/PDFs/Erkap/RiskIdentificationPdf.php`
- `resources/views/erkap/dashboard/widgets/executive-summary.blade.php`
- `resources/views/erkap/dashboard/widgets/risk-heatmap.blade.php`
- `resources/views/erkap/dashboard/widgets/budget-comparison.blade.php`
- `resources/views/erkap/dashboard/widgets/program-status.blade.php`
- `resources/views/erkap/dashboard/widgets/profit-loss.blade.php`
- `resources/views/erkap/dashboard/charts/risk-heatmap.blade.php`
- `resources/views/erkap/dashboard/charts/budget-bar.blade.php`
- `resources/views/erkap/dashboard/charts/program-pie.blade.php`
- `resourcesviews/erkap/dashboard/charts/profit-loss-line.blade.php`
- `resources/views/erkap/exports/work-program-pdf.blade.php`
- `resources/views/erkap/exports/routine-cost-pdf.blade.php`
- `resources/views/erkap/exports/risk-identification-pdf.blade.php`
- `resources/views/erkap/dashboard/modals/risk-detail.blade.php`
- `resources/views/erkap/dashboard/modals/budget-detail.blade.php`

### File yang Diubah:
- `app/Http/Controllers/Erkap/DashboardController.php` - tambah data & logic
- `resources/views/erkap/dashboard/index.blade.php` - update dengan widget
- `app/Http/Controllers/Erkap/WorkProgramController.php` - tambah export
- `app/Http/Controllers/Erkap/RoutineCostController.php` - tambah export
- `app/Http/Controllers/Erkap/RiskIdentificationController.php` - tambah export
- `routes/routers/erkap.php` - tambah route export & dashboard

## 5. Estimasi Effort

| Task | Effort (hari) |
|------|--------------|
| Dashboard Widget | 3-4 |
| Chart & Visualisasi | 2-3 |
| Export Excel | 2-3 |
| Export PDF | 1-2 |
| Drill-Down | 1-2 |
| Testing | 1-2 |
| **Total** | **10-16** |

## 6. Dependency

- Semua tabel data ERKAP sudah ada
- Maatwebsite Excel sudah terinstall
- DomPDF sudah terinstall
- Highcharts/Chart.js (frontend library)

## 7. Risk & Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| Data terlalu banyak | Dashboard lambat | Gunakan caching & pagination |
| Chart tidak responsive | Tampilan rusak | Gunakan responsive library |
| Export error | Download gagal | Validasi data sebelum export |
| Performance query dashboard | Timeout | Gunakan summary table & index