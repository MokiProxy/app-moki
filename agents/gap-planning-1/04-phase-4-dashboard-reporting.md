# Phase 4: Dashboard & Reporting Engine

**Priority**: 🟡 Medium  
**Timeline**: Week 5-7 (10-15 days)  
**Dependencies**: Phase 1-3 (data must be correct for dashboards)

---

## 4.1 Dashboard Missing Widgets

### 4.1.1 Executive Summary Dashboard (Section 4.1)
**Missing**: Widget 6 - Cash Flow Position

**Implementation**:
- **File**: `app/Http/Controllers/Dashboard/ExecutiveDashboardController.php`
- **Service**: `app/Services/Dashboard/CashFlowWidgetService.php`
- **Data**: 
  - Operating cash flow (from P&L + working capital changes)
  - Investing cash flow (CAPEX payments from InvestmentPlan)
  - Financing cash flow (loan proceeds/repayments - future)
- **Visualization**: Waterfall chart or grouped bar chart
- **Period**: Monthly + YTD + Forecast

### 4.1.2 Risk Dashboard (Section 4.2)
**Missing**: Widget 3 - Risk Appetite Meter, Widget 5 - Risk Trend

**Widget 3: Risk Appetite Meter**
- **Service**: `app/Services/Dashboard/RiskAppetiteWidgetService.php`
- **Data**: Current risk exposure vs defined appetite thresholds
- **Visual**: Gauge chart (green/yellow/red zones)
- **Drill-down**: Click → Risk Dashboard filtered by appetite breach

**Widget 5: Risk Trend**
- **Service**: `app/Services/Dashboard/RiskTrendWidgetService.php`
- **Data**: Monthly risk count by level (VL/L/M/H/VH) over 12 months
- **Visual**: Stacked area chart or line chart
- **Filter**: By division, risk taxonomy, risk type

### 4.1.3 Program Dashboard (Section 4.3)
**Missing**: Widget 1 - Gantt Chart, Widget 4 - Dependency Map

**Widget 1: Gantt Chart**
- **Library**: `frappe-gantt` or `svg-gantt` (lightweight, no heavy deps)
- **File**: `resources/js/components/GanttChart.vue` (or Blade + JS)
- **Data**: WorkSchedule activities with start/end dates (derive from monthly)
- **Features**: 
  - Timeline view (months)
  - Progress bars (from realization %)
  - Critical path highlighting
  - Click → navigate to WorkSchedule detail

**Widget 4: Dependency Map**
- **Library**: `cytoscape.js` or `vis-network`
- **Data**: WorkProgram dependencies (new field: `depends_on_work_program_id`)
- **Visual**: Directed graph, nodes=programs, edges=dependencies
- **Features**: Zoom, pan, highlight critical path

### 4.1.4 Budget Dashboard (Section 4.4)
**Missing**: Widget 2 - Cost Center Heatmap, Widget 5 - Variance Analysis Top 10

**Widget 2: Cost Center Heatmap**
- **Service**: `app/Services/Dashboard/CostCenterHeatmapService.php`
- **Data**: Variance % by cost_center (rows) × month (columns)
- **Visual**: Heatmap grid (red=over budget, green=under)
- **Drill-down**: Click cell → BudgetRealization detail for that cost_center/month

**Widget 5: Variance Analysis Top 10**
- **Service**: `app/Services/Dashboard/TopVarianceService.php`
- **Data**: Top 10 cost elements by absolute variance (budget vs actual)
- **Visual**: Horizontal bar chart with variance % labels
- **Period**: Current month, YTD, selected period

### 4.1.5 P&L Dashboard (Section 4.5)
**Missing**: Widget 3 - Revenue Stream Breakdown, Widget 4 - Expense Category Breakdown, Widget 5 - Scenario Comparison

**Widget 3: Revenue Stream Breakdown**
- **Service**: `app/Services/Dashboard/RevenueBreakdownService.php`
- **Data**: Revenue by category (CoA type=revenue) monthly
- **Visual**: Stacked bar chart or treemap

**Widget 4: Expense Category Breakdown**
- **Service**: `app/Services/Dashboard/ExpenseBreakdownService.php`
- **Data**: Expenses by CoA category (personnel, operational, maintenance, etc.)
- **Visual**: Pie/donut for current month, stacked bar for trend

**Widget 5: Scenario Comparison**
- **Data**: P&L Scenario results (base, optimistic, pessimistic)
- **Visual**: Grouped bar chart or line chart with 3 lines
- **Metrics**: Revenue, EBITDA, Net Profit, Cash Flow

---

## 4.2 Drill-Down Analytics (Section 4.6)

### 4.2.1 Transaction-Level Drill-Down
**Requirement**: "Detail ke level transaksi akuntansi" (Section 6.6)

**Implementation**:
- **Route**: `/dashboard/drilldown/{type}/{id}` (type: budget, realization, pnl)
- **Controller**: `DashboardDrilldownController`
- **Service**: `app/Services/Dashboard/DrilldownService.php`
- **Data Sources**:
  - Budget: RoutineCost/InvestmentPlan line items
  - Realization: BudgetRealization entries
  - P&L: Journal entries from accounting integration (future)
- **UI**: Modal or new page with DataTable (server-side processing)
- **Export**: Excel from drill-down view

---

## 4.3 Reporting Engine (Phase 5 Step 5.7)

### 4.3.1 Report Types & Templates

