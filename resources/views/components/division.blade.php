@extends('layouts.App')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body border-bottom">
                    <div class="d-flex align-items-center">
                        <h5 class="mb-0 card-title flex-grow-1">Daftar Divisi</h5>
                        <div class="flex-shrink-0">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-division">Tambah Divisi</button>
                            <a href="#!" class="btn btn-light" onclick="window.location.reload()"><i class="mdi mdi-refresh"></i></a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-striped align-middle dt-responsive nowrap w-100" id="division-table">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 10px">No</th>
                                <th scope="col">Nama</th>
                                <th scope="col">Kode</th>
                                <th scope="col">Singkatan</th>
                                <th scope="col">Regional</th>
                                <th scope="col">Perusahaan</th>
                                <th scope="col">Created At</th>
                                <th scope="col" style="width:30px">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Add --}}
    <div class="modal fade" id="modal-add-division" data-bs-backdrop="static" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Divisi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="{{ route('division.store') }}" id="form-add-division">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Nama Divisi</label>
                            <input type="text" class="form-control" name="name">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kode Divisi</label>
                            <input type="text" class="form-control" name="code">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Singkatan</label>
                            <input type="text" class="form-control" name="abbreviation">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Regional</label>
                            <select name="regional_id" class="form-control select2-add">
                                <option value="">Pilih Regional</option>
                                @foreach ($regionals as $regional)
                                    <option value="{{ $regional->id }}">{{ $regional->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Perusahaan</label>
                            <select name="company_id" class="form-control select2-add">
                                <option value="">Pilih Perusahaan</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="form-add-division" class="btn btn-primary">Save</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Update/View --}}
    <div class="modal fade" id="modal-update-division" data-bs-backdrop="static" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-update-title">Ubah Divisi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="form-update-division">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">Nama Divisi</label>
                            <input type="text" class="form-control" name="name" id="update-name">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kode Divisi</label>
                            <input type="text" class="form-control" name="code" id="update-code">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Singkatan</label>
                            <input type="text" class="form-control" name="abbreviation" id="update-abbreviation">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Regional</label>
                            <select name="regional_id" id="update-regional_id" class="form-control select2-update">
                                @foreach ($regionals as $regional)
                                    <option value="{{ $regional->id }}">{{ $regional->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Perusahaan</label>
                            <select name="company_id" id="update-company_id" class="form-control select2-update">
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="btn-update-submit" form="form-update-division" class="btn btn-primary">Update</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <link href="{{ asset('libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('libs/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('plugin')
    <script src="{{ asset('libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('libs/select2/js/select2.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment-with-locales.min.js"></script>

<script>
    $(document).ready(function() {
        const CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        
        const table = $("#division-table").DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('division.datatable') }}",
                type: "POST",
                data: { _token: CSRF_TOKEN }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'name', name: 'name' },
                { data: 'code', name: 'code' },
                { data: 'abbreviation', name: 'abbreviation' },
                { data: 'regional.name', name: 'regional.name', defaultContent: '-' },
                { data: 'company.name', name: 'company.name', defaultContent: '-' },
                { 
                    data: 'created_at', 
                    render: (data) => data ? moment(data).locale('id').format('Do MMMM YYYY H:mm') : '-' 
                },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });

        // Alerter Helper
        const notify = (icon, text) => {
            Swal.fire({ icon: icon, title: text, showConfirmButton: false, timer: 1500 });
        };

        // Error Handler Helper
        const handleAjaxError = (xhr, formId) => {
            if (xhr.status === 422) {
                const errors = xhr.responseJSON.errors;
                $.each(errors, function(key, val) {
                    let el = $(`${formId} [name="${key}"]`);
                    el.addClass('is-invalid');
                    el.after(`<div class="invalid-feedback">${val[0]}</div>`);
                });
            } else {
                notify('error', 'Terjadi kesalahan sistem');
            }
        };

        // --- Store ---
        $('#form-add-division').on('submit', function(e) {
            e.preventDefault();
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    $('#modal-add-division').modal('hide');
                    $('#form-add-division')[0].reset();
                    $('.select2-add').val(null).trigger('change');
                    notify('success', res.message);
                    table.ajax.reload();
                },
                error: (xhr) => handleAjaxError(xhr, '#form-add-division')
            });
        });

        // --- Edit & View ---
        $('#division-table').on('click', '.btn-edit, .btn-view', function() {
            const id = $(this).data('id');
            const isView = $(this).hasClass('btn-view');
            const url = `{{ url('division/show') }}/${id}`;
            
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            $.get(url, function(res) {
                if (res.success) {
                    $('#form-update-division').attr('action', `{{ url('division/update') }}/${id}`);
                    $('#update-name').val(res.data.name).prop('readonly', isView);
                    $('#update-code').val(res.data.code).prop('readonly', isView);
                    $('#update-abbreviation').val(res.data.abbreviation).prop('readonly', isView);
                    $('#update-regional_id').val(res.data.regional_id).trigger('change').prop('disabled', isView);
                    $('#update-company_id').val(res.data.company_id).trigger('change').prop('disabled', isView);
                    
                    $('#modal-update-title').text(isView ? 'Detail Divisi' : 'Ubah Divisi');
                    isView ? $('#btn-update-submit').hide() : $('#btn-update-submit').show();
                    $('#modal-update-division').modal('show');
                }
            });
        });

        // --- Update ---
        $('#form-update-division').on('submit', function(e) {
            e.preventDefault();
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    $('#modal-update-division').modal('hide');
                    notify('success', res.message);
                    table.ajax.reload();
                },
                error: (xhr) => handleAjaxError(xhr, '#form-update-division')
            });
        });

        // --- Delete ---
        $('#division-table').on('click', '.btn-delete', function() {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Hapus data ini?',
                text: "Data yang dihapus tidak bisa dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f46a6a',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `{{ url('division/delete') }}/${id}`,
                        type: 'DELETE',
                        data: { _token: CSRF_TOKEN },
                        success: function(res) {
                            notify('success', res.message);
                            table.ajax.reload();
                        }
                    });
                }
            });
        });

        $('.select2-add').select2({ width: '100%', dropdownParent: "#modal-add-division" });
        $('.select2-update').select2({ width: '100%', dropdownParent: "#modal-update-division" });
    });
</script>
@endsection