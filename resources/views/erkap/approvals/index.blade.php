@extends('layouts.Erkap')

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
    .hover-shadow:hover {
        box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15) !important;
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
                        <i class="mdi mdi-file-document-check me-1"></i> {{ $pageName }}
                    </h5>
                    <a href="{{ route('erkap.index') }}" class="btn btn-secondary">
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

                @php
                    $pendingCount = $pendingGroups->sum(fn ($group) => $group['approvals']->count());
                @endphp

                <ul class="nav nav-pills nav-justified bg-light rounded mb-4">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#waiting-list">
                            <i class="mdi mdi-clock-outline me-1"></i> Menunggu Persetujuan
                            @if($pendingCount > 0)
                            <span class="badge bg-danger ms-1">{{ $pendingCount }}</span>
                            @endif
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#history-list">
                            <i class="mdi mdi-history me-1"></i> Riwayat Approval
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="waiting-list">
                        @forelse($pendingGroups as $group)
                        @php
                            $groupCount = $group['approvals']->count();
                        @endphp
                        <a href="{{ route('erkap.approvals.division', $group['key']) }}" class="text-decoration-none">
                            <div class="card mb-3 border-start border-3 border-primary shadow-sm hover-shadow">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3 d-flex align-items-center justify-content-center rounded-circle bg-soft-info text-primary" style="width: 44px; height: 44px;">
                                                <i class="mdi mdi-domain fs-4"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold">{{ $group['division_label'] }}</h6>
                                                <small class="text-muted">
                                                    {{ $groupCount }} dokumen menunggu persetujuan Anda
                                                </small>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-danger">{{ $groupCount }}</span>
                                            <i class="mdi mdi-chevron-right text-muted"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                        @empty
                        <div class="text-center py-4 text-muted">
                            <i class="mdi mdi-check-circle-outline fs-1 d-block mb-2"></i>
                            Tidak ada dokumen yang menunggu persetujuan.
                        </div>
                        @endforelse
                    </div>

                    <div class="tab-pane fade" id="history-list">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered align-middle w-100">
                                <thead class="table-dark text-center">
                                    <tr>
                                        <th width="50">No</th>
                                        <th>Tipe Dokumen</th>
                                        <th>Dokumen</th>
                                        <th>Level Persetujuan</th>
                                        <th>Status</th>
                                        <th>Waktu</th>
                                        <th width="130">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($historyApprovals as $index => $item)
                                    @php
                                        $docType = \App\Services\ApprovalService::typeFor($item->approvalable);
                                        $docMeta = $docType ? \App\Services\ApprovalService::documentTypes()[$docType] : null;
                                    @endphp
                                    @if($docMeta)
                                    <tr>
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td>{{ $docMeta['label'] }}</td>
                                        <td class="fw-bold">{{ $docMeta['title']($item->approvalable) }}</td>
                                        <td>{{ $item->getLevelLabel() }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-soft-{{ $item->getStatusClass() }} text-{{ $item->getStatusClass() }}">
                                                {{ $item->getStatusLabel() }}
                                            </span>
                                        </td>
                                        <td class="text-center">{{ $item->approved_at ? $item->approved_at->format('d M Y H:i') : '-' }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('erkap.approvals.show', [$docType, $item->approvalable->id]) }}" class="btn btn-sm btn-outline-secondary">
                                                <i class="mdi mdi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endif
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="mdi mdi-information-outline fs-1 d-block mb-2"></i>
                                            Belum ada riwayat approval.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection