@extends('layouts.App')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-body border-bottom bg-light">
                    <div class="d-flex align-items-center">
                        <h5 class="mb-0 card-title flex-grow-1">Monitoring Asset</h5>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle w-100" id="monitoring-asset-table">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 10px">No</th>
                                    <th>Nomor Asset / UID</th>
                                    <th>Kategori</th>
                                    <th>Brand & S/N</th>
                                    <th>Spesifikasi</th>
                                    <th class="text-center">Kondisi</th>
                                    <th>ID Employee</th>
                                    <th>Nama Pemakai</th>
                                    <th class="text-center">Status</th>
                                    <th style="width:30px" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-hitory-transaction" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white"><i class="mdi mdi-history me-1"></i> History Transaksi Per Asset</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-6 border-end">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <th width="120">ID Asset (UID)</th>
                                    <td>: <code id="nomor_asset_bast-detail" class="fs-6"></code></td>
                                </tr>
                                <tr>
                                    <th>Kategori</th>
                                    <td>: <span id="category-detail"></span></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <th width="120">Status Saat Ini</th>
                                    <td>: <span id="status-detail"></span></td>
                                </tr>
                                <tr>
                                    <th>Spesifikasi</th>
                                    <td>: <span id="specification-detail" class="text-muted small"></span></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <h6 class="fw-bold border-bottom pb-2 mb-3"><i class="mdi mdi-format-list-bulleted me-1"></i> Log Pergerakan Asset</h6>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered table-sm align-middle">
                            <thead class="table-light text-center small">
                                <tr>
                                    <th>No</th>
                                    <th>ID Employee</th>
                                    <th>Nama Karyawan</th>
                                    <th>Departemen / Divisi</th>
                                    <th>Tipe</th>
                                    <th>Tanggal</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody id="table-body-transaksi" class="small"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <link href="{{ asset('libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <style>
        code { color: #d63384; font-weight: bold; }
        .badge { font-weight: 500; text-transform: uppercase; padding: 5px 10px; }
        #monitoring-asset-table thead th { vertical-align: middle; text-align: center; font-size: 13px; }
        .text-primary.fw-bold { cursor: default; }
    </style>
@endsection

@section('plugin')
    <script src="{{ asset('libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>

    <script>
        $(function() {
            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            
            var table = $("#monitoring-asset-table").DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('monitor.asset.datatable') }}",
                    type: "POST",
                    data: function(d) { d._token = CSRF_TOKEN; }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                    { 
                        data: 'nomor_asset_bast', 
                        name: 'nomor_asset_bast',
                        render: function(data) {
                            return `<span class="text-primary fw-bold">${data || '-'}</span>`;
                        }
                    },
                    { data: 'category.name', name: 'category.name', defaultContent: '-' },
                    { 
                        data: 'brand', 
                        name: 'brand',
                        render: function(data, type, row) {
                            return `<strong>${data || '-'}</strong><br><small class="text-muted">SN: ${row.serial_number || '-'}</small>`;
                        }
                    },
                    { data: 'specification', name: 'specification', defaultContent: '-' },
                    { 
                        data: 'condition', 
                        className: 'text-center',
                        render: function(data) {
                            let map = { 1: ['BARU', 'success'], 2: ['SEKEN', 'primary'], 3: ['RUSAK', 'danger'] };
                            let res = map[data] || ['-', 'secondary'];
                            return `<span class="badge bg-${res[1]}">${res[0]}</span>`;
                        }
                    },
                    { data: 'last_employee_id', name: 'last_employee_id', className: 'text-center', defaultContent: '-' },
                    { data: 'last_employee_name', name: 'last_employee_name', defaultContent: '-' },
                    { 
                        data: '_status', 
                        name: 'status',
                        className: 'text-center'
                    },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
                ]
            });

            // Handle Klik Tombol History
            $('#monitoring-asset-table').on('click', '.btn-history', function(e) {
                e.preventDefault();
                var btn = $(this);
                var id = btn.data('id');
                
                // 1. Tampilkan Modal & Reset Body Tabel ke Loading
                $('#modal-hitory-transaction').modal('show');
                $('#table-body-transaksi').html('<tr><td colspan="7" class="text-center"><div class="spinner-border spinner-border-sm text-primary"></div> Memuat riwayat...</td></tr>');

                // 2. Isi Header Info Modal (Ambil dari data-attribute tombol action)
                $('#nomor_asset_bast-detail').text(btn.data('nomor_asset_bast') || '-');
                $('#category-detail').text(btn.data('category') || '-');
                $('#specification-detail').text(btn.data('specification') || '-');
                
                let currentStatus = btn.data('status');
                let statusBadge = currentStatus == 0 ? 
                    '<span class="badge bg-success">Standby</span>' : 
                    '<span class="badge bg-danger">Not Standby</span>';
                $('#status-detail').html(statusBadge);

                // 3. Tarik Data History via AJAX
                $.get("{{ url('monitoring/asset/transaction') }}/" + id, function(response) {
                    var html = "";
                    if (response.success && response.data.length > 0) {
                        $.each(response.data, function(i, item) {
                            // Penyesuaian variabel sesuai hasil mapping Controller
                            let typeBadge = item.history_type === 'IN' ? 'success' : 'danger';
                            
                            html += `<tr>
                                <td class="text-center">${i + 1}</td>
                                <td class="text-center"><code>${item.history_emp_id}</code></td>
                                <td>${item.history_emp_name}</td>
                                <td>${item.history_division}</td>
                                <td class="text-center"><span class="badge bg-${typeBadge}">${item.history_type}</span></td>
                                <td>${item.history_date}</td>
                                <td>${item.history_note || '-'}</td>
                            </tr>`;
                        });
                    } else {
                        html = '<tr><td colspan="7" class="text-center text-muted py-3">Belum ada log pergerakan untuk asset ini.</td></tr>';
                    }
                    $('#table-body-transaksi').html(html);
                }).fail(function(xhr) {
                    $('#table-body-transaksi').html(`<tr><td colspan="7" class="text-center text-danger">Gagal mengambil data riwayat.</td></tr>`);
                });
            });
        });
    </script>
@endsection