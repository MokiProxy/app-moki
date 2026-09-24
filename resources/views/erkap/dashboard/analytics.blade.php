@php
$monthShort = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$money = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');

$riskTrendSeries = collect($riskTrend['levels'])->map(fn ($level) => [
    'name' => $level,
    'data' => collect($riskTrend['labels'])->map(fn ($label, $i) => ['x' => $label, 'y' => $riskTrend['series'][$level][$i]])->all(),
])->all();

$costCenterSeries = collect($costCenterHeatmap['rows'])->map(function ($row) use ($monthShort) {
    return [
        'name' => $row['name'],
        'data' => collect($row['cells'])->map(function ($cell, $i) use ($monthShort) {
            return ['x' => $monthShort[$i], 'y' => $cell ? (float) $cell['variance_percent'] : 0];
        })->values()->all(),
    ];
})->all();

$topVarianceNames = collect($topVariance['rows'])->pluck('name')->map(fn ($n) => \Illuminate\Support\Str::limit($n, 28))->all();
$topVarianceValues = collect($topVariance['rows'])->pluck('variance')->all();
$topVarianceColors = collect($topVariance['rows'])->map(fn ($r) => $r['variance'] < 0 ? '#16a34a' : '#ef4444')->all();

$revenueSeries = collect($revenueBreakdown['categories'])->take(6)->map(fn ($c) => ['name' => $c['label'], 'data' => array_map(fn ($v) => round($v), $c['monthly'])])->all();
$expenseSeries = collect($expenseBreakdown['categories'])->take(6)->map(fn ($c) => ['name' => $c['label'], 'data' => array_map(fn ($v) => round($v), $c['monthly'])])->all();

$scenarioSeries = [
    ['name' => 'Best Case', 'data' => [round($scenarioComparison['scenarios']['best']['revenue']), round($scenarioComparison['scenarios']['best']['expense']), round($scenarioComparison['scenarios']['best']['profit'])]],
    ['name' => 'Base Case', 'data' => [round($scenarioComparison['scenarios']['base']['revenue']), round($scenarioComparison['scenarios']['base']['expense']), round($scenarioComparison['scenarios']['base']['profit'])]],
    ['name' => 'Worst Case', 'data' => [round($scenarioComparison['scenarios']['worst']['revenue']), round($scenarioComparison['scenarios']['worst']['expense']), round($scenarioComparison['scenarios']['worst']['profit'])]],
];

$gaugeZoneColor = ['green' => '#10b981', 'yellow' => '#f59e0b', 'red' => '#ef4444'][$riskAppetite['gauge']['zone']] ?? '#10b981';
$gaugeZoneLabel = ['green' => 'Aman', 'yellow' => 'Waspada', 'red' => 'Berisiko'][$riskAppetite['gauge']['zone']] ?? '-';
@endphp

@extends('layouts.Erkap')

@section('title', 'Analytics & Widgets')

