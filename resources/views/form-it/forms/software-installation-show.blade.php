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
    .bg-soft-info {
        background-color: rgba(52, 195, 235, 0.18);
    }
    .bg-soft-success {
        background-color: rgba(54, 203, 131, 0.18);
    }
    .bg-soft-danger {
        background-color: rgba(240, 101, 101, 0.18);
    }
    .timeline-item {
        position: relative;
        padding-left: 30px;
        padding-bottom: 20px;
    }
    .timeline-item::before {
        content: '';
        position: absolute;
        left: 8px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e9ecef;
    }
    .timeline-item::after {
        content: '';
        position: absolute;
        left: 3px;
        top: 5px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #e9ecef;
        border: 2px solid #dee2e6;
    }
    .timeline-item.active::after {
        background: #556ee6;
        border-color: #556ee6;
    }
    .timeline-item.success::after {
        background: #36c783;
        border-color: #36c783;
    }
    .timeline-item.danger::after {
        background: #f06565;
        border-color: #f06565;
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
                        <i class="mdi mdi-file-document me-1"></i> {{ $pageName }}
                    </h5>
                    <a href="{{ route('form-it.index') }}" class="btn btn-secondary">
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

                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
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
                                        <p class="mb-0">{{ $softwareInstallation->pemohon->name ?? '-' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Jabatan</label>
                                        <p class="mb-0">{{ $softwareInstallation->pemohon->jabatan ?? '-' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Departemen</label>
                                        <p class="mb-0">{{ $softwareInstallation->pemohon->division->name ?? '-' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Area / Job Site</label>
                                        <p class="mb-0">{{ $softwareInstallation->pemohon->regional->name ?? '-' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Tanggal Pengajuan</label>
                                        <p class="mb-0">{{ $softwareInstallation->created_at->format('d F Y') }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Status</label>
                                        <p class="mb-0">
                                            <span class="badge bg-soft-{{ $softwareInstallation->getApprovalStatusClass() }} text-{{ $softwareInstallation->getApprovalStatusClass() }}">
                                                {{ $softwareInstallation->getApprovalStatusLabel() }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="mdi mdi-package-variant me-1"></i> Software yang Diajukan</h6>
                            </div>
                            <div class="card-body">
                                @foreach($softwareInstallation->softwares as $sw)
                                <span class="badge bg-soft-info text-info me-1 mb-1">{{ $sw }}</span>
                                @endforeach
                            </div>
                        </div>

                        @if($softwareInstallation->keterangan)
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="mdi mdi-note-text me-1"></i> Keterangan</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-0">{{ $softwareInstallation->keterangan }}</p>
                            </div>
                        </div>
                        @endif

                        @if($softwareInstallation->status === 'rejected' && $softwareInstallation->rejection_reason)
                        <div class="card mb-3 border-danger">
                            <div class="card-header bg-danger text-white">
                                <h6 class="mb-0"><i class="mdi mdi-close-circle me-1"></i> Alasan Penolakan</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-0">{{ $softwareInstallation->rejection_reason }}</p>
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="mdi mdi-source-branch me-1"></i> Status Approval</h6>
                            </div>
                            <div class="card-body">
                                @foreach($softwareInstallation->approvals->sortBy('level') as $approval)
                                <div class="timeline-item {{ $approval->status === 'approved' ? 'success' : ($approval->status === 'rejected' ? 'danger' : '') }}">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong class="d-block">{{ $approval->getLevelLabel() }}</strong>
                                            <small class="text-muted">{{ $approval->approver->name ?? '-' }}</small>
                                        </div>
                                        <span class="badge bg-soft-{{ $approval->getStatusClass() }} text-{{ $approval->getStatusClass() }}">
                                            {{ $approval->getStatusLabel() }}
                                        </span>
                                    </div>
                                    @if($approval->approved_at)
                                    <small class="text-muted d-block mt-1">
                                        <i class="mdi mdi-clock-outline me-1"></i>{{ $approval->approved_at->format('d M Y H:i') }}
                                    </small>
                                    @endif
                                    @if($approval->notes)
                                    <small class="text-muted d-block mt-1">
                                        <i class="mdi mdi-note-text me-1"></i>{{ $approval->notes }}
                                    </small>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>

                        @if($softwareInstallation->status === 'approved')
                        <div class="card border-success">
                            <div class="card-body text-center">
                                <i class="mdi mdi-check-circle text-success" style="font-size: 3rem;"></i>
                                <h5 class="mt-2 text-success">Pengajuan Disetujui</h5>
                                <p class="text-muted small">Semua approval telah selesai</p>
                                <a href="{{ route('form-it.forms.software-installation.pdf', $softwareInstallation->id) }}" class="btn btn-success" target="_blank">
                                    <i class="mdi mdi-file-pdf me-1"></i> Lihat PDF
                                </a>
                            </div>
                        </div>
                        @elseif($softwareInstallation->status === 'rejected')
                        <div class="card border-danger">
                            <div class="card-body text-center">
                                <i class="mdi mdi-close-circle text-danger" style="font-size: 3rem;"></i>
                                <h5 class="mt-2 text-danger">Pengajuan Ditolak</h5>
                                <p class="text-muted small">Pengajuan telah ditolak oleh approver</p>
                            </div>
                        </div>
                        @else
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <i class="mdi mdi-clock-outline text-warning" style="font-size: 3rem;"></i>
                                <h5 class="mt-2 text-warning">Menunggu Approval</h5>
                                <p class="text-muted small">Pengajuan sedang dalam proses approval</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
