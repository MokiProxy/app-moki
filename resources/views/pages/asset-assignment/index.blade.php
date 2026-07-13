@extends('layouts.App')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body border-bottom">
                    <div class="d-flex align-items-center">
                        <h5 class="mb-0 card-title flex-grow-1">Daftar Penugasan Asset</h5>
                        <div class="flex-shrink-0">
                            <button class="btn btn-primary" id="btn-add-modal">
                                <i class="mdi mdi-plus"></i> Penugasan Baru
                            </button>
                            <button class="btn btn-light" onclick="drawTable()">
                                <i class="mdi mdi-refresh"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    {{-- Container Responsive --}}
                    <div class="table-responsive">
                        <table class="table table-striped align-middle nowrap w-100" id="assign-table">
                            <thead>
                                <tr>
                                    <th style="width: 50px">No</th>
                                    <th>NIK</th>
                                    <th>Nama Pegawai</th>
                                    <th>Jabatan</th>
                                    <th>Departemen</th>
                                    <th>Brand</th>
                                    <th>Asset (SN)</th>
                                    <th>Nomor Aset</th>
                                    <th>Lokasi</th>
                                    <th>Tanggal Terima</th>
                                    <th>Kondisi</th>
                                    <th>Spesifikasi</th>
                                    <th>Remarks</th>
                                    <th>Berkas</th>
                                    <th style="width: 120px">Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Form --}}
    <div class="modal fade" id="modal-assign" data-bs-backdrop="static" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-title">Form Penugasan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="form-assign" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="id" id="assign_id">
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold text-primary">1. Pilih Karyawan (NIK)</label>
                                <select name="employee_id" id="employee_id" class="form-control select2-modal" required>
                                    <option value="">-- Pilih NIK Karyawan --</option>
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}" 
                                            data-name="{{ $emp->name }}" 
                                            data-job="{{ $emp->jabatan }}" 
                                            data-dept="{{ $emp->division ? $emp->division->name : '-' }}">
                                            {{ $emp->employee_id }} - {{ $emp->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label small">Nama Pegawai</label>
                                <input type="text" id="display_name" class="form-control bg-light" readonly>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label small">Jabatan</label>
                                <input type="text" id="display_job" class="form-control bg-light" readonly>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label small">Departemen</label>
                                <input type="text" id="display_dept" class="form-control bg-light" readonly>
                            </div>

                            <div class="col-md-12"><hr></div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-primary">2. Pilih Asset</label>
                                <select name="asset_id" id="asset_id" class="form-control select2-modal" required>
                                    <option value="">-- Pilih Brand - SN --</option>
                                    @foreach($assets as $asset)
                                        <option value="{{ $asset->id }}" data-spec="{{ $asset->specification }}">
                                            {{ $asset->brand }} - {{ $asset->serial_number ?? $asset->barcode ?? 'No SN' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nomor Aset (Internal)</label>
                                <input type="text" name="asset_no" id="asset_no" class="form-control" placeholder="Contoh: AST-2024-001">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tanggal Terima</label>
                                <input type="date" class="form-control" name="assignment_date" id="assignment_date" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Lokasi Penempatan</label>
                                <select name="location" id="location" class="form-control">
                                    <option value="">-- Pilih Lokasi --</option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->name }}">{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Kondisi</label>
                                <select name="condition" id="condition" class="form-control">
                                    <option value="baru">Baru</option>
                                    <option value="bekas">Bekas / Bagus</option>
                                </select>
                            </div>

                            <div class="col-md-8 mb-3">
                                <label class="form-label">Upload Berkas (PDF)</label>
                                <input type="file" name="document" id="document" class="form-control" accept=".pdf">
                                <small class="text-danger" id="edit-file-info"></small>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">Spesifikasi Unit</label>
                                <textarea id="specification" class="form-control bg-light" rows="3" readonly></textarea>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Catatan / Remarks</label>
                                <textarea name="remarks" id="remarks" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="form-assign" id="btn-submit" class="btn btn-primary">Simpan Penugasan</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal View PDF --}}
    <div class="modal fade" id="modal-view-pdf" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pratinjau Dokumen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="pdf-container"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <link href="{{ asset('libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('libs/select2/css/select2.min.css') }}" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        .select2-container--open { z-index: 9999999 !important; }
        .bg-light { background-color: #f8f9fa !important; }
        .btn-soft-primary { background-color: rgba(85, 110, 230, 0.1); color: #556ee6; border: none; }
        .btn-soft-info { background-color: rgba(80, 165, 241, 0.1); color: #50a5f1; border: none; }
        .btn-soft-danger { background-color: rgba(244, 106, 106, 0.1); color: #f46a6a; border: none; }
        #pdf-container embed { border: none; width: 100%; height: 75vh; }
        
        /* CSS Tambahan untuk Scroll Horizontal */
        #assign-table thead th, #assign-table tbody td {
            white-space: nowrap; /* Mencegah teks turun ke bawah */
            padding: 10px 15px;
        }
        .table-responsive {
            overflow-x: auto !important;
            padding-bottom: 15px; /* Memberi ruang untuk scrollbar */
        }
    </style>
@endsection

@section('plugin')
    <script src="{{ asset('libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('libs/select2/js/select2.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    var table;
    $(document).ready(function() {
        $.ajaxSetup({ 
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } 
        });

        $('.select2-modal').select2({
            dropdownParent: $('#modal-assign'),
            width: '100%'
        });

        table = $("#assign-table").DataTable({
            processing: true, 
            serverSide: true,
            scrollX: true,        // AKTIFKAN SCROLL HORIZONTAL
            scrollCollapse: true,
            autoWidth: false,     // MATIKAN AUTOWIDTH AGAR KOLOM SESUAI ISI
            ajax: { 
                url: "{{ route('assignment.datatable') }}", 
                type: "POST" 
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'employee_nik', name: 'e.employee_id' },
                { data: 'employee_name', name: 'aa.employee_name' },
                { data: 'job_title', name: 'aa.job_title' },
                { data: 'department_name', name: 'aa.department' },
                { data: 'asset_brand', name: 'a.brand' },
                { data: 'asset_sn', name: 'a.serial_number' },
                { data: 'asset_no', name: 'aa.asset_no' },
                { data: 'location', name: 'aa.location' },
                { data: 'assignment_date', render: v => v ? moment(v).format('DD MMM YYYY') : '-' },
                { 
                    data: 'condition', 
                    render: v => `<span class="badge ${v === 'baru' ? 'bg-success' : 'bg-warning'}">${(v||'').toUpperCase()}</span>` 
                },
                { data: 'specification', name: 'a.specification', defaultContent: '-' },
                { data: 'remarks', name: 'aa.remarks', defaultContent: '-' },
                { 
                    data: 'document_path', 
                    render: function(d) {
                        if (d && d !== '-') {
                            return `<button type="button" class="btn btn-sm btn-info text-white btn-preview-pdf" data-file="${d}">
                                        <i class="mdi mdi-file-pdf-box"></i> Lihat
                                    </button>`;
                        }
                        return '<span class="text-muted small">No File</span>';
                    } 
                },
                { 
                    data: null, 
                    orderable: false, 
                    searchable: false,
                    render: function() {
                        return `
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-soft-primary btn-view" title="Detail"><i class="mdi mdi-eye-outline"></i></button>
                                <button type="button" class="btn btn-sm btn-soft-info btn-edit" title="Edit"><i class="mdi mdi-pencil-outline"></i></button>
                                <button type="button" class="btn btn-sm btn-soft-danger btn-delete" title="Hapus"><i class="mdi mdi-trash-can-outline"></i></button>
                            </div>`;
                    }
                },
            ]
        });

        // Event Listener: Popup PDF
        $('#assign-table').on('click', '.btn-preview-pdf', function() {
            let fileName = $(this).data('file');
            let fileUrl = "{{ asset('storage') }}/" + fileName;
            $('#pdf-container').html(`<embed src="${fileUrl}" type="application/pdf" />`);
            $('#modal-view-pdf').modal('show');
        });

        // Autofill Employee
        $('#employee_id').on('change', function() {
            let opt = $(this).find(':selected');
            $('#display_name').val(opt.data('name') || '');
            $('#display_job').val(opt.data('job') || '');
            $('#display_dept').val(opt.data('dept') || '');
        });

        // Autofill Asset Spec
        $('#asset_id').on('change', function() {
            let spec = $(this).find(':selected').data('spec');
            $('#specification').val(spec || '-');
        });

        // Reset PDF when modal closed
        $('#modal-view-pdf').on('hidden.bs.modal', function () {
            $('#pdf-container').html('');
        });

        $('#btn-add-modal').click(function() {
            $('#form-assign')[0].reset();
            $('#assign_id').val('');
            $('#edit-file-info').text('');
            $('.select2-modal').val(null).trigger('change');
            $('#modal-title').text('Tambah Penugasan Asset');
            enableForm();
            $('#modal-assign').modal('show');
        });

        $('#assign-table').on('click', '.btn-edit', function() {
            let data = getRowData(this);
            $('#modal-title').text('Edit Penugasan Asset');
            $('#edit-file-info').text(data.document_path ? '*Biarkan kosong jika tidak ingin mengubah file' : '');
            enableForm();
            fillModal(data);
            $('#modal-assign').modal('show');
        });

        $('#assign-table').on('click', '.btn-view', function() {
            let data = getRowData(this);
            $('#modal-title').text('Detail Penugasan Asset');
            $('#edit-file-info').text('');
            disableForm();
            fillModal(data);
            $('#modal-assign').modal('show');
        });

        $('#assign-table').on('click', '.btn-delete', function() {
            let data = getRowData(this);
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data penugasan akan dihapus!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f46a6a',
                cancelButtonColor: '#74788d',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('assignment/destroy') }}/" + data.id,
                        type: "DELETE",
                        success: function(res) {
                            if(res.success) {
                                table.ajax.reload(null, false);
                                Swal.fire('Dihapus!', res.message, 'success');
                            }
                        },
                        error: function() {
                            Swal.fire('Error!', 'Gagal menghapus data.', 'error');
                        }
                    });
                }
            });
        });

        $('#form-assign').submit(function(e) {
            e.preventDefault();
            let id = $('#assign_id').val();
            let baseUrl = "{{ url('assignment/update') }}";
            let storeUrl = "{{ route('assignment.store') }}";
            let finalUrl = id ? `${baseUrl}/${id}` : storeUrl;
            
            $.ajax({
                url: finalUrl,
                type: "POST",
                data: new FormData(this),
                processData: false, 
                contentType: false,
                beforeSend: function() { 
                    $('#btn-submit').prop('disabled', true).text('Processing...'); 
                },
                success: function(res) {
                    if (res.success) {
                        $('#modal-assign').modal('hide');
                        table.ajax.reload(null, false);
                        Swal.fire('Berhasil!', res.message, 'success');
                    }
                },
                error: function(xhr) { 
                    let errorMsg = xhr.responseJSON ? xhr.responseJSON.message : 'Gagal menyimpan data.';
                    Swal.fire('Error!', errorMsg, 'error'); 
                },
                complete: function() { 
                    $('#btn-submit').prop('disabled', false).text('Simpan Penugasan'); 
                }
            });
        });

        function getRowData(btn) {
            let tr = $(btn).closest('tr');
            if (tr.hasClass('child')) tr = tr.prev();
            return table.row(tr).data();
        }

        function fillModal(data) {
            $('#assign_id').val(data.id);
            $('#employee_id').val(data.employee_id).trigger('change');
            $('#asset_id').val(data.asset_id).trigger('change');
            
            setTimeout(() => {
                $('#specification').val(data.specification || '-');
            }, 300);

            $('#asset_no').val(data.asset_no);
            $('#assignment_date').val(data.assignment_date);
            $('#location').val(data.location);
            $('#condition').val(data.condition);
            $('#remarks').val(data.remarks);
        }

        function disableForm() {
            $('#form-assign input, #form-assign select, #form-assign textarea').prop('disabled', true);
            $('#btn-submit').hide();
        }

        function enableForm() {
            $('#form-assign input, #form-assign select, #form-assign textarea').prop('disabled', false);
            $('#display_name, #display_job, #display_dept, #specification').prop('readonly', true);
            $('#btn-submit').show();
        }
    });

    function drawTable() { table.ajax.reload(); }
</script>
@endsection