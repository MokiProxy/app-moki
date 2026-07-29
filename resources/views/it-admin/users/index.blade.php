@extends('layouts.ITAdmin')

@section('title', 'Manajemen User')

@section('css')
<link href="{{ asset('libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
<link href="{{ asset('libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css') }}" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .text-dark { color: #000000 !important; }
    .font-monospace { font-family: 'Courier New', Courier, monospace !important; }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm mb-4">
            <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title text-dark fw-bold">Tambah User & Role</h5>
            </div>
            <div class="card-body">
                <form id="formTambahUser">
                    @csrf
                    <input type="hidden" name="id" id="input_id">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark">Pilih Karyawan</label>
                                <select class="form-control text-dark select2-init" name="employee_id" id="select_employee" required style="width: 100%">
                                    <option value="">-- Cari NIK atau Nama --</option>
                                    @foreach($employees as $emp)
                                    <option value="{{ $emp->employee_id }}">{{ $emp->employee_id }} - {{ $emp->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark">Jabatan</label>
                                <input type="text" class="form-control text-dark bg-light" id="input_jabatan" readonly placeholder="Otomatis...">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark">Role Akses</label>
                                <select class="form-select select2-multiple text-dark" name="role_names[]" multiple required>
                                    @foreach($roles as $role)
                                    <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="text-end border-top pt-3">
                        <button type="submit" class="btn btn-primary px-4" id="btnSimpan">
                            <i class="mdi mdi-content-save me-1"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title text-dark fw-bold">Daftar User</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100" id="tableUsers">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center">No</th>
                                <th>NIP</th>
                                <th>Nama</th>
                                <th>Jabatan</th>
                                <th>Email</th>
                                <th class="text-center">Role</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white">Edit Role User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEdit">
                @csrf
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="employee_id" id="edit_employee_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Nama Karyawan</label>
                        <input type="text" class="form-control text-dark bg-light" id="edit_nama" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Jabatan</label>
                        <input type="text" class="form-control text-dark bg-light" id="edit_jabatan" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Role Akses</label>
                        <select class="form-select select2-multiple text-dark" name="role_names[]" id="edit_role_names" multiple required>
                            @foreach($roles as $role)
                            <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPassword" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info">
                <h5 class="modal-title text-white"><i class="mdi mdi-key me-1"></i> Set Password</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formSetPassword">
                @csrf
                <input type="hidden" name="user_id" id="pass_user_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Password Baru</label>
                        <input type="password" name="password" class="form-control text-dark" required minlength="6" placeholder="Min. 6 Karakter">
                        <small class="text-muted">Password ini akan digunakan untuk Login aplikasi.</small>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="submit" class="btn btn-info text-white w-100">Simpan & Sinkronkan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('plugin')
<script src="{{ asset('libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ asset('libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    jQuery(function($) {
        var editModal = new bootstrap.Modal(document.getElementById('modalEdit'));
        var passwordModal = new bootstrap.Modal(document.getElementById('modalPassword'));

        $('.select2-init').select2({
            placeholder: "-- Cari NIK atau Nama --",
            allowClear: true,
            width: '100%'
        });

        $('.select2-multiple').select2({
            placeholder: "-- Pilih Role --",
            width: '100%'
        });

        $('#select_employee').on('change', function() {
            var id = $(this).val();
            if (id) {
                $('#input_jabatan').val('Memuat...');
                $.get("{{ url('settings/role/employee') }}/" + id, function(res) {
                    $('#input_jabatan').val(res.jabatan);
                });
            } else {
                $('#input_jabatan').val('');
            }
        });

        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        var table = $('#tableUsers').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('it-admin.users.datatable') }}",
                type: 'POST',
                data: function(d) {
                    d._token = CSRF_TOKEN;
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'nip', name: 'employee_id' },
                { data: 'employee_name', name: 'employee.name' },
                { data: 'jabatan', name: 'employee.jabatan', orderable: false },
                { data: 'email', name: 'email' },
                { data: 'role_name', name: 'role_name', orderable: false, searchable: false, className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' },
            ],
        });

        $('#formTambahUser').on('submit', function(e) {
            e.preventDefault();
            $('#btnSimpan').prop('disabled', true).text('Menyimpan...');
            $.ajax({
                url: "{{ route('it-admin.users.store') }}",
                type: "POST",
                data: $(this).serialize(),
                success: function(res) {
                    Swal.fire('Berhasil!', res.message, 'success').then(function() {
                        location.reload();
                    });
                },
                error: function(xhr) {
                    var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan.';
                    Swal.fire('Gagal!', msg, 'error');
                    $('#btnSimpan').prop('disabled', false).html('<i class="mdi mdi-content-save me-1"></i> Simpan');
                }
            });
        });

        $(document).on('click', '.btn-password', function() {
            var id = $(this).data('id');
            $('#pass_user_id').val(id);
            $('#formSetPassword')[0].reset();
            passwordModal.show();
        });

        $('#formSetPassword').on('submit', function(e) {
            e.preventDefault();
            var btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).text('Menyinkronkan...');
            $.ajax({
                url: "{{ route('it-admin.users.set-password') }}",
                type: "POST",
                data: $(this).serialize(),
                success: function(res) {
                    passwordModal.hide();
                    Swal.fire('Berhasil!', res.message, 'success');
                    btn.prop('disabled', false).text('Simpan Password');
                },
                error: function(xhr) {
                    var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Kesalahan Server';
                    Swal.fire('Gagal!', msg, 'error');
                    btn.prop('disabled', false).text('Simpan Password');
                }
            });
        });

        $(document).on('click', '.btn-edit', function() {
            var id = $(this).data('id');
            $.get("{{ url('it-admin/users/edit') }}/" + id, function(res) {
                if (res.success) {
                    $('#edit_id').val(res.data.id);
                    $('#edit_employee_id').val(res.data.employee_id);
                    $('#edit_nama').val(res.data.name);
                    $('#edit_jabatan').val(res.data.jabatan);
                    $('#edit_role_names').val(res.data.role_names).trigger('change');
                    editModal.show();
                }
            });
        });

        $('#formEdit').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: "{{ route('it-admin.users.store') }}",
                type: "POST",
                data: $(this).serialize(),
                success: function(res) {
                    editModal.hide();
                    Swal.fire('Berhasil!', res.message, 'success').then(function() {
                        location.reload();
                    });
                },
                error: function(xhr) {
                    var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan.';
                    Swal.fire('Gagal!', msg, 'error');
                }
            });
        });

        $(document).on('click', '.btn-delete', function() {
            var id = $(this).data('id');
            Swal.fire({
                title: 'Hapus User?',
                text: "User ini tidak akan bisa login jika dihapus.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f46a6a',
                confirmButtonText: 'Ya, Hapus!'
            }).then(function(result) {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('it-admin/users/delete') }}/" + id,
                        type: "DELETE",
                        data: { _token: "{{ csrf_token() }}" },
                        success: function() {
                            location.reload();
                        }
                    });
                }
            });
        });
    });
</script>
@endsection
