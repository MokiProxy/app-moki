@extends('layouts.ITAdmin')

@section('title', 'Manajemen Role')

@section('css')
<link href="{{ asset('libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
<link href="{{ asset('libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css') }}" rel="stylesheet">
<style>
    .text-dark { color: #000000 !important; }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm mb-4">
            <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title text-dark fw-bold">Tambah Role Baru</h5>
            </div>
            <div class="card-body">
                <form id="formTambahRole">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark">Nama Role</label>
                                <input type="text" name="name" class="form-control text-dark" required placeholder="Masukkan nama role (contoh: manager)">
                            </div>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="mb-3 w-100">
                                <button type="submit" class="btn btn-primary px-4 w-100" id="btnSimpan">
                                    <i class="mdi mdi-plus me-1"></i> Tambah Role
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title text-dark fw-bold">Daftar Role</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100" id="tableRoles">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center">No</th>
                                <th>Nama Role</th>
                                <th>Guard</th>
                                <th class="text-center">Jumlah User</th>
                                <th class="text-center">Tanggal Dibuat</th>
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
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white">Edit Role</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEdit">
                @csrf
                @method('PUT')
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Nama Role</label>
                        <input type="text" name="name" class="form-control text-dark" id="edit_name" required>
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

<div class="modal fade" id="modalPermissions" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success">
                <h5 class="modal-title text-white">
                    <i class="mdi mdi-shield-key me-1"></i>
                    Atur Permission: <span id="perm_role_name"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formPermissions">
                @csrf
                <input type="hidden" name="role_id" id="perm_role_id">
                <div class="modal-body">
                    <div class="row" id="permissions_container">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan Permission</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('plugin')
<script src="{{ asset('libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    jQuery(function($) {
        var editModal = new bootstrap.Modal(document.getElementById('modalEdit'));
        var permModal = new bootstrap.Modal(document.getElementById('modalPermissions'));

        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        var table = $('#tableRoles').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('it-admin.roles.datatable') }}",
                type: 'POST',
                data: function(d) {
                    d._token = CSRF_TOKEN;
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'name', name: 'name' },
                { data: 'guard_name', name: 'guard_name' },
                { data: 'user_count', name: 'user_count', orderable: false, searchable: false, className: 'text-center' },
                { data: 'created_at_formatted', name: 'created_at', className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' },
            ],
        });

        $('#formTambahRole').on('submit', function(e) {
            e.preventDefault();
            $('#btnSimpan').prop('disabled', true).text('Menyimpan...');
            $.ajax({
                url: "{{ route('it-admin.roles.store') }}",
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
                    $('#btnSimpan').prop('disabled', false).html('<i class="mdi mdi-plus me-1"></i> Tambah Role');
                }
            });
        });

        $(document).on('click', '.btn-edit', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');
            $('#edit_id').val(id);
            $('#edit_name').val(name);
            editModal.show();
        });

        $('#formEdit').on('submit', function(e) {
            e.preventDefault();
            var id = $('#edit_id').val();
            $.ajax({
                url: "{{ url('it-admin/roles/update') }}/" + id,
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
                title: 'Hapus Role?',
                text: "Role yang masih memiliki user tidak dapat dihapus.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f46a6a',
                confirmButtonText: 'Ya, Hapus!'
            }).then(function(result) {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('it-admin/roles/delete') }}/" + id,
                        type: "DELETE",
                        data: { _token: "{{ csrf_token() }}" },
                        success: function() {
                            location.reload();
                        }
                    });
                }
            });
        });

        $(document).on('click', '.btn-permissions', function() {
            var id = $(this).data('id');
            $('#perm_role_id').val(id);

            $.get("{{ url('it-admin/roles/permissions') }}/" + id, function(res) {
                if (res.success) {
                    $('#perm_role_name').text(res.role.name);
                    var html = '';
                    var groups = {};
                    res.permissions.forEach(function(p) {
                        var group = p.name.split('.')[0] || 'general';
                        if (!groups[group]) groups[group] = [];
                        groups[group].push(p);
                    });

                    for (var group in groups) {
                        html += '<div class="col-md-6 mb-3">';
                        html += '<div class="card"><div class="card-header py-2">';
                        html += '<strong class="text-uppercase small">' + group + '</strong>';
                        html += '</div><div class="card-body py-2">';
                        groups[group].forEach(function(p) {
                            var checked = res.role_permission_names.includes(p.name) ? 'checked' : '';
                            html += '<div class="form-check mb-1">';
                            html += '<input class="form-check-input permission-check" type="checkbox" ';
                            html += 'name="permissions[]" value="' + p.name + '" id="perm_' + p.name.replace(/\./g, '_') + '" ' + checked + '>';
                            html += '<label class="form-check-label small" for="perm_' + p.name.replace(/\./g, '_') + '">';
                            html += p.name + '</label></div>';
                        });
                        html += '</div></div></div>';
                    }
                    $('#permissions_container').html(html);
                    permModal.show();
                }
            });
        });

        $('#formPermissions').on('submit', function(e) {
            e.preventDefault();
            var id = $('#perm_role_id').val();
            $.ajax({
                url: "{{ url('it-admin/roles/sync-permissions') }}/" + id,
                type: "POST",
                data: $(this).serialize(),
                success: function(res) {
                    permModal.hide();
                    Swal.fire('Berhasil!', res.message, 'success');
                },
                error: function(xhr) {
                    var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan.';
                    Swal.fire('Gagal!', msg, 'error');
                }
            });
        });
    });
</script>
@endsection
