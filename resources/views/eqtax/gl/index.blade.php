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
                <form action="{{ route('eqtax.gl.index') }}" method="GET" class="filter-form">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="search" class="form-label fw-bold">Cari</label>
                            <input type="text" name="search" id="search" class="form-control" placeholder="No Faktur / Nama Supplier / Jurnal No" value="{{ request('search') }}">
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
                                        <h6 class="text-white mb-0">{{ format_rupiah($totalDpp) }}</h6>
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
                                        <h6 class="text-white mb-0">{{ format_rupiah($totalPpn) }}</h6>
                                    </div>
                                    <div class="icon-overlay">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @can('eqtax.gl.import')
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
                    @endcan
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100" id="gl-table">
                        <thead class="table-dark">
                            <tr class="align-middle">
                                <th>No</th>
                                <th>Sheet</th>
                                <th>No Supplier</th>
                                <th>Nama Supplier</th>
                                <th>No Faktur Pajak</th>
                                <th>Jurnal Date</th>
                                <th>Jurnal No</th>
                                <th>Invoice Date</th>
                                <th>Invoice No</th>
                                <th>Invoice Item</th>
                                <th>DPP</th>
                                <th>PPN</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($glData as $key => $dt)
                            <tr data-id="{{ $dt->id }}">
                                <td>{{ ($glData->currentPage() - 1) * $glData->perPage() + $key + 1 }}</td>
                                <td class="editable text-start" data-field="sheet" data-type="text" data-value="{{ $dt->sheet }}">{{ $dt->sheet }}</td>
                                <td class="editable text-start" data-field="no_supplier" data-type="text" data-value="{{ $dt->no_supplier }}">{{ $dt->no_supplier }}</td>
                                <td class="editable text-start" data-field="nama_supplier" data-type="text" data-value="{{ $dt->nama_supplier }}">{{ $dt->nama_supplier }}</td>
                                <td class="editable text-start fw-bold" data-field="no_faktur_pajak" data-type="text" data-value="{{ $dt->no_faktur_pajak }}">{{ $dt->no_faktur_pajak }}</td>
                                <td class="editable text-start" data-field="jurnal_date" data-type="text" data-value="{{ $dt->jurnal_date }}">{{ $dt->jurnal_date }}</td>
                                <td class="editable text-start" data-field="jurnal_no" data-type="text" data-value="{{ $dt->jurnal_no }}">{{ $dt->jurnal_no }}</td>
                                <td class="editable text-start" data-field="invoice_date" data-type="text" data-value="{{ $dt->invoice_date }}">{{ $dt->invoice_date }}</td>
                                <td class="editable text-start" data-field="invoice_no" data-type="text" data-value="{{ $dt->invoice_no }}">{{ $dt->invoice_no }}</td>
                                <td class="editable text-start" data-field="invoice_item" data-type="text" data-value="{{ $dt->invoice_item }}">{{ $dt->invoice_item }}</td>
                                <td class="text-end editable" data-field="dpp" data-type="number" data-value="{{ $dt->dpp }}">{{ format_rupiah($dt->dpp) }}</td>
                                <td class="text-end editable" data-field="ppn" data-type="number" data-value="{{ $dt->ppn }}">{{ format_rupiah($dt->ppn) }}</td>
                                <td class="editable text-start" data-field="keterangan" data-type="text" data-value="{{ $dt->keterangan }}">{{ Str::limit($dt->keterangan, 30) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="13" class="text-center text-muted">Belum ada data. Silakan import file GL terlebih dahulu.</td>
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
<div id="toastContainer"></div>
<script>
    const uploadFileForm = document.getElementById('file-form')
    const uploadFile = document.getElementById('file')
    uploadFile.addEventListener("change", () => {
        if(uploadFile.files.length > 0) {
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
        return $(`<input type="text" class="${inputClass}" value="${currentValue || ''}">`);
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
            url: '{{ route("eqtax.gl.update-field") }}',
            type: 'POST',
            data: JSON.stringify({ id: id, field: field, value: sendValue }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) {
                    $cell.html(response.formatted_value);
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
