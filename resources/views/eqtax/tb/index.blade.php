@php
$authUserName = auth()->user()->name;
@endphp

@extends('layouts.EQTax')

@section('title', 'EQTax - Pencocokkan Trial Balance')

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

    .bg-cyan-grad {
        background: linear-gradient(135deg, #06b6d4 0%, #22d3ee 100%);
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
        padding: 20px;
        margin-bottom: 20px;
    }

    .info-box {
        background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .info-box h6 {
        color: #3730a3;
        font-weight: 700;
    }

    .info-box p {
        color: #4338ca;
        margin-bottom: 0;
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
                </div>
            </div>

            <div class="card-body">
                @can('eqtax.tb.process')
                <form action="{{ route('eqtax.tb.process') }}" method="POST" class="filter-form">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
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
                        <div class="col-md-4">
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
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="mdi mdi-cog me-1"></i> Proses Pencocokkan
                            </button>
                        </div>
                    </div>
                </form>
                @endcan

                @if(isset($summary))
                @can('eqtax.tb.save')
                <div class="card mb-4 border-primary">
                    <div class="card-body">
                        <div class="row align-items-center g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">PPN Trial Balance (TB)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control" id="ppn_tb_input"
                                           value="{{ isset($summary['ppn_tb']) && $summary['ppn_tb'] !== null ? format_number($summary['ppn_tb']) : '' }}"
                                           placeholder="Masukkan total PPN dari TB"
                                           oninput="formatNumberInput(this)">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Keterangan</label>
                                <input type="text" class="form-control" id="tb_keterangan"
                                       value="{{ $summary['keterangan'] ?? '' }}"
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
                @endcan

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card stat-card bg-indigo-grad">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white mb-1">Total PPN SPT</h6>
                                        <h4 class="text-white mb-0">{{ format_rupiah($summary['total_ppn_spt']) }}</h4>
                                    </div>
                                    <div class="icon-overlay">
                                        <i class="fas fa-file-invoice-dollar"></i>
                                    </div>
                                </div>
                                <small class="text-white-50">Dari laporan SPT Coretax</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card bg-amber-grad">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white mb-1">Total PPN GL</h6>
                                        <h4 class="text-white mb-0">{{ format_rupiah($summary['total_ppn_gl']) }}</h4>
                                    </div>
                                    <div class="icon-overlay">
                                        <i class="fas fa-book"></i>
                                    </div>
                                </div>
                                <small class="text-white-50">Dari General Ledger</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card bg-purple-grad">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white mb-1">PPN Trial Balance</h6>
                                        <h4 class="text-white mb-0">
                                            @if($summary['ppn_tb'] !== null)
                                                {{ format_rupiah($summary['ppn_tb']) }}
                                            @else
                                                -
                                            @endif
                                        </h4>
                                    </div>
                                    <div class="icon-overlay">
                                        <i class="fas fa-balance-scale-left"></i>
                                    </div>
                                </div>
                                <small class="text-white-50">Input manual dari user</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card stat-card {{ ($summary['selisih_tb_vs_spt'] ?? 0) >= 0 ? 'bg-emerald-grad' : 'bg-red-grad' }}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white mb-1">Selisih TB vs SPT</h6>
                                        <h4 class="text-white mb-0">
                                            @if($summary['selisih_tb_vs_spt'] !== null)
                                                {{ format_rupiah(abs($summary['selisih_tb_vs_spt'])) }}
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
                    <div class="col-md-4">
                        <div class="card stat-card {{ ($summary['selisih_tb_vs_gl'] ?? 0) >= 0 ? 'bg-emerald-grad' : 'bg-red-grad' }}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white mb-1">Selisih TB vs GL</h6>
                                        <h4 class="text-white mb-0">
                                            @if($summary['selisih_tb_vs_gl'] !== null)
                                                {{ format_rupiah(abs($summary['selisih_tb_vs_gl'])) }}
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
                    <div class="col-md-4">
                        <div class="card stat-card bg-cyan-grad">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white mb-1">Masa Pajak</h6>
                                        <h4 class="text-white mb-0">{{ $summary['masa_pajak'] }} {{ $summary['tahun'] }}</h4>
                                    </div>
                                    <div class="icon-overlay">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                </div>
                                <small class="text-white-50">Periode pencocokkan</small>
                            </div>
                        </div>
                    </div>
                </div>

                @else
                <div class="text-center py-5">
                    <i class="fas fa-balance-scale fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Pilih periode dan klik "Proses Pencocokkan" untuk melihat perbandingan</h5>
                    <p class="text-muted">Masukkan angka PPN dari Trial Balance untuk dibandingkan dengan SPT dan GL</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('plugin')
<script>
    function showToast(type, message) {
        const icon = type === 'success' ? 'fa-check-circle text-success' : 'fa-exclamation-circle text-danger';
        const $toast = $(`<div class="toast show" role="alert"><div class="toast-body d-flex align-items-center"><i class="fas ${icon} me-2"></i>${message}</div></div>`);
        $('#toastContainer').append($toast);
        setTimeout(() => $toast.fadeOut(300, () => $toast.remove()), 3000);
    }

    function formatNumberInput(el) {
        let val = el.value.replace(/\D/g, '');
        el.value = val.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    @if(isset($summary))
    function saveTB() {
        const rawVal = $('#ppn_tb_input').val().replace(/\./g, '');
        const keterangan = $('#tb_keterangan').val();

        if (rawVal === '' || isNaN(rawVal)) {
            showToast('error', 'Masukkan angka PPN TB yang valid');
            return;
        }

        $.ajax({
            url: '{{ route("eqtax.tb.save") }}',
            type: 'POST',
            data: JSON.stringify({
                period: '{{ $summary["period"] }}',
                ppn_tb: parseFloat(rawVal),
                keterangan: keterangan
            }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) {
                    showToast('success', response.message);
                    location.reload();
                }
            },
            error: function(xhr) {
                showToast('error', xhr.responseJSON?.message || 'Gagal menyimpan');
            }
        });
    }
    @endif
</script>
@endsection
