@extends('layouts.App')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body border-bottom">
                    <div class="d-flex align-items-center">
                        <h5 class="mb-0 card-title flex-grow-1">Daftar Master Posisi</h5>
                        <div class="flex-shrink-0">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-posisi">Tambah
                                Posisi</button>
                            <a href="#!" class="btn btn-light"><i class="mdi mdi-refresh"></i></a>
                        </div>
                    </div>
                </div>
                <div class="card-body">

                    <table class="table table-striped align-middle dt-responsive nowrap w-100" id="posisi-table">
                        <thead>
                            <th scope="col" style="width: 10px">No</th>
                            <th scope="col">Position ID</th>
                            <th scope="col">Pos Title</th>
                            <th scope="col">Superior</th>
                            <th scope="col">Last Mode Date</th>
                            <th scope="col">Last Mode Time</th>
                            <th scope="col" style="width:30px">Action</th>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-add-posisi" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        role="dialog" aria-labelledby="title-add-posisi" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="title-add-posisi">Tambah Posisi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="{{ route('master-posisi.store') }}" id="form-add-posisi">
                        @csrf
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="">Position ID</label>
                                    <input type="text" class="form-control" name="position_id">
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="">Pos Title</label>
                                    <input type="text" class="form-control" name="pos_title">
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="">Superior</label>
                                    <select class="form-select" name="superior_id">
                                        <option value="">-- Pilih Superior --</option>
                                        @foreach($positions as $pos)
                                            <option value="{{ $pos->position_id }}">{{ $pos->pos_title }} ({{ $pos->position_id }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="">Last Mode Date</label>
                                    <input type="date" class="form-control" name="last_mode_date">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="">Last Mode Time</label>
                                    <input type="time" class="form-control" name="last_mode_time">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="form-add-posisi" class="btn btn-primary">Save</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-update-posisi" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        role="dialog" aria-labelledby="title-update-posisi" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="title-update-posisi">Ubah Posisi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="form-update-posisi">
                        @csrf
                        @method('PUT')

                        <input type="hidden" name="id">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="">Position ID</label>
                                    <input type="text" class="form-control" name="position_id">
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="">Pos Title</label>
                                    <input type="text" class="form-control" name="pos_title">
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="">Superior</label>
                                    <select class="form-select" name="superior_id">
                                        <option value="">-- Pilih Superior --</option>
                                        @foreach($positions as $pos)
                                            <option value="{{ $pos->position_id }}">{{ $pos->pos_title }} ({{ $pos->position_id }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="">Last Mode Date</label>
                                    <input type="date" class="form-control" name="last_mode_date">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="">Last Mode Time</label>
                                    <input type="time" class="form-control" name="last_mode_time">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="form-update-posisi" class="btn btn-primary">Update</button>
                </div>
            </div>
        </div>
    </div>

    <form action="" id="form-delete-posisi">
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
    Master Posisi
@endsection

@section('plugin')
    <script src="{{ asset('libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>

    <script>
        $(function() {
            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            var table = $("#posisi-table").DataTable({
                lengthChange: !1,
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('master-posisi.datatable') }}",
                    type: "POST",
                    data: function(d) {
                        d._token = CSRF_TOKEN;
                    }
                },
                columnDefs: [{
                    className: "align-middle",
                    targets: "_all"
                }],
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'position_id',
                        name: 'position_id'
                    },
                    {
                        data: 'pos_title',
                        name: 'pos_title'
                    },
                    {
                        data: 'superior_name',
                        name: 'superior_name'
                    },
                    {
                        data: 'last_mode_date',
                        name: 'last_mode_date',
                        render: function(value) {
                            if (value === null) return "";
                            return moment(value).lang('id').format('Do MMMM YYYY');
                        }
                    },
                    {
                        data: 'last_mode_time',
                        name: 'last_mode_time'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ]
            });

            $('#form-add-posisi').submit(function() {
                var data = $(this).serialize();

                $.ajax({
                    type: "POST",
                    url: $(this).attr('action'),
                    data: data,
                    dataType: "JSON",
                    success: function(response) {
                        if (response.success == false) {
                            if (response.hasOwnProperty('data')) {
                                $.each(response.data.error, function(i, v) {
                                    $('#form-add-posisi [name="' + i + '"]').after(
                                        '<small class="text-danger">' + v + '</small>');
                                });
                            } else {
                                notification('error', response.message);
                            }
                            return false;
                        }

                        notification('success', response.message);
                        drawTable('add-posisi');
                    }
                });

                return false;
            });

            $('#form-update-posisi').submit(function() {
                var data = $(this).serialize();

                $.ajax({
                    type: "PUT",
                    url: $(this).attr('action'),
                    data: data,
                    dataType: "JSON",
                    success: function(response) {
                        if (response.success == false) {
                            if (response.hasOwnProperty('data')) {
                                $.each(response.data.error, function(i, v) {
                                    $('#form-update-posisi [name="' + i + '"]').after(
                                        '<small class="text-danger">' + v + '</small>');
                                });
                            } else {
                                notification('error', response.message);
                            }
                            return false;
                        }

                        notification('success', response.message);
                        drawTable('update-posisi');
                    }
                });

                return false;
            });

            $('#form-delete-posisi').submit(function() {
                var data = $(this).serialize();

                $.ajax({
                    type: "DELETE",
                    url: $(this).attr('action'),
                    data: data,
                    dataType: "JSON",
                    success: function(response) {
                        if (response.success == false) {
                            if (response.hasOwnProperty('data')) {
                                $.each(response.data.error, function(i, v) {
                                    $('#form-delete-posisi [name="' + i + '"]').after(
                                        '<small class="text-danger">' + v + '</small>');
                                });
                            } else {
                                notification('error', response.message);
                            }
                            return false;
                        }

                        notification('success', response.message);
                        drawTable('delete-posisi');
                    }
                });

                return false;
            });

            $('#posisi-table').on('click', '.btn-edit', function() {
                var id = $(this).data('id');
                $('#modal-update-posisi').modal('show');

                var action = "{{ url('master-posisi/edit') }}/" + id;

                $('#form-update-posisi').attr('action', action);

                $.ajax({
                    type: "get",
                    url: action,
                    dataType: "JSON",
                    success: function(response) {
                        if (response.success) {
                            $.each(response.data, function(i, v) {
                                $('#form-update-posisi [name="' + i + '"]').val(v);
                            });
                        } else {
                            notification('error', response.message);
                        }
                    }
                });

                return false;
            });

            $('#posisi-table').on('click', '.btn-delete', function() {
                var id = $(this).data('id');
                $('#form-delete-posisi').attr('action', "{{ url('master-posisi/delete') }}/" + id);

                Swal.fire({
                    title: "Are you sure?",
                    text: "Apakah anda yakin ingin menghapus Posisi ini?",
                    icon: "warning",
                    showCancelButton: !0,
                    confirmButtonColor: "#34c38f",
                    cancelButtonColor: "#f46a6a",
                    confirmButtonText: "Yes, delete it!",
                }).then(function(t) {
                    if (t.isConfirmed != false) {
                        $('#form-delete-posisi').submit();
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
