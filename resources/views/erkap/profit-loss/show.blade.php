@extends('layouts.Erkap')

@section('title', $pageName)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">
                        {{ $pageName }}
                        <small class="text-muted ms-2">
                            {{ $profitLossStatement->rkap->year ?? '-' }}
                            @if($profitLossStatement->division)
                                - {{ $profitLossStatement->division->name }}
                            @else
                                - Perusahaan
                            @endif
                        </small>
                    </h5>
                    <a href="{{ route('erkap.profit-loss.index') }}" class="btn btn-secondary">
                        <i class="mdi mdi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 bg-success text-white shadow-sm">
                            <div class="card-body py-3">
                                <small class="text-white-50 text-uppercase fw-bold">Total Pendapatan</small>
                                <h5 class="mb-0 mt-1">Rp {{ number_format($profitLossStatement->total_revenue, 0, ',', '.') }}</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 bg-danger text-white shadow-sm">
                            <div class="card-body py-3">
                                <small class="text-white-50 text-uppercase fw-bold">Total Beban</small>
                                <h5 class="mb-0 mt-1">Rp {{ number_format($profitLossStatement->total_expense, 0, ',', '.') }}</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 bg-primary text-white shadow-sm">
                            <div class="card-body py-3">
                                <small class="text-white-50 text-uppercase fw-bold">Laba Bersih</small>
                                <h5 class="mb-0 mt-1">Rp {{ number_format($profitLossStatement->net_profit, 0, ',', '.') }}</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 bg-info text-white shadow-sm">
                            <div class="card-body py-3">
                                <small class="text-white-50 text-uppercase fw-bold">Margin</small>
                                <h5 class="mb-0 mt-1">{{ number_format($profitLossStatement->margin, 2, ',', '.') }}%</h5>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border shadow-sm mb-4">
                    <div class="card-header bg-light py-2 fw-bold"><i class="mdi mdi-chart-bar me-1"></i> Grafik Bulanan Pendapatan vs Beban</div>
                    <div class="card-body">
                        <div id="pnl-chart"></div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="card border shadow-sm h-100">
                            <div class="card-header bg-success text-white py-2 fw-bold">Rincian Rencana Pendapatan</div>
                            <div class="card-body py-2 table-responsive p-0">
                                <table class="table table-sm table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>Akun</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($revenueRows as $plan)
                                        <tr>
                                            <td>{{ $plan->chartOfAccount->code ?? '-' }} - {{ $plan->chartOfAccount->name ?? '-' }}</td>
                                            <td class="text-end fw-bold">Rp {{ number_format($plan->total, 0, ',', '.') }}</td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="2" class="text-center text-muted p-2">Belum ada rencana pendapatan.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card border shadow-sm h-100">
                            <div class="card-header bg-danger text-white py-2 fw-bold">Rincian Rencana Beban</div>
                            <div class="card-body py-2 table-responsive p-0">
                                <table class="table table-sm table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>Akun</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($expenseRows as $plan)
                                        <tr>
                                            <td>{{ $plan->chartOfAccount->code ?? '-' }} - {{ $plan->chartOfAccount->name ?? '-' }}</td>
                                            <td class="text-end fw-bold">Rp {{ number_format($plan->total, 0, ',', '.') }}</td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="2" class="text-center text-muted p-2">Belum ada rencana beban.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('plugin')
<script src="{{ asset('libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
    $(document).ready(function() {
        var labels = @json($monthLabels);
        var revenue = @json($monthlyRevenue);
        var expense = @json($monthlyExpense);
        var profit = @json($monthlyProfit);

        var options = {
            chart: {
                type: 'bar',
                height: 320,
                toolbar: { show: false }
            },
            series: [
                { name: 'Pendapatan', type: 'bar', data: revenue },
                { name: 'Beban', type: 'bar', data: expense },
                { name: 'Laba', type: 'line', data: profit }
            ],
            colors: ['#34c38f', '#f46a6a', '#556ee6'],
            stroke: { width: [0, 0, 3] },
            xaxis: { categories: labels },
            yaxis: {
                labels: {
                    formatter: function(value) {
                        return new Intl.NumberFormat('id-ID').format(Math.round(value));
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(value) {
                        return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(value));
                    }
                }
            },
            legend: { position: 'top' }
        };

        var chart = new ApexCharts(document.querySelector('#pnl-chart'), options);
        chart.render();
    });
</script>
@endsection