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
                    <h5 class="mb-0 card-title flex-grow-1">Laporan</h5>
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
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('plugin')
<style>
    .tl-item {
        position: relative;
        padding-left: 0;
    }

    .tl-icon {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 15px;
    }

    .tl-content {
        border-left: 2px solid #e9ecef;
        padding-left: 14px;
        padding-bottom: 4px;
    }

    .tl-item:last-child .tl-content {
        border-left-color: transparent;
    }

    .tl-meta {
        font-size: 0.8rem;
        color: #6c757d;
    }

    .tl-diff {
        font-size: 0.78rem;
        margin-top: 2px;
    }

    .tl-diff .old {
        color: #dc3545;
        text-decoration: line-through;
    }

    .tl-diff .new {
        color: #198754;
        font-weight: 600;
    }

    .chat-bubble {
        max-width: 75%;
        padding: 10px 14px;
        border-radius: 16px;
        font-size: 0.9rem;
        word-wrap: break-word;
        margin-bottom: 4px;
        display: inline-block;
        width: fit-content;
    }

    .chat-bubble-right {
        background-color: #0d6efd;
        color: #fff;
        margin-left: auto;
        border-bottom-right-radius: 4px;
    }

    .chat-bubble-left {
        background-color: #fff;
        color: #212529;
        border: 1px solid #dee2e6;
        margin-right: auto;
        border-bottom-left-radius: 4px;
    }

    .chat-avatar {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.8rem;
        color: #fff;
        flex-shrink: 0;
    }

    .chat-meta {
        font-size: 0.72rem;
        color: #6c757d;
        margin-top: 2px;
    }

    .chat-file-link {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 8px;
        border-radius: 8px;
        font-size: 0.8rem;
        text-decoration: none;
        margin-top: 4px;
    }

    .chat-bubble-right .chat-file-link {
        background: rgba(255, 255, 255, 0.15);
        color: #fff;
    }

    .chat-bubble-left .chat-file-link {
        background: #f0f0f0;
        color: #333;
    }

    #chat-container {
        scroll-behavior: smooth;
    }

    .star-btn {
        font-size: 2rem;
        cursor: pointer;
        color: #dee2e6;
        transition: color 0.15s;
    }

    .star-btn:hover,
    .star-btn.active {
        color: #ffc107;
    }
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
            url: "{{ route('helpdesk.reports.datatable') }}",
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

                    // Load chat
                    loadChatMessages(id);
                    startChatPolling(id);

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

        // Tombol Resolved
        $(document).on('click', '.btn-resolved', function() {
            var ticketId = $(this).data('id');

            Swal.fire({
                title: 'Tandai Selesai?',
                text: "Status tiket akan berubah menjadi Resolved.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Selesai!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('helpdesk/tickets/resolve') }}/" + ticketId,
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

        // Tombol Confirm (Atasan)
        var confirmTicketId = null;

        $(document).on('click', '.btn-confirm', function() {
            confirmTicketId = $(this).data('id');
            $('#rating-value').val(0);
            $('#rating-text').text('Pilih rating');
            $('#star-rating .star-btn').removeClass('active').removeClass('mdi-star').addClass('mdi-star-outline');
            $('#modal-confirm').modal('show');
        });

        // Star rating hover
        $(document).on('mouseenter', '#star-rating .star-btn', function() {
            var val = $(this).data('value');
            $('#star-rating .star-btn').each(function() {
                if ($(this).data('value') <= val) {
                    $(this).removeClass('mdi-star-outline').addClass('mdi-star active');
                } else {
                    $(this).removeClass('mdi-star active').addClass('mdi-star-outline');
                }
            });
        });

        $(document).on('mouseleave', '#star-rating', function() {
            var current = parseInt($('#rating-value').val()) || 0;
            $('#star-rating .star-btn').each(function() {
                if ($(this).data('value') <= current) {
                    $(this).removeClass('mdi-star-outline').addClass('mdi-star active');
                } else {
                    $(this).removeClass('mdi-star active').addClass('mdi-star-outline');
                }
            });
        });

        // Star rating click
        $(document).on('click', '#star-rating .star-btn', function() {
            var val = $(this).data('value');
            $('#rating-value').val(val);
            var labels = ['', 'Sangat Kurang', 'Kurang', 'Cukup', 'Baik', 'Sangat Baik'];
            $('#rating-text').text(labels[val]);
        });

        // Submit confirm
        $(document).on('click', '#btn-submit-confirm', function() {
            var rating = parseInt($('#rating-value').val());
            if (!rating || rating < 1) {
                Swal.fire('Peringatan', 'Silakan pilih rating terlebih dahulu', 'warning');
                return;
            }

            var btn = $(this);
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Memproses...');

            $.ajax({
                url: "{{ url('helpdesk/tickets/confirm') }}/" + confirmTicketId,
                type: "POST",
                data: {
                    _token: CSRF_TOKEN,
                    _method: 'PUT',
                    rating: rating,
                },
                success: function(res) {
                    if (res.success) {
                        $('#modal-confirm').modal('hide');
                        Swal.fire('Berhasil', res.message, 'success');
                        table.ajax.reload();
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Error Sistem', 'error');
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="mdi mdi-check-all me-1"></i> Konfirmasi');
                }
            });
        });

        // Tombol Reopen
        $(document).on('click', '.btn-reopen', function() {
            var ticketId = $(this).data('id');

            Swal.fire({
                title: 'Reopen Tiket?',
                text: "Status tiket akan kembali ke OPEN dan teknisi akan di-reset.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#f59e0b',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Reopen!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('helpdesk/tickets/reopen') }}/" + ticketId,
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

        // Tombol Delete
        $(document).on('click', '.btn-delete', function() {
            var ticketId = $(this).data('id');

            Swal.fire({
                title: 'Hapus Tiket?',
                text: "Tiket akan dihapus secara permanen.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('helpdesk/tickets') }}/" + ticketId,
                        type: "POST",
                        data: {
                            _token: CSRF_TOKEN,
                            _method: 'DELETE',
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

        // ==================== CHAT EVENT HANDLERS ====================

        // Stop polling when modal closed
        $('#modal-ticket').on('hidden.bs.modal', function() {
            stopChatPolling();
            resetChatFilePreview();
            $('#chat-input').val('');
        });

        // Send message button
        $(document).on('click', '#btn-send-chat', function() {
            var ticketId = $('#tc-ticket_number').data('id');
            if (ticketId) sendChatMessage(ticketId);
        });

        // Enter key to send
        $(document).on('keypress', '#chat-input', function(e) {
            if (e.which === 13 && !e.shiftKey) {
                e.preventDefault();
                var ticketId = $('#tc-ticket_number').data('id');
                if (ticketId) sendChatMessage(ticketId);
            }
        });

        // File input change
        $(document).on('change', '#chat-file-input', function() {
            var file = this.files[0];
            if (file) {
                chatSelectedFile = file;
                $('#chat-file-name').text(file.name);
                $('#chat-attachment-preview').show();
            }
        });

        // Remove selected file
        $(document).on('click', '#btn-remove-chat-file', function() {
            resetChatFilePreview();
        });

        // ==================== END CHAT EVENT HANDLERS ====================
    });
</script>
@endsection
