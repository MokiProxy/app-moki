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
                                    <input type="file" name="attachments" id="tc-ticket_attachment" class="form-control" multiple>
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

        // Submit Ajax
        $('#form-ticket').submit(function(e) {
            e.preventDefault();
            // Aktifkan sementara agar data terkirim
            $('#form-ticket input, #form-ticket select').prop('disabled', false);
            $('.select2-modal').val('').trigger('change');
            $('#form-method').val('POST');
            $('#form-ticket').attr('action', "{{ route('helpdesk.tickets.store') }}");
            $.ajax({
                url: $(this).attr('action'),
                type: "POST",
                data: $(this).serialize(),
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
