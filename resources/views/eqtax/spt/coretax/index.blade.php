@php
$authUserName = auth()->user()->name;
@endphp

@extends('layouts.EQTax')

@section('title', 'EQTax - SPT Coretax')

@section('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root {
        --primary-grad: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
        --warning-grad: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
        --success-grad: linear-gradient(135deg, #10b981 0%, #34d399 100%);
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

    .icon-overlay {
        position: absolute;
        right: -10px;
        bottom: -10px;
        font-size: 4rem;
        opacity: 0.15;
        transform: rotate(-15deg);
    }


    .filter-form {
        background: #f8fafc;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 20px;
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">{{ $pageName }}</h5>
                    <a href="{{ route('eqtax.index') }}" class="btn btn-secondary">
                        <i class="mdi mdi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="row g-3 mb-4 d-flex align-items-center">
                    <div class="col-md-3">
                        <div class="card stat-card bg-indigo-grad">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white mb-1">Total Records</h6>
                                        <h4 class="text-white mb-0">{{ number_format($totalRecords) }}</h4>
                                    </div>
                                    <div class="icon-overlay">
                                        <i class="fas fa-file-invoice"></i>
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
                                        <h6 class="text-white mb-1">Total DPP</h6>
                                        <h4 class="text-white mb-0">Rp {{ number_format($totalDpp, 0, ',', '.') }}</h4>
                                    </div>
                                    <div class="icon-overlay">
                                        <i class="fas fa-coins"></i>
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
                                        <h6 class="text-white mb-1">Total PPN</h6>
                                        <h4 class="text-white mb-0">Rp {{ number_format($totalPpn, 0, ',', '.') }}</h4>
                                    </div>
                                    <div class="icon-overlay">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-indigo-grad">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <form action="{{ route('eqtax.spt.coretax.import') }}" id="file-form" enctype="multipart/form-data" method="POST" class="w-100 d-flex justify-content-center align-items-center h-100">
                                        @csrf
                                        <input type="file" name="file" id="file" hidden>
                                        <label for="file" class="m-0 p-0 cursor-pointer">
                                            <i class="fas fa-upload me-1"></i> Upload File SPT
                                        </label>
                                    </form>
                                    <div class="icon-overlay">
                                        <i class="fas fa-file-import"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <form action="{{ route('eqtax.spt.coretax.index') }}" method="GET" class="filter-form">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="search" class="form-label fw-bold">Cari</label>
                            <input type="text" name="search" id="search" class="form-control" placeholder="No Faktur / Nama Penjual / NPWP" value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="entity" class="form-label fw-bold">Entity</label>
                            <select name="entity" id="entity" class="form-select">
                                <option value="">-- Semua --</option>
                                @foreach($entities as $ent)
                                <option value="{{ $ent }}" {{ request('entity') == $ent ? 'selected' : '' }}>{{ $ent }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="masa_pajak" class="form-label fw-bold">Masa Pajak</label>
                            <select name="masa_pajak" id="masa_pajak" class="form-select">
                                <option value="">-- Semua --</option>
                                @foreach($masaPajakList as $mp)
                                <option value="{{ $mp }}" {{ request('masa_pajak') == $mp ? 'selected' : '' }}>{{ $mp }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="tahun" class="form-label fw-bold">Tahun</label>
                            <select name="tahun" id="tahun" class="form-select">
                                <option value="">-- Semua --</option>
                                @foreach($tahunList as $th)
                                <option value="{{ $th }}" {{ request('tahun') == $th ? 'selected' : '' }}>{{ $th }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="mdi mdi-magnify me-1"></i> Filter
                            </button>
                            <a href="{{ route('eqtax.spt.coretax.index') }}" class="btn btn-secondary">
                                <i class="mdi mdi-refresh"></i>
                            </a>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100" id="spt-table">
                        <thead class="table-dark">
                            <tr class="align-middle">
                                <th>No</th>
                                <th>No Faktur Pajak</th>
                                <th>Nama Penjual</th>
                                <th>NPWP</th>
                                <th>Tanggal FP</th>
                                <th>Masa Pajak</th>
                                <th>DPP</th>
                                <th>PPN</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sptData as $key => $dt)
                            <tr>
                                <td>{{ ($sptData->currentPage() - 1) * $sptData->perPage() + $key + 1 }}</td>
                                <td class="fw-bold">{{ $dt->no_faktur_pajak }}</td>
                                <td>{{ $dt->nama_penjual }}</td>
                                <td>{{ $dt->npwp_penjual }}</td>
                                <td>{{ $dt->tgl_faktur_pajak ? \Carbon\Carbon::parse($dt->tgl_faktur_pajak)->format('d/m/Y') : '-' }}</td>
                                <td>{{ $dt->masa_pajak }} {{ $dt->tahun }}</td>
                                <td class="text-end">Rp {{ number_format($dt->dpp, 0, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format($dt->ppn, 0, ',', '.') }}</td>
                                <td>
                                    <span class="badge {{ $dt->status_faktur == 'CREDITED' ? 'bg-success' : 'bg-warning' }}">
                                        {{ $dt->status_faktur }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">Belum ada data. Silakan import file SPT terlebih dahulu.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center">
                    {{ $sptData->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('plugin')
<script>
    const uploadFileForm = document.getElementById('file-form')
    const uploadFile = document.getElementById('file')
    uploadFile.addEventListener("change", () => {
        if (uploadFile.files.length > 0) {
            uploadFileForm.submit()
        }
    })
</script>
@endsection
