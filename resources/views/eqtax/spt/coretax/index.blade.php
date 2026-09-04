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

    .editable {
        cursor: pointer;
        position: relative;
        transition: background-color 0.15s;
    }

    .editable:hover {
        background-color: #e0e7ff !important;
    }

    .editable:hover::after {
        content: '\f044';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        position: absolute;
        right: 4px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 0.6rem;
        color: #6366f1;
        opacity: 0.6;
    }

    .inline-edit-input {
        width: 100%;
        padding: 2px 6px;
        border: 2px solid #6366f1;
        border-radius: 4px;
        font-size: inherit;
        text-align: right;
        background: white;
        outline: none;
    }

    .inline-edit-input.text-start {
        text-align: left;
    }

    .inline-edit-input:focus {
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
    }

    .inline-edit-select {
        width: 100%;
        padding: 2px 4px;
        border: 2px solid #6366f1;
        border-radius: 4px;
        font-size: inherit;
        background: white;
        outline: none;
    }

    .inline-edit-select:focus {
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
    }

    #toastContainer {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
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
                                        <h4 class="text-white mb-0">{{ format_rupiah($totalDpp) }}</h4>
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
                                        <h4 class="text-white mb-0">{{ format_rupiah($totalPpn) }}</h4>
                                    </div>
                                    <div class="icon-overlay">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @can('eqtax.spt.coretax.import')
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
                    @endcan
                </div>

                <ul class="nav nav-tabs mb-3" id="spt-tabs" role="tablist">
                    @foreach($tabs as $key => $label)
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $activeTab === $key ? 'active fw-bold' : '' }}"
                           href="{{ route('eqtax.spt.coretax.index', array_merge(request()->except(['tab', 'page']), ['tab' => $key])) }}">
                            <i class="fas fa-file-invoice me-1"></i>{{ $label }}
                        </a>
                    </li>
                    @endforeach
                </ul>

                <form action="{{ route('eqtax.spt.coretax.index') }}" method="GET" class="filter-form">
                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="search" class="form-label fw-bold">Cari</label>
                            <input type="text" name="search" id="search" class="form-control" placeholder="No Faktur / Nama / NPWP" value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="masa_pajak" class="form-label fw-bold">Masa Pajak</label>
                            <select name="masa_pajak" id="masa_pajak" class="form-select">
                                <option value="">-- Semua --</option>
                                @foreach($masaPajakList as $mp)
                                <option value="{{ $mp }}" {{ request('masa_pajak') == $mp ? 'selected' : '' }}>{{ $mp }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
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
                            <a href="{{ route('eqtax.spt.coretax.index', ['tab' => $activeTab]) }}" class="btn btn-secondary">
                                <i class="mdi mdi-refresh"></i>
                            </a>
                        </div>
                    </div>
                </form>

                @php
                $columns = match ($activeTab) {
                    'PM' => [
                        ['field' => 'no_faktur_pajak', 'label' => 'No Faktur Pajak', 'type' => 'text', 'align' => 'text-start'],
                        ['field' => 'nama_penjual', 'label' => 'Nama Pembeli', 'type' => 'text', 'align' => 'text-start'],
                        ['field' => 'npwp_penjual', 'label' => 'NPWP Pembeli', 'type' => 'text', 'align' => 'text-start'],
                        ['field' => 'tgl_faktur_pajak', 'label' => 'Tanggal FP', 'type' => 'date', 'align' => 'text-start'],
                        ['field' => 'masa_pajak', 'label' => 'Masa Pajak', 'type' => 'text', 'align' => 'text-start'],
                        ['field' => 'tahun', 'label' => 'Tahun', 'type' => 'text', 'align' => 'text-start'],
                        ['field' => 'kode_transaksi', 'label' => 'Kode Transaksi', 'type' => 'text', 'align' => 'text-start'],
                        ['field' => 'status_faktur', 'label' => 'Status Faktur', 'type' => 'badge', 'align' => 'text-start'],
                        ['field' => 'esign_status', 'label' => 'ESign Status', 'type' => 'text', 'align' => 'text-start'],
                        ['field' => 'harga_jual', 'label' => 'Harga Jual', 'type' => 'number', 'align' => 'text-end'],
                        ['field' => 'dpp', 'label' => 'DPP', 'type' => 'number', 'align' => 'text-end'],
                        ['field' => 'ppn', 'label' => 'PPN', 'type' => 'number', 'align' => 'text-end'],
                        ['field' => 'ppnbm', 'label' => 'PPNBM', 'type' => 'number', 'align' => 'text-end'],
                        ['field' => 'perekam', 'label' => 'Perekam', 'type' => 'text', 'align' => 'text-start'],
                        ['field' => 'referensi', 'label' => 'Referensi', 'type' => 'text', 'align' => 'text-start'],
                        ['field' => 'metode_input', 'label' => 'Metode Input', 'type' => 'text', 'align' => 'text-start'],
                        ['field' => 'dilaporkan_oleh_penjual', 'label' => 'Dilaporkan oleh Penjual', 'type' => 'boolean', 'align' => 'text-start'],
                        ['field' => 'is_show_clear_name', 'label' => 'IsShowClearName', 'type' => 'boolean', 'align' => 'text-start'],
                        ['field' => 'uraian', 'label' => 'Uraian', 'type' => 'text', 'align' => 'text-start'],
                    ],
                    'PMS' => [
                        ['field' => 'no_faktur_pajak', 'label' => 'Nomor Dokumen', 'type' => 'text', 'align' => 'text-start'],
                        ['field' => 'npwp_penjual', 'label' => 'NPWP Penjual', 'type' => 'text', 'align' => 'text-start'],
                        ['field' => 'nama_penjual', 'label' => 'Nama Penjual', 'type' => 'text', 'align' => 'text-start'],
                        ['field' => 'tgl_faktur_pajak', 'label' => 'Tanggal Dokumen', 'type' => 'date', 'align' => 'text-start'],
                        ['field' => 'masa_pajak', 'label' => 'Masa Pajak', 'type' => 'text', 'align' => 'text-start'],
                        ['field' => 'tahun', 'label' => 'Tahun', 'type' => 'text', 'align' => 'text-start'],
                        ['field' => 'masa_pajak_pengkreditan', 'label' => 'Masa Pengkreditan', 'type' => 'text', 'align' => 'text-start'],
                        ['field' => 'tahun_pajak_pengkreditan', 'label' => 'Tahun Pengkreditan', 'type' => 'text', 'align' => 'text-start'],
                        ['field' => 'harga_jual', 'label' => 'Nilai Tagihan', 'type' => 'number', 'align' => 'text-end'],
                        ['field' => 'dpp', 'label' => 'DPP', 'type' => 'number', 'align' => 'text-end'],
                        ['field' => 'ppn', 'label' => 'PPN', 'type' => 'number', 'align' => 'text-end'],
                        ['field' => 'ppnbm', 'label' => 'PPNBM', 'type' => 'number', 'align' => 'text-end'],
                        ['field' => 'status_faktur', 'label' => 'Status', 'type' => 'badge', 'align' => 'text-start'],
                        ['field' => 'perekam', 'label' => 'Perekam', 'type' => 'text', 'align' => 'text-start'],
                        ['field' => 'keterangan', 'label' => 'Keterangan', 'type' => 'text', 'align' => 'text-start'],
                        ['field' => 'jenis_transaksi', 'label' => 'Jenis Transaksi', 'type' => 'text', 'align' => 'text-start'],
                        ['field' => 'dibuat_oleh', 'label' => 'Dibuat Oleh', 'type' => 'text', 'align' => 'text-start'],
                        ['field' => 'is_show_clear_name', 'label' => 'IsShowClearName', 'type' => 'boolean', 'align' => 'text-start'],
                    ],
                    default => [
                        ['field' => 'no_faktur_pajak', 'label' => 'No Faktur Pajak', 'type' => 'text', 'align' => 'text-start'],
                        ['field' => 'nama_penjual', 'label' => 'Nama Penjual/Pembeli', 'type' => 'text', 'align' => 'text-start'],
                        ['field' => 'npwp_penjual', 'label' => 'NPWP', 'type' => 'text', 'align' => 'text-start'],
                        ['field' => 'tgl_faktur_pajak', 'label' => 'Tanggal FP', 'type' => 'date', 'align' => 'text-start'],
                        ['field' => 'masa_pajak', 'label' => 'Masa Pajak', 'type' => 'text', 'align' => 'text-start'],
                        ['field' => 'tahun', 'label' => 'Tahun', 'type' => 'text', 'align' => 'text-start'],
                        ['field' => 'masa_pajak_pengkreditan', 'label' => 'Masa Pengkreditan', 'type' => 'text', 'align' => 'text-start'],
                        ['field' => 'tahun_pajak_pengkreditan', 'label' => 'Tahun Pengkreditan', 'type' => 'text', 'align' => 'text-start'],
                        ['field' => 'status_faktur', 'label' => 'Status Faktur', 'type' => 'badge', 'align' => 'text-start'],
                        ['field' => 'harga_jual', 'label' => 'Harga Jual', 'type' => 'number', 'align' => 'text-end'],
                        ['field' => 'dpp', 'label' => 'DPP', 'type' => 'number', 'align' => 'text-end'],
                        ['field' => 'ppn', 'label' => 'PPN', 'type' => 'number', 'align' => 'text-end'],
                        ['field' => 'ppnbm', 'label' => 'PPNBM', 'type' => 'number', 'align' => 'text-end'],
                        ['field' => 'penandatangan', 'label' => 'Penandatangan', 'type' => 'text', 'align' => 'text-start'],
                        ['field' => 'referensi', 'label' => 'Referensi', 'type' => 'text', 'align' => 'text-start'],
                        ['field' => 'no_sp2d', 'label' => 'No SP2D', 'type' => 'text', 'align' => 'text-start'],
                    ],
                };
                @endphp

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100" id="spt-table">
                        <thead class="table-dark">
                            <tr class="align-middle">
                                <th>No</th>
                                @foreach($columns as $col)
                                <th>{{ $col['label'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sptData as $key => $dt)
                            <tr data-id="{{ $dt->id }}">
                                <td>{{ ($sptData->currentPage() - 1) * $sptData->perPage() + $key + 1 }}</td>
                                @foreach($columns as $col)
                                @php
                                    $field = $col['field'];
                                    $value = $dt->{$field};
                                @endphp
                                @if($col['type'] === 'number')
                                <td class="text-end editable" data-field="{{ $field }}" data-type="number" data-value="{{ $value }}">{{ format_rupiah($value) }}</td>
                                @elseif($col['type'] === 'date')
                                <td class="editable text-start" data-field="{{ $field }}" data-type="date" data-value="{{ $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : '' }}">{{ $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '-' }}</td>
                                @elseif($col['type'] === 'boolean')
                                <td class="editable text-start" data-field="{{ $field }}" data-type="boolean" data-value="{{ $value }}">
                                    @if(is_null($value)) - @else {{ $value ? 'Yes' : 'No' }} @endif
                                </td>
                                @elseif($col['type'] === 'badge')
                                <td class="editable text-start" data-field="{{ $field }}" data-type="text" data-value="{{ $value }}">
                                    <span class="badge {{ $value == 'CREDITED' ? 'bg-success' : 'bg-warning' }}">{{ $value ?? "-" }}</span>
                                </td>
                                @else
                                <td class="editable text-start" data-field="{{ $field }}" data-type="text" data-value="{{ $value }}">{{ $value ?? "-" }}</td>
                                @endif
                                @endforeach
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ count($columns) + 1 }}" class="text-center text-muted">Belum ada data untuk tab {{ $activeTab }}. Silakan import file SPT terlebih dahulu.</td>
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
<div id="toastContainer"></div>
<script>
    const uploadFileForm = document.getElementById('file-form')
    const uploadFile = document.getElementById('file')
    uploadFile.addEventListener("change", () => {
        if (uploadFile.files.length > 0) {
            uploadFileForm.submit()
        }
    })

    function showToast(type, message) {
        const icon = type === 'success' ? 'fa-check-circle text-success' : 'fa-exclamation-circle text-danger';
        const $toast = $(`<div class="toast show" role="alert"><div class="toast-body d-flex align-items-center"><i class="fas ${icon} me-2"></i>${message}</div></div>`);
        $('#toastContainer').append($toast);
        setTimeout(() => $toast.fadeOut(300, () => $toast.remove()), 3000);
    }

    function createEditInput($cell, currentValue, fieldType) {
        const isText = fieldType === 'text';
        const inputClass = 'inline-edit-input' + (isText ? ' text-start' : '');

        if (fieldType === 'date') {
            return $(`<input type="date" class="${inputClass}" value="${currentValue || ''}">`);
        }
        if (fieldType === 'number') {
            return $(`<input type="number" class="${inputClass}" value="${currentValue}" min="0" step="any">`);
        }
        if (fieldType === 'boolean') {
            const selected = currentValue === true || currentValue === '1' || currentValue === 'TRUE' ? '1' : '0';
            return $(`<select class="inline-edit-select">
                <option value="1" ${selected === '1' ? 'selected' : ''}>Yes</option>
                <option value="0" ${selected === '0' ? 'selected' : ''}>No</option>
            </select>`);
        }
        return $(`<input type="text" class="${inputClass}" value="${currentValue || ''}">`);
    }

    function formatDisplayValue(field, value, fieldType) {
        if (fieldType === 'date' && value) {
            const d = new Date(value);
            if (!isNaN(d.getTime())) {
                const dd = String(d.getDate()).padStart(2, '0');
                const mm = String(d.getMonth() + 1).padStart(2, '0');
                const yyyy = d.getFullYear();
                return dd + '/' + mm + '/' + yyyy;
            }
            return value;
        }
        return value;
    }

    $(document).on('dblclick', '.editable', function() {
        const $cell = $(this);
        if ($cell.find('input').length > 0) return;

        const currentValue = $cell.data('value');
        const field = $cell.data('field');
        const fieldType = $cell.data('type') || 'text';
        const id = $cell.closest('tr').data('id');
        const originalHtml = $cell.html();

        const $input = createEditInput($cell, currentValue, fieldType);
        $cell.html($input);
        $input.focus();
        if (fieldType !== 'date') $input.select();

        $input.on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                saveInlineEdit($cell, id, field, fieldType, $(this).val(), originalHtml);
            }
            if (e.key === 'Escape') {
                $cell.html(originalHtml);
            }
        });

        $input.on('blur', function() {
            setTimeout(() => {
                if ($cell.find('input').length > 0) {
                    saveInlineEdit($cell, id, field, fieldType, $(this).val(), originalHtml);
                }
            }, 200);
        });
    });

    function saveInlineEdit($cell, id, field, fieldType, newValue, originalHtml) {
        if (newValue === '' || newValue === null || newValue === undefined) {
            $cell.html(originalHtml);
            return;
        }

        if (fieldType === 'boolean') {
            newValue = newValue === '1' || newValue === 'true' || newValue === true ? true : false;
        }

        if (fieldType === 'number' && (isNaN(newValue) || parseFloat(newValue) < 0)) {
            showToast('error', 'Nilai tidak valid');
            $cell.html(originalHtml);
            return;
        }

        $cell.html('<i class="fas fa-spinner fa-spin"></i>');

        let sendValue = newValue;
        if (fieldType === 'number') {
            sendValue = parseFloat(newValue);
        }

        $.ajax({
            url: '{{ route("eqtax.spt.coretax.update-field") }}',
            type: 'POST',
            data: JSON.stringify({ id: id, field: field, value: sendValue }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) {
                    const displayVal = formatDisplayValue(field, response.formatted_value, fieldType);
                    $cell.html(displayVal);
                    $cell.data('value', newValue);
                    showToast('success', response.message);
                } else {
                    $cell.html(originalHtml);
                    showToast('error', response.message);
                }
            },
            error: function(xhr) {
                $cell.html(originalHtml);
                showToast('error', xhr.responseJSON?.message || 'Gagal menyimpan');
            }
        });
    }
</script>
@endsection
