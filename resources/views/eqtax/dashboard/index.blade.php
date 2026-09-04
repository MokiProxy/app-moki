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
        --info-grad: linear-gradient(135deg, #059669 0%, #05b57f 100%);
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
        opacity: 0.9;
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
        opacity: 0.5;
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

    .status-to-be-check {
        background-color: #fee2e2;
        color: #991b1b;
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
        background-color: #fef3c7;
        color: #92400e;
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

    .clickable-widget {
        position: relative;
        transition: all 0.3s ease;
    }

    .clickable-widget:hover {
        transform: translateY(-5px);
    }

    .clickable-widget.active {
        outline: 3px solid #fff;
        outline-offset: -3px;
        transform: translateY(-5px) scale(1.02);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
    }

    .clickable-widget.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: #fff;
        border-radius: 0 0 12px 12px;
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-0">
    <div class="d-flex align-items-start justify-content-between mb-4">
        <div>
            <h1 class="fw-bold text-dark mb-0">Halo, {{ explode(" ", $authUserName)[0] }}!</h1>
            <p>Selamat datang di EQTax - Aplikasi Ekualisasi Pajak</p>
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
                            <h6 class="text-white mb-1">Total General Ledger</h6>
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
            <div class="card stat-card bg-danger-grad clickable-widget" data-type="kurang_bayar" style="cursor: pointer;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white mb-1">Selisih PPN (SPT)</h6>
                            <h4 class="text-white mb-0">{{ format_rupiah($selisihKurangBayar) }}</h4>
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
            <div class="card stat-card bg-info-grad clickable-widget" data-type="lebih_bayar" style="cursor: pointer;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white mb-1">Selisih PPN (GL)</h6>
                            <h4 class="text-white mb-0">{{ format_rupiah(abs($selisihLebihBayar)) }}</h4>
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

    <div class="row g-4 mb-2">
        <div class="col-md-3">
            <div class="card stat-card" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white mb-1">Match</h6>
                            <h3 class="text-white mb-0">{{ number_format($statusSummary['MATCH']) }}</h3>
                            <small class="text-white-50">Data Cocok</small>
                        </div>
                        <div class="icon-overlay">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card" style="background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white mb-1">SPT Only</h6>
                            <h3 class="text-white mb-0">{{ number_format($statusSummary['SPT_ONLY']) }}</h3>
                            <small class="text-white-50">Hanya di SPT</small>
                        </div>
                        <div class="icon-overlay">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card" style="background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white mb-1">GL Only</h6>
                            <h3 class="text-white mb-0">{{ number_format($statusSummary['GL_ONLY']) }}</h3>
                            <small class="text-white-50">Hanya di GL</small>
                        </div>
                        <div class="icon-overlay">
                            <i class="fas fa-book-open"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card" style="background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 100%);">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white mb-1">To Be Check</h6>
                            <h3 class="text-white mb-0">{{ number_format($statusSummary['TO_BE_CHECK']) }}</h3>
                            <small class="text-white-50">Perlu Dicek</small>
                        </div>
                        <div class="icon-overlay">
                            <i class="fas fa-exclamation-triangle"></i>
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
                        <div class="col-md-1">
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
                        <div class="col-md-3">
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
                        <div class="col-md-3">
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
                        <div class="col-md-3">
                            <label for="status_filter" class="form-label fw-bold">Status</label>
                            <select name="status" id="status_filter" class="form-select">
                                <option value="">-- Semua Status --</option>
                                <option value="MATCH" {{ ($filterStatus == 'MATCH') ? 'selected' : '' }}>Match</option>
                                <option value="SPT_ONLY" {{ ($filterStatus == 'SPT_ONLY') ? 'selected' : '' }}>SPT Only</option>
                                <option value="GL_ONLY" {{ ($filterStatus == 'GL_ONLY') ? 'selected' : '' }}>GL Only</option>
                                <option value="TO_BE_CHECK" {{ ($filterStatus == 'TO_BE_CHECK') ? 'selected' : '' }}>To Be Check</option>
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

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0" id="table-title">
                        <i class="fas fa-table me-2"></i>Data Ekualisasi Terbaru
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        <div class="input-group" style="width: 300px;">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            <input type="text"
                                   class="form-control"
                                   id="search-input"
                                   name="search"
                                   value="{{ $filterSearch ?? '' }}"
                                   placeholder="Cari No Faktur / Nama Penjual / Periode / Status..."
                                   aria-label="Cari data">
                            <button class="btn btn-outline-secondary" type="button" id="btn-clear-search" title="Bersihkan pencarian" onclick="clearSearch()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary d-none" id="btn-reset-widget" onclick="resetWidgetFilter()">
                            <i class="fas fa-times me-1"></i>Tampilkan Semua
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100" id="equalization-table">
                        <thead class="table-dark">
                            <tr class="align-middle">
                                <th>Periode</th>
                                <th>No Faktur Pajak</th>
                                <th>Nama Penjual/Pembeli</th>
                                <th>DPP SPT</th>
                                <th>DPP GL</th>
                                <th>PPN SPT</th>
                                <th>PPN GL</th>
                                <th>Selisih PPN</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentEqualization as $key => $dt)
                            <tr>
                                <td>{{ $dt->period }}</td>
                                <td class="fw-bold">{{ $dt->no_faktur_pajak }}</td>
                                <td>{{ $dt->nama_penjual }}</td>
                                <td class="text-end">{{ format_rupiah($dt->dpp_spt) }}</td>
                                <td class="text-end">{{ format_rupiah($dt->dpp_gl) }}</td>
                                <td class="text-end">{{ format_rupiah($dt->ppn_spt) }}</td>
                                <td class="text-end">{{ format_rupiah($dt->ppn_gl) }}</td>
                                <td class="text-end {{ $dt->selisih_ppn > 0 ? 'selisih-negative' : ($dt->selisih_ppn < 0 ? 'selisih-positive' : 'selisih-zero') }}">
                                    {{ format_rupiah(abs($dt->selisih_ppn)) }}
                                </td>
                                <td class="text-center">
                                    @if($dt->status == 'MATCH')
                                    <span class="status-match">Match</span>
                                    @elseif($dt->status == 'TO_BE_CHECK')
                                    <span class="status-to-be-check">To Be Check</span>
                                    @elseif($dt->status == 'SPT_ONLY')
                                    <span class="status-spt-only"> SPT Only</span>
                                    @else
                                    <span class="status-gl-only">GL Only</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">Tidak ada data untuk filter yang dipilih.</td>
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
    let activeWidgetType = null;

    document.querySelectorAll('.clickable-widget').forEach(widget => {
        widget.addEventListener('click', function() {
            const type = this.dataset.type;

            if (activeWidgetType === type) {
                resetWidgetFilter();
                return;
            }

            activeWidgetType = type;

            document.querySelectorAll('.clickable-widget').forEach(w => w.classList.remove('active'));
            this.classList.add('active');

            const title = document.getElementById('table-title');
            const btnReset = document.getElementById('btn-reset-widget');
            if (type === 'kurang_bayar') {
                title.innerHTML = '<i class="fas fa-filter me-2"></i>Data Ekualisasi - Selisih PPN SPT (Kurang Bayar)';
            } else {
                title.innerHTML = '<i class="fas fa-filter me-2"></i>Data Ekualisasi - Selisih PPN GL (Lebih Bayar)';
            }
            btnReset.classList.remove('d-none');

            loadFilteredData(type, 1);
        });
    });

    function loadFilteredData(type, page) {
        const yearSelect = document.querySelector('select[name="year"]');
        const monthFromSelect = document.querySelector('select[name="month_from"]');
        const monthToSelect = document.querySelector('select[name="month_to"]');
        const statusSelect = document.querySelector('select[name="status"]');
        const searchInput = document.getElementById('search-input');

        const params = new URLSearchParams({
            type: type,
            page: page,
            year: yearSelect ? yearSelect.value : '',
            month_from: monthFromSelect ? monthFromSelect.value : '',
            month_to: monthToSelect ? monthToSelect.value : '',
            status: statusSelect ? statusSelect.value : '',
            search: searchInput ? searchInput.value.trim() : ''
        });

        const url = `{{ route('eqtax.dashboard.filter-selisih') }}?${params.toString()}`;

        const tbody = document.querySelector('#equalization-table tbody');
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Memuat data...</p>
                </td>
            </tr>
        `;

        document.querySelector('.d-flex.justify-content-center').classList.add('d-none');

        fetch(url)
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    renderTable(result.data.data);
                    // renderPagination(result.data, type);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                tbody.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center text-danger py-4">
                            <i class="fas fa-exclamation-triangle me-2"></i>Gagal memuat data. Silakan coba lagi.
                        </td>
                    </tr>
                `;
            });
    }

    function renderTable(data) {
        const tbody = document.querySelector('#equalization-table tbody');

        if (data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        <i class="fas fa-inbox me-2"></i>Tidak ada data untuk filter yang dipilih.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = data.map(dt => {
            const selisihClass = dt.selisih_ppn > 0 ? 'selisih-negative' :
                                (dt.selisih_ppn < 0 ? 'selisih-positive' : 'selisih-zero');

            let statusBadge = '';
            switch(dt.status) {
                case 'MATCH':
                    statusBadge = '<span class="status-match">Match</span>';
                    break;
                case 'TO_BE_CHECK':
                    statusBadge = '<span class="status-to-be-check">To Be Check</span>';
                    break;
                case 'SPT_ONLY':
                    statusBadge = '<span class="status-spt-only">SPT Only</span>';
                    break;
                default:
                    statusBadge = '<span class="status-gl-only">GL Only</span>';
            }

            return `
                <tr>
                    <td>${dt.period}</td>
                    <td class="fw-bold">${dt.no_faktur_pajak}</td>
                    <td>${dt.nama_penjual}</td>
                    <td class="text-end">Rp ${numberFormat(dt.dpp_spt)}</td>
                    <td class="text-end">Rp ${numberFormat(dt.dpp_gl)}</td>
                    <td class="text-end">Rp ${numberFormat(dt.ppn_spt)}</td>
                    <td class="text-end">Rp ${numberFormat(dt.ppn_gl)}</td>
                    <td class="text-end ${selisihClass}">Rp ${numberFormat(Math.abs(dt.selisih_ppn))}</td>
                    <td class="text-center">${statusBadge}</td>
                </tr>
            `;
        }).join('');
    }

    function renderPagination(paginationData, type) {
        const container = document.querySelector('.d-flex.justify-content-center');
        container.classList.remove('d-none');

        if (paginationData.last_page <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '<nav><ul class="pagination">';

        html += `<li class="page-item ${paginationData.current_page === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadFilteredData('${type}', ${paginationData.current_page - 1}); return false;">&laquo;</a>
        </li>`;

        for (let i = 1; i <= paginationData.last_page; i++) {
            if (i === 1 || i === paginationData.last_page ||
                (i >= paginationData.current_page - 2 && i <= paginationData.current_page + 2)) {
                html += `<li class="page-item ${i === paginationData.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="loadFilteredData('${type}', ${i}); return false;">${i}</a>
                </li>`;
            } else if (i === paginationData.current_page - 3 || i === paginationData.current_page + 3) {
                html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        html += `<li class="page-item ${paginationData.current_page === paginationData.last_page ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadFilteredData('${type}', ${paginationData.current_page + 1}); return false;">&raquo;</a>
        </li>`;

        html += '</ul></nav>';
        container.innerHTML = html;
    }

    function numberFormat(num) {
        return new Intl.NumberFormat('id-ID').format(num);
    }

    function applySearch() {
        const searchInput = document.getElementById('search-input');
        const search = searchInput ? searchInput.value.trim() : '';

        // Jika widget aktif, jalankan pencarian via AJAX
        if (activeWidgetType) {
            loadFilteredData(activeWidgetType, 1);
            return;
        }

        // Jika tidak ada widget, navigasi server-side dengan mempertahankan filter lain
        const yearSelect = document.querySelector('select[name="year"]');
        const monthFromSelect = document.querySelector('select[name="month_from"]');
        const monthToSelect = document.querySelector('select[name="month_to"]');
        const statusSelect = document.querySelector('select[name="status"]');

        const params = new URLSearchParams();
        if (yearSelect && yearSelect.value) params.set('year', yearSelect.value);
        if (monthFromSelect && monthFromSelect.value) params.set('month_from', monthFromSelect.value);
        if (monthToSelect && monthToSelect.value) params.set('month_to', monthToSelect.value);
        if (statusSelect && statusSelect.value) params.set('status', statusSelect.value);
        if (search) params.set('search', search);

        const qs = params.toString();
        window.location.href = '{{ route("eqtax.index") }}' + (qs ? '?' + qs : '');
    }

    function clearSearch() {
        const searchInput = document.getElementById('search-input');
        if (searchInput) searchInput.value = '';

        if (activeWidgetType) {
            loadFilteredData(activeWidgetType, 1);
            return;
        }

        const yearSelect = document.querySelector('select[name="year"]');
        const monthFromSelect = document.querySelector('select[name="month_from"]');
        const monthToSelect = document.querySelector('select[name="month_to"]');
        const statusSelect = document.querySelector('select[name="status"]');

        const params = new URLSearchParams();
        if (yearSelect && yearSelect.value) params.set('year', yearSelect.value);
        if (monthFromSelect && monthFromSelect.value) params.set('month_from', monthFromSelect.value);
        if (monthToSelect && monthToSelect.value) params.set('month_to', monthToSelect.value);
        if (statusSelect && statusSelect.value) params.set('status', statusSelect.value);

        const qs = params.toString();
        window.location.href = '{{ route("eqtax.index") }}' + (qs ? '?' + qs : '');
    }

    document.getElementById('search-input').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            e.stopPropagation();
            applySearch();
        }
    });

    // Sertakan nilai pencarian saat form filter disubmit
    document.querySelector('.filter-form').addEventListener('submit', function(e) {
        let hiddenSearch = this.querySelector('input[name="search"]');
        if (!hiddenSearch) {
            hiddenSearch = document.createElement('input');
            hiddenSearch.type = 'hidden';
            hiddenSearch.name = 'search';
            this.appendChild(hiddenSearch);
        }
        const searchInput = document.getElementById('search-input');
        hiddenSearch.value = searchInput ? searchInput.value.trim() : '';
    });

    function resetWidgetFilter() {
        activeWidgetType = null;
        document.querySelectorAll('.clickable-widget').forEach(w => w.classList.remove('active'));
        document.getElementById('table-title').innerHTML = '<i class="fas fa-table me-2"></i>Data Ekualisasi Terbaru';
        document.getElementById('btn-reset-widget').classList.add('d-none');
        window.location.href = '{{ route("eqtax.index") }}';
    }

    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('selisihChart').getContext('2d');

        const labels = @json($chartLabels);
        const kurangBayar = @json($chartKurangBayar);
        const lebihBayar = @json($chartLebihBayar);

        const selisihChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
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
                        titleFont: {
                            size: 13,
                            weight: '600'
                        },
                        bodyFont: {
                            size: 12
                        },
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
                            font: {
                                size: 11
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            font: {
                                size: 11
                            },
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