| Report | Template | Frequency | Output |
|--------|----------|-----------|--------|
| **Laporan RKAP** | `resources/views/reports/rkap.blade.php` | Annual | PDF + Excel |
| **Laporan Keuangan (P&L)** | `resources/views/reports/financial.blade.php` | Monthly/Quarterly/Annual | PDF + Excel |
| **Risk Report** | `resources/views/reports/risk.blade.php` | Monthly/Quarterly | PDF |
| **Realization Report** | `resources/views/reports/realization.blade.php` | Monthly | PDF + Excel |
| **Performance Report** | `resources/views/reports/performance.blade.php` | Quarterly/Annual | PDF |

### 4.3.2 Reporting Architecture

**Core Service**: `app/Services/Reporting/ReportGenerator.php`

```php
class ReportGenerator {
    public function generate(string $reportType, array $params): ReportResult
    {
        $data = $this->collectData($reportType, $params);
        $html = view("reports.{$reportType}", $data)->render();
        
        return match($params['format']) {
            'pdf' => $this->generatePdf($html, $params),
            'excel' => $this->generateExcel($data, $params),
            default => $html
        };
    }
}
```

**PDF Generation**: `barryvdh/laravel-dompdf` (already in Laravel ecosystem)
**Excel Generation**: `maatwebsite/excel` (already used)

### 4.3.3 Scheduled Reports

**Console Command**: `app/Console/Commands/GenerateScheduledReports.php`
- **Schedule** (in `app/Console/Kernel.php`):
  - Monthly: 1st of month, 02:00 - Monthly reports
  - Quarterly: 1st of Apr/Jul/Oct/Jan, 03:00 - Quarterly reports
  - Annual: Jan 1, 04:00 - Annual reports
- **Delivery**: Email to stakeholders + store in `storage/app/reports/`
- **Notification**: Database + email when report ready

### 4.3.4 Consolidated Workbook Export

**Requirement**: Single Excel file with all Forms (1-6) as sheets

**Implementation**: `app/Exports/ConsolidatedRkapExport.php` (Maatwebsite Excel)
- Sheet 1: Form 1 (Sasaran & Risiko)
- Sheet 2: Form 2 (Jadwal Pelaksanaan)
- Sheet 3: Form 3 (Biaya Rutin)
- Sheet 4: Form 4 (Biaya Investasi)
- Sheet 5: Form 5 (Ringkasan Investasi)
- Sheet 6: Form 6 (Risk Assessment)
- Sheet 7: RKAP Consolidation
- Sheet 8: P&L Summary

---

## 4.4 Chart Library Integration

### 4.4.1 Recommended: Chart.js (Lightweight, Canvas)
**Installation**: `npm install chart.js`
**Integration**: 
- `resources/js/dashboard/charts.js` - Chart factory functions
- Blade components: `<x-chart.type :data="..." :options="..." />`

### 4.4.2 Chart Components Needed
| Component | Chart Type | Used In |
|-----------|------------|---------|
| `BarChart` | Bar | Variance Top 10, Revenue/Expense breakdown |
| `LineChart` | Line | Risk Trend, P&L Trend, Scenario Comparison |
| `GaugeChart` | Doughnut (custom) | Risk Appetite Meter |
| `HeatmapChart` | Custom (canvas) | Cost Center Heatmap |
| `GanttChart` | Custom (frappe-gantt) | Program Dashboard |
| `NetworkChart` | Custom (cytoscape) | Dependency Map |
| `WaterfallChart` | Custom | Cash Flow Position |
| `StackedBarChart` | Bar (stacked) | Revenue/Expense monthly |

---

## 4.5 Implementation Tasks Checklist

### Dashboard Widgets
- [x] Cash Flow Position widget (Executive)
- [x] Risk Appetite Meter widget (Risk)
- [x] Risk Trend widget (Risk)
- [x] Gantt Chart widget (Program) - berbasis tabel (frappe-gantt tidak dipakai, sesuai constraint)
- [x] Dependency Map widget (Program) - berbasis tabel (cytoscape.js tidak dipakai, sesuai constraint)
- [x] Cost Center Heatmap widget (Budget)
- [x] Variance Top 10 widget (Budget)
- [x] Revenue Breakdown widget (P&L)
- [x] Expense Breakdown widget (P&L)
- [x] Scenario Comparison widget (P&L)

### Drill-Down
- [x] DrilldownController + routes
- [x] DrilldownService with data collectors
- [x] UI: Modal with DataTable (server-side)
- [x] Export from drill-down

### Reporting Engine
- [x] ReportGenerator core service
- [x] Report templates (Blade views) for 5 report types
- [x] PDF generation setup (dompdf)
- [x] Consolidated Excel export (Maatwebsite)
- [x] Scheduled report command + kernel schedule
- [x] Email delivery + storage
- [x] Report history/listing UI

### Chart Infrastructure
- [x] Integrasi charting via ApexCharts (menggantikan Chart.js); Gantt & Dependency Map berbasis tabel
- [x] Create chart Blade components
- [x] Create chart JS factory functions
- [x] Add chart containers to dashboard views
- [x] Wire up data endpoints (API or inline)

---

## 4.6 Testing Strategy

### Unit Tests
- `ReportGeneratorTest` - All report types generate without error
- `DashboardWidgetServicesTest` - Each service returns correct data shape
- `ConsolidatedExportTest` - All 8 sheets present, data matches

### Feature Tests
- `ScheduledReportsTest` - Command runs, files created, emails sent
- `DrilldownTest` - Returns correct transaction-level data
- `DashboardIntegrationTest` - All widgets load on dashboard pages

### Visual Regression (Optional)
- Screenshot comparison for chart rendering