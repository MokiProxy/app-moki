@extends('layouts.Helpdesk')

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">{{ isset($ticket) ? 'Edit Tiket' : 'Buat Tiket Baru' }}</h5>
                </div>
            </div>
            <div class="card-body">
                <form id="form-ticket" data-mode="{{ isset($ticket) ? 'edit' : 'create' }}" 
                      @if(isset($ticket)) data-ticket-id="{{ $ticket->id }}" @endif>
                    @csrf
                    <input type="hidden" name="_method" id="form-method" value="{{ isset($ticket) ? 'PUT' : 'POST' }}">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Judul Tiket</label>
                                <input type="text" name="title" id="tc-ticket_title" class="form-control" required
                                       value="{{ $ticket->title ?? '' }}">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Deskripsi</label>
                                <textarea name="description" id="tc-ticket_description" class="form-control" rows="5">{{ $ticket->description ?? '' }}</textarea>
                            </div>
                            <div class="row g-3">
                                <div class="col">
                                    <label class="form-label fw-bold">Kategori Tiket</label>
                                    <select class="form-control text-dark select2-init" name="ticket_category_id" id="tc-ticket_category_id" required style="width: 100%;">
                                        @foreach($ticketCategories as $ticketCategory)
                                        <option value="{{ $ticketCategory->id }}" {{ (isset($ticket) && $ticket->ticket_category_id == $ticketCategory->id) ? 'selected' : '' }}>
                                            {{ $ticketCategory->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col">
                                    <label class="form-label fw-bold">Prioritas Tiket</label>
                                    <select class="form-control text-dark select2-init" name="ticket_priority_id" id="tc-ticket_priority_id" required style="width: 100%;">
                                        @foreach($ticketPriorities as $ticketPriority)
                                        <option value="{{ $ticketPriority->id }}" {{ (isset($ticket) && $ticket->ticket_priority_id == $ticketPriority->id) ? 'selected' : '' }}>
                                            {{ $ticketPriority->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col">
                                    <label class="form-label fw-bold">SLA (Service Level Agreement)</label>
                                    <select class="form-control text-dark select2-init" name="sla" id="tc-ticket_sla" required style="width: 100%">
                                        @for($i = 1; $i <= 5; $i++)
                                        <option value="{{ $i }}" {{ (isset($ticket) && $ticket->sla == $i) ? 'selected' : '' }}>
                                            {{ $i }} Jam
                                        </option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col">
                                    <label class="form-label fw-bold">Upload Attachment</label>
                                    @if(isset($ticket) && $ticket->attachments->count())
                                    <div id="existing-attachments" class="mb-2">
                                        @foreach($ticket->attachments as $attachment)
                                        <div class="d-flex align-items-center gap-2 mb-1 existing-attachment" data-id="{{ $attachment->id }}">
                                            <i class="mdi mdi-file text-primary"></i>
                                            <a href="{{ url('helpdesk/tickets/attachments/' . $attachment->id . '/download') }}" target="_blank" class="text-decoration-none">
                                                {{ $attachment->file_name }}
                                            </a>
                                            <span class="text-muted small">({{ round($attachment->file_size / 1024, 1) }} KB)</span>
                                        </div>
                                        @endforeach
                                    </div>
                                    @endif
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
                        <button type="submit" class="btn btn-primary" id="btn-save-helpdesk.ticket-categories">
                            {{ isset($ticket) ? 'Update Data' : 'Simpan Data' }}
                        </button>
                        <a href="/helpdesk" class="btn btn-secondary">Kembali</a>
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
        var formMode = $('#form-ticket').data('mode');
        var ticketId = $('#form-ticket').data('ticket-id');

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
            
            var url, method;
            if (formMode === 'edit') {
                url = "{{ url('helpdesk/tickets') }}/" + ticketId + "/update-content";
                method = "POST";
                $('#form-method').val('PUT');
            } else {
                url = "{{ route('helpdesk.tickets.store') }}";
                method = "POST";
                $('#form-method').val('POST');
            }

            var formData = new FormData(this);
            $.ajax({
                url: url,
                type: method,
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res.success) {
                        Swal.fire('Berhasil', res.message, 'success');
                        setTimeout(() => {
                            window.location.replace("/helpdesk")
                        }, 2000);
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
