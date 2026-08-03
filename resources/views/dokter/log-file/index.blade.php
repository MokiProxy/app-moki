@extends('layouts.Dokter')

@section('title', $pageName)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0 card-title text-dark fw-bold">
                    <i class="mdi mdi-history me-1"></i> {{ $pageName }}
                </h5>
                <div class="d-flex gap-1">
                    @can('dokter.log-file.export')
                    <a href="{{ route('dokter.log-file.export', request()->query()) }}" class="btn btn-success" title="Download Excel">
                        <i class="mdi mdi-file-excel me-1"></i> Export Excel
                    </a>
                    @endcan
                    <a href="{{ route('dokter.log-file.index') }}" class="btn btn-light" id="btn-reset" title="Reset Filter">
                        <i class="mdi mdi-filter-remove"></i>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('dokter.log-file.index') }}" class="row g-2 mb-3 align-items-end" id="form-filter">
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Dari Tanggal</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Sampai Tanggal</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Semua Status</option>
                            @foreach($statuses as $status)
                            <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                {{ strtoupper($status) }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Jenis Dokumen</label>
                        <select name="document_type_id" class="form-select form-select-sm">
                            <option value="">Semua Jenis Dokumen</option>
                            @foreach($documentTypes as $documentType)
                            <option value="{{ $documentType->id }}" {{ request('document_type_id') == $documentType->id ? 'selected' : '' }}>
                                {{ $documentType->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Pencarian</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama file / vendor / nomor dokumen / keterangan / FTP path"
                               class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="mdi mdi-filter me-1"></i> Filter
                        </button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 50px">No</th>
                                <th style="width: 160px">Waktu Scan</th>
                                <th class="text-center" style="width: 110px">Status</th>
                                <th>Nama File</th>
                                <th>Jenis Dokumen</th>
                                <th>Nomor Dokumen</th>
                                <th>Vendor</th>
                                <th>Keterangan</th>
                                <th>Uraian</th>
                                <th>Lokasi File Final</th>
                                <th style="width: 130px">Waktu Proses</th>
                                <th>Pesan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $key => $log)
                            <tr>
                                <td class="text-center">{{ $logs->firstItem() + $key }}</td>
                                <td class="text-nowrap">{{ $log->created_at?->format('d-m-Y H:i:s') }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $log->status_badge_class }}">{{ $log->status_label }}</span>
                                </td>
                                <td class="fw-semibold">
                                    <i class="mdi mdi-file me-1 text-muted"></i>{{ $log->filename ?? '-' }}
                                    @if($log->extension)
                                    <span class="badge bg-secondary ms-1">{{ strtoupper($log->extension) }}</span>
                                    @endif
                                </td>
                                <td>{{ $log->document_type_name ?? '-' }}</td>
                                <td>{{ $log->document_number ?? '-' }}</td>
                                <td>{{ $log->vendor_name ?? '-' }}</td>
                                <td class="text-truncate" style="max-width: 200px" title="{{ $log->keterangan }}">
                                    {{ $log->keterangan ?? '-' }}
                                </td>
                                <td class="text-truncate" style="max-width: 200px" title="{{ $log->uraian }}">
                                    {{ $log->uraian ?? '-' }}
                                </td>
                                <td class="text-truncate" style="max-width: 200px" title="{{ $log->ftp_path }}">
                                    {{ $log->ftp_path ?? '-' }}
                                </td>
                                <td class="text-nowrap">
                                    @if($log->processing_time_ms)
                                    {{ number_format($log->processing_time_ms) }} ms
                                    @else
                                    -
                                    @endif
                                </td>
                                <td class="text-truncate" style="max-width: 220px" title="{{ $log->message }}">
                                    {{ $log->message ?? '-' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="13" class="text-center text-muted py-4">
                                    <i class="mdi mdi-file-remove text-secondary" style="font-size: 2.5rem;"></i>
                                    <p class="mt-2 fw-semibold mb-0">Belum ada data log.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                    <p class="text-muted small mb-0">
                        Menampilkan {{ $logs->total() }} data log
                        ({{ $logs->firstItem() ?? 0 }} - {{ $logs->lastItem() ?? 0 }})
                    </p>
                    <div>
                        {{ $logs->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('plugin')
<script>
    $(document).ready(function() {
        $('#btn-reset').click(function() {
            window.location.href = "{{ route('dokter.log-file.index') }}";
        });
    });
</script>
@endsection
