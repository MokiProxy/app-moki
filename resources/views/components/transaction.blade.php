@extends('layouts.App')

@section('content')
    {{-- CSS ANTI-FLASH: Cara paling ampuh secara visual --}}
    @cannot('transactions.export-pdf')
    <style>
        .btn-pdf, .btn-delete, [class*="mdi-file-pdf"], [class*="mdi-delete"] {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
        }
    </style>
    @endcannot

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-body border-bottom bg-light">
                    <div class="d-flex align-items-center">
                        <h5 class="mb-0 card-title flex-grow-1">Daftar Transaksi (BAST)</h5>
                        <div class="flex-shrink-0">
                            @can('transactions.create')
                                <a href="{{ route('transaction.create') }}" class="btn btn-primary">
                                    <i class="mdi mdi-plus me-1"></i> Tambah Transaksi
                                </a>
                            @endcan
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle w-100" id="transaction-table">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 10px">No</th>
                                    <th>Nomor BAST</th>
                                    <th>ID Employee</th>
                                    <th>Nama</th>
                                    <th>Jabatan</th>
                                    <th>Departemen</th>
                                    <th>Lokasi</th>
                                    <th class="text-center">Tipe</th>
                                    <th class="text-center">Status Approval</th>
                                    <th>Kategori</th>
                                    <th>Item</th>
                                    <th>Created At</th>
                                    <th style="width:150px" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL VIEW & PDF --}}
    <div class="modal fade" id="modal-view" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="mdi mdi-information-outline me-1"></i> Detail Transaksi</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modal-content-detail"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-pdf" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content" style="height: 90vh;">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="mdi mdi-file-pdf-box me-1"></i> Pratinjau Dokumen BAST</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 bg-secondary text-center">
                    <iframe id="pdf-frame" src="" width="100%" height="100%" frameborder="0" style="display:block;"></iframe>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('plugin')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.1/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        var $j = jQuery.noConflict(true);

        $j(document).ready(function() {
            var CSRF_TOKEN = $j('meta[name="csrf-token"]').attr('content');
            var isStaff = "{{ Auth::user()->hasPermissionTo('transactions.create') && !Auth::user()->hasPermissionTo('transactions.export-pdf') ? 'true' : 'false' }}" === 'true';
            
            var table = $j("#transaction-table").DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('transaction.datatable') }}",
                    type: "POST",
                    data: { _token: CSRF_TOKEN }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'transaction_number', name: 'order_number' },
                    { data: 'employee_id_display', name: 'employee.employee_id' },
                    { data: 'employee_name', name: 'employee.name' },
                    { data: 'jabatan', name: 'employee.jabatan' },
                    { data: 'division_name', name: 'division.name' },
                    { data: 'regional_name', name: 'employee.regional.name' },
                    { 
                        data: 'status_type', 
                        className: 'text-center',
                        render: function(data) {
                            if(!data) return '-';
                            let color = data.toUpperCase() === 'IN' ? 'success' : 'danger';
                            return '<span class="badge bg-' + color + '">' + data.toUpperCase() + '</span>';
                        }
                    },
                    { data: 'status_approved', className: 'text-center', orderable: false },
                    { data: 'category', name: 'category', orderable: false },
                    { data: '_asset_count', className: 'text-center' },
                    { 
                        data: 'created_at', 
                        render: function(data) { 
                            return data ? moment(data).format('DD/MM/YYYY HH:mm') : '-'; 
                        } 
                    },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                "columnDefs": [
                    {
                        "targets": -1,
                        "createdCell": function (td, cellData, rowData, row, col) {
                            if (isStaff) {
                                $j(td).find('.btn-pdf, .btn-delete').remove();
                            }
                        }
                    }
                ],
                "drawCallback": function(settings) {
                    if (isStaff) {
                        $j('.btn-pdf, .btn-delete').each(function() {
                            $j(this).remove();
                        });
                    }
                }
            });

            // Action View (Tersedia untuk semua)
            $j('#transaction-table').on('click', '.btn-view', function() {
                var id = $j(this).data('id');
                var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-view'));
                $j('#modal-content-detail').html('<div class="text-center p-5"><div class="spinner-border text-primary"></div></div>');
                modal.show();
                $j.get("{{ url('transaction/detail') }}/" + id, function(res) {
                    if(res.success) {
                        let d = res.data;
                        let html = `
                            <div class="row mb-3">
                                <div class="col-md-6 border-end">
                                    <p class="mb-1 text-muted small fw-bold text-uppercase">Informasi Karyawan</p>
                                    <table class="table table-sm table-borderless">
                                        <tr><th width="100">Nama</th><td>: ${d.employee ? d.employee.name : '-'}</td></tr>
                                        <tr><th>Jabatan</th><td>: ${d.employee ? (d.employee.jabatan || '-') : '-'}</td></tr>
                                        <tr><th>Lokasi</th><td>: ${d.employee && d.employee.regional ? d.employee.regional.name : '-'}</td></tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1 text-muted small fw-bold text-uppercase">Informasi Transaksi</p>
                                    <table class="table table-sm table-borderless">
                                        <tr><th width="100">No. BAST</th><td>: <strong>${d.order_number}</strong></td></tr>
                                        <tr><th>Dept</th><td>: ${d.division ? d.division.name : '-'}</td></tr>
                                        <tr><th>Tipe</th><td>: <span class="badge bg-${d.type == 'IN' ? 'success' : 'danger'}">${d.type}</span></td></tr>
                                    </table>
                                </div>
                            </div>
                            <h6 class="fw-bold text-primary mb-2">Daftar Item Asset</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm align-middle">
                                    <thead class="table-light text-center small">
                                        <tr>
                                            <th width="150">Nomor Aset</th>
                                            <th>Brand & SN</th>
                                            <th>Spesifikasi</th>
                                            <th width="80">Kondisi</th>
                                            <th>Kategori</th>
                                        </tr>
                                    </thead>
                                    <tbody class="small">
                                        ${d.details.map(item => {
                                            let kondisiText = '-';
                                            let badgeColor = 'secondary';
                                            if(item.asset) {
                                                if(item.asset.condition == 1) { kondisiText = 'Baru'; badgeColor = 'success'; }
                                                else if(item.asset.condition == 2) { kondisiText = 'Seken'; badgeColor = 'primary'; }
                                                else if(item.asset.condition == 3) { kondisiText = 'Rusak'; badgeColor = 'danger'; }
                                            }
                                            return `
                                            <tr>
                                                <td class="text-center"><code>${item.new_uid || (item.asset ? item.asset.uid : '-')}</code></td>
                                                <td>
                                                    <strong>${item.asset ? item.asset.brand : 'N/A'}</strong><br>
                                                    <small class="text-muted">SN: ${item.asset ? item.asset.serial_number : '-'}</small>
                                                </td>
                                                <td>${item.asset ? (item.asset.specification || '-') : '-'}</td>
                                                <td class="text-center"><span class="badge bg-${badgeColor}">${kondisiText}</span></td>
                                                <td class="text-center">${(item.asset && item.asset.category) ? item.asset.category.name : '-'}</td>
                                            </tr>`;
                                        }).join('')}
                                    </tbody>
                                </table>
                            </div>`;
                        $j('#modal-content-detail').html(html);
                    }
                });
            });

            // Action Approval
            $j('#transaction-table').on('click', '.btn-approval', function() {
                var id = $j(this).data('id');
                Swal.fire({
                    title: 'Proses Approval',
                    text: 'Silahkan tentukan tindakan untuk transaksi ini:',
                    icon: 'question',
                    showCancelButton: true,
                    showDenyButton: true,
                    confirmButtonText: 'Approved',
                    denyButtonText: 'Reject',
                    confirmButtonColor: '#198754',
                    denyButtonColor: '#dc3545',
                }).then((result) => {
                    let statusId = result.isConfirmed ? 2 : (result.isDenied ? 3 : 0);
                    if (statusId > 0) {
                        $j.ajax({
                            url: "{{ url('transaction/update-status') }}/" + id,
                            type: "POST",
                            data: { _token: CSRF_TOKEN, status: statusId },
                            success: function(res) {
                                table.ajax.reload(null, false);
                                Swal.fire('Berhasil!', res.message, 'success');
                            }
                        });
                    }
                });
            });
            
            // PDF & Delete Protection di level JS
            $j(document).on('click', '.btn-pdf', function(e) {
                if(isStaff) {
                    Swal.fire('Error', 'Anda tidak memiliki akses.', 'error');
                    return false;
                }
                e.preventDefault();
                var id = $j(this).data('id');
                $j('#pdf-frame').attr('src', "{{ url('transaction/pdf') }}/" + id);
                new bootstrap.Modal(document.getElementById('modal-pdf')).show();
            });

            $j('#transaction-table').on('click', '.btn-delete', function() {
                if(isStaff) return false;
                var id = $j(this).data('id');
                Swal.fire({
                    title: 'Hapus Data?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Ya, Hapus!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $j.ajax({
                            url: "{{ url('transaction/delete') }}/" + id,
                            type: "DELETE",
                            data: { _token: CSRF_TOKEN },
                            success: function(res) {
                                table.ajax.reload(null, false);
                                Swal.fire('Berhasil!', res.message, 'success');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection