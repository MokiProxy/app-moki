<?php

namespace App\Services\Reporting;

use App\Exports\Erkap\ReportExcelExport;
use App\Models\Erkap\RevenuePlan;
use App\Services\Simka\DashboardSummaryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class ReportGenerator
{
    public const TYPES = ['rkap', 'financial', 'risk', 'realization', 'performance', 'combined'];

    public function generate(string $reportType, array $params, string $format = 'html')
    {
        if (! in_array($reportType, self::TYPES, true)) {
            abort(404, 'Tipe laporan tidak dikenal.');
        }

        $data = $this->collectData($reportType, $params);
        $view = view("reports.{$reportType}", $data);

        return match ($format) {
            'pdf' => $this->generatePdf($view, $reportType, $params),
            'excel' => $this->generateExcel($data, $params),
            default => $view,
        };
    }

    public function generatePdf(View $view, string $reportType, array $params)
    {
        $pdf = Pdf::loadView($view->getName(), $view->getData());
        $pdf->setOption('isRemoteEnabled', true);
        $pdf->setPaper('a4', 'landscape');

        return $pdf;
    }

    public function generateExcel(array $data, array $params)
    {
        $rows = $data['rows'] ?? collect();
        if ($rows instanceof Collection) {
            $rows = $rows->map(fn ($row) => is_array($row) ? $row : $row->toArray());
        }

        return Excel::download(
            new ReportExcelExport($rows, $data['title'] ?? 'Laporan'),
            ($params['filename'] ?? 'laporan') . '.xlsx'
        );
    }

    public function renderHtml(string $reportType, array $params): string
    {
        return $this->generate($reportType, $params, 'html')->render();
    }

    public function store(string $reportType, array $params, string $format, ?string $filename = null): string
    {
        $data = $this->collectData($reportType, $params);
        $year = $params['year'] ?? now()->year;
        $slug = $reportType;
        $stamp = now()->format('Ymd_His');

        if ($format === 'excel') {
            $file = ($filename ?: "reports/{$slug}-{$year}") . "_{$stamp}.xlsx";
            Excel::store(new ReportExcelExport($this->toArrayRows($data), $data['title'] ?? 'Laporan'), $file, 'public');

            return $file;
        }

        $file = ($filename ?: "reports/{$slug}-{$year}") . "_{$stamp}.pdf";
        $view = view("reports.{$reportType}", $data);
        $pdf = Pdf::loadView($view->getName(), $view->getData());
        $pdf->setOption('isRemoteEnabled', true);
        $pdf->setPaper('a4', 'landscape');
        $pdf->save(storage_path('app/public/' . $file));

        return $file;
    }

    protected function toArrayRows(array $data): array
    {
        $rows = $data['rows'] ?? [];

        return collect($rows)->map(function ($row) {
            if (is_array($row)) {
                return $row;
            }

            $values = [];

            foreach ($row->getAttributes() as $key => $value) {
                try {
                    $values[$key] = $row->{$key};
                } catch (\Throwable $e) {
                    $values[$key] = $value;
                }
            }

            return $values;
        })->values()->all();
    }

    public function collectData(string $reportType, array $params): array
    {
        return match ($reportType) {
            'rkap' => $this->rkapData($params),
            'financial' => $this->financialData($params),
            'risk' => $this->riskData($params),
            'realization' => $this->realizationData($params),
            'performance' => $this->performanceData($params),
            'combined' => $this->combinedData($params),
        };
    }

    protected function combinedData(array $params): array
    {
        $rkap = $params['rkap'] ?? null;
        $divisionId = $params['division_id'] ?? null;
        $year = $params['year'] ?? now()->year;
        $domain = $params['domain'] ?? 'all';

        if (! in_array($domain, ['all', 'revenue', 'expense'], true)) {
            $domain = 'all';
        }

        $summary = app(DashboardSummaryService::class)->build($rkap, $divisionId);

        $revenueRows = [
            ['Komponen' => 'Pendapatan (Rencana)', 'Nilai' => $summary['revenue_plan']],
            ['Komponen' => 'Skema SPPH', 'Nilai' => $summary['spph']['schemes']],
            ['Komponen' => 'Baris Tarif SPPH', 'Nilai' => $summary['spph']['tarif_entries']],
            ['Komponen' => 'Kategori Pendapatan SIMKA', 'Nilai' => $summary['spph']['revenue_categories']],
            ['Komponen' => 'Kategori Tarif SIMKA', 'Nilai' => $summary['spph']['tariff_categories']],
        ];

        $expenseRows = [
            ['Komponen' => 'Pengeluaran (Rencana)', 'Nilai' => $summary['expense_plan']],
            ['Komponen' => 'Total Pengeluaran (Opex + Capex)', 'Nilai' => $summary['expense_total']],
            ['Komponen' => 'Anggaran Opex', 'Nilai' => $summary['opex_budget']],
            ['Komponen' => 'Anggaran Capex', 'Nilai' => $summary['capex_budget']],
            ['Komponen' => 'Realisasi Pengeluaran', 'Nilai' => $summary['realized']],
            ['Komponen' => 'Serapan Anggaran (%)', 'Nilai' => $summary['absorption']],
        ];

        $rows = match ($domain) {
            'revenue' => $revenueRows,
            'expense' => $expenseRows,
            default => [
                ['Komponen' => 'Pendapatan (Rencana)', 'Nilai' => $summary['revenue_plan']],
                ['Komponen' => 'Pengeluaran (Rencana)', 'Nilai' => $summary['expense_plan']],
                ['Komponen' => 'Total Pengeluaran (Opex + Capex)', 'Nilai' => $summary['expense_total']],
                ['Komponen' => 'Selisih Pendapatan - Pengeluaran', 'Nilai' => $summary['balance']],
                ['Komponen' => 'Anggaran Opex', 'Nilai' => $summary['opex_budget']],
                ['Komponen' => 'Anggaran Capex', 'Nilai' => $summary['capex_budget']],
                ['Komponen' => 'Realisasi Pengeluaran', 'Nilai' => $summary['realized']],
                ['Komponen' => 'Serapan Anggaran (%)', 'Nilai' => $summary['absorption']],
                ['Komponen' => 'Skema SPPH', 'Nilai' => $summary['spph']['schemes']],
                ['Komponen' => 'Baris Tarif SPPH', 'Nilai' => $summary['spph']['tarif_entries']],
                ['Komponen' => 'Kategori Pendapatan SIMKA', 'Nilai' => $summary['spph']['revenue_categories']],
                ['Komponen' => 'Kategori Tarif SIMKA', 'Nilai' => $summary['spph']['tariff_categories']],
            ],
        };

        $effectiveRkap = $summary['rkap'];

        $revenueByCoa = RevenuePlan::query()
            ->with('chartOfAccount')
            ->when($effectiveRkap, fn ($query) => $query->where('erkap_rkap_id', $effectiveRkap->id))
            ->when($divisionId, fn ($query) => $query->where('division_id', $divisionId))
            ->get()
            ->groupBy('chart_of_account_id')
            ->map(function ($plans) {
                return [
                    'code' => $plans->first()->chartOfAccount?->code ?? '-',
                    'name' => $plans->first()->chartOfAccount?->name ?? 'Tanpa Akun',
                    'total' => (float) $plans->sum('total'),
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();

        $title = match ($domain) {
            'revenue' => 'Laporan Pendapatan SIMKA',
            'expense' => 'Laporan Pengeluaran E-RKAP',
            default => 'Laporan Gabungan SIMKA & E-RKAP',
        };

        $subtitle = match ($domain) {
            'revenue' => 'Konfigurasi SPPH dan Rencana Pendapatan',
            'expense' => 'Rencana, Anggaran, dan Realisasi Pengeluaran',
            default => 'Pendapatan (SIMKA) vs Pengeluaran (E-RKAP)',
        };

        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'year' => $year,
            'rkap' => $summary['rkap'],
            'summary' => $summary,
            'domain' => $domain,
            'revenue_by_coa' => $revenueByCoa,
            'rows' => $rows,
        ];
    }

    protected function rkapData(array $params): array
    {
        $rkap = $params['rkap'] ?? null;
        $year = $params['year'] ?? now()->year;

        return [
            'title' => 'Laporan RKAP',
            'subtitle' => 'Rencana Kerja dan Anggaran Perusahaan',
            'year' => $year,
            'rkap' => $rkap,
            'rows' => \App\Models\Erkap\RoutineCost::query()
                ->with('workProgram.riskIdentification.departmentTarget.companyTarget')
                ->when($rkap, fn ($q) => $q->whereHas('workProgram.riskIdentification.departmentTarget.companyTarget', fn ($q2) => $q2->where('erkap_rkap_id', $rkap->id)))
                ->get(),
        ];
    }

    protected function financialData(array $params): array
    {
        $rkap = $params['rkap'] ?? null;
        $divisionId = $params['division_id'] ?? null;

        $revenues = \App\Models\Erkap\RevenuePlan::with('chartOfAccount', 'division')
            ->when($rkap, fn ($q) => $q->where('erkap_rkap_id', $rkap->id))
            ->when($divisionId, fn ($q) => $q->where('division_id', $divisionId))
            ->get();

        $expenses = \App\Models\Erkap\ExpensePlan::with('chartOfAccount', 'division')
            ->when($rkap, fn ($q) => $q->where('erkap_rkap_id', $rkap->id))
            ->when($divisionId, fn ($q) => $q->where('division_id', $divisionId))
            ->get();

        $totalRevenue = (float) $revenues->sum('total');
        $totalExpense = (float) $expenses->sum('total');

        return [
            'title' => 'Laporan Keuangan (P&L)',
            'subtitle' => 'Rencana Pendapatan dan Beban',
            'revenues' => $revenues,
            'expenses' => $expenses,
            'totalRevenue' => $totalRevenue,
            'totalExpense' => $totalExpense,
            'netProfit' => $totalRevenue - $totalExpense,
            'rows' => $revenues->concat($expenses),
        ];
    }

    protected function riskData(array $params): array
    {
        $year = $params['year'] ?? now()->year;
        $month = $params['month'] ?? null;

        $rows = \App\Models\Erkap\RiskAssessmentMonthly::with('riskIdentification.departmentTarget.division')
            ->where('year', $year)
            ->when($month, fn ($q) => $q->where('month', $month))
            ->orderBy('month')
            ->get();

        return [
            'title' => 'Laporan Risiko',
            'subtitle' => 'Risk Assessment Bulanan',
            'year' => $year,
            'month' => $month,
            'rows' => $rows,
        ];
    }

    protected function realizationData(array $params): array
    {
        $rkap = $params['rkap'] ?? null;
        $year = $params['year'] ?? now()->year;

        $rows = \App\Models\Erkap\BudgetRealization::with('routineCost.workProgram.riskIdentification.departmentTarget.division', 'investmentPlan.workProgram')
            ->where('year', $year)
            ->when($rkap, fn ($q) => $q->where('erkap_rkap_id', $rkap->id))
            ->orderBy('month')
            ->get();

        return [
            'title' => 'Laporan Realisasi Anggaran',
            'subtitle' => 'Budget vs Actual (BvA)',
            'year' => $year,
            'rkap' => $rkap,
            'rows' => $rows,
            'totalBudgeted' => (float) $rows->sum('budgeted'),
            'totalRealized' => (float) $rows->sum('realized'),
            'totalVariance' => (float) $rows->sum('variance'),
        ];
    }

    protected function performanceData(array $params): array
    {
        $rkap = $params['rkap'] ?? null;

        $rows = \App\Models\Erkap\PerformanceScorecard::with('departmentTarget.riskIdentifications', 'rkap')
            ->when($rkap, fn ($q) => $q->where('erkap_rkap_id', $rkap->id))
            ->get();

        return [
            'title' => 'Laporan Performa (KPI)',
            'subtitle' => 'Performance Scorecard',
            'rkap' => $rkap,
            'rows' => $rows,
        ];
    }
}