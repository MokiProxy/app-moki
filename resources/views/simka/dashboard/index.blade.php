@php
$authUserName = auth()->user()->name;
@endphp

@extends('layouts.Simka')

@section('title', 'Dashboard SIMKA')

@section('css')
<style>
    .text-dark { color: #000000 !important; }

    .domain-card {
        border: 1px solid #edf2f7;
        border-radius: 16px;
        background: #fff;
        transition: 0.3s;
        height: 100%;
    }

    .domain-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.08);
    }

    .domain-card .domain-icon {
        width: 56px;
        height: 56px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
    }

    .domain-card .list-group-item {
        border-left: 0;
        border-right: 0;
        padding-left: 0;
        padding-right: 0;
    }

    .domain-card .list-group-item:last-child {
        border-bottom: 0;
    }

    .kpi-card {
        border: 1px solid #edf2f7;
        border-radius: 16px;
        background: #fff;
        height: 100%;
    }

    .kpi-card .kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
    }

    .kpi-card .kpi-value {
        font-size: 1.35rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .kpi-card .kpi-label {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #74788d;
    }
</style>
@endsection

@section('content')
@php
    $rupiah = function ($value) {
        $value = (float) $value;
        $abs = abs($value);
        $sign = $value < 0 ? '-' : '';

        if ($abs >= 1000000000000) {
            return $sign . 'Rp ' . number_format($abs / 1000000000000, 2, ',', '.') . ' T';
        }
        if ($abs >= 1000000000) {
            return $sign . 'Rp ' . number_format($abs / 1000000000, 2, ',', '.') . ' M';
        }
        if ($abs >= 1000000) {
            return $sign . 'Rp ' . number_format($abs / 1000000, 2, ',', '.') . ' Jt';
        }

        return $sign . 'Rp ' . number_format($abs, 0, ',', '.');
    };
    $balance = (float) $summary['balance'];
@endphp

<div class="container-fluid py-2">
    <div class="row mb-4 align-items-center">
        <div class="col-lg-8">
            <h4 class="mb-1">Selamat datang, {{ $authUserName }}</h4>
            <p class="text-muted mb-0">
                Satu modul untuk mengelola <strong>Pendapatan (SIMKA)</strong> dan
                <strong>Pengeluaran (E-RKAP)</strong>.
            </p>
        </div>
        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
            @if ($rkaps->isNotEmpty())
                <form method="GET" action="{{ route('simka.index') }}" class="d-inline-flex align-items-center gap-2">
                    <label for="rkap_id" class="text-muted small mb-0">Periode RKAP</label>
                    <select name="rkap_id" id="rkap_id" class="form-select form-select-sm no-select2"
                            onchange="this.form.submit()" style="width: auto;">
                        @foreach ($rkaps as $rkap)
                            <option value="{{ $rkap->id }}" {{ $selectedRkap?->id === $rkap->id ? 'selected' : '' }}>
                                {{ $rkap->year }}
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>
    </div>

    @if (! $selectedRkap)
        <div class="alert alert-info">
            Belum ada periode RKAP. Angka Pendapatan/Pengeluaran akan tampil setelah
            periode RKAP dibuat pada menu <strong>Lifecycle RKAP</strong>.
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="kpi-label">Pendapatan (Rencana)</span>
                    <span class="kpi-icon bg-primary bg-opacity-10 text-primary">
                        <i class="mdi mdi-cash-multiple"></i>
                    </span>
                </div>
                <div class="kpi-value">{{ $rupiah($summary['revenue_plan']) }}</div>
                <small class="text-muted">
                    {{ $selectedRkap ? 'RKAP ' . $selectedRkap->year : 'Belum ada RKAP' }}
                </small>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="kpi-label">Pengeluaran (Opex + Capex)</span>
                    <span class="kpi-icon bg-warning bg-opacity-10 text-warning">
                        <i class="mdi mdi-cash-register"></i>
                    </span>
                </div>
                <div class="kpi-value">{{ $rupiah($summary['expense_total']) }}</div>
                <small class="text-muted">
                    Rencana {{ $rupiah($summary['expense_plan']) }}
                </small>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="kpi-label">Anggaran Opex + Capex</span>
                    <span class="kpi-icon bg-info bg-opacity-10 text-info">
                        <i class="mdi mdi-chart-bar"></i>
                    </span>
                </div>
                <div class="kpi-value">{{ $rupiah($summary['expense_budget']) }}</div>
                <small class="text-muted">
                    Opex {{ $rupiah($summary['opex_budget']) }} &middot;
                    Capex {{ $rupiah($summary['capex_budget']) }}
                </small>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="kpi-card p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="kpi-label">Realisasi Pengeluaran</span>
                    <span class="kpi-icon bg-success bg-opacity-10 text-success">
                        <i class="mdi mdi-check-decagram"></i>
                    </span>
                </div>
                <div class="kpi-value">{{ $rupiah($summary['realized']) }}</div>
                <small class="text-muted">{{ number_format($summary['absorption'], 2, ',', '.') }}% dari anggaran</small>
            </div>
        </div>
    </div>

    <div class="alert {{ $balance >= 0 ? 'alert-success' : 'alert-danger' }} d-flex align-items-center mb-4">
        <i class="mdi {{ $balance >= 0 ? 'mdi-trending-up' : 'mdi-trending-down' }} fs-4 me-2"></i>
        <div>
            <strong>Selisih pendapatan vs pengeluaran: {{ $rupiah($balance) }}</strong>
            <span class="ms-1">
                ({{ $balance >= 0 ? 'surplus pendapatan terhadap pengeluaran' : 'defisit: pengeluaran lebih besar dari pendapatan' }})
            </span>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h5 class="card-title mb-0">Rencana Pendapatan vs Pengeluaran</h5>
                        <span class="text-muted small">{{ $selectedRkap ? 'RKAP ' . $selectedRkap->year : '-' }}</span>
                    </div>
                    <div id="chart-revenue-expense"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3">Ringkasan</h5>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted">Pendapatan rencana</td>
                                    <td class="text-end fw-semibold">{{ $rupiah($summary['revenue_plan']) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Pengeluaran rencana</td>
                                    <td class="text-end fw-semibold">{{ $rupiah($summary['expense_plan']) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Selisih (pendapatan - total pengeluaran)</td>
                                    <td class="text-end fw-semibold {{ $balance >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $rupiah($balance) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Anggaran Opex</td>
                                    <td class="text-end">{{ $rupiah($summary['opex_budget']) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Anggaran Capex</td>
                                    <td class="text-end">{{ $rupiah($summary['capex_budget']) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Realisasi</td>
                                    <td class="text-end">{{ $rupiah($summary['realized']) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        @can('simka.menu')
            <div class="col-lg-6">
                <div class="domain-card p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="domain-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="mdi mdi-cash-multiple"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">Pendapatan: SIMKA</h5>
                            <small class="text-muted">Sistem Informasi Manajemen Keuangan &amp; Anggaran</small>
                        </div>
                    </div>

                    <p class="text-muted mb-3">
                        Kelola konfigurasi dan tarif SPPH untuk pendapatan perusahaan.
                    </p>

                    <div class="d-flex flex-wrap gap-3 small text-muted mb-3">
                        <span><strong>{{ $summary['spph']['schemes'] }}</strong> skema SPPH</span>
                        <span><strong>{{ $summary['spph']['tarif_entries'] }}</strong> baris tarif</span>
                        <span><strong>{{ $summary['spph']['revenue_categories'] }}</strong> kategori pendapatan</span>
                    </div>

                    <div class="list-group list-group-flush">
                        <a href="{{ route('simka.spph773.index') }}" class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="mdi mdi-chevron-right me-1"></i> SPPH 773</span>
                            <span class="badge bg-light text-dark">Tarif</span>
                        </a>
                        <a href="{{ route('simka.spph2287.index') }}" class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="mdi mdi-chevron-right me-1"></i> SPPH 2287</span>
                            <span class="badge bg-light text-dark">Tarif</span>
                        </a>
                        <a href="{{ route('simka.spph2899.index') }}" class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="mdi mdi-chevron-right me-1"></i> SPPH 2899</span>
                            <span class="badge bg-light text-dark">View</span>
                        </a>
                        <a href="{{ route('simka.spph16368.index') }}" class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="mdi mdi-chevron-right me-1"></i> SPPH 16368</span>
                            <span class="badge bg-light text-dark">Tarif</span>
                        </a>
                    </div>
                </div>
            </div>
        @endcan

        @can('erkap.menu')
            <div class="col-lg-6">
                <div class="domain-card p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="domain-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="mdi mdi-cash-register"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">Pengeluaran: E-RKAP</h5>
                            <small class="text-muted">Manajemen RKAP Digital</small>
                        </div>
                    </div>

                    <p class="text-muted mb-3">
                        Kelola perencanaan, penganggaran, realisasi, dan risiko pengeluaran.
                    </p>

                    <div class="list-group list-group-flush">
                        <a href="{{ route('erkap.index') }}" class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="mdi mdi-chevron-right me-1"></i> Dashboard E-RKAP</span>
                            <span class="badge bg-light text-dark">PCU</span>
                        </a>
                        @can('erkap.rkap.view')
                            <a href="{{ route('erkap.rkap.index') }}" class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="mdi mdi-chevron-right me-1"></i> Lifecycle RKAP</span>
                            </a>
                        @endcan
                        @can('erkap.work-programs.view')
                            <a href="{{ route('erkap.work-programs.index') }}" class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="mdi mdi-chevron-right me-1"></i> Program Kerja</span>
                            </a>
                        @endcan
                        @can('erkap.budget-realizations.view')
                            <a href="{{ route('erkap.budget-realizations.index') }}" class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="mdi mdi-chevron-right me-1"></i> Realisasi Anggaran (BvA)</span>
                            </a>
                        @endcan
                        @can('erkap.reports.view')
                            <a href="{{ route('erkap.reports.index') }}" class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="mdi mdi-chevron-right me-1"></i> Report Center</span>
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        @endcan
    </div>
</div>
@endsection

@section('plugin')
<script src="{{ asset('libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var chartElement = document.querySelector('#chart-revenue-expense');
        if (!chartElement || typeof ApexCharts === 'undefined') {
            return;
        }

        var monthly = @json($summary['monthly']);

        new ApexCharts(chartElement, {
            chart: {
                type: 'bar',
                height: 300,
                toolbar: { show: false }
            },
            series: [
                {
                    name: 'Pendapatan',
                    data: monthly.map(function (item) { return item.revenue; })
                },
                {
                    name: 'Pengeluaran',
                    data: monthly.map(function (item) { return item.expense; })
                }
            ],
            xaxis: {
                categories: monthly.map(function (item) { return item.label; })
            },
            colors: ['#556ee6', '#34c38f'],
            plotOptions: {
                bar: {
                    columnWidth: '45%',
                    borderRadius: 4
                }
            },
            dataLabels: { enabled: false },
            legend: { position: 'top' },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return 'Rp ' + Number(value).toLocaleString('id-ID');
                    }
                }
            }
        }).render();
    });
</script>
@endsection
