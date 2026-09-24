<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\RiskTaxonomy;
use App\Models\Erkap\RiskType;
use App\Services\Dashboard\CashFlowWidgetService;
use App\Services\Dashboard\CostCenterHeatmapService;
use App\Services\Dashboard\DependencyMapService;
use App\Services\Dashboard\ExpenseBreakdownService;
use App\Services\Dashboard\GanttWidgetService;
use App\Services\Dashboard\RevenueBreakdownService;
use App\Services\Dashboard\RiskAppetiteWidgetService;
use App\Services\Dashboard\RiskTrendWidgetService;
use App\Services\Dashboard\ScenarioComparisonService;
use App\Services\Dashboard\TopVarianceService;
use App\Services\ErkapAccess;
use Illuminate\Http\Request;

class DashboardWidgetsController extends Controller
{
    public function index(Request $request)
    {
        $pageName = 'Analytics & Widgets';

        $rkaps = RKAP::orderByDesc('year')->get();
        $selectedRkap = RKAP::find($request->integer('rkap_id')) ?? $rkaps->first();
        $rkapId = $selectedRkap?->id;
        $year = $request->integer('year', $selectedRkap?->year ?? now()->year);
        $isDivisionScoped = ErkapAccess::isDivisionScoped();

        $cashFlow = app(CashFlowWidgetService::class)->data($rkapId, $year);
        $riskAppetite = app(RiskAppetiteWidgetService::class)->data($rkapId, $year);
        $riskTrend = app(RiskTrendWidgetService::class)->data($rkapId, $year, [
            'division_id' => $request->integer('division_id') ?: null,
            'taxonomy_id' => $request->integer('taxonomy_id') ?: null,
            'risk_type_id' => $request->integer('risk_type_id') ?: null,
        ]);
        $gantt = app(GanttWidgetService::class)->data($rkapId);
        $dependencyMap = app(DependencyMapService::class)->data($rkapId);
        $costCenterHeatmap = app(CostCenterHeatmapService::class)->data($rkapId, $year);
        $topVariance = app(TopVarianceService::class)->data($rkapId, $year);
        $revenueBreakdown = app(RevenueBreakdownService::class)->data($rkapId);
        $expenseBreakdown = app(ExpenseBreakdownService::class)->data($rkapId);
        $scenarioComparison = app(ScenarioComparisonService::class)->data($rkapId);

        $divisions = Division::query()
            ->when($isDivisionScoped, fn ($q) => $q->where('id', ErkapAccess::divisionId()))
            ->get();
        $taxonomies = RiskTaxonomy::orderBy('name')->get();
        $riskTypes = RiskType::orderBy('name')->get();

        return view('erkap.dashboard.analytics', compact(
            'pageName', 'rkaps', 'selectedRkap', 'year', 'isDivisionScoped',
            'cashFlow', 'riskAppetite', 'riskTrend', 'gantt', 'dependencyMap',
            'costCenterHeatmap', 'topVariance', 'revenueBreakdown', 'expenseBreakdown',
            'scenarioComparison', 'divisions', 'taxonomies', 'riskTypes'
        ));
    }
}