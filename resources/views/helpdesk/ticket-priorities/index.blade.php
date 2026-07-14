@extends('layouts.Helpdesk')

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">Daftar Prioritas Tiket</h5>
                    <div class="flex-shrink-0 d-flex gap-1">
                        <button class="btn btn-primary" id="btn-add-ticket-priority">
                            <i class="mdi mdi-plus me-1"></i> Tambah Prioritas Tiket
                        </button>
                        <a href="#!" class="btn btn-light" id="btn-refresh"><i class="mdi mdi-refresh"></i></a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-hover align-middle dt-responsive nowrap w-100" id="ticket-priority-table">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px">No</th>
                            <th>Nama</th>
                            <th>Level</th>
                            <th>Color</th>
                            <th style="width: 80px" class="text-center">Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-ticket-priority" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white" id="ticket-priority-title">Form Prioritas Tiket</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-ticket-priority">
                @csrf
                <input type="hidden" name="_method" id="form-method" value="POST">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col">
                            <label class="form-label fw-bold">Nama Prioritas</label>
                            <input type="text" name="name" id="tc-ticket_priority_name" class="form-control" required>
                        </div>
                        <div class="col">
                            <label class="form-label fw-bold">Level</label>
                            <input type="number" name="level" id="tc-ticket_priority_level" class="form-control" required>
                        </div>
                        <div class="col-2">
                            <label class="form-label fw-bold">Color</label>
                            <input type="color" name="color" id="tc-ticket_priority_color" class="form-control"  required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary" id="btn-save-helpdesk.ticket-priorities">Simpan Data</button>
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
    var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
    // 1. DATA TABLE
    var table = $("#ticket-priority-table").DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('helpdesk.ticket-priorities.datatable') }}",
            type: "POST",
            data: {
                _token: CSRF_TOKEN
            }
        },
        columns: [{
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },
            {
                data: 'name',
                name: 'name'
            },
            {
                data: 'level',
                name: 'level',
                defaultContent: '-'
            },
            {
                data: 'color',
                name: 'color',
                defaultContent: '-'
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
                className: 'text-center'
            }
        ]
    });

    $(document).ready(function() {
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');

        // Helper: Reset & Mode
        function setupModal(mode = 'edit') {
            const isReadonly = mode === 'view';
            $('#form-ticket-priority input, #form-ticket-priority textarea, #form-ticket-priority select').prop('disabled', isReadonly);
            isReadonly ? $('#btn-save-helpdesk.ticket-priorities').hide() : $('#btn-save-helpdesk.ticket-priorities').show();
        }

        // Helper: Fill Form
        function fillForm(data) {
            $('#tc-ticket_priority_name').val(data.name);
            $('#tc-ticket_priority_level').val(data.level);
            $('#tc-ticket_priority_color').val(data.color);
        }

        // Tombol Tambah
        $('#btn-add-ticket-priority').on('click', function() {
            $('#form-ticket-priority')[0].reset();
            $('.select2-modal').val('').trigger('change');
            $('#form-method').val('POST');
            $('#form-ticket-priority').attr('action', "{{ route('helpdesk.ticket-priorities.store') }}");
            $('#ticket-priority-title').text('Tambah Prioritas Tiket Baru');
            setupModal('edit');
            $('#modal-ticket-priority').modal('show');
        });

        // Tombol Edit
        $(document).on('click', '.btn-edit', function() {
            console.log("test");

            var id = $(this).data('id');
            $('#form-method').val('PUT');
            $('#form-ticket-priority').attr('action', "{{ url('helpdesk/ticket-priorities') }}/" + id);
            $.get("{{ url('helpdesk/ticket-priorities') }}/" + id, function(res) {
                if (res.success) {
                    fillForm(res.data);
                    $('#ticket-priority-title').text('Ubah Data Prioritas Tiket');
                    setupModal('edit');
                    $('#modal-ticket-priority').modal('show');
                }
            });
        });

        // Submit Ajax
        $('#form-ticket-priority').submit(function(e) {
            e.preventDefault();
            // Aktifkan sementara agar data terkirim
            $('#form-ticket-priority input, #form-ticket-priority select').prop('disabled', false);

            $.ajax({
                url: $(this).attr('action'),
                type: "POST",
                data: $(this).serialize(),
                success: function(res) {
                    if (res.success) {
                        Swal.fire('Berhasil', res.message, 'success');
                        $('#modal-ticket-priority').modal('hide');
                        table.ajax.reload(null, false);
                    }
                },
                error: function(xhr) {
                    console.log(xhr.status)
                    Swal.fire('Error', xhr.responseJSON.message || 'Error Sistem', 'error');
                }
            });
        });

        // Delete
        $(document).on('click', '.btn-delete', function() {
            var id = $(this).data('id');
            Swal.fire({
                title: "Hapus data prioritas tiket?",
                text: "Data yang terhapus tidak dapat dikembalikan!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                confirmButtonText: "Ya, Hapus!"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "DELETE",
                        url: "{{ url('helpdesk/ticket-priorities') }}/" + id,
                        data: {
                            _token: CSRF_TOKEN,
                            _method: "DELETE"
                        },
                        success: function(res) {
                            Swal.fire('Terhapus!', res.message, 'success');
                            table.ajax.reload(null, false);
                        }
                    });
                }
            });
        });

        $('#btn-refresh').click(function() {
            table.ajax.reload();
        });
    });
</script>
@endsection
