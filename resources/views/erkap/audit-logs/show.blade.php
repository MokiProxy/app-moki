@extends('layouts.Erkap')

@section('title', $pageName)

@section('css')
<style>
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
    .bg-soft-secondary {
        background-color: rgba(108, 117, 125, 0.15);
    }
    .code-block {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 0.25rem;
        padding: 0.5rem;
        font-size: 0.75rem;
        white-space: pre-wrap;
        word-break: break-all;
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title text-dark fw-bold">
                    <i class="mdi mdi-history me-1"></i> {{ $pageName }}
                </h5>
                <a href="{{ route('erkap.audit-logs.index') }}" class="btn btn-secondary">
                    <i class="mdi mdi-arrow-left me-1"></i> Kembali
                </a>
            </div>
            <div class="card-body">
                <div class="card border mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <small class="text-muted d-block">Waktu Kejadian</small>
                                <span class="fw-bold">{{ $log->created_at->format('d M Y H:i:s') }}</span>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">User</small>
                                <span class="fw-bold">{{ $log->user?->name ?? 'System / CLI' }}</span>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">IP &amp; Browser</small>
                                <span class="fw-bold">{{ $log->ip_address ?? '-' }}</span>
                                @if($log->user_agent)
                                <small class="d-block text-muted text-truncate" style="max-width: 300px;">{{ $log->user_agent }}</small>
                                @endif
                            </div>
                            <div class="col-md-4 mt-3">
                                <small class="text-muted d-block">Tipe Dokumen</small>
                                <span class="fw-bold">{{ \App\Models\Erkap\AuditLog::typeLabel($log->auditable_type) }}</span>
                            </div>
                            <div class="col-md-4 mt-3">
                                <small class="text-muted d-block">ID Record</small>
                                <span class="fw-bold">#{{ $log->auditable_id }}</span>
                            </div>
                            <div class="col-md-4 mt-3">
                                <small class="text-muted d-block">Aksi</small>
                                <span class="badge bg-soft-{{ $log->actionClass() }} text-{{ $log->actionClass() }} fs-6">
                                    {{ $log->actionLabel() }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0 fw-bold">Perubahan Data</h6>
                </div>

                @if(count($diff) > 0)
                <div class="table-responsive">
                    <table class="table table-bordered align-middle w-100">
                        <thead class="table-dark text-center">
                            <tr>
                                <th width="180">Field</th>
                                <th>Nilai Lama</th>
                                <th>Nilai Baru</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($diff as $row)
                            <tr>
                                <td class="fw-bold">{{ $row['field'] }}</td>
                                <td>{{ $row['old'] }}</td>
                                <td>{{ $row['new'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4 text-muted">
                    <i class="mdi mdi-information-outline fs-1 d-block mb-2"></i>
                    Tidak ada perubahan data terperinci untuk log ini (mis. aktivitas delete tanpa data lama).
                </div>
                @endif

                <div class="row mt-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block mb-1">Data Lama (JSON)</small>
                        <div class="code-block">{{ !empty($log->old_values) ? json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block mb-1">Data Baru (JSON)</small>
                        <div class="code-block">{{ !empty($log->new_values) ? json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection