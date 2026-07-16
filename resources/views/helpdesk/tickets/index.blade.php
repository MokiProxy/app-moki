@php
$role = session('user_role');
$authUserRoleId = auth()->user()->role_id;
@endphp

@extends('layouts.Helpdesk')

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">{{ in_array($authUserRoleId, [4]) ? "Daftar Tiket Saya" : "Daftar Semua Tiket" }}</h5>
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
                            <p class="m-0 p-1 ps-2 pe-2 bg-success rounded fw-bold text-white" id="tc-ticket_status"></p>
                        </div>
                        <h2 id="tc-ticket_title"></h2>
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
                        <div class="mt-3">
                            <h6 class="fw-bold">
                                File Attachment
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="btn-toggle-folder">
                                    <i class="mdi mdi-folder" id="folder-icon"></i> <span id="folder-label">Buka Folder</span>
                                </button>
                            </h6>
                            <div id="attachment-list" class="ps-3" style="display: none;">
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <h6 class="fw-bold">
                            <i class="mdi mdi-history me-1"></i> Riwayat Aktivitas
                            <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="btn-toggle-timeline">
                                <i class="mdi mdi-clock-outline" id="timeline-icon"></i>
                                <span id="timeline-label">Tampilkan Riwayat</span>
                            </button>
                        </h6>
                        <div id="timeline-container" class="ps-2 mt-2" style="display: none;">
                            <div id="timeline-list"></div>
                        </div>
                    </div>
                    <div class="mt-3" id="assign-teknisi-section">
                        <hr>
                        <h6 class="fw-bold">Assign Teknisi</h6>
                        <div class="row g-2 align-items-center">
                            <div class="col">
                                <select class="form-control" id="select-teknisi">
                                    <option value="">-- Pilih Teknisi --</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-primary" id="btn-assign-teknisi">Pilih</button>
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
<style>
    .tl-item { position: relative; padding-left: 0; }
    .tl-icon {
        width: 34px; height: 34px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; font-size: 15px;
    }
    .tl-content {
        border-left: 2px solid #e9ecef; padding-left: 14px; padding-bottom: 4px;
    }
    .tl-item:last-child .tl-content { border-left-color: transparent; }
    .tl-meta { font-size: 0.8rem; color: #6c757d; }
    .tl-diff { font-size: 0.78rem; margin-top: 2px; }
    .tl-diff .old { color: #dc3545; text-decoration: line-through; }
    .tl-diff .new { color: #198754; font-weight: 600; }
</style>

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

    // Timeline helpers
    var timelineMap = {
        'TICKET_CREATED':     { icon: 'mdi-plus-circle',       color: 'primary',  desc: 'Tiket dibuat' },
        'ASSIGNED_AGENT':     { icon: 'mdi-account-plus',      color: 'info',     desc: 'Ditugaskan ke teknisi' },
        'REASSIGNED_AGENT':   { icon: 'mdi-account-switch',    color: 'warning',  desc: 'Ditugaskan ulang' },
        'STATUS_CHANGED':     { icon: 'mdi-arrow-right-bold-circle', color: 'secondary', desc: 'Status berubah' },
        'PRIORITY_CHANGED':   { icon: 'mdi-flag',              color: 'warning',  desc: 'Prioritas berubah' },
        'CATEGORY_CHANGED':   { icon: 'mdi-tag',               color: 'primary',  desc: 'Kategori berubah' },
        'ATTACHMENT_UPLOADED': { icon: 'mdi-paperclip',        color: 'success',  desc: 'Lampiran diunggah' },
        'COMMENT_ADDED':      { icon: 'mdi-comment-text',      color: 'info',     desc: 'Komentar ditambahkan' },
        'TICKET_RESOLVED':    { icon: 'mdi-check-circle',      color: 'success',  desc: 'Tiket diselesaikan' },
        'TICKET_CLOSED':      { icon: 'mdi-close-circle',      color: 'secondary',desc: 'Tiket ditutup' },
        'TICKET_REOPENED':    { icon: 'mdi-refresh',           color: 'danger',   desc: 'Tiket dibuka kembali' },
    };

    function renderTimelineItem(item) {
        var cfg = timelineMap[item.action] || { icon: 'mdi-circle', color: 'secondary', desc: item.action };
        var userName = item.user ? item.user.name : 'Sistem';
        var time = moment(item.created_at).locale('id').fromNow();
        var timeFull = moment(item.created_at).locale('id').format('DD MMM YYYY, HH:mm');

        var diff = '';
        if (item.old_value && item.new_value && item.action !== 'ATTACHMENT_UPLOADED') {
            diff = '<div class="tl-diff"><span class="old">' + $('<span>').text(item.old_value).html() +
                   '</span> &rarr; <span class="new">' + $('<span>').text(item.new_value).html() + '</span></div>';
        } else if (item.new_value && item.action === 'ATTACHMENT_UPLOADED') {
            diff = '<div class="tl-diff"><span class="new"><i class="mdi mdi-file"></i> ' +
                   $('<span>').text(item.new_value).html() + '</span></div>';
        }

        return '<div class="tl-item d-flex mb-3">' +
            '<div class="tl-icon bg-' + cfg.color + ' me-3"><i class="mdi ' + cfg.icon + ' text-white"></i></div>' +
            '<div class="tl-content flex-grow-1">' +
                '<div class="d-flex justify-content-between align-items-start">' +
                    '<strong class="small">' + cfg.desc + '</strong>' +
                    '<span class="tl-meta ms-2" title="' + timeFull + '">' + time + '</span>' +
                '</div>' +
                '<div class="tl-meta">oleh ' + $('<span>').text(userName).html() + '</div>' +
                diff +
            '</div>' +
        '</div>';
    }

    function loadTimeline(ticketId) {
        var container = $('#timeline-list');
        container.html('<div class="text-center py-2"><div class="spinner-border spinner-border-sm text-primary"></div> Memuat riwayat...</div>');

        $.get("{{ url('helpdesk/tickets') }}/" + ticketId + "/timeline", function(res) {
            if (res.success && res.data.length > 0) {
                var html = '';
                $.each(res.data, function(i, item) {
                    html += renderTimelineItem(item);
                });
                container.html(html);
            } else {
                container.html('<p class="text-muted small fst-italic">Belum ada riwayat aktivitas.</p>');
            }
        }).fail(function() {
            container.html('<p class="text-danger small">Gagal memuat riwayat.</p>');
        });
    }

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
            $('#tc-ticket_status').html(data.status)
            $('#tc-ticket_number').html(data.ticket_number).data('id', data.id);
            $('#tc-ticket_number').attr('data-id', data.id);
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

            // Reset folder
            $('#attachment-list').hide();
            $('#folder-icon').removeClass('mdi-folder-open').addClass('mdi-folder');
            $('#folder-label').text('Buka Folder');

            renderAttachments(data.attachments || []);

            loadTeknisiDropdown(data.assigned_to);
        }

        function loadTeknisiDropdown(selectedId) {
            $.get("{{ route('helpdesk.tickets.teknisi') }}", function(res) {
                if (res.success) {
                    var select = $('#select-teknisi');
                    select.empty().append('<option value="">-- Pilih Teknisi --</option>');
                    $.each(res.data, function(i, teknisi) {
                        var isSelected = teknisi.id == selectedId ? 'selected' : '';
                        select.append('<option value="' + teknisi.id + '" ' + isSelected + '>' + teknisi.name + '</option>');
                    });
                    if (selectedId) {
                        $('#assign-teknisi-section').hide();
                    } else {
                        $('#assign-teknisi-section').show();
                    }
                }
            });
        }

        function renderAttachments(attachments) {
            var container = $('#attachment-list');
            container.empty();

            if (attachments.length === 0) {
                container.html('<p class="text-muted small">Tidak ada file attachment</p>');
                return;
            }

            var html = '<ul class="list-unstyled m-0 p-0">';
            $.each(attachments, function(i, file) {
                var icon = getFileIcon(file.mime_type);
                var url = "{{ url('helpdesk/tickets/attachments') }}/" + file.id + "/download";
                html += '<li class="py-1">';
                html += '  <a href="' + url + '" target="_blank" class="text-decoration-none file-attachment-link">';
                html += icon + ' ' + $('<span>').text(file.file_name).html();
                html += '  </a>';
                html += '  <span class="text-muted small ms-2">(' + formatFileSize(file.file_size) + ')</span>';
                html += '</li>';
            });
            html += '</ul>';
            container.html(html);
        }

        function getFileIcon(mime) {
            if (mime === 'application/pdf') return '<i class="mdi mdi-file-pdf text-danger"></i>';
            if (mime.includes('word') || mime.includes('document')) return '<i class="mdi mdi-file-word text-primary"></i>';
            if (mime.includes('spreadsheet') || mime.includes('excel') || mime.includes('sheet')) return '<i class="mdi mdi-file-excel text-success"></i>';
            if (mime.includes('presentation') || mime.includes('powerpoint') || mime.includes('slides')) return '<i class="mdi mdi-file-powerpoint text-warning"></i>';
            if (mime.includes('image')) return '<i class="mdi mdi-file-image text-info"></i>';
            if (mime.includes('zip') || mime.includes('rar')) return '<i class="mdi mdi-folder-zip"></i>';
            return '<i class="mdi mdi-file"></i>';
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 B';
            var k = 1024;
            var sizes = ['B', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Tombol View
        $(document).on('click', '.btn-view', function() {
            var id = $(this).data('id');
            $.get("{{ url('helpdesk/tickets') }}/" + id, function(res) {
                if (res.success) {
                    fillForm(res.data);

                    // Reset timeline
                    $('#timeline-container').hide();
                    $('#timeline-label').text('Tampilkan Riwayat');
                    loadTimeline(id);

                    $('#modal-ticket').modal('show');
                }
            });
        });

        // Tombol Approve
        $(document).on('click', '.btn-approve', function() {
            var ticketId = $(this).data('id');

            Swal.fire({
                title: 'Approve Tiket?',
                text: "Tiket akan disetujui dan status berubah menjadi In Progress.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Approve!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('helpdesk/tickets/approve') }}/" + ticketId,
                        type: "POST",
                        data: {
                            _token: CSRF_TOKEN,
                            _method: 'PUT',
                        },
                        success: function(res) {
                            if (res.success) {
                                Swal.fire('Berhasil', res.message, 'success');
                                table.ajax.reload();
                            } else {
                                Swal.fire('Gagal', res.message, 'error');
                            }
                        },
                        error: function(xhr) {
                            Swal.fire('Error', xhr.responseJSON?.message || 'Error Sistem', 'error');
                        }
                    });
                }
            });
        });
    
        // Tombol Assign Teknisi
        $(document).on('click', '#btn-assign-teknisi', function() {
            var ticketId = $('#tc-ticket_number').data('id');
            var teknisiId = $('#select-teknisi').val();

            if (!teknisiId) {
                Swal.fire('Peringatan', 'Silakan pilih teknisi terlebih dahulu', 'warning');
                return;
            }

            $.ajax({
                url: "{{ url('helpdesk/tickets/assign') }}/" + ticketId,
                type: "POST",
                data: {
                    _token: CSRF_TOKEN,
                    _method: 'PUT',
                    assigned_to: teknisiId,
                },
                success: function(res) {
                    if (res.success) {
                        Swal.fire('Berhasil', res.message, 'success');
                        $('#modal-ticket').modal('hide');
                        table.ajax.reload();
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Error Sistem', 'error');
                }
            });
        });

        // Tombol toggle folder attachment
        $(document).on('click', '#btn-toggle-folder', function() {
            var list = $('#attachment-list');
            var icon = $('#folder-icon');
            var label = $('#folder-label');
            if (list.is(':visible')) {
                list.slideUp();
                icon.removeClass('mdi-folder-open').addClass('mdi-folder');
                label.text('Buka Folder');
            } else {
                list.slideDown();
                icon.removeClass('mdi-folder').addClass('mdi-folder-open');
                label.text('Tutup Folder');
            }
        });

        // Tombol toggle timeline
        $(document).on('click', '#btn-toggle-timeline', function() {
            var list = $('#timeline-container');
            var label = $('#timeline-label');
            if (list.is(':visible')) {
                list.slideUp();
                label.text('Tampilkan Riwayat');
            } else {
                list.slideDown();
                label.text('Sembunyikan Riwayat');
            }
        });

        $('#btn-refresh').click(function() {
            table.ajax.reload();
        });
    });
</script>
@endsection
