@extends('layouts.Erkap')

@section('title', $pageName)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <h5 class="mb-0 card-title fw-bold">{{ $pageName }}</h5>
            </div>
            <div class="card-body">
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ route('erkap.profit-loss.simulate') }}" method="GET" class="row g-3 align-items-end mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Tahun RKAP <span class="text-danger">*</span></label>
                        <select name="erkap_rkap_id" class="form-select" required>
                            <option value="" disabled {{ request('erkap_rkap_id') ? '' : 'selected' }}>Pilih Tahun RKAP</option>
                            @foreach($rkaps as $rkap)
                                <option value="{{ $rkap->id }}" {{ request('erkap_rkap_id') == $rkap->id ? 'selected' : '' }}>{{ $rkap->year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Divisi</label>
                        @if($isDivisionScoped)
                            @php $ownDivision = $divisions->first(); @endphp
                            <input type="text" class="form-control" value="{{ $ownDivision->name ?? '-' }}" disabled>
                            <input type="hidden" name="division_id" value="{{ $ownDivision ? $ownDivision->id : '' }}">
                        @else
                            <select name="division_id" class="form-select">
                                <option value="" selected>Seluruh Divisi (Perusahaan)</option>
                                @foreach($divisions as $division)
                                    <option value="{{ $division->id }}" {{ request('division_id') == $division->id ? 'selected' : '' }}>{{ $division->name }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="mdi mdi-rhombus-split me-1"></i> Jalankan Simulasi
                        </button>
                    </div>
                </form>

                @if($results)
                @php
                    $cardClass = [
                        'best' => 'bg-success',
                        'base' => 'bg-info',
                        'worst' => 'bg-warning',
                    ];
                @endphp
                <div class="row g-3 mb-4">
                    @foreach($results as $key => $result)
                    <div class="col-md-4">
                        <div class="card border-0 {{ $cardClass[$key] }} text-white shadow-sm">
                            <div class="card-body py-3">
                                <small class="text-white-50 text-uppercase fw-bold">{{ $result['label'] }}</small>
                                <h6 class="mb-1 mt-2">Pendapatan: Rp {{ number_format($result['revenue'], 0, ',', '.') }}</h6>
                                <h6 class="mb-1">Beban: Rp {{ number_format($result['expense'], 0, ',', '.') }}</h6>
                                <h6 class="mb-1 fw-bold">Laba: Rp {{ number_format($result['profit'], 0, ',', '.') }}</h6>
                                <small class="text-white-50">Margin: {{ number_format($result['margin'], 2, ',', '.') }}%</small>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-lg-6">
                        <div class="card border shadow-sm">
                            <div class="card-header bg-light py-2 fw-bold"><i class="mdi mdi-chart-line me-1"></i> Proyeksi Pendapatan Bulanan per Skenario</div>
                            <div class="card-body">
                                <div id="chart-revenue"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card border shadow-sm">
                            <div class="card-header bg-light py-2 fw-bold"><i class="mdi mdi-chart-line me-1"></i> Proyeksi Laba Bulanan per Skenario</div>
                            <div class="card-body">
                                <div id="chart-profit"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border shadow-sm">
                    <div class="card-header bg-light py-2 fw-bold">Ringkasan Simulasi</div>
                    <div class="card-body py-2 table-responsive p-0">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Skenario</th>
                                    <th class="text-center">Multiplier</th>
                                    <th class="text-end">Pendapatan</th>
                                    <th class="text-end">Beban</th>
                                    <th class="text-end">Laba</th>
                                    <th class="text-center">Margin</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($results as $result)
                                <tr>
                                    <td class="fw-bold">{{ $result['label'] }}</td>
                                    <td class="text-center">{{ $result['multiplier'] }}×</td>
                                    <td class="text-end">Rp {{ number_format($result['revenue'], 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($result['expense'], 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold">Rp {{ number_format($result['profit'], 0, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($result['margin'], 2, ',', '.') }}%</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @else
                <div class="text-center text-muted py-5">
                    <i class="mdi mdi-rhombus-split mdi-48px d-block mb-2"></i>
                    Pilih Tahun RKAP (dan divisi) untuk menjalankan simulasi skenario laba rugi.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('plugin')
@if($results)
<script src="{{ asset('libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
    $(document).ready(function() {
        var labels = @json($monthLabels);
        var scenarioColor = {
            best: '#34c38f',
            base: '#556ee6',
            worst: '#f7b84b'
        };
        var labelsShort = {
            best: 'Best Case',
            base: 'Base Case',
            worst: 'Worst Case'
        };

        var revenueSeries = [];
        var profitSeries = [];

        @foreach(array_keys($results) as $key)
        profitSeries.push({
            name: labelsShort['{{ $key }}'],
            data: @json($results[$key]['monthly_revenue']).map(function(rev, idx) {
                return rev - @json($results[$key]['monthly_expense'])[idx];
            })
        });
        revenueSeries.push({
            name: labelsShort['{{ $key }}'],
            data: @json($results[$key]['monthly_revenue'])
        });
        @endforeach

        function baseOptions(series, colors) {
            return {
                chart: { type: 'line', height: 320, toolbar: { show: false } },
                series: series,
                colors: colors,
                stroke: { width: 3, curve: 'smooth' },
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
        }

        var revenueChart = new ApexCharts(document.querySelector('#chart-revenue'),
            baseOptions(revenueSeries, ['#34c38f', '#556ee6', '#f7b84b']));
        revenueChart.render();

        var profitChart = new ApexCharts(document.querySelector('#chart-profit'),
            baseOptions(profitSeries, ['#34c38f', '#556ee6', '#f7b84b']));
        profitChart.render();
    });
</script>
@endif
@endsection