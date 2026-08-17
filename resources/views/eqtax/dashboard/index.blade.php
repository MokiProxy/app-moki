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
        --info-grad: linear-gradient(135deg, #06b6d4 0%, #22d3ee 100%);
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

    .table thead th {
        background-color: #f8fafc;
        color: #64748b;
        font-size: 0.75rem;
        text-transform: uppercase;
        border: none;
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

    <div class="row g-4 mb-4">
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
            <div class="card stat-card bg-emerald-grad">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white mb-1">PPN SPT</h6>
                            <h4 class="text-white mb-0">Rp {{ number_format($totalPpnSpt, 0, ',', '.') }}</h4>
                        </div>
                        <div class="icon-overlay">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card {{ $selisihPpn >= 0 ? 'bg-info-grad' : 'bg-danger-grad' }}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white mb-1">Selisih PPN</h6>
                            <h4 class="text-white mb-0">Rp {{ number_format($selisihPpn, 0, ',', '.') }}</h4>
                            <small class="text-white-50">Kandidat Restitusi</small>
                        </div>
                        <div class="icon-overlay">
                            <i class="fas fa-balance-scale"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title fw-bold">Ringkasan per Entity</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Entity</th>
                                    <th class="text-end">Jumlah Records</th>
                                    <th class="text-end">Total PPN</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($entitySummary as $entity)
                                <tr>
                                    <td><span class="entity-badge">{{ $entity->entity ?? 'Unknown' }}</span></td>
                                    <td class="text-end">{{ number_format($entity->count) }}</td>
                                    <td class="text-end">Rp {{ number_format($entity->total_ppn, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Belum ada data GL</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title fw-bold">Aksi Cepat</h5>
                    <div class="d-grid gap-2">
                        <a href="{{ route('eqtax.spt.coretax.index') }}" class="btn btn-outline-primary">
                            <i class="fas fa-upload me-2"></i> Import SPT Pajak
                        </a>
                        <a href="{{ route('eqtax.gl.index') }}" class="btn btn-outline-warning">
                            <i class="fas fa-upload me-2"></i> Import General Ledger
                        </a>
                        <a href="{{ route('eqtax.equalization.index') }}" class="btn btn-outline-success">
                            <i class="fas fa-balance-scale me-2"></i> Proses Ekualisasi
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title fw-bold">Data SPT Terbaru</h5>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>No Faktur Pajak</th>
                                    <th>Nama Penjual</th>
                                    <th class="text-end">PPN</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentSpt as $spt)
                                <tr>
                                    <td class="fw-bold">{{ Str::limit($spt->no_faktur_pajak, 20) }}</td>
                                    <td>{{ Str::limit($spt->nama_penjual, 20) }}</td>
                                    <td class="text-end">Rp {{ number_format($spt->ppn, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Belum ada data</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title fw-bold">Data GL Terbaru</h5>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>No Faktur Pajak</th>
                                    <th>Nama Supplier</th>
                                    <th class="text-end">PPN</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentGl as $gl)
                                <tr>
                                    <td class="fw-bold">{{ Str::limit($gl->no_faktur_pajak, 20) }}</td>
                                    <td>{{ Str::limit($gl->nama_supplier, 20) }}</td>
                                    <td class="text-end">Rp {{ number_format($gl->ppn, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Belum ada data</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
