@php
$authUserName = auth()->user()->name;
@endphp

@extends('layouts.EQTax')

@section('title', 'EQTax - General Ledger')

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

    .entity-badge {
        background: #e0e7ff;
        color: #3730a3;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
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
                <form action="{{ route('eqtax.gl.index') }}" method="GET" class="filter-form">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="search" class="form-label fw-bold">Cari</label>
                            <input type="text" name="search" id="search" class="form-control" placeholder="No Faktur / Nama Supplier / Jurnal No" value="{{ request('search') }}">
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
                            <label for="sheet" class="form-label fw-bold">Sheet</label>
                            <select name="sheet" id="sheet" class="form-select">
                                <option value="">-- Semua --</option>
                                @foreach($sheets as $sh)
                                <option value="{{ $sh }}" {{ request('sheet') == $sh ? 'selected' : '' }}>{{ $sh }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="date_from" class="form-label fw-bold">Dari Tanggal</label>
                            <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="date_to" class="form-label fw-bold">Sampai Tanggal</label>
                            <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-1 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="mdi mdi-magnify"></i>
                            </button>
                            <a href="{{ route('eqtax.gl.index') }}" class="btn btn-secondary">
                                <i class="mdi mdi-refresh"></i>
                            </a>
                        </div>
                    </div>
                </form>

                <div class="row g-3 mb-4">
                    <div class="col-md-2">
                        <div class="card stat-card bg-indigo-grad">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white mb-1">Total Records</h6>
                                        <h4 class="text-white mb-0">{{ number_format($totalRecords) }}</h4>
                                    </div>
                                    <div class="icon-overlay">
                                        <i class="fas fa-database"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card bg-amber-grad">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white mb-1">Total DPP</h6>
                                        <h6 class="text-white mb-0">Rp {{ number_format($totalDpp, 0, ',', '.') }}</h6>
                                    </div>
                                    <div class="icon-overlay">
                                        <i class="fas fa-coins"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card bg-emerald-grad">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white mb-1">Total PPN</h6>
                                        <h6 class="text-white mb-0">Rp {{ number_format($totalPpn, 0, ',', '.') }}</h6>
                                    </div>
                                    <div class="icon-overlay">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @foreach($entitySummary as $entity)
                    <div class="col-md-2">
                        <div class="card stat-card bg-indigo-grad">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white mb-1">{{ $entity->entity ?? 'Unknown' }}</h6>
                                        <h6 class="text-white mb-0">{{ number_format($entity->count) }} records</h6>
                                        <small class="text-white-50">PPN: Rp {{ number_format($entity->total_ppn, 0, ',', '.') }}</small>
                                    </div>
                                    <div class="icon-overlay">
                                        <i class="fas fa-building"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                    <div class="col-md-2">
                        <div class="card stat-card bg-emerald-grad">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white mb-1">Import Data</h6>
                                        <form action="{{ route('eqtax.gl.import') }}" id="file-form" enctype="multipart/form-data" method="POST">
                                            @csrf
                                            <input type="file" name="file" id="file" hidden>
                                            <label for="file" class="btn btn-light btn-sm mt-2 cursor-pointer">
                                                <i class="fas fa-upload me-1"></i> Upload File GL
                                            </label>
                                        </form>
                                    </div>
                                    <div class="icon-overlay">
                                        <i class="fas fa-file-import"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100" id="gl-table">
                        <thead class="table-dark">
                            <tr class="align-middle">
                                <th>No</th>
                                <th>Entity</th>
                                <th>No Supplier</th>
                                <th>Nama Supplier</th>
                                <th>No Faktur Pajak</th>
                                <th>Jurnal Date</th>
                                <th>Jurnal No</th>
                                <th>Invoice No</th>
                                <th>DPP</th>
                                <th>PPN</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($glData as $key => $dt)
                            <tr>
                                <td>{{ ($glData->currentPage() - 1) * $glData->perPage() + $key + 1 }}</td>
                                <td><span class="entity-badge">{{ $dt->entity ?? $dt->sheet }}</span></td>
                                <td>{{ $dt->no_supplier }}</td>
                                <td>{{ $dt->nama_supplier }}</td>
                                <td class="fw-bold">{{ $dt->no_faktur_pajak }}</td>
                                <td>{{ $dt->jurnal_date }}</td>
                                <td>{{ $dt->jurnal_no }}</td>
                                <td>{{ $dt->invoice_no }}</td>
                                <td class="text-end">Rp {{ number_format($dt->dpp, 0, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format($dt->ppn, 0, ',', '.') }}</td>
                                <td>{{ Str::limit($dt->keterangan, 30) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted">Belum ada data. Silakan import file GL terlebih dahulu.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center">
                    {{ $glData->links('pagination::bootstrap-4') }}
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
        if(uploadFile.files.length > 0) {
            uploadFileForm.submit()
        }
    })
</script>
@endsection