@section('css')
<style>
    .widget-card { border: none; border-radius: 14px; box-shadow: 0 2px 12px rgba(15, 23, 42, 0.06); }
    .widget-card .card-header { background: transparent; border-bottom: 1px solid #f1f5f9; font-weight: 700; }
    .kpi-box { border-radius: 12px; padding: 14px 16px; color: #fff; }
    .kpi-box .kpi-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.85; }
    .kpi-box .kpi-value { font-size: 1.15rem; font-weight: 700; }
    .gantt-dot { color: #3b82f6; font-size: 1rem; }
    .edge-row { display: flex; align-items: center; gap: 8px; padding: 5px 0; border-bottom: 1px dashed #e2e8f0; font-size: 0.85rem; }
    .drilldown-form select, .drilldown-form input { max-width: 220px; }
</style>
@endsection

@section('content')
<div class="container-fluid py-2">

    <div class="d-flex flex-wrap align-items-start justify-content-between mb-4 gap-3">
        <div>
            <h4 class="fw-bold text-dark mb-1">Analytics & Widgets</h4>
            <p class="text-muted mb-0">Monitoring indikator E-RKAP dalam satu tampilan</p>
        </div>
        <form method="GET" action="{{ route('erkap.dashboard.widgets') }}" class="d-flex flex-wrap align-items-center gap-2">
            <select name="rkap_id" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                <option value="">- Semua RKAP -</option>
                @foreach($rkaps as $rkap)
                <option value="{{ $rkap->id }}" @selected($selectedRkap?->id === $rkap->id)>Periode RKAP {{ $rkap->year }}</option>
                @endforeach
            </select>
            <input type="number" name="year" class="form-control form-control-sm" style="width:90px;" value="{{ $year }}" title="Tahun">
            @if(!$isDivisionScoped)
            <select name="division_id" class="form-select form-select-sm" style="width:auto;">
                <option value="">- Divisi -</option>
                @foreach($divisions as $division)
                <option value="{{ $division->id }}" @selected(request('division_id') == $division->id)>{{ $division->name }}</option>
                @endforeach
            </select>
            @endif
            <select name="taxonomy_id" class="form-select form-select-sm" style="width:auto;">
                <option value="">- Taksonomi Risiko -</option>
                @foreach($taxonomies as $taxonomy)
                <option value="{{ $taxonomy->id }}" @selected(request('taxonomy_id') == $taxonomy->id)>{{ $taxonomy->name }}</option>
                @endforeach
            </select>
            <select name="risk_type_id" class="form-select form-select-sm" style="width:auto;">
                <option value="">- Tipe Risiko -</option>
                @foreach($riskTypes as $riskType)
                <option value="{{ $riskType->id }}" @selected(request('risk_type_id') == $riskType->id)>{{ $riskType->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-primary" title="Filter"><i class="mdi mdi-magnify"></i></button>
            @if($selectedRkap)
            <a href="{{ route('erkap.dashboard.export-consolidated', ['rkap_id' => $selectedRkap->id, 'year' => $year]) }}" class="btn btn-sm btn-outline-success" title="Export Konsolidasi RKAP (8 Sheet)">
                <i class="mdi mdi-file-excel me-1"></i> Konsolidasi
            </a>
            @endif
        </form>
    </div>

    <div class="row mb-4 g-3">
        <div class="col-md-3">
            <div class="kpi-box" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);">
                <div class="kpi-label">Proyeksi Arus Kas Bersih</div>
                <div class="kpi-value">{{ $money($cashFlow['totals']['net']) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-box" style="background:linear-gradient(135deg,#10b981,#047857);">
                <div class="kpi-label">Realisasi Anggaran (YTD)</div>
                <div class="kpi-value">{{ $money($topVariance['total_realized']) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-box" style="background:linear-gradient(135deg,#f59e0b,#b45309);">
                <div class="kpi-label">Total Pendapatan Terencana</div>
                <div class="kpi-value">{{ $money($revenueBreakdown['total']) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-box" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9);">
                <div class="kpi-label">Beban Bulan Ini</div>
                <div class="kpi-value">{{ $money($expenseBreakdown['current_month']) }}</div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-xl-8 mb-3">
            <div class="card widget-card">
                <div class="card-header d-flex justify-content-between">
                    <span><i class="mdi mdi-cash-multiple me-2 text-primary"></i>Arus Kas (Cash Flow)</span>
                    <span class="text-muted small">{{ $year }}</span>
                </div>
                <div class="card-body"><div id="chart-cash-flow"></div></div>
            </div>
        </div>
        <div class="col-xl-4 mb-3">
            <div class="card widget-card">
                <div class="card-header"><i class="mdi mdi-gauge me-2 text-danger"></i>Risk Appetite Meter</div>
                <div class="card-body">
                    <div id="chart-risk-appetite"></div>
                    <p class="text-center mb-2">
                        <span class="badge" style="background:{{ $gaugeZoneColor }};">{{ $gaugeZoneLabel }}</span>
                        <span class="small text-muted"> - {{ $riskAppetite['gauge']['label'] }}</span>
                    </p>
                    <div class="mt-2">
                        @forelse($riskAppetite['breaches'] as $breach)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-1 small">
                            <span>{{ $breach['appetite'] }}</span>
                            <span>
                                <span class="badge" style="background:{{ ['green'=>'#10b981','yellow'=>'#f59e0b','red'=>'#ef4444'][$breach['zone']] ?? '#10b981' }};">
                                    {{ $breach['percentage'] }}% ({{ $breach['exposed'] }}/{{ $breach['total'] }})
                                </span>
                            </span>
                        </div>
                        @empty
                        <p class="text-muted text-center small mb-0">Belum ada data exposur terhadap appetite.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card widget-card mb-3">
        <div class="card-header d-flex justify-content-between">
            <span><i class="mdi mdi-heatmap me-2 text-warning"></i>Tren Level Risiko ({{ $year }})</span>
            <span class="text-muted small">VL:{{ $riskTrend['totals']['VL'] }} L:{{ $riskTrend['totals']['L'] }} M:{{ $riskTrend['totals']['M'] }} H:{{ $riskTrend['totals']['H'] }} VH:{{ $riskTrend['totals']['VH'] }}</span>
        </div>
        <div class="card-body"><div id="chart-risk-trend"></div></div>
    </div>

    <div class="row mb-3">
        <div class="col-xl-8 mb-3">
            <div class="card widget-card h-100">
                <div class="card-header"><i class="mdi mdi-chart-timeline me-2 text-info"></i>Gantt Program Kerja (Top {{ count($gantt['items']) }})</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle table-striped mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Program</th>
                                    <th>Divisi</th>
                                    @foreach($monthShort as $m)
                                    <th class="text-center">{{ $m }}</th>
                                    @endforeach
                                    <th class="text-center" style="width:150px">Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($gantt['items'] as $program)
                                <tr>
                                    <td class="fw-bold">
                                        <a href="{{ route('erkap.dashboard.drilldown', ['type' => 'routine-cost', 'id' => $program['id']]) }}" class="text-decoration-none">
                                            {{ $program['name'] }}
                                        </a>
                                    </td>
                                    <td>{{ $program['division'] }}</td>
                                    @foreach($monthShort as $i => $m)
                                    <td class="text-center">
                                        @if($program['start_month'] !== null && $i >= $program['start_month'] && $i <= $program['end_month'])
                                        <i class="mdi mdi-square gantt-dot"></i>
                                        @else
                                        <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    @endforeach
                                    <td>
                                        <div class="progress" style="height:14px;">
                                            <div class="progress-bar @if($program['progress'] >= 100) bg-success @elseif($program['progress'] >= 50) bg-info @else bg-warning @endif" role="progressbar" style="width:{{ min(100, $program['progress']) }}%">{{ number_format($program['progress'], 1) }}%</div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="16" class="text-center text-muted">Belum ada program kerja.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 mb-3">
            <div class="card widget-card h-100">
                <div class="card-header"><i class="mdi mdi-sitemap me-2 text-secondary"></i>Dependency Map</div>
                <div class="card-body">
                    <p class="small text-muted">{{ count($dependencyMap['nodes']) }} program, {{ count($dependencyMap['edges']) }} ketergantungan</p>
                    <div class="mb-3">
                        @forelse($dependencyMap['edges'] as $edge)
                        <div class="edge-row">
                            <span class="badge bg-primary text-truncate" style="max-width:38%;">{{ $edge['label'] }}</span>
                            <i class="mdi mdi-arrow-right"></i>
                            <span class="text-truncate" style="max-width:38%;">{{ $edge['label'] }}</span>
                        </div>
                        @empty
                        <p class="text-muted text-center small mb-0">Belum ada ketergantungan antar program.</p>
                        @endforelse
                    </div>
                    <h6 class="text-muted text-uppercase small fw-bold mt-3">Semua Node</h6>
                    <div class="d-flex flex-wrap gap-2">
                        @forelse($dependencyMap['nodes'] as $node)
                        <span class="badge bg-light border text-dark">{{ $node['label'] }}</span>
                        @empty
                        <span class="text-muted small">-</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card widget-card mb-3">
        <div class="card-header"><i class="mdi mdi-chart-box me-2 text-success"></i>Heatmap Variance per Cost Center ({{ $year }})</div>
        <div class="card-body"><div id="chart-cost-center-heatmap"></div></div>
    </div>

    <div class="row mb-3">
        <div class="col-xl-6 mb-3">
            <div class="card widget-card h-100">
                <div class="card-header"><i class="mdi mdi-format-line-spacing me-2 text-danger"></i>Top 10 Variance Anggaran</div>
                <div class="card-body"><div id="chart-top-variance"></div></div>
            </div>
        </div>
        <div class="col-xl-6 mb-3">
            <div class="card widget-card h-100">
                <div class="card-header"><i class="mdi mdi-chart-scatter-plot me-2 text-warning"></i>Perbandingan Skenario Anggaran</div>
                <div class="card-body"><div id="chart-scenario"></div></div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-xl-6 mb-3">
            <div class="card widget-card h-100">
                <div class="card-header"><i class="mdi mdi-chart-bar me-2 text-info"></i>Breakdown Pendapatan per Akun</div>
                <div class="card-body"><div id="chart-revenue-breakdown"></div></div>
            </div>
        </div>
        <div class="col-xl-6 mb-3">
            <div class="card widget-card h-100">
                <div class="card-header"><i class="mdi mdi-chart-bar me-2 text-danger"></i>Breakdown Beban per Akun</div>
                <div class="card-body"><div id="chart-expense-breakdown"></div></div>
            </div>
        </div>
    </div>

    <div class="card widget-card mb-3">
        <div class="card-header"><i class="mdi mdi-table-search me-2 text-secondary"></i>Drilldown Detail</div>
        <div class="card-body">
            <form method="GET" action="{{ url('erkap/dashboard/drilldown') }}" class="d-flex flex-wrap align-items-end gap-2">
                <div>
                    <label class="form-label small fw-bold mb-1">Tipe</label>
                    <select name="type" class="form-select form-select-sm drilldown-form">
                        <option value="routine-cost">Biaya Rutin</option>
                        <option value="investment-plan">Investasi (CAPEX)</option>
                        <option value="realization">Realisasi Anggaran</option>
                        <option value="budget">Budget CAPEX</option>
                        <option value="pnl">Laba Rugi</option>
                    </select>
                </div>
                <div>
                    <label class="form-label small fw-bold mb-1">ID</label>
                    <input type="number" name="id" class="form-control form-control-sm drilldown-form" placeholder="ID" min="1" required>
                </div>
                <button type="submit" class="btn btn-sm btn-secondary">Lihat Detail</button>
                <input type="hidden" name="_export" value="0">
            </form>
            <p class="small text-muted mt-2 mb-0">Detail dapat diunduh dalam format Excel melalui tombol di halaman drilldown.</p>
        </div>
    </div>

</div>
@endsection

@section('plugin')
<script src="{{ asset('libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
    var moneyFmt = function (v) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(v); };
    var moneyAxis = function (v) { return v >= 1000000000 ? (v / 1000000000).toFixed(1) + ' M' : v >= 1000000 ? (v / 1000000).toFixed(1) + ' jt' : v; };

    $(function () {
        new ApexCharts(document.querySelector('#chart-cash-flow'), {
            chart: { type: 'bar', toolbar: { show: true }, stacked: true },
            series: [
                { name: 'Operasional', data: @json(array_map(fn ($v) => round($v), $cashFlow['operating'])) },
                { name: 'Investasi', data: @json(array_map(fn ($v) => round($v), $cashFlow['investing'])) },
                { name: 'Arus Kas Bersih', data: @json(array_map(fn ($v) => round($v), $cashFlow['net'])) }
            ],
            xaxis: { categories: @json($cashFlow['labels']) },
            plotOptions: { bar: { columnWidth: '55%', borderRadius: 2 } },
            dataLabels: { enabled: false },
            legend: { position: 'top' },
            colors: ['#3b82f6', '#f43f5e', '#10b981'],
            stroke: { width: 2, colors: ['transparent'] },
            yaxis: { labels: { formatter: moneyAxis } },
            tooltip: { y: { formatter: moneyFmt } }
        }).render();

        new ApexCharts(document.querySelector('#chart-risk-appetite'), {
            chart: { type: 'radialBar' },
            series: [{{ $riskAppetite['gauge']['value'] }}],
            colors: ['{{ $gaugeZoneColor }}'],
            plotOptions: { radialBar: { hollow: { size: '60%' }, dataLabels: { name: { show: true, fontSize: '12px' }, value: { show: true, fontSize: '22px', formatter: function (v) { return v + '%'; } } } } },
            labels: ['Eksposur']
        }).render();

        new ApexCharts(document.querySelector('#chart-risk-trend'), {
            chart: { type: 'heatmap', toolbar: { show: true } },
            series: @json($riskTrendSeries),
            xaxis: { categories: @json($riskTrend['labels']) },
            plotOptions: { heatmap: { colorScale: { ranges: [
                { from: 0, to: 0, color: '#e2e8f0' },
                { from: 1, to: 2, color: '#22c55e' },
                { from: 3, to: 5, color: '#eab308' },
                { from: 6, to: 10, color: '#f97316' },
                { from: 11, to: 999, color: '#ef4444' }
            ] } } },
            dataLabels: { enabled: true, style: { fontSize: '10px' } },
            legend: { show: true }
        }).render();

        new ApexCharts(document.querySelector('#chart-cost-center-heatmap'), {
            chart: { type: 'heatmap', toolbar: { show: true } },
            series: @json($costCenterSeries),
            xaxis: { categories: @json($monthShort) },
            colors: ['#f87171'],
            plotOptions: { heatmap: { colorScale: { inverse: true, ranges: [
                { from: -999, to: -15, color: '#16a34a' },
                { from: -15, to: 0, color: '#86efac' },
                { from: 0, to: 15, color: '#fde68a' },
                { from: 15, to: 999, color: '#ef4444' }
            ] } } },
            dataLabels: { enabled: true, style: { fontSize: '10px' } }
        }).render();

        new ApexCharts(document.querySelector('#chart-top-variance'), {
            chart: { type: 'bar', toolbar: { show: true } },
            series: [{ name: 'Variance', data: @json($topVarianceValues) }],
            xaxis: { categories: @json($topVarianceNames) },
            colors: @json($topVarianceColors),
            plotOptions: { bar: { horizontal: true, barHeight: '55%', borderRadius: 2 } },
            dataLabels: { enabled: true, formatter: function (v) { return moneyAxis(v); } },
            yaxis: { labels: { style: { fontSize: '10px' } } },
            tooltip: { y: { formatter: moneyFmt } }
        }).render();

        new ApexCharts(document.querySelector('#chart-scenario'), {
            chart: { type: 'bar', toolbar: { show: true } },
            series: @json($scenarioSeries),
            xaxis: { categories: @json($scenarioComparison['labels']) },
            plotOptions: { bar: { columnWidth: '55%', borderRadius: 2 } },
            dataLabels: { enabled: false },
            legend: { position: 'top' },
            colors: ['#10b981', '#3b82f6', '#ef4444'],
            yaxis: { labels: { formatter: moneyAxis } },
            tooltip: { y: { formatter: moneyFmt } }
        }).render();

        new ApexCharts(document.querySelector('#chart-revenue-breakdown'), {
            chart: { type: 'bar', toolbar: { show: true }, stacked: true },
            series: @json($revenueSeries),
            xaxis: { categories: @json($revenueBreakdown['labels']) },
            plotOptions: { bar: { columnWidth: '55%', borderRadius: 2 } },
            dataLabels: { enabled: false },
            legend: { position: 'bottom' },
            colors: ['#3b82f6', '#60a5fa', '#93c5fd', '#2563eb', '#1d4ed8', '#1e40af'],
            yaxis: { labels: { formatter: moneyAxis } },
            tooltip: { y: { formatter: moneyFmt } }
        }).render();

        new ApexCharts(document.querySelector('#chart-expense-breakdown'), {
            chart: { type: 'bar', toolbar: { show: true }, stacked: true },
            series: @json($expenseSeries),
            xaxis: { categories: @json($expenseBreakdown['labels']) },
            plotOptions: { bar: { columnWidth: '55%', borderRadius: 2 } },
            dataLabels: { enabled: false },
            legend: { position: 'bottom' },
            colors: ['#f43f5e', '#fb7185', '#fda4af', '#e11d48', '#be123c', '#9f1239'],
            yaxis: { labels: { formatter: moneyAxis } },
            tooltip: { y: { formatter: moneyFmt } }
        }).render();
    });
</script>
@endsection