@extends('layouts.Erkap')

@section('title', $pageName)

@section('css')
<style>
    .text-dark {
        color: #000000 !important;
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
    .bg-soft-secondary {
        background-color: rgba(108, 117, 125, 0.15);
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
                <a href="{{ route('erkap.index') }}" class="btn btn-secondary">
                    <i class="mdi mdi-arrow-left me-1"></i> Kembali
                </a>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('erkap.audit-logs.index') }}" class="row g-2 align-items-end mb-3">
                    <div class="col-md-3">
                        <label class="form-label mb-1">Tipe Dokumen</label>
                        <select name="type" class="form-select form-select-sm">
                            <option value="">Semua Tipe</option>
                            @foreach($types as $value => $label)
                            <option value="{{ $value }}" @if(($filters['type'] ?? '') === $value) selected @endif>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1">ID Record</label>
                        <input type="number" name="id" class="form-control form-control-sm" value="{{ $filters['id'] ?? '' }}" placeholder="cth. 12">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1">Aksi</label>
                        <select name="action" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            @foreach($actions as $value => $label)
                            <option value="{{ $value }}" @if(($filters['action'] ?? '') === $value) selected @endif>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1">User</label>
                        <select name="user_id" class="form-select form-select-sm">
                            <option value="">Semua User</option>
                            @foreach($users as $id => $name)
                            <option value="{{ $id }}" @if(($filters['user_id'] ?? '') == $id) selected @endif>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1">Tanggal</label>
                        <input type="date" name="date" class="form-control form-control-sm" value="{{ $filters['date'] ?? '' }}">
                    </div>
                    <div class="col-md-1 d-grid">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="mdi mdi-magnify"></i>
                        </button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100">
                        <thead class="table-dark text-center">
                            <tr>
                                <th width="50">No</th>
                                <th>Waktu</th>
                                <th>User</th>
                                <th>Tipe Dokumen</th>
                                <th>ID Record</th>
                                <th>Keterangan</th>
                                <th>Aksi</th>
                                <th width="80">Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $key => $log)
                            <tr>
                                <td class="text-center">{{ $logs->firstItem() + $key }}</td>
                                <td>{{ $log->created_at->format('d M Y H:i') }}</td>
                                <td>{{ $log->user?->name ?? 'System / CLI' }}</td>
                                <td>{{ \App\Models\Erkap\AuditLog::typeLabel($log->auditable_type) }}</td>
                                <td class="text-center">#{{ $log->auditable_id }}</td>
                                <td class="text-center">
                                    <span class="badge bg-soft-{{ $log->actionClass() }} text-{{ $log->actionClass() }}">
                                        {{ $log->actionLabel() }}
                                    </span>
                                </td>
                                <td class="text-truncate" style="max-width: 260px;">
                                    {{ $log->new_values ? implode(', ', array_slice(array_keys($log->new_values), 0, 3)) : '-' }}...
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('erkap.audit-logs.show', $log->id) }}" class="btn btn-sm btn-outline-secondary" title="Detail">
                                        <i class="mdi mdi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="mdi mdi-history fs-1 d-block mb-2"></i>
                                    Belum ada log audit.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    {{ $logs->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection