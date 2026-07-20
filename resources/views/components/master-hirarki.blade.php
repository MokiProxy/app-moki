@extends('layouts.App')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body border-bottom">
                    <div class="d-flex align-items-center">
                        <h5 class="mb-0 card-title flex-grow-1">Daftar Master Hirarki Posisi</h5>
                        <div class="flex-shrink-0">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-master-hirarki">Tambah
                                Master Hirarki</button>
                            <a href="#!" class="btn btn-light"><i class="mdi mdi-refresh"></i></a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-striped align-middle dt-responsive nowrap w-100" id="master-hirarki-table">
                        <thead>
                            <th scope="col" style="width: 10px">No</th>
                            <th scope="col">Position ID</th>
                            <th scope="col">Posisi</th>
                            <th scope="col">Superior 1</th>
                            <th scope="col">Superior 2</th>
                            <th scope="col">Superior 3</th>
                            <th scope="col">Action</th>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @php
        $fieldLabels = [
            1 => 'Superior 1', 2 => 'Superior 2', 3 => 'Superior 3', 4 => 'Superior 4',
            5 => 'Superior 5', 6 => 'Superior 6', 7 => 'Superior 7', 8 => 'Superior 8',
        ];
    @endphp

    <!-- MODAL ADD -->
    <div class="modal fade" id="modal-add-master-hirarki" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        role="dialog" aria-labelledby="title-add-master-hirarki" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="title-add-master-hirarki">Tambah Master Hirarki</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="{{ route('master-hirarki.store') }}" id="form-add-master-hirarki">
                        @csrf
                        <div class="row">
                            <div class="col-md-12">
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
                            @for($i = 1; $i <= 8; $i++)
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>{{ $fieldLabels[$i] }}</label>
                                    <select class="form-select" name="superior_{{ $i }}">
                                        <option value="">-- Pilih {{ $fieldLabels[$i] }} --</option>
                                        @foreach($positions as $pos)
                                            <option value="{{ $pos->position_id }}">{{ $pos->pos_title }} ({{ $pos->position_id }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @endfor
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="form-add-master-hirarki" class="btn btn-primary">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL UPDATE -->
    <div class="modal fade" id="modal-update-master-hirarki" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        role="dialog" aria-labelledby="title-update-master-hirarki" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="title-update-master-hirarki">Ubah Master Hirarki</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="form-update-master-hirarki">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="id">
                        <div class="row">
                            <div class="col-md-12">
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
                            @for($i = 1; $i <= 8; $i++)
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>{{ $fieldLabels[$i] }}</label>
                                    <select class="form-select" name="superior_{{ $i }}">
                                        <option value="">-- Pilih {{ $fieldLabels[$i] }} --</option>
                                        @foreach($positions as $pos)
                                            <option value="{{ $pos->position_id }}">{{ $pos->pos_title }} ({{ $pos->position_id }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @endfor
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="form-update-master-hirarki" class="btn btn-primary">Update</button>
                </div>
            </div>
        </div>
    </div>

    <form action="" id="form-delete-master-hirarki">
        @csrf
        @method('DELETE')
    </form>
@endsection

@section('css')
    <link href="{{ asset('libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet"
        type="text/css" />
    <link href="{{ asset('libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css') }}" rel="stylesheet"
        type="text/css" />
    <link href="{{ asset('libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet"
        type="text/css" />
@endsection

@section('title')
    Master Hirarki Posisi
@endsection

@section('plugin')
    <script src="{{ asset('libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>

    <script>
        $(function() {
            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            var table = $("#master-hirarki-table").DataTable({
                lengthChange: !1,
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('master-hirarki.datatable') }}",
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
                    { data: 'superior_1', name: 'superior_1' },
                    { data: 'superior_2', name: 'superior_2' },
                    { data: 'superior_3', name: 'superior_3' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ]
            });

            $('#form-add-master-hirarki').submit(function() {
                $.ajax({
                    type: "POST",
                    url: $(this).attr('action'),
                    data: $(this).serialize(),
                    dataType: "JSON",
                    success: function(response) {
                        if (response.success == false) {
                            if (response.hasOwnProperty('data')) {
                                $.each(response.data.error, function(i, v) {
                                    $('#form-add-master-hirarki [name="' + i + '"]').after(
                                        '<small class="text-danger">' + v + '</small>');
                                });
                            } else {
                                notification('error', response.message);
                            }
                            return false;
                        }
                        notification('success', response.message);
                        drawTable('add-master-hirarki');
                    }
                });
                return false;
            });

            $('#form-update-master-hirarki').submit(function() {
                $.ajax({
                    type: "PUT",
                    url: $(this).attr('action'),
                    data: $(this).serialize(),
                    dataType: "JSON",
                    success: function(response) {
                        if (response.success == false) {
                            if (response.hasOwnProperty('data')) {
                                $.each(response.data.error, function(i, v) {
                                    $('#form-update-master-hirarki [name="' + i + '"]').after(
                                        '<small class="text-danger">' + v + '</small>');
                                });
                            } else {
                                notification('error', response.message);
                            }
                            return false;
                        }
                        notification('success', response.message);
                        drawTable('update-master-hirarki');
                    }
                });
                return false;
            });

            $('#form-delete-master-hirarki').submit(function() {
                $.ajax({
                    type: "DELETE",
                    url: $(this).attr('action'),
                    data: $(this).serialize(),
                    dataType: "JSON",
                    success: function(response) {
                        if (response.success == false) {
                            if (response.hasOwnProperty('data')) {
                                $.each(response.data.error, function(i, v) {
                                    $('#form-delete-master-hirarki [name="' + i + '"]').after(
                                        '<small class="text-danger">' + v + '</small>');
                                });
                            } else {
                                notification('error', response.message);
                            }
                            return false;
                        }
                        notification('success', response.message);
                        drawTable('delete-master-hirarki');
                    }
                });
                return false;
            });

            $('#master-hirarki-table').on('click', '.btn-edit', function() {
                var id = $(this).data('id');
                $('#modal-update-master-hirarki').modal('show');
                var action = "{{ url('master-hirarki/edit') }}/" + id;
                $('#form-update-master-hirarki').attr('action', action);

                $.ajax({
                    type: "get",
                    url: action,
                    dataType: "JSON",
                    success: function(response) {
                        if (response.success) {
                            $.each(response.data, function(i, v) {
                                var el = $('#form-update-master-hirarki [name="' + i + '"]');
                                if (el.length) {
                                    el.val(v);
                                }
                            });
                        } else {
                            notification('error', response.message);
                        }
                    }
                });
                return false;
            });

            $('#master-hirarki-table').on('click', '.btn-delete', function() {
                var id = $(this).data('id');
                $('#form-delete-master-hirarki').attr('action', "{{ url('master-hirarki/delete') }}/" + id);

                Swal.fire({
                    title: "Are you sure?",
                    text: "Apakah anda yakin ingin menghapus Master Hirarki ini?",
                    icon: "warning",
                    showCancelButton: !0,
                    confirmButtonColor: "#34c38f",
                    cancelButtonColor: "#f46a6a",
                    confirmButtonText: "Yes, delete it!",
                }).then(function(t) {
                    if (t.isConfirmed != false) {
                        $('#form-delete-master-hirarki').submit();
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
        });
    </script>
@endsection
