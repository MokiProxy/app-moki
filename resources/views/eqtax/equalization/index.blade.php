@php
$authUserName = auth()->user()->name;
@endphp

@extends('layouts.EQTax')

@section('title', 'EQTax - Ekualisasi Pajak')

@section('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root {
        --primary-grad: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
        --warning-grad: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
        --success-grad: linear-gradient(135deg, #10b981 0%, #34d399 100%);
        --danger-grad: linear-gradient(135deg, #ef4444 0%, #f87171 100%);
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

    .bg-red-grad {
        background: var(--danger-grad);
    }

    .bg-purple-grad {
        background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);
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
<div id="toastContainer" style="position:fixed;top:20px;right:20px;z-index:9999;"></div>
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">{{ $pageName }}</h5>
                    @if(isset($summary) && isset($results) && $results->count() > 0)
                    <a href="{{ route('eqtax.equalization.export', ['masa_pajak' => $summary['masa_pajak'], 'tahun' => $summary['tahun'], 'entity' => $summary['entity'] !== 'Semua' ? $summary['entity'] : '']) }}" class="btn btn-success me-2">
                        <i class="mdi mdi-file-excel me-1"></i> Export Excel
                    </a>
                    @endif
                </div>
            </div>

            <div class="card-body">
                <form action="{{ route('eqtax.equalization.process') }}" method="POST" class="filter-form">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="masa_pajak" class="form-label fw-bold">Masa Pajak</label>
                            <select name="masa_pajak" id="masa_pajak" class="form-select" required>
                                <option value="">-- Pilih Masa Pajak --</option>
                                @foreach($distinctPeriods as $period)
                                <option value="{{ $period->masa_pajak }}" {{ (isset($summary) && $summary['masa_pajak'] == $period->masa_pajak) ? 'selected' : '' }}>
                                    {{ $period->masa_pajak }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="tahun" class="form-label fw-bold">Tahun</label>
                            <select name="tahun" id="tahun" class="form-select" required>
                                <option value="">-- Pilih Tahun --</option>
                                @foreach($distinctPeriods->pluck('tahun')->unique() as $year)
                                <option value="{{ $year }}" {{ (isset($summary) && $summary['tahun'] == $year) ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="entity" class="form-label fw-bold">Entity (Opsional)</label>
                            <select name="entity" id="entity" class="form-select">
                                <option value="">-- Semua Entity --</option>
                                @foreach($distinctEntities as $ent)
                                <option value="{{ $ent->entity }}" {{ (isset($summary) && $summary['entity'] == $ent->entity) ? 'selected' : '' }}>
                                    {{ $ent->entity }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="mdi mdi-cog me-1"></i> Proses Ekualisasi
                            </button>
                        </div>
                    </div>
                </form>

                @if(isset($summary))
                <div class="card mb-4 border-primary">
                    <div class="card-body">
                        <div class="row align-items-center g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">PPN Trial Balance (TB)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="ppn_tb_input"
                                           value="{{ $summary['ppn_tb'] ?? '' }}"
                                           placeholder="Masukkan total PPN dari TB">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Keterangan</label>
                                <input type="text" class="form-control" id="tb_keterangan"
                                       placeholder="Catatan (opsional)">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="button" class="btn btn-primary w-100" id="btn-save-tb"
                                        onclick="saveTB()">
                                    <i class="fas fa-save me-1"></i> Simpan TB
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card bg-indigo-grad">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white mb-1">Total PPN SPT</h6>
                                        <h4 class="text-white mb-0">Rp {{ number_format($summary['total_ppn_spt'], 0, ',', '.') }}</h4>
                                    </div>
                                    <div class="icon-overlay">
                                        <i class="fas fa-file-invoice-dollar"></i>
                                    </div>
                                </div>
                                <small class="text-white-50">{{ $summary['total_spt'] }} faktur pajak</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-amber-grad">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white mb-1">Total PPN GL</h6>
                                        <h4 class="text-white mb-0">Rp {{ number_format($summary['total_ppn_gl'], 0, ',', '.') }}</h4>
                                    </div>
                                    <div class="icon-overlay">
                                        <i class="fas fa-book"></i>
                                    </div>
                                </div>
                                <small class="text-white-50">{{ $summary['total_gl'] }} faktur pajak</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card {{ $summary['total_selisih'] >= 0 ? 'bg-emerald-grad' : 'bg-red-grad' }}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white mb-1">Total Selisih PPN</h6>
                                        <h4 class="text-white mb-0">Rp {{ number_format($summary['total_selisih'], 0, ',', '.') }}</h4>
                                    </div>
                                    <div class="icon-overlay">
                                        <i class="fas fa-balance-scale"></i>
                                    </div>
                                </div>
                                <small class="text-white-50">Kandidat restitusi</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-emerald-grad">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white mb-1">Total Faktur Cocok</h6>
                                        <h4 class="text-white mb-0">{{ number_format($summary['count_match']) }}</h4>
                                    </div>
                                    <div class="icon-overlay">
                                        <i class="fas fa-check-double"></i>
                                    </div>
                                </div>
                                <small class="text-white-50">Dari {{ $summary['total_spt'] }} faktur SPT</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card bg-amber-grad">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white mb-1">Hanya di SPT</h6>
                                        <h4 class="text-white mb-0">{{ number_format($summary['count_spt_only']) }}</h4>
                                    </div>
                                    <div class="icon-overlay">
                                        <i class="fas fa-file-invoice"></i>
                                    </div>
                                </div>
                                <small class="text-white-50">Faktur tidak ada di GL</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-red-grad">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white mb-1">Hanya di GL</h6>
                                        <h4 class="text-white mb-0">{{ number_format($summary['count_gl_only']) }}</h4>
                                    </div>
                                    <div class="icon-overlay">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                </div>
                                <small class="text-white-50">Faktur tidak ada di SPT</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-info-grad">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white mb-1">Masa Pajak</h6>
                                        <h4 class="text-white mb-0">{{ $summary['masa_pajak'] }}</h4>
                                    </div>
                                    <div class="icon-overlay">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                </div>
                                <small class="text-white-50">Tahun {{ $summary['tahun'] }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-indigo-grad">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white mb-1">Entity</h6>
                                        <h4 class="text-white mb-0" style="font-size: 1.1rem;">{{ $summary['entity'] }}</h4>
                                    </div>
                                    <div class="icon-overlay">
                                        <i class="fas fa-building"></i>
                                    </div>
                                </div>
                                <small class="text-white-50">Filter yang dipilih</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card bg-purple-grad">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white mb-1">PPN Trial Balance</h6>
                                        <h4 class="text-white mb-0">
                                            @if($summary['ppn_tb'] !== null)
                                                Rp {{ number_format($summary['ppn_tb'], 0, ',', '.') }}
                                            @else
                                                -
                                            @endif
                                        </h4>
                                    </div>
                                    <div class="icon-overlay">
                                        <i class="fas fa-balance-scale-left"></i>
                                    </div>
                                </div>
                                <small class="text-white-50">Data dari TB (input manual)</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card {{ ($summary['selisih_tb_vs_spt'] ?? 0) >= 0 ? 'bg-emerald-grad' : 'bg-red-grad' }}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white mb-1">Selisih TB vs SPT</h6>
                                        <h4 class="text-white mb-0">
                                            @if($summary['selisih_tb_vs_spt'] !== null)
                                                Rp {{ number_format(abs($summary['selisih_tb_vs_spt']), 0, ',', '.') }}
                                            @else
                                                -
                                            @endif
                                        </h4>
                                    </div>
                                    <div class="icon-overlay">
                                        <i class="fas fa-not-equal"></i>
                                    </div>
                                </div>
                                <small class="text-white-50">
                                    @if($summary['selisih_tb_vs_spt'] !== null)
                                        TB {{ $summary['selisih_tb_vs_spt'] >= 0 ? 'lebih besar' : 'lebih kecil' }} dari SPT
                                    @else
                                        Input TB terlebih dahulu
                                    @endif
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card {{ ($summary['selisih_tb_vs_gl'] ?? 0) >= 0 ? 'bg-emerald-grad' : 'bg-red-grad' }}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white mb-1">Selisih TB vs GL</h6>
                                        <h4 class="text-white mb-0">
                                            @if($summary['selisih_tb_vs_gl'] !== null)
                                                Rp {{ number_format(abs($summary['selisih_tb_vs_gl']), 0, ',', '.') }}
                                            @else
                                                -
                                            @endif
                                        </h4>
                                    </div>
                                    <div class="icon-overlay">
                                        <i class="fas fa-not-equal"></i>
                                    </div>
                                </div>
                                <small class="text-white-50">
                                    @if($summary['selisih_tb_vs_gl'] !== null)
                                        TB {{ $summary['selisih_tb_vs_gl'] >= 0 ? 'lebih besar' : 'lebih kecil' }} dari GL
                                    @else
                                        Input TB terlebih dahulu
                                    @endif
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100" id="equalization-table">
                        <thead class="table-dark">
                            <tr class="align-middle">
                                <th>No</th>
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
                            @forelse($results as $key => $dt)
                            <tr>
                                <td>{{ $key + 1 }}</td>
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
                                <td>{{ $dt->entities }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted">Belum ada data. Silakan proses ekualisasi terlebih dahulu.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Pilih periode dan klik "Proses Ekualisasi" untuk melihat hasil</h5>
                    <p class="text-muted">Hasil akan menampilkan pencocokan antara data SPT Pajak dan General Ledger</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('plugin')
<script>
    $(document).ready(function() {
        $('#equalization-table').DataTable({
            "order": [[7, "desc"]],
            "pageLength": 25,
            "language": {
                "search": "Cari:",
                "lengthMenu": "Tampilkan _MENU_ data",
                "info": "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                "emptyTable": "Tidak ada data",
                "zeroRecords": "Tidak ada data yang cocok"
            }
        });
    });

    function showToast(type, message) {
        const icon = type === 'success' ? 'fa-check-circle text-success' : 'fa-exclamation-circle text-danger';
        const $toast = $(`<div class="toast show" role="alert"><div class="toast-body d-flex align-items-center"><i class="fas ${icon} me-2"></i>${message}</div></div>`);
        $('#toastContainer').append($toast);
        setTimeout(() => $toast.fadeOut(300, () => $toast.remove()), 3000);
    }

    function saveTB() {
        const ppnTb = $('#ppn_tb_input').val();
        const keterangan = $('#tb_keterangan').val();

        if (ppnTb === '' || isNaN(ppnTb)) {
            showToast('error', 'Masukkan angka PPN TB yang valid');
            return;
        }


    }
</script>
@endsection
