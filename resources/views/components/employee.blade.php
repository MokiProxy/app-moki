@extends('layouts.App')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-body border-bottom bg-light">
                    <div class="d-flex align-items-center">
                        <h5 class="mb-0 card-title flex-grow-1">Daftar Karyawan</h5>
                        <div class="flex-shrink-0 d-flex gap-1">
                            <button class="btn btn-success" id="btn-download-template">
                                <i class="mdi mdi-file-download-outline me-1"></i> Template
                            </button>
                            <button class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#modal-import-employee">
                                <i class="mdi mdi-file-import-outline me-1"></i> Import
                            </button>
                            <button class="btn btn-primary" id="btn-add-employee">
                                <i class="mdi mdi-plus me-1"></i> Tambah Karyawan
                            </button>
                            <a href="#!" class="btn btn-light" id="btn-refresh"><i class="mdi mdi-refresh"></i></a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-hover align-middle dt-responsive nowrap w-100" id="employee-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px">No</th>
                                <th>ID Employee</th>
                                <th>Nama</th>
                                <th>Jabatan</th>
                                <th>Kode Dept</th>
                                <th>Departemen</th>
                                <th>Lokasi</th> 
                                <th>HP</th>
                                <th>Email</th>
                                <th style="width: 80px" class="text-center">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-employee" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white" id="employee-modal-title">Form Karyawan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="form-employee">
                    @csrf
                    <input type="hidden" name="_method" id="form-method" value="POST">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">ID Employee (NIK)</label>
                                <input type="text" name="employee_id" id="emp-employee_id" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nama Lengkap</label>
                                <input type="text" name="name" id="emp-name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Jabatan</label>
                                <input type="text" name="jabatan" id="emp-jabatan" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-primary">Kode Departemen (Otomatis)</label>
                                <input type="text" id="display_division_code" class="form-control bg-light fw-bold text-primary" readonly placeholder="Auto-fill...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Departemen</label>
                                <select name="division_id" id="emp-division_id" class="form-select select2-modal" required>
                                    <option value="">Pilih Departemen</option>
                                    @foreach($divisions as $div)
                                        <option value="{{ $div->id }}" data-code="{{ $div->code }}">{{ $div->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Regional / Lokasi</label>
                                <select name="regional_id" id="emp-regional_id" class="form-select select2-modal">
                                    <option value="">Pilih Regional</option>
                                    @foreach($regionals as $reg)
                                        <option value="{{ $reg->id }}">{{ $reg->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">No. HP</label>
                                <input type="text" name="hp" id="emp-hp" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Email</label>
                                <input type="email" name="email" id="emp-email" class="form-control">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Alamat</label>
                                <textarea name="address" id="emp-address" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary" id="btn-save-employee">Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-import-employee" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Import Data Karyawan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="form-import-employee" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Pilih File Excel (.xlsx / .xls)</label>
                            <input type="file" name="file" class="form-control" accept=".xlsx, .xls" required>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-info text-white">Mulai Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('plugin')
    <script src="{{ asset('libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>

<script>
$(document).ready(function() {
    var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');

    // 1. DATA TABLE
    var table = $("#employee-table").DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('employee.datatable') }}",
            type: "POST",
            data: { _token: CSRF_TOKEN }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false }, 
            { data: 'employee_id', name: 'employee_id' },
            { data: 'name', name: 'name' },
            { data: 'jabatan', name: 'jabatan', defaultContent: '-' },
            { data: 'kode_dept', name: 'kode_dept' }, // Pastikan di controller kolomnya bernama 'kode_dept'
            { data: 'departemen', name: 'departemen' }, 
            { data: 'regional', name: 'regional', defaultContent: '-' },
            { data: 'hp', name: 'hp', defaultContent: '-' },
            { data: 'email', name: 'email', defaultContent: '-' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
        ]
    });

    // 2. LOGIKA OTOMATIS KODE DEPARTEMEN
    $('#emp-division_id').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var divisionCode = selectedOption.data('code'); // Ambil dari atribut data-code
        
        if (divisionCode) {
            $('#display_division_code').val(divisionCode);
        } else {
            $('#display_division_code').val('');
        }
    });

    // Helper: Reset & Mode
    function setupModal(mode = 'edit') {
        const isReadonly = mode === 'view';
        $('#form-employee input, #form-employee textarea, #form-employee select').prop('disabled', isReadonly);
        isReadonly ? $('#btn-save-employee').hide() : $('#btn-save-employee').show();
    }

    // Helper: Fill Form
    function fillForm(data) {
        $('#emp-name').val(data.name);
        $('#emp-employee_id').val(data.employee_id);
        $('#emp-jabatan').val(data.jabatan);
        
        // Pilih Departemen & Trigger Change agar Kode Dept muncul otomatis
        $('#emp-division_id').val(data.division_id).trigger('change');
        
        $('#emp-regional_id').val(data.regional_id).trigger('change');
        $('#emp-hp').val(data.hp);
        $('#emp-email').val(data.email);
        $('#emp-address').val(data.address);
    }

    // Tombol Tambah
    $('#btn-add-employee').on('click', function() {
        $('#form-employee')[0].reset();
        $('.select2-modal').val('').trigger('change');
        $('#display_division_code').val('');
        $('#form-method').val('POST');
        $('#form-employee').attr('action', "{{ route('employee.store') }}");
        $('#employee-modal-title').text('Tambah Karyawan Baru');
        setupModal('edit');
        $('#modal-employee').modal('show');
    });

    // Tombol Edit
    $(document).on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        $('#form-method').val('PUT');
        $('#form-employee').attr('action', "{{ url('employee/update') }}/" + id);
        $.get("{{ url('employee/show') }}/" + id, function(res) {
            if (res.success) {
                fillForm(res.data);
                $('#employee-modal-title').text('Ubah Data Karyawan');
                setupModal('edit');
                $('#modal-employee').modal('show');
            }
        });
    });

    // Tombol View
    $(document).on('click', '.btn-view', function() {
        var id = $(this).data('id');
        $.get("{{ url('employee/show') }}/" + id, function(res) {
            if (res.success) {
                fillForm(res.data);
                $('#employee-modal-title').text('Detail Karyawan');
                setupModal('view');
                $('#modal-employee').modal('show');
            }
        });
    });

    // Submit Ajax
    $('#form-employee').submit(function(e) {
        e.preventDefault();
        // Aktifkan sementara agar data terkirim
        $('#form-employee input, #form-employee select').prop('disabled', false);
        
        $.ajax({
            url: $(this).attr('action'),
            type: "POST",
            data: $(this).serialize(),
            success: function(res) {
                if (res.success) {
                    Swal.fire('Berhasil', res.message, 'success');
                    $('#modal-employee').modal('hide');
                    table.ajax.reload(null, false);
                }
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON.message || 'Error Sistem', 'error');
            }
        });
    });

    // Delete
    $(document).on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: "Hapus data karyawan?",
            text: "Data yang terhapus tidak dapat dikembalikan!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            confirmButtonText: "Ya, Hapus!"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: "{{ url('employee/delete') }}/" + id,
                    data: { _token: CSRF_TOKEN, _method: "DELETE" },
                    success: function(res) {
                        Swal.fire('Terhapus!', res.message, 'success');
                        table.ajax.reload(null, false);
                    }
                });
            }
        });
    });

    // Import Excel
    $('#form-import-employee').submit(function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        $.ajax({
            url: "{{ route('employee.import') }}",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                Swal.fire('Berhasil', res.message, 'success');
                $('#modal-import-employee').modal('hide');
                table.ajax.reload();
            },
            error: function(xhr) {
                Swal.fire('Gagal', 'Terjadi kesalahan saat import data.', 'error');
            }
        });
    });

    $('#btn-download-template').click(function() {
        window.location.href = "{{ route('employee.template') }}";
    });

    $('#btn-refresh').click(function(){ table.ajax.reload(); });
});
</script>
@endsection