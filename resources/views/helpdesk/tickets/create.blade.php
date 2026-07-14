@extends('layouts.Helpdesk')

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">Buat Tiket Baru</h5>
                </div>
            </div>
            <div class="card-body">
                <form id="form-ticket">
                    @csrf
                    <input type="hidden" name="_method" id="form-method" value="POST">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Judul Tiket</label>
                                <input type="text" name="title" id="tc-ticket_title" class="form-control" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Deskripsi</label>
                                <textarea name="description" id="tc-ticket_description" class="form-control" rows="5"></textarea>
                            </div>
                            <div class="row g-3">
                                <div class="col">
                                    <label class="form-label fw-bold">Kategori Tiket</label>
                                    <select class="form-control text-dark select2-init" name="ticket_category_id" id="tc-ticket_category_id" required style="width: 100%;">
                                        @foreach($ticketCategories as $ticketCategory)
                                        <option value="{{ $ticketCategory->id }}">{{ $ticketCategory->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col">
                                    <label class="form-label fw-bold">Prioritas Tiket</label>
                                    <select class="form-control text-dark select2-init" name="ticket_priority_id" id="tc-ticket_priority_id" required style="width: 100%;">
                                        @foreach($ticketPriorities as $ticketPriority)
                                        <option value="{{ $ticketPriority->id }}">{{ $ticketPriority->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col">
                                    <label class="form-label fw-bold">SLA (Service Level Agreement)</label>
                                    <select class="form-control text-dark select2-init" name="sla" id="tc-ticket_sla" required style="width: 100%">
                                        <option value="1">1 Jam</option>
                                        <option value="2">2 Jam</option>
                                        <option value="3">3 Jam</option>
                                        <option value="4">4 Jam</option>
                                        <option value="5">5 Jam</option>
                                    </select>
                                </div>
                                <div class="col">
                                    <label class="form-label fw-bold">Upload Attachment</label>
                                    <div id="attachment-container">
                                        <div class="attachment-row input-group mb-2">
                                            <input type="file" name="attachments[]" class="form-control">
                                            <button type="button" class="btn btn-outline-danger btn-remove-attachment" title="Hapus" style="display:none;">
                                                <i class="mdi mdi-close"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary mt-1" id="btn-add-attachment">
                                        <i class="mdi mdi-plus"></i> Tambah Attachment
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary" id="btn-save-helpdesk.ticket-categories">Simpan Data</button>
                    </div>
                </form>
            </div>
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

        // Tambah attachment
        $('#btn-add-attachment').on('click', function() {
            var $container = $('#attachment-container');
            var $row = $container.find('.attachment-row:first').clone();
            $row.find('input').val('');
            $container.append($row);
            updateRemoveButtons();
        });

        // Hapus attachment
        $(document).on('click', '.btn-remove-attachment', function() {
            $(this).closest('.attachment-row').remove();
            updateRemoveButtons();
        });

        // Update visibility tombol hapus
        function updateRemoveButtons() {
            var count = $('#attachment-container .attachment-row').length;
            if (count > 1) {
                $('.btn-remove-attachment').show();
            } else {
                $('.btn-remove-attachment').hide();
            }
        }

        // Submit Ajax
        $('#form-ticket').submit(function(e) {
            e.preventDefault();
            $('#form-ticket input, #form-ticket select').prop('disabled', false);
            $('.select2-modal').val('').trigger('change');
            $('#form-method').val('POST');
            $('#form-ticket').attr('action', "{{ route('helpdesk.tickets.store') }}");

            var formData = new FormData(this);
            $.ajax({
                url: $(this).attr('action'),
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res.success) {
                        Swal.fire('Berhasil', res.message, 'success');
                        $('#modal-ticket').modal('hide');
                    }
                },
                error: function(xhr) {
                    console.log(xhr.status)
                    Swal.fire('Error', xhr.responseJSON.message || 'Error Sistem', 'error');
                }
            });
        });
    });
</script>
@endsection
