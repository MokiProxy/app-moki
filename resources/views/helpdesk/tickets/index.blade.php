@extends('layouts.Helpdesk')

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">Daftar Semua Tiket</h5>
                    <div class="flex-shrink-0 d-flex gap-1">
                        <a href="#!" class="btn btn-light" id="btn-refresh"><i class="mdi mdi-refresh"></i></a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-hover align-middle dt-responsive nowrap w-100" id="ticket-priority-table">
                    <thead class="table-light">
                        <tr>
                            <th>Nomor Tiket</th>
                            <th>Pemohon</th>
                            <th>Keluhan</th>
                            <th>Teknisi</th>
                            <th>Kategori</th>
                            <th>Prioritas</th>
                            <th>Batas Waktu</th>
                            <th>Status</th>
                            <th style="width: 80px" class="text-center">Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-ticket" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white" id="employee-modal-title">Detail Tiket</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-employee">
                @csrf
                <input type="hidden" name="_method" id="form-method" value="POST">
                <div class="modal-body p-4">
                    <div>
                        <div class="d-flex justify-content-between">
                            <p class="m-0 p-0" id="tc-ticket_number"></p>
                            <p class="m-0 p-1 ps-2 pe-2 bg-success rounded fw-bold text-white" id="tc-ticket_status">OPEN</p>
                        </div>
                        <h3 id="tc-ticket_title"></h3>
                        <div class="d-flex align-items-center gap-2 mt-3">
                            <div id="tc-ticket_category" class="bg-primary m-0 p-1 ps-2 pe-2 fw-bold text-white rounded"></div>
                            <div class="bg-primary m-0 p-1 ps-2 pe-2 fw-bold text-white rounded">
                                <p id="tc-ticket_requester" class="m-0 p-0"></p>
                            </div>
                            <div class="m-0 p-1 ps-2 pe-2 fw-bold text-white rounded" id="tc-ticket_assignedto_color">
                                <p id="tc-ticket_assignedto" class="m-0 p-0"></p>
                            </div>
                            <div class="m-0 p-1 ps-2 pe-2 fw-bold text-white rounded" id="tc-ticket_priority_color">
                                <p id="tc-ticket_priority" class="m-0 p-0"></p>
                            </div>
                        </div>
                        <textarea name="description" id="tc-ticket_description" class="form-control mt-2" rows="5" disabled></textarea>
                        <div class="row g-3">
                            <div class="row g-3">
                                <div class="col">
                                    <label class="form-label fw-bold">SLA</label>
                                    <input type="text" name="title" id="tc-ticket_sla" class="form-control" disabled>
                                </div>
                                <div class="col">
                                    <label class="form-label fw-bold">Batas Waktu</label>
                                    <input type="text" name="title" id="tc-ticket_due_time" class="form-control" disabled>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
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
            url: "{{ route('helpdesk.tickets.datatable') }}",
            type: "POST",
            data: {
                _token: CSRF_TOKEN
            },
        },
        columns: [{
                data: 'ticket_number',
                name: 'ticket_number'
            },
            {
                data: 'requester_name',
                name: 'requester_name',
                defaultContent: '-'
            },
            {
                data: 'title',
                name: 'title',
            },
            {
                data: 'assigned_to_name',
                name: 'assigned_to_name',
                defaultContent: 'Belum Ditugaskan'
            },
            {
                data: 'ticket_category_name',
                name: 'ticket_category_name',
            },
            {
                data: 'ticket_priority_name',
                name: 'ticket_priority_name',
            },
            {
                data: 'due_time',
                name: 'due_time',
            },
            {
                data: 'status',
                name: 'status',
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
            $('#tc-ticket_number').html(data.ticket_number);
            $('#tc-ticket_title').html(data.title);
            $('#tc-ticket_description').html(data.description);
            $('#tc-ticket_category').html(data.ticket_category.name);
            $('#tc-ticket_priority').html("Prioritas: " + data.ticket_priority.name);
            $('#tc-ticket_priority_color').css('background-color', data.ticket_priority.color);
            $('#tc-ticket_requester').html("Pemohon: " + data.requester.employee.name + " - " + data.requester.employee.division.name);
            $('#tc-ticket_assignedto').html(data.assigned_to?.name ? "Teknisi: " + data.assigned_to?.name : "Teknisi Belum Ditugaskan");
            $('#tc-ticket_assignedto_color').css('background-color', data.assigned_to?.name ? "green" : "red");
            $('#tc-ticket_sla').val(data.sla);
            $('#tc-ticket_due_time').val(data.due_time);
        }

        // Tombol View
        $(document).on('click', '.btn-view', function() {
            var id = $(this).data('id');
            $.get("{{ url('helpdesk/tickets') }}/" + id, function(res) {
                if (res.success) {
                    console.log(res.data)
                    fillForm(res.data)
                    $('#modal-ticket').modal('show');
                }
            });
        });

        $('#btn-refresh').click(function() {
            table.ajax.reload();
        });
    });
</script>
@endsection
