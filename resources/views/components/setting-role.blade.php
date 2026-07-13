@extends('layouts.App')

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm mb-4">
            <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title text-dark fw-bold">Setting Role User</h5>
                <span class="badge bg-soft-info text-info">Konfigurasi Hak Akses & Password</span>
            </div>
            <div class="card-body">
                <form id="formTambahRole">
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark">Pilih Karyawan</label>
                                <select class="form-control text-dark select2-init" name="employee_id" id="select_employee" required style="width: 100%">
                                    <option value="">-- Cari NIK atau Nama --</option>
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}">{{ $emp->employee_id }} - {{ $emp->name }}</option>
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
                                <select class="form-select text-dark" name="role_id" required>
                                    <option value="">-- Pilih Role --</option>
                                    <option value="1">Super Admin</option>
                                    <option value="2">Approver</option>
                                    <option value="3">User/Staff</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="text-end border-top pt-3">
                        <button type="submit" class="btn btn-primary px-4" id="btnSimpan">
                            <i class="mdi mdi-content-save me-1"></i> Simpan Role
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 50px" class="text-center border-bottom-0">No</th>
                                <th class="border-bottom-0">NIP</th>
                                <th class="border-bottom-0">Nama</th>
                                <th class="border-bottom-0">Jabatan</th>
                                <th class="text-center border-bottom-0">Role</th>
                                <th class="text-center border-bottom-0" style="width: 120px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($userRoles as $key => $row)
                            <tr>
                                <td class="text-center text-dark">{{ $key + 1 }}</td>
                                <td class="text-dark font-monospace fw-bold">{{ $row->nip }}</td>
                                <td class="text-dark">{{ $row->name }}</td>
                                <td class="text-dark">{{ $row->jabatan }}</td>
                                <td class="text-center">
                                    @if($row->role_id == 1) <span class="badge bg-danger">Super Admin</span>
                                    @elseif($row->role_id == 2) <span class="badge bg-warning text-dark">Approver</span>
                                    @else <span class="badge bg-info">Staff</span> @endif
                                </td>
                                <td class="p-0 text-center">
                                    <div class="d-flex justify-content-center align-items-stretch" style="height: 38px;">
                                        <button class="btn-action-box btn-password-color btn-password" data-id="{{ $row->id }}" title="Set Password">
                                            <i class="mdi mdi-key text-white"></i>
                                        </button>
                                        <button class="btn-action-box btn-edit-color btn-edit" data-id="{{ $row->id }}" title="Edit">
                                            <i class="mdi mdi-pencil text-white"></i>
                                        </button>
                                        <button class="btn-action-box btn-delete-color btn-delete" data-id="{{ $row->id }}" title="Hapus">
                                            <i class="mdi mdi-delete text-white"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Belum ada data role user.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditRole" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white">Edit Role User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditRole">
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
                        <select class="form-select text-dark" name="role_id" id="edit_role_id" required>
                            <option value="1">Super Admin</option>
                            <option value="2">Approver</option>
                            <option value="3">User/Staff</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnUpdate">Update Role</button>
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
                <input type="hidden" name="user_role_id" id="pass_user_role_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Password Baru</label>
                        <input type="password" name="password" class="form-control text-dark" required minlength="6" placeholder="Min. 6 Karakter">
                        <small class="text-muted">Password ini akan digunakan untuk Login aplikasi.</small>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="submit" class="btn btn-info text-white w-100" id="btnSavePassword">Simpan & Sinkronkan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .text-dark { color: #000000 !important; }
    .font-monospace { font-family: 'Courier New', Courier, monospace !important; font-weight: bold; color: #000 !important; }
    .btn-action-box { border: none; width: 40px; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
    .btn-password-color { background-color: #17a2b8; border-radius: 4px 0 0 4px; }
    .btn-edit-color { background-color: #556ee6; }
    .btn-delete-color { background-color: #f46a6a; border-radius: 0 4px 4px 0; }
    .btn-action-box:hover { opacity: 0.8; transform: scale(1.05); }
    .select2-container--default .select2-selection--single { height: 38px !important; border: 1px solid #ced4da !important; padding-top: 5px; }
</style>
@endsection

@section('plugin')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        var $j = jQuery.noConflict(true);

        $j(document).ready(function() {
            var editModal = new bootstrap.Modal(document.getElementById('modalEditRole'));
            var passwordModal = new bootstrap.Modal(document.getElementById('modalPassword'));

            // 1. Inisialisasi Select2
            $j('.select2-init').select2({
                placeholder: "-- Cari NIK atau Nama --",
                allowClear: true,
                width: '100%'
            });

            // 2. Ambil Jabatan Otomatis
            $j('#select_employee').on('change', function() {
                var id = $j(this).val();
                if (id) {
                    $j('#input_jabatan').val('Memuat...');
                    $j.get("{{ url('settings/role/employee') }}/" + id, function(res) {
                        $j('#input_jabatan').val(res.jabatan);
                    });
                } else {
                    $j('#input_jabatan').val('');
                }
            });

            // 3. Simpan Role Baru
            $j('#formTambahRole').on('submit', function(e) {
                e.preventDefault();
                $j('#btnSimpan').prop('disabled', true).text('Menyimpan...');
                $j.ajax({
                    url: "{{ route('settings.role.store') }}",
                    type: "POST",
                    data: $j(this).serialize(),
                    success: function(res) {
                        Swal.fire('Berhasil!', res.message, 'success').then(() => { location.reload(); });
                    },
                    error: function() {
                        Swal.fire('Gagal!', 'Terjadi kesalahan saat menyimpan data.', 'error');
                        $j('#btnSimpan').prop('disabled', false).html('<i class="mdi mdi-content-save me-1"></i> Simpan Role');
                    }
                });
            });

            // 4. Modal Password
            $j(document).on('click', '.btn-password', function() {
                var id = $j(this).data('id');
                $j('#pass_user_role_id').val(id);
                $j('#formSetPassword')[0].reset();
                passwordModal.show();
            });

            // 5. Submit Set Password (Sinkronisasi ke tabel Users)
            $j('#formSetPassword').on('submit', function(e) {
                e.preventDefault();
                var btn = $j('#btnSavePassword');
                btn.prop('disabled', true).text('Menyinkronkan...');

                $j.ajax({
                    // Gunakan route name yang benar agar tidak 404
                    url: "{{ route('settings.role.set-password') }}",
                    type: "POST",
                    data: $j(this).serialize(),
                    success: function(res) {
                        passwordModal.hide();
                        Swal.fire({
                            title: 'Berhasil!',
                            text: res.message,
                            icon: 'success'
                        });
                        btn.prop('disabled', false).text('Simpan Password');
                    },
                    error: function(xhr) {
                        var errorMsg = xhr.responseJSON ? xhr.responseJSON.message : 'Kesalahan Route/Server';
                        Swal.fire('Gagal!', errorMsg, 'error');
                        btn.prop('disabled', false).text('Simpan Password');
                    }
                });
            });

            // 6. Trigger Edit
            $j(document).on('click', '.btn-edit', function() {
                var id = $j(this).data('id');
                $j.get("{{ url('settings/role/edit') }}/" + id, function(res) {
                    if (res.success) {
                        $j('#edit_id').val(res.data.id);
                        $j('#edit_employee_id').val(res.data.employee_id);
                        $j('#edit_nama').val(res.data.name);
                        $j('#edit_jabatan').val(res.data.jabatan);
                        $j('#edit_role_id').val(res.data.role_id);
                        editModal.show();
                    }
                });
            });

            // 7. Update Role
            $j('#formEditRole').on('submit', function(e) {
                e.preventDefault();
                $j.ajax({
                    url: "{{ route('settings.role.store') }}",
                    type: "POST",
                    data: $j(this).serialize(),
                    success: function() {
                        editModal.hide();
                        Swal.fire('Berhasil!', 'Data role diperbarui.', 'success').then(() => { location.reload(); });
                    }
                });
            });

            // 8. Delete Role
            $j(document).on('click', '.btn-delete', function() {
                var id = $j(this).data('id');
                Swal.fire({
                    title: 'Hapus Role?',
                    text: "User ini tidak akan bisa login jika role dihapus.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f46a6a',
                    confirmButtonText: 'Ya, Hapus!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $j.ajax({
                            url: "{{ url('settings/role/delete') }}/" + id,
                            type: "DELETE",
                            data: { _token: "{{ csrf_token() }}" },
                            success: function() { location.reload(); }
                        });
                    }
                });
            });
        });
    </script>
@endsection