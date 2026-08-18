@php
$authUserName = auth()->user()->name;
@endphp

@extends('layouts.EQTax')

@section('title', 'Dashboard EQTax')

@section('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root {
        --primary-grad: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
        --warning-grad: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
        --success-grad: linear-gradient(135deg, #10b981 0%, #34d399 100%);
        --danger-grad: linear-gradient(135deg, #ef4444 0%, #f87171 100%);
        --info-grad: linear-gradient(135deg, #059669 0%, #059669 100%);
    }

    body {
        background-color: #f1f5f9;
        font-family: 'Inter', sans-serif;
    }

    .card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        transition: 0.3s;
    }

    .card:hover {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .stat-card {
        color: white;
        position: relative;
        overflow: hidden;
        border-radius: 12px;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .bg-indigo-grad {
        background: var(--primary-grad);
    }

    .bg-amber-grad {
        background: var(--warning-grad);
    }

    .bg-emerald-grad {
        background: var(--success-grad);
    }

    .bg-danger-grad {
        background: var(--danger-grad);
    }

    .bg-info-grad {
        background: var(--info-grad);
    }

    .icon-overlay {
        position: absolute;
        right: -10px;
        bottom: -10px;
        font-size: 4rem;
        opacity: 0.15;
        transform: rotate(-15deg);
    }

    .entity-badge {
        background: #e0e7ff;
        color: #3730a3;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .summary-card {
        border-left: 4px solid;
        padding-left: 16px;
    }

    .summary-card.spt {
        border-color: #4f46e5;
    }

    .summary-card.gl {
        border-color: #f59e0b;
    }

    .summary-card.selisih {
        border-color: #10b981;
    }


    .icon-overlay {
        position: absolute;
        right: -10px;
        bottom: -10px;
        font-size: 4rem;
        opacity: 0.15;
        transform: rotate(-15deg);
    }

    .status-match {
        background-color: #d1fae5;
        color: #065f46;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status-spt-only {
        background-color: #fef3c7;
        color: #92400e;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status-gl-only {
        background-color: #fee2e2;
        color: #991b1b;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .selisih-positive {
        color: #059669;
        font-weight: 600;
    }

    .selisih-negative {
        color: #dc2626;
        font-weight: 600;
    }

    .selisih-zero {
        color: #6b7280;
    }

    .filter-form {
        background: #f8fafc;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .summary-card {
        border-left: 4px solid;
        padding-left: 16px;
    }

    .summary-card.match {
        border-color: #10b981;
    }

    .summary-card.spt-only {
        border-color: #f59e0b;
    }

    .summary-card.gl-only {
        border-color: #ef4444;
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-0">
    <div class="d-flex align-items-start justify-content-between mb-4">
        <div>
            <h1 class="fw-bold text-dark mb-0">Halo, {{ explode(" ", $authUserName)[0] }}!</h1>
            <p>Selamat datang di EQTax - Aplikasi Restitusi Pajak</p>
        </div>
        <span class="text-muted"><i class="fa-regular fa-calendar me-2"></i>{{ date('d F Y') }}</span>
    </div>

    <div class="row g-4 mb-2">
        <div class="col-md-3">
            <div class="card stat-card bg-indigo-grad">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white mb-1">Total SPT Pajak</h6>
                            <h3 class="text-white mb-0">{{ number_format($totalSpt) }}</h3>
                            <small class="text-white-50">Faktur Pajak</small>
                        </div>
                        <div class="icon-overlay">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-amber-grad">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white mb-1">Total GL</h6>
                            <h3 class="text-white mb-0">{{ number_format($totalGl) }}</h3>
                            <small class="text-white-50">Baris Data</small>
                        </div>
                        <div class="icon-overlay">
                            <i class="fas fa-book"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-danger-grad">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white mb-1">Selisih PPN (Kurang Bayar)</h6>
                            <h4 class="text-white mb-0">Rp {{ number_format($selisihKurangBayar, 0, ',', '.') }}</h4>
                            <small class="text-white-50">{{ $countKurangBayar }} faktur</small>
                        </div>
                        <div class="icon-overlay">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-info-grad">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white mb-1">Selisih PPN (Lebih Bayar)</h6>
                            <h4 class="text-white mb-0">Rp {{ number_format(abs($selisihLebihBayar), 0, ',', '.') }}</h4>
                            <small class="text-white-50">{{ $countLebihBayar }} faktur</small>
                        </div>
                        <div class="icon-overlay">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-12">
            <div class="card-body">
                <form action="{{ route('eqtax.index') }}" method="GET" class="filter-form">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label for="year" class="form-label fw-bold">Tahun</label>
                            <select name="year" id="year" class="form-select">
                                <option value="">-- Semua Tahun --</option>
                                @foreach($years as $year)
                                <option value="{{ $year }}" {{ ($filterYear == $year) ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="month_from" class="form-label fw-bold">Bulan Dari</label>
                            <select name="month_from" id="month_from" class="form-select">
                                <option value="">-- Semua --</option>
                                @php
                                $monthNames = [
                                '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
                                '04' => 'April', '05' => 'Mei', '06' => 'Juni',
                                '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
                                '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
                                ];
                                @endphp
                                @foreach($months as $m)
                                <option value="{{ $m }}" {{ ($filterMonthFrom == $m) ? 'selected' : '' }}>
                                    {{ $monthNames[$m] ?? $m }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="month_to" class="form-label fw-bold">Sampai Bulan</label>
                            <select name="month_to" id="month_to" class="form-select">
                                <option value="">-- Semua --</option>
                                @foreach($months as $m)
                                <option value="{{ $m }}" {{ ($filterMonthTo == $m) ? 'selected' : '' }}>
                                    {{ $monthNames[$m] ?? $m }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="entity" class="form-label fw-bold">Entity</label>
                            <select name="entity" id="entity" class="form-select">
                                <option value="">-- Semua Entity --</option>
                                @foreach($distinctEntities as $ent)
                                <option value="{{ $ent }}" {{ ($filterEntity == $ent) ? 'selected' : '' }}>
                                    {{ $ent }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="status_filter" class="form-label fw-bold">Status</label>
                            <select name="status" id="status_filter" class="form-select">
                                <option value="">-- Semua Status --</option>
                                <option value="MATCH" {{ ($filterStatus == 'MATCH') ? 'selected' : '' }}>Match</option>
                                <option value="SPT_ONLY" {{ ($filterStatus == 'SPT_ONLY') ? 'selected' : '' }}>SPT Only</option>
                                <option value="GL_ONLY" {{ ($filterStatus == 'GL_ONLY') ? 'selected' : '' }}>GL Only</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                        </div>
                        <div class="col-md-1">
                            <a href="{{ route('eqtax.index') }}" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center">
                                <i class="fas fa-undo me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100" id="equalization-table">
                        <thead class="table-dark">
                            <tr class="align-middle">
                                <th>Periode</th>
                                <th>No Faktur Pajak</th>
                                <th>Nama Penjual</th>
                                <th>DPP SPT</th>
                                <th>DPP GL</th>
                                <th>PPN SPT</th>
                                <th>PPN GL</th>
                                <th>Selisih PPN</th>
                                <th>Status</th>
                                <th>Entity</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentEqualization as $key => $dt)
                            <tr>
                                <td>{{ $dt->period }}</td>
                                <td class="fw-bold">{{ $dt->no_faktur_pajak }}</td>
                                <td>{{ $dt->nama_penjual }}</td>
                                <td class="text-end">Rp {{ number_format($dt->dpp_spt, 0, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format($dt->dpp_gl, 0, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format($dt->ppn_spt, 0, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format($dt->ppn_gl, 0, ',', '.') }}</td>
                                <td class="text-end {{ $dt->selisih_ppn > 0 ? 'selisih-negative' : ($dt->selisih_ppn < 0 ? 'selisih-positive' : 'selisih-zero') }}">
                                    Rp {{ number_format(abs($dt->selisih_ppn), 0, ',', '.') }}
                                </td>
                                <td>
                                    @if($dt->status == 'MATCH')
                                    <span class="status-match">Match</span>
                                    @elseif($dt->status == 'SPT_ONLY')
                                    <span class="status-spt-only">SPT Only</span>
                                    @else
                                    <span class="status-gl-only">GL Only</span>
                                    @endif
                                </td>
                                <td>{{ $dt->entity }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted">Tidak ada data untuk filter yang dipilih.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center">
                    {{ $recentEqualization->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('plugin')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('selisihChart').getContext('2d');

        const labels = @json($chartLabels);
        const kurangBayar = @json($chartKurangBayar);
        const lebihBayar = @json($chartLebihBayar);

        const selisihChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Kurang Bayar',
                        data: kurangBayar,
                        borderColor: 'rgba(239, 68, 68, 1)',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        borderWidth: 3,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointBackgroundColor: 'rgba(239, 68, 68, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        fill: true,
                        tension: 0.3,
                    },
                    {
                        label: 'Lebih Bayar (Restitusi)',
                        data: lebihBayar,
                        borderColor: 'rgba(16, 185, 129, 1)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 3,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointBackgroundColor: 'rgba(16, 185, 129, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        fill: true,
                        tension: 0.3,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                size: 12,
                                weight: '600'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: { size: 13, weight: '600' },
                        bodyFont: { size: 12 },
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                let value = context.parsed.y;
                                return context.dataset.label + ': Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: { size: 11 }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            font: { size: 11 },
                            callback: function(value) {
                                if (value >= 1000000000) {
                                    return 'Rp ' + (value / 1000000000).toFixed(1) + ' M';
                                } else if (value >= 1000000) {
                                    return 'Rp ' + (value / 1000000).toFixed(1) + ' JT';
                                } else if (value >= 1000) {
                                    return 'Rp ' + (value / 1000).toFixed(0) + ' RB';
                                }
                                return 'Rp ' + value;
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endsection
