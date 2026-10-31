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

    .table > tbody > tr.table-active > td {
        background-color: #e0e7ff !important;
    }

    #bulk-toolbar {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 12px;
    }

    #bulk-toolbar .btn-link {
        text-decoration: none;
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

                @php($canDelete = auth()->user()->can('eqtax.gl.delete'))

                @if($canDelete)
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3" id="bulk-toolbar"
                     data-url="{{ route('eqtax.gl.delete') }}"
                     data-search="{{ request('search') }}"
                     data-sheet="{{ request('sheet') }}"
                     data-date-from="{{ request('date_from') }}"
                     data-date-to="{{ request('date_to') }}"
                     data-total="{{ $glData->total() }}">
                    <span class="text-muted small" id="selected-info">0 data dipilih</span>
                    <button type="button" class="btn btn-danger btn-sm" id="btn-delete-selected" disabled>
                        <i class="fas fa-trash me-1"></i> Hapus Terpilih
                    </button>
                    <button type="button" class="btn btn-link btn-sm p-0 d-none" id="btn-select-all-filtered">
                        Pilih semua data pada filter ini
                    </button>
                </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100" id="gl-table">
                        <thead class="table-dark">
                            <tr class="align-middle">
                                @if($canDelete)
                                <th class="text-center" style="width: 42px">
                                    <input type="checkbox" id="checkAll" class="form-check-input" title="Pilih semua pada halaman ini">
                                </th>
                                @endif
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
                                @if($canDelete)
                                <th class="text-center" style="width: 70px">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($glData as $key => $dt)
                            <tr data-id="{{ $dt->id }}">
                                @if($canDelete)
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input row-checkbox" value="{{ $dt->id }}">
                                </td>
                                @endif
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
                                @if($canDelete)
                                <td class="text-center">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger btn-delete-row"
                                            data-id="{{ $dt->id }}"
                                            data-label="{{ $dt->no_faktur_pajak ?? $dt->id }}"
                                            title="Hapus data">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ $canDelete ? 15 : 13 }}" class="text-center text-muted">Belum ada data. Silakan import file GL terlebih dahulu.</td>
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
<script src="{{ asset('libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    (function () {
        const $toolbar = $('#bulk-toolbar');
        if (!$toolbar.length) return;

        const deleteUrl = $toolbar.data('url');
        const totalFiltered = parseInt($toolbar.data('total'), 10) || 0;
        let selectAllFiltered = false;
        let requestRunning = false;

        function formatNumber(n) {
            return Number(n).toLocaleString('id-ID');
        }

        function escapeHtml(value) {
            return $('<div>').text(value == null ? '' : value).html();
        }

        function getSelectedIds() {
            return $('.row-checkbox:checked').map(function () {
                return $(this).val();
            }).get();
        }

        function updateBulkState() {
            const ids = getSelectedIds();
            const count = ids.length;
            const effectiveCount = selectAllFiltered ? totalFiltered : count;

            $('#selected-info').text(
                selectAllFiltered
                    ? `Semua ${formatNumber(totalFiltered)} data pada filter ini dipilih`
                    : `${formatNumber(count)} data dipilih`
            );

            $('#btn-delete-selected')
                .prop('disabled', effectiveCount === 0 || requestRunning)
                .html('<i class="fas fa-trash me-1"></i> Hapus Terpilih' + (effectiveCount ? ` (${formatNumber(effectiveCount)})` : ''));

            const $checkAll = $('#checkAll');
            const $all = $('.row-checkbox');
            const allChecked = $all.length > 0 && $all.length === $all.filter(':checked').length;

            $checkAll.prop('checked', allChecked);
            $checkAll.prop('indeterminate', count > 0 && !allChecked);

            $('tr[data-id]').removeClass('table-active');
            $('.row-checkbox:checked').closest('tr').addClass('table-active');

            const showSelectAll = !selectAllFiltered && count > 0 && totalFiltered > $all.length;
            $('#btn-select-all-filtered')
                .toggleClass('d-none', !showSelectAll)
                .text(`Pilih semua ${formatNumber(totalFiltered)} data pada filter ini`);
        }

        $('#checkAll').on('change', function () {
            selectAllFiltered = false;
            $('.row-checkbox').prop('checked', this.checked);
            updateBulkState();
        });

        $(document).on('change', '.row-checkbox', function () {
            selectAllFiltered = false;
            updateBulkState();
        });

        $('#btn-select-all-filtered').on('click', function () {
            selectAllFiltered = true;
            $('.row-checkbox').prop('checked', true);
            updateBulkState();
        });

        function basePayload() {
            return {
                _token: $('meta[name="csrf-token"]').attr('content'),
                search: $toolbar.data('search') || '',
                sheet: $toolbar.data('sheet') || '',
                date_from: $toolbar.data('date-from') || '',
                date_to: $toolbar.data('date-to') || ''
            };
        }

        function buildPayload() {
            const payload = basePayload();

            if (selectAllFiltered) {
                payload.select_all = 1;
            } else {
                payload.ids = getSelectedIds();
            }

            return payload;
        }

        function sendDelete(payload) {
            if (requestRunning) return;
            requestRunning = true;
            updateBulkState();

            $.ajax({
                url: deleteUrl,
                type: 'POST',
                data: payload,
                success: function (res) {
                    if (!res.success) {
                        Swal.fire('Gagal!', res.message || 'Gagal menghapus data.', 'error');
                        return;
                    }
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        html: escapeHtml(res.message) + '<br><small class="text-muted">Jalankan ulang ekualisasi agar hasil sesuai data terbaru.</small>'
                    }).then(function () {
                        window.location.href = res.redirect_url;
                    });
                },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Terjadi kesalahan saat menghapus data.';
                    Swal.fire('Gagal!', msg, 'error');
                },
                complete: function () {
                    requestRunning = false;
                    updateBulkState();
                }
            });
        }

        $('#btn-delete-selected').on('click', function () {
            const ids = getSelectedIds();
            if (!ids.length) {
                Swal.fire('Tidak ada data', 'Pilih minimal satu data terlebih dahulu.', 'warning');
                return;
            }

            const total = selectAllFiltered ? totalFiltered : ids.length;
            const text = selectAllFiltered
                ? `Semua ${formatNumber(total)} data pada filter aktif akan dihapus permanen.`
                : `${formatNumber(total)} data terpilih akan dihapus permanen.`;

            Swal.fire({
                title: selectAllFiltered ? 'Hapus semua data terfilter?' : `Hapus ${formatNumber(total)} data terpilih?`,
                text: text + ' Data yang dihapus tidak dapat dikembalikan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f46a6a',
                confirmButtonText: selectAllFiltered ? 'Ya, Hapus Semua' : 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (result.isConfirmed) {
                    sendDelete(buildPayload());
                }
            });
        });

        $(document).on('click', '.btn-delete-row', function () {
            const id = $(this).data('id');
            const label = $(this).data('label');

            Swal.fire({
                title: 'Hapus data ini?',
                html: 'No Faktur: <strong>' + escapeHtml(label) + '</strong><br><br>Data yang dihapus tidak dapat dikembalikan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f46a6a',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (result.isConfirmed) {
                    const payload = basePayload();
                    payload.ids = [id];
                    sendDelete(payload);
                }
            });
        });

        updateBulkState();
    })();
</script>
@endsection
