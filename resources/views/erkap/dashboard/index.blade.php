@php
$authUserName = auth()->user()->name;
$monthShort = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$statusLabelMap = [
    'draft' => 'Draft',
    'submitted' => 'Menunggu Persetujuan',
    'approved' => 'Disetujui',
    'rejected' => 'Ditolak',
    'revised' => 'Direvisi',
];
$programDonutValues = array_values($program['statusCounts']);
$programDonutLabels = collect($program['statusCounts'])->keys()->map(fn ($status) => $statusLabelMap[$status] ?? ucfirst($status))->values()->all();
$programPlanColumns = ['jan_plan', 'feb_plan', 'mar_plan', 'apr_plan', 'may_plan', 'jun_plan', 'jul_plan', 'aug_plan', 'sep_plan', 'oct_plan', 'nov_plan', 'dec_plan'];
@endphp

@extends('layouts.Erkap')

@section('title', 'Dashboard E-RKAP')

@section('css')
<style>
    .text-dark { color: #000000 !important; }

    .stat-card {
        border-radius: 16px;
        color: #fff;
        position: relative;
        overflow: hidden;
        transition: 0.3s;
    }

    .stat-card:hover {
        transform: translateY(-4px);
    }

    .stat-card .icon-overlay {
        position: absolute;
        right: -10px;
        bottom: -10px;
        font-size: 4.5rem;
        opacity: 0.18;
        transform: rotate(-15deg);
    }

    .heat-cell {
        min-width: 52px;
        min-height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        border: 2px solid #fff;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 700;
        color: #1e293b;
        transition: transform 0.15s, box-shadow 0.15s;
    }

    .heat-cell:hover {
        transform: scale(1.06);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.18);
    }

    .heat-cell .heat-count {
        font-size: 0.7rem;
        font-weight: 600;
    }

    .gantt-dot {
        color: #3b82f6;
        font-size: 1rem;
    }

    .progress {
        background-color: #e9ecef;
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-2">

    <div class="d-flex flex-wrap align-items-start justify-content-between mb-4 gap-3">
        <div>
            <h4 class="fw-bold text-dark mb-1">Halo, {{ explode(' ', $authUserName)[0] }}!</h4>
            <p class="text-muted mb-0">Selamat datang di Dashboard Monitoring & Reporting E-RKAP</p>
        </div>
        <form method="GET" action="{{ route('erkap.index') }}" class="d-flex flex-wrap align-items-center gap-2">
            <select name="rkap_id" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                @forelse($rkaps as $rkap)
                <option value="{{ $rkap->id }}" @selected($selectedRkap?->id === $rkap->id)>Periode RKAP {{ $rkap->year }}</option>
                @empty
                <option value="">Belum ada RKAP</option>
                @endforelse
            </select>
            <input type="number" name="year" class="form-control form-control-sm" style="width: 90px;" value="{{ $year }}" title="Tahun">
            <button type="submit" class="btn btn-sm btn-primary" title="Filter"><i class="mdi mdi-magnify"></i></button>
            @if($selectedRkap)
            <a href="{{ route('erkap.dashboard.export-budget', ['rkap_id' => $selectedRkap->id, 'year' => $year]) }}" class="btn btn-sm btn-outline-success" title="Export Konsolidasi Anggaran (Excel)">
                <i class="mdi mdi-file-excel me-1"></i> Excel
            </a>
            <a href="{{ route('erkap.dashboard.export-budget-pdf', ['rkap_id' => $selectedRkap->id, 'year' => $year]) }}" class="btn btn-sm btn-outline-danger" title="Export Konsolidasi Anggaran (PDF)">
                <i class="mdi mdi-file-pdf me-1"></i> PDF
            </a>
            @endif
        </form>
    </div>

    @include('erkap.dashboard.widgets.executive-summary')

    <div class="row">
        <div class="col-xl-5 col-lg-6 mb-3">
            @include('erkap.dashboard.widgets.risk-heatmap')
        </div>
        <div class="col-xl-7 col-lg-6 mb-3">
            @include('erkap.dashboard.widgets.budget-comparison')
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6 col-lg-6 mb-3">
            @include('erkap.dashboard.widgets.program-status')
        </div>
        <div class="col-xl-6 col-lg-6 mb-3">
            @include('erkap.dashboard.widgets.profit-loss')
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark"><i class="mdi mdi-chart-timeline me-2"></i>Gantt Chart - Program Kerja ({{ $selectedRkap->year ?? $year }})</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Program Kerja</th>
                            <th>Divisi</th>
                            @foreach($monthShort as $m)
                            <th class="text-center">{{ $m }}</th>
                            @endforeach
                            <th class="text-center" style="width: 170px">Progress</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($programGantt as $program)
                        @php
                        $pct = (float) ($program->latestRealization->percent_complete ?? 0);
                        $division = $program->riskIdentification?->departmentTarget?->division;
                        @endphp
                        <tr>
                            <td class="fw-bold">{{ $program->name }}</td>
                            <td>{{ $division->name ?? '-' }}</td>
                            @foreach($programPlanColumns as $column)
                            <td class="text-center">
                                @if((float) $program->{$column} > 0)
                                <i class="mdi mdi-square gantt-dot" title="{{ number_format($program->{$column}, 0, ',', '.') }} unit"></i>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                            @endforeach
                            <td>
                                <div class="progress" style="height: 16px;">
                                    <div class="progress-bar @if($pct >= 100) bg-success @elseif($pct >= 50) bg-info @else bg-warning @endif" role="progressbar" style="width: {{ min(100, $pct) }}%">{{ number_format($pct, 1) }}%</div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-{{ $program->statusClass() }}">{{ $program->statusLabel() }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="16" class="text-center text-muted">Belum ada program kerja.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<div class="modal fade" id="riskDetailModal" tabindex="-1" aria-labelledby="riskDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="riskDetailModalLabel"><i class="mdi mdi-shield-alert-outline me-2"></i>Detail Risiko</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" id="riskDetailContent">
                @include('erkap.dashboard.modals.risk-detail')
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="budgetDetailModal" tabindex="-1" aria-labelledby="budgetDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="budgetDetailModalLabel"><i class="mdi mdi-chart-bar me-2"></i>Detail Realisasi Anggaran</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" id="budgetDetailContent">
                @include('erkap.dashboard.modals.budget-detail')
            </div>
        </div>
    </div>
</div>
@endsection

@section('plugin')
<script src="{{ asset('libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
    var yearVal = {{ $year }};
    var monthShort = @json($monthShort);

    function openBudgetDetail(seriesIndex, monthIndex) {
        var type = seriesIndex < 2 ? 'opex' : 'capex';
        var month = monthIndex + 1;

        $.ajax({
            url: '{{ route("erkap.dashboard.budget-detail") }}',
            data: { type: type, year: yearVal, month: month },
            success: function (response) {
                $('#budgetDetailContent').html(response);
                $('#budgetDetailModal').modal('show');
            },
            error: function () {
                $('#budgetDetailContent').html('<div class="alert alert-danger">Gagal memuat detail anggaran.</div>');
            }
        });
    }

    $(function () {
        var budgetChart = new ApexCharts(document.querySelector('#chart-budget'), {
            chart: {
                type: 'bar',
                toolbar: { show: true },
                events: {
                    dataPointSelection: function (event, chartContext, config) {
                        if (config.dataPointIndex >= 0) {
                            openBudgetDetail(config.seriesIndex, config.dataPointIndex);
                        }
                    }
                }
            },
            series: [
                { name: 'Budget OPEX', data: @json(array_map(fn ($v) => round($v), $budget['opexBudgetMonthly'])) },
                { name: 'Realisasi OPEX', data: @json(array_map(fn ($v) => round($v), $budget['opexRealizedMonthly'])) },
                { name: 'Budget CAPEX', data: @json(array_map(fn ($v) => round($v), $budget['capexBudgetMonthly'])) },
                { name: 'Realisasi CAPEX', data: @json(array_map(fn ($v) => round($v), $budget['capexRealizedMonthly'])) }
            ],
            xaxis: { categories: monthShort },
            plotOptions: { bar: { columnWidth: '55%', borderRadius: 2 } },
            dataLabels: { enabled: false },
            legend: { position: 'top' },
            colors: ['#3b82f6', '#60a5fa', '#f59e0b', '#fbbf24'],
            yaxis: { labels: { formatter: function (v) { return v >= 1000000000 ? (v / 1000000000).toFixed(1) + ' M' : v >= 1000000 ? (v / 1000000).toFixed(1) + ' jt' : v; } } },
            tooltip: { y: { formatter: function (v) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(v); } } }
        });
        budgetChart.render();

        var programChart = new ApexCharts(document.querySelector('#chart-program'), {
            chart: { type: 'donut' },
            series: @json($programDonutValues),
            labels: @json($programDonutLabels),
            colors: ['#94a3b8', '#f59e0b', '#10b981', '#ef4444', '#0ea5e9'],
            legend: { position: 'bottom' },
            plotOptions: { pie: { donut: { labels: { show: true, name: { show: true }, value: { show: true, formatter: function (v) { return v; } }, total: { show: true, label: 'Total Program', formatter: function (w) { return w.globals.seriesTotals.reduce(function (a, b) { return a + b; }, 0); } } } } } }
        });
        programChart.render();

        var plChart = new ApexCharts(document.querySelector('#chart-profit-loss'), {
            chart: { type: 'line', toolbar: { show: true } },
            series: [
                { name: 'Pendapatan', data: @json(array_map(fn ($v) => round($v), $profitLoss['monthlyRevenue'])) },
                { name: 'Beban', data: @json(array_map(fn ($v) => round($v), $profitLoss['monthlyExpense'])) },
                { name: 'Laba Rugi', data: @json(array_map(fn ($v) => round($v), $profitLoss['monthlyProfit'])) }
            ],
            xaxis: { categories: monthShort },
            stroke: { width: [3, 3, 3], dashArray: [0, 0, 5] },
            colors: ['#10b981', '#f43f5e', '#3b82f6'],
            legend: { position: 'top' },
            dataLabels: { enabled: false },
            yaxis: { labels: { formatter: function (v) { return v >= 1000000000 ? (v / 1000000000).toFixed(1) + ' M' : v >= 1000000 ? (v / 1000000).toFixed(1) + ' jt' : v; } } },
            tooltip: { y: { formatter: function (v) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(v); } } }
        });
        plChart.render();

        $(document).on('click', '.heat-cell[data-total]', function () {
            var total = parseInt($(this).data('total'), 10);
            if (!total) {
                return;
            }

            var self = this;

            $.ajax({
                url: '{{ route("erkap.dashboard.risk-detail") }}',
                data: { probability: $(this).data('probability'), impact: $(this).data('impact'), year: yearVal },
                success: function (response) {
                    $('#riskDetailContent').html(response);
                    $('#riskDetailModal .modal-title').text('Detail Risiko - Prob ' + $(self).data('probability') + ' x Impact ' + $(self).data('impact'));
                    $('#riskDetailModal').modal('show');
                },
                error: function () {
                    $('#riskDetailContent').html('<div class="alert alert-danger">Gagal memuat detail risiko.</div>');
                }
            });
        });
    });
</script>
@endsection