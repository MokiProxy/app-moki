@extends('layouts.App')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body border-bottom">
                    <div class="d-flex align-items-center">
                        <h5 class="mb-0 card-title flex-grow-1">Daftar Pegawai Hirarki</h5>
                        <div class="flex-shrink-0">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-hirarki">Tambah
                                Hirarki</button>
                            <a href="#!" class="btn btn-light"><i class="mdi mdi-refresh"></i></a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-striped align-middle dt-responsive nowrap w-100" id="hirarki-table">
                        <thead>
                            <th scope="col" style="width: 10px">No</th>
                            <th scope="col">Position ID</th>
                            <th scope="col">Posisi</th>
                            <th scope="col">Employee ID</th>
                            <th scope="col">Nama</th>
                            <th scope="col">Jabatan</th>
                            <th scope="col">Satuan Kerja</th>
                            <th scope="col" style="width:30px">Action</th>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @php
        $employeeMap = $employees->mapWithKeys(fn($e) => [
            $e->employee_id => [
                'name' => $e->name,
                'email' => $e->email,
                'jabatan' => $e->jabatan,
                'nopeg' => $e->user->nopeg ?? null,
            ]
        ]);
    @endphp

    <!-- MODAL ADD -->
    <div class="modal fade" id="modal-add-hirarki" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        role="dialog" aria-labelledby="title-add-hirarki" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="title-add-hirarki">Tambah Pegawai Hirarki</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="{{ route('pegawai-hirarki.store') }}" id="form-add-hirarki">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Position ID <span class="text-danger">*</span></label>
                                    <select class="form-select" name="position_id" required>
                                        <option value="">-- Pilih Posisi --</option>
                                        @foreach($positions as $pos)
                                            <option value="{{ $pos->position_id }}">{{ $pos->pos_title }} ({{ $pos->position_id }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Satuan Kerja <span class="text-danger">*</span></label>
                                    <select class="form-select" name="kode_satker" required>
                                        <option value="">-- Pilih Satuan Kerja --</option>
                                        @foreach($satkers as $sk)
                                            <option value="{{ $sk->kode_satker }}">{{ $sk->nama_satker }} ({{ $sk->kode_satker }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Employee</label>
                                    <select class="form-select employee-select" data-target="add">
                                        <option value="">-- Pilih Employee --</option>
                                        @foreach($employees as $emp)
                                            <option value="{{ $emp->employee_id }}">{{ $emp->name }} ({{ $emp->employee_id }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Employee ID</label>
                                    <input type="text" class="form-control" name="employee_id" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label>Nopeg</label>
                                    <input type="text" class="form-control" name="nopeg" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label>Nama</label>
                                    <input type="text" class="form-control" name="nama" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label>Email</label>
                                    <input type="email" class="form-control" name="email" readonly>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label>Jabatan</label>
                                    <input type="text" class="form-control" name="jabatan0" readonly>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="form-add-hirarki" class="btn btn-primary">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL UPDATE -->
    <div class="modal fade" id="modal-update-hirarki" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        role="dialog" aria-labelledby="title-update-hirarki" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="title-update-hirarki">Ubah Pegawai Hirarki</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="form-update-hirarki">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Position ID <span class="text-danger">*</span></label>
                                    <select class="form-select" name="position_id" required>
                                        <option value="">-- Pilih Posisi --</option>
                                        @foreach($positions as $pos)
                                            <option value="{{ $pos->position_id }}">{{ $pos->pos_title }} ({{ $pos->position_id }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Satuan Kerja <span class="text-danger">*</span></label>
                                    <select class="form-select" name="kode_satker" required>
                                        <option value="">-- Pilih Satuan Kerja --</option>
                                        @foreach($satkers as $sk)
                                            <option value="{{ $sk->kode_satker }}">{{ $sk->nama_satker }} ({{ $sk->kode_satker }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Employee</label>
                                    <select class="form-select employee-select" data-target="update">
                                        <option value="">-- Pilih Employee --</option>
                                        @foreach($employees as $emp)
                                            <option value="{{ $emp->employee_id }}">{{ $emp->name }} ({{ $emp->employee_id }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Employee ID</label>
                                    <input type="text" class="form-control" name="employee_id" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label>Nopeg</label>
                                    <input type="text" class="form-control" name="nopeg" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label>Nama</label>
                                    <input type="text" class="form-control" name="nama" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label>Email</label>
                                    <input type="email" class="form-control" name="email" readonly>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label>Jabatan</label>
                                    <input type="text" class="form-control" name="jabatan0" readonly>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="form-update-hirarki" class="btn btn-primary">Update</button>
                </div>
            </div>
        </div>
    </div>

    <form action="" id="form-delete-hirarki">
        @csrf
        @method('DELETE')
    </form>

    <!-- MODAL VIEW HIRARKI -->
    <div class="modal fade" id="modal-view-hirarki" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        role="dialog" aria-labelledby="title-view-hirarki" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="title-view-hirarki">Struktur Hirarki</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="hierarchy-chart" class="org-chart"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <link href="{{ asset('libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet"
        type="text/css" />
    <link href="{{ asset('libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css') }}" rel="stylesheet"
        type="text/css" />
    <link href="{{ asset('libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet"
        type="text/css" />

    <style>
        .org-chart {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 0;
            overflow-x: auto;
        }
        .org-node {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 14px 24px;
            min-width: 240px;
            max-width: 300px;
            text-align: center;
            box-shadow: 0 1px 4px rgba(0,0,0,.08);
        }
        .org-node.employee {
            border: 2px solid #556ee6;
            background: #f0f3ff;
        }
        .org-node-position {
            font-weight: 600;
            font-size: 13px;
            color: #556ee6;
            margin-bottom: 4px;
        }
        .org-node-name {
            font-size: 15px;
            font-weight: 600;
            color: #343a40;
        }
        .org-node-jabatan {
            font-size: 12px;
            color: #6c757d;
        }
        .org-node-email {
            font-size: 11px;
            color: #8c8fa3;
            margin-top: 2px;
        }
        .org-node-satker {
            font-size: 11px;
            color: #556ee6;
            margin-top: 4px;
            font-weight: 500;
        }
        .org-connector {
            width: 2px;
            height: 24px;
            background: #dee2e6;
        }
        .org-empty {
            border-style: dashed;
            color: #adb5bd;
            font-style: italic;
            font-size: 13px;
        }
    </style>
@endsection

@section('title')
    Pegawai Hirarki
@endsection

@section('plugin')
    <script src="{{ asset('libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>

    <script>
        var employeeMap = @json($employeeMap);

        function fillEmployeeFields(target, employeeId) {
            var prefix = target === 'add' ? '#form-add-hirarki' : '#form-update-hirarki';
            var data = employeeMap[employeeId];

            if (data) {
                $(prefix + ' [name="employee_id"]').val(data.employee_id || employeeId);
                $(prefix + ' [name="nopeg"]').val(data.nopeg || '');
                $(prefix + ' [name="nama"]').val(data.name || '');
                $(prefix + ' [name="email"]').val(data.email || '');
                $(prefix + ' [name="jabatan0"]').val(data.jabatan || '');
            } else {
                $(prefix + ' [name="employee_id"]').val('');
                $(prefix + ' [name="nopeg"]').val('');
                $(prefix + ' [name="nama"]').val('');
                $(prefix + ' [name="email"]').val('');
                $(prefix + ' [name="jabatan0"]').val('');
            }
        }

        $(function() {
            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');

            $('.employee-select').on('change', function() {
                var target = $(this).data('target');
                fillEmployeeFields(target, $(this).val());
            });

            var table = $("#hirarki-table").DataTable({
                lengthChange: !1,
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('pegawai-hirarki.datatable') }}",
                    type: "POST",
                    data: function(d) {
                        d._token = CSRF_TOKEN;
                    }
                },
                columnDefs: [{
                    className: "align-middle",
                    targets: "_all"
                }],
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                    { data: 'position_id', name: 'position_id' },
                    { data: 'posisi_title', name: 'posisi_title' },
                    { data: 'employee_id', name: 'employee_id' },
                    { data: 'nama', name: 'nama' },
                    { data: 'jabatan0', name: 'jabatan0' },
                    { data: 'satker_nama', name: 'satker_nama' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ]
            });

            $('#form-add-hirarki').submit(function() {
                $.ajax({
                    type: "POST",
                    url: $(this).attr('action'),
                    data: $(this).serialize(),
                    dataType: "JSON",
                    success: function(response) {
                        if (response.success == false) {
                            if (response.hasOwnProperty('data')) {
                                $.each(response.data.error, function(i, v) {
                                    $('#form-add-hirarki [name="' + i + '"]').after(
                                        '<small class="text-danger">' + v + '</small>');
                                });
                            } else {
                                notification('error', response.message);
                            }
                            return false;
                        }
                        notification('success', response.message);
                        drawTable('add-hirarki');
                    }
                });
                return false;
            });

            $('#form-update-hirarki').submit(function() {
                $.ajax({
                    type: "PUT",
                    url: $(this).attr('action'),
                    data: $(this).serialize(),
                    dataType: "JSON",
                    success: function(response) {
                        if (response.success == false) {
                            if (response.hasOwnProperty('data')) {
                                $.each(response.data.error, function(i, v) {
                                    $('#form-update-hirarki [name="' + i + '"]').after(
                                        '<small class="text-danger">' + v + '</small>');
                                });
                            } else {
                                notification('error', response.message);
                            }
                            return false;
                        }
                        notification('success', response.message);
                        drawTable('update-hirarki');
                    }
                });
                return false;
            });

            $('#form-delete-hirarki').submit(function() {
                $.ajax({
                    type: "DELETE",
                    url: $(this).attr('action'),
                    data: $(this).serialize(),
                    dataType: "JSON",
                    success: function(response) {
                        if (response.success == false) {
                            if (response.hasOwnProperty('data')) {
                                $.each(response.data.error, function(i, v) {
                                    $('#form-delete-hirarki [name="' + i + '"]').after(
                                        '<small class="text-danger">' + v + '</small>');
                                });
                            } else {
                                notification('error', response.message);
                            }
                            return false;
                        }
                        notification('success', response.message);
                        drawTable('delete-hirarki');
                    }
                });
                return false;
            });

            $('#hirarki-table').on('click', '.btn-view', function() {
                var id = $(this).data('id');
                $.ajax({
                    type: "GET",
                    url: "{{ url('pegawai-hirarki') }}/" + id + "/hierarchy",
                    dataType: "JSON",
                    success: function(response) {
                        if (response.success) {
                            renderOrgChart(response.data);
                            $('#modal-view-hirarki').modal('show');
                        } else {
                            notification('error', response.message);
                        }
                    }
                });
                return false;
            });

            $('#hirarki-table').on('click', '.btn-edit', function() {
                var id = $(this).data('id');
                $('#modal-update-hirarki').modal('show');
                var action = "{{ url('pegawai-hirarki/edit') }}/" + id;
                $('#form-update-hirarki').attr('action', action);

                $.ajax({
                    type: "get",
                    url: action,
                    dataType: "JSON",
                    success: function(response) {
                        if (response.success) {
                            $.each(response.data, function(i, v) {
                                var el = $('#form-update-hirarki [name="' + i + '"]');
                                if (el.length) {
                                    el.val(v);
                                }
                            });
                            var empId = response.data.employee_id;
                            if (empId && employeeMap[empId]) {
                                $('#form-update-hirarki .employee-select').val(empId);
                            }
                        } else {
                            notification('error', response.message);
                        }
                    }
                });
                return false;
            });

            $('#hirarki-table').on('click', '.btn-delete', function() {
                var id = $(this).data('id');
                $('#form-delete-hirarki').attr('action', "{{ url('pegawai-hirarki/delete') }}/" + id);

                Swal.fire({
                    title: "Are you sure?",
                    text: "Apakah anda yakin ingin menghapus Pegawai Hirarki ini?",
                    icon: "warning",
                    showCancelButton: !0,
                    confirmButtonColor: "#34c38f",
                    cancelButtonColor: "#f46a6a",
                    confirmButtonText: "Yes, delete it!",
                }).then(function(t) {
                    if (t.isConfirmed != false) {
                        $('#form-delete-hirarki').submit();
                    }
                });
            });

            function drawTable(param) {
                table.draw();
                if (param != null) {
                    $('#form-' + param)[0].reset();
                    $('#modal-' + param).modal('hide');
                }
            }

            function renderOrgChart(data) {
                var html = '';
                var superiors = data.superiors;
                var employee = data.employee;

                if (superiors.length === 0) {
                    html += '<div class="org-node org-empty">Top Level Position</div>';
                    html += '<div class="org-connector"></div>';
                } else {
                    for (var i = superiors.length - 1; i >= 0; i--) {
                        html += renderNode(superiors[i], false);
                        html += '<div class="org-connector"></div>';
                    }
                }

                html += renderNode(employee, true);

                $('#hierarchy-chart').html(html);
            }

            function renderNode(node, isEmployee) {
                var cls = isEmployee ? 'org-node employee' : 'org-node';
                var level = isEmployee ? 'Employee' : 'Level ' + node.level;
                var satker = isEmployee && node.nama_satker
                    ? '<div class="org-node-satker"><i class="bx bx-building"></i> ' + node.nama_satker + '</div>'
                    : '';

                return '<div class="' + cls + '">' +
                    '<div class="org-node-position">' + (node.pos_title || '-') + '</div>' +
                    '<div class="org-node-name">' + (node.nama || '-') + '</div>' +
                    '<div class="org-node-jabatan">' + (node.jabatan || node.jabatan0 || '-') + '</div>' +
                    (node.email ? '<div class="org-node-email"><i class="bx bx-envelope"></i> ' + node.email + '</div>' : '') +
                    satker +
                    '</div>';
            }
        });
    </script>
@endsection
