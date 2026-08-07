@extends('layouts.FormIT')

@section('title', $pageName)

@section('css')
<style>
    .badge {
        font-size: 0.7rem;
        padding: 0.4em 0.7em;
    }
    .bg-soft-warning {
        background-color: rgba(241, 180, 76, 0.18);
    }
    .bg-soft-success {
        background-color: rgba(54, 203, 131, 0.18);
    }
    .bg-soft-danger {
        background-color: rgba(240, 101, 101, 0.18);
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">
                        <i class="mdi mdi-laptop me-1"></i> {{ $pageName }}
                    </h5>
                    <a href="{{ route('form-it.forms.fixed-asset.my-submissions') }}" class="btn btn-secondary">
                        <i class="mdi mdi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                <div class="row">
                    <div class="col-md-8">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="mdi mdi-account-circle me-1"></i> Data Pemohon</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Nama Pemohon</label>
                                        <p class="mb-0">{{ $borrowing->pemohon->name ?? '-' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Jabatan</label>
                                        <p class="mb-0">{{ $borrowing->pemohon->jabatan ?? '-' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Departemen</label>
                                        <p class="mb-0">{{ $borrowing->pemohon->division->name ?? '-' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Area / Job Site</label>
                                        <p class="mb-0">{{ $borrowing->pemohon->regional->name ?? '-' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="mdi mdi-laptop me-1"></i> Data Pengajuan</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Tanggal Peminjaman</label>
                                        <p class="mb-0">{{ $borrowing->date_start->format('d F Y') }} - {{ $borrowing->date_end->format('d F Y') }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Tipe Perangkat</label>
                                        <p class="mb-0"><span class="badge bg-soft-info text-info">{{ $borrowing->tipe_perangkat }}</span></p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Tujuan Lokasi</label>
                                        <p class="mb-0">{{ $borrowing->tujuan_lokasi }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Status</label>
                                        <p class="mb-0">
                                            <span class="badge bg-soft-{{ $borrowing->getApprovalStatusClass() }} text-{{ $borrowing->getApprovalStatusClass() }}">
                                                {{ $borrowing->getApprovalStatusLabel() }}
                                            </span>
                                        </p>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold text-muted small">Keperluan</label>
                                        <p class="mb-0">{{ $borrowing->keperluan }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($borrowing->status === 'approved')
                        <div class="card mb-3 border-success">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="mdi mdi-check-circle me-1"></i> Data Penyerahan</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Nama Yang Menyerahkan</label>
                                        <p class="mb-0">{{ $borrowing->penyerahkan_name ?? '-' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Jabatan Yang Menyerahkan</label>
                                        <p class="mb-0">{{ $borrowing->penyerahkan_jabatan ?? '-' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Departemen Yang Menyerahkan</label>
                                        <p class="mb-0">{{ $borrowing->penyerahkan_departemen ?? '-' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Area Yang Menyerahkan</label>
                                        <p class="mb-0">{{ $borrowing->penyerahkan_area ?? '-' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($borrowing->deviceCompletions && $borrowing->deviceCompletions->count() > 0)
                        <div class="card mb-3 border-info">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="mdi mdi-clipboard-check me-1"></i> Kelengkapan Perangkat</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered table-sm mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="5%" class="text-center">No</th>
                                            <th width="35%">Uraian</th>
                                            <th width="10%" class="text-center">Ada</th>
                                            <th width="10%" class="text-center">Tidak Ada</th>
                                            <th width="40%">Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($borrowing->deviceCompletions as $index => $device)
                                        <tr>
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td>{{ $device->uraian }}</td>
                                            <td class="text-center">{{ $device->ada ? '✓' : '-' }}</td>
                                            <td class="text-center">{{ $device->tidak_ada ? '✓' : '-' }}</td>
                                            <td>{{ $device->keterangan ?? '-' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif
                        @endif

                        @if($borrowing->status === 'rejected' && $borrowing->rejection_reason)
                        <div class="card mb-3 border-danger">
                            <div class="card-header bg-danger text-white">
                                <h6 class="mb-0"><i class="mdi mdi-close-circle me-1"></i> Alasan Penolakan</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-0">{{ $borrowing->rejection_reason }}</p>
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="mdi mdi-information-outline me-1"></i> Status Pengajuan</h6>
                            </div>
                            <div class="card-body text-center">
                                @if($borrowing->status === 'approved')
                                <i class="mdi mdi-check-circle text-success" style="font-size: 3rem;"></i>
                                <h5 class="mt-2 text-success">Pengajuan Disetujui</h5>
                                <p class="text-muted small">Peminjaman telah disetujui oleh approver</p>
                                @if($borrowing->approved_at)
                                <p class="text-muted small mb-0">
                                    <i class="mdi mdi-clock-outline me-1"></i>{{ $borrowing->approved_at->format('d M Y H:i') }}
                                </p>
                                @endif
                                @elseif($borrowing->status === 'rejected')
                                <i class="mdi mdi-close-circle text-danger" style="font-size: 3rem;"></i>
                                <h5 class="mt-2 text-danger">Pengajuan Ditolak</h5>
                                <p class="text-muted small">Pengajuan telah ditolak oleh approver</p>
                                @if($borrowing->rejected_at)
                                <p class="text-muted small mb-0">
                                    <i class="mdi mdi-clock-outline me-1"></i>{{ $borrowing->rejected_at->format('d M Y H:i') }}
                                </p>
                                @endif
                                @else
                                <i class="mdi mdi-clock-outline text-warning" style="font-size: 3rem;"></i>
                                <h5 class="mt-2 text-warning">Menunggu Approval</h5>
                                <p class="text-muted small">Pengajuan sedang dalam proses approval</p>
                                @if($borrowing->approver)
                                <p class="text-muted small mb-0">
                                    <i class="mdi mdi-account me-1"></i>Approver: {{ $borrowing->approver->name }}
                                </p>
                                @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
