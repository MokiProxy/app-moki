@extends('layouts.App')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-body border-bottom bg-light">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-sm">
                                <span class="avatar-title rounded-circle bg-soft-primary text-primary font-size-22">
                                    <i class="bx bxs-check-shield"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-0 card-title">Persetujuan BAST (Approval)</h5>
                            <p class="text-muted mb-0 small">Verifikasi penugasan aset oleh Atasan & Admin IT</p>
                        </div>
                        <div class="flex-shrink-0">
                            <img src="{{ asset('assets/images/logo-dark.png') }}" alt="Logo" height="30" onerror="this.src='https://via.placeholder.com/120x30?text=LOGO+AMS'">
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <ul class="nav nav-pills nav-justified bg-light rounded mb-4" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#waiting-list" role="tab">
                                <i class="bx bx-time-five me-1"></i> Menunggu Persetujuan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#history-list" role="tab">
                                <i class="bx bx-history me-1"></i> Riwayat Approval
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane active" id="waiting-list" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered align-middle w-100" id="approve-table">
                                    <thead class="table-dark text-center">
                                        <tr>
                                            <th style="width: 10px">No</th>
                                            <th>No. BAST</th>
                                            <th>ID Employee</th>
                                            <th>Nama</th>
                                            <th>Departemen</th>
                                            <th>Status Atasan</th>
                                            <th>Status Admin</th>
                                            <th style="width:150px">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($pending as $index => $p)
                                        <tr>
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td class="fw-bold text-primary">{{ $p->order_number }}</td>
                                            <td><code>{{ $p->employee->employee_id ?? '-' }}</code></td>
                                            <td>{{ $p->employee->name ?? '-' }}</td>
                                            <td>{{ $p->division->name ?? '-' }}</td>
                                            <td class="text-center"><span class="badge bg-soft-warning text-warning">Pending</span></td>
                                            <td class="text-center"><span class="badge bg-soft-warning text-warning">Pending</span></td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-info btn-view" data-id="{{ $p->id }}" title="Lihat Detail">
                                                    <i class="bx bx-search-alt"></i>
                                                </button>
                                                <button onclick="approveAction('{{ $p->id }}', 'approve')" class="btn btn-sm btn-success" title="Setujui">
                                                    <i class="bx bx-check-double"></i> Approve
                                                </button>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="8" class="text-center py-4 text-muted">Tidak ada transaksi menunggu persetujuan.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                       
@endsection

@section('plugin')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // ACTION DETAIL (VIEW)
        $(document).on('click', '.btn-view', function() {
            var id = $(this).data('id');
            var modal = new bootstrap.Modal(document.getElementById('modal-view'));
            $('#modal-content-detail').html('<div class="text-center p-5"><div class="spinner-border text-primary"></div></div>');
            modal.show();
            
            $.get("{{ url('transaction/detail') }}/" + id, function(res) {
                if(res.success) {
                    let d = res.data;
                    let html = `
                        <div class="row mb-3 border-bottom pb-3">
                            <div class="col-md-6 border-end">
                                <p class="mb-1 text-muted small fw-bold">PENGGUNA</p>
                                <h6>${d.employee.name} (${d.employee.employee_id})</h6>
                                <p class="mb-0 small text-muted">${d.employee.jabatan || '-'}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1 text-muted small fw-bold">NO. BAST</p>
                                <h6 class="text-primary">${d.order_number}</h6>
                                <p class="mb-0 small text-muted">Dept: ${d.division ? d.division.name : '-'}</p>
                            </div>
                        </div>
                        <h6 class="fw-bold mb-2">Daftar Asset yang Diserahkan:</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light small text-center">
                                    <tr>
                                        <th>UID Asset</th>
                                        <th>Item</th>
                                        <th>SN</th>
                                        <th>Kondisi</th>
                                    </tr>
                                </thead>
                                <tbody class="small">
                                    ${d.details.map(item => `
                                        <tr>
                                            <td class="text-center"><code>${item.new_uid || item.asset.uid}</code></td>
                                            <td><strong>${item.asset.brand}</strong><br>${item.asset.specification || '-'}</td>
                                            <td>${item.asset.serial_number}</td>
                                            <td class="text-center">${item.asset.condition == 1 ? 'Baru' : 'Seken'}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>`;
                    $('#modal-content-detail').html(html);
                }
            });
        });

        // ACTION APPROVE/REJECT
        function approveAction(id, action) {
            Swal.fire({
                title: 'Konfirmasi Persetujuan',
                text: "Apakah Anda yakin ingin menyetujui BAST ini?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#34c38f',
                cancelButtonColor: '#74788d',
                confirmButtonText: 'Ya, Approve!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Memproses...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                    
                    $.ajax({
                        url: "{{ url('settings/approve/action') }}/" + id,
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            action: action
                        },
                        success: function(res) {
                            Swal.fire('Berhasil!', res.message, 'success').then(() => {
                                location.reload();
                            });
                        },
                        error: function() {
                            Swal.fire('Error', 'Gagal memproses persetujuan.', 'error');
                        }
                    });
                }
            });
        }
    </script>

    <style>
        .badge { font-size: 0.7rem; padding: 0.4em 0.7em; }
        .bg-soft-warning { background-color: rgba(241, 180, 76, 0.18); }
        .nav-pills .nav-link.active { background-color: #556ee6; }
    </style>
@endsection