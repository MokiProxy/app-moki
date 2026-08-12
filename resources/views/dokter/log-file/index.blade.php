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
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama file / vendor / nomor dokumen / tanggal / FTP path"
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
                                <th>Vendor</th>
                                <th class="text-center" style="width: 90px">Aksi</th>
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
                                    <i class="mdi mdi-file me-1 text-muted"></i>{{ explode("/", $log->ftp_path)[2] ?? '-' }}
                                    @if($log->extension)
                                    <span class="badge bg-secondary ms-1">{{ strtoupper($log->extension) }}</span>
                                    @endif
                                </td>
                                <td>{{ $log->document_type_name ?? '-' }}</td>
                                <td>{{ $log->vendor_name ?? '-' }}</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-info btn-sm btn-view-log"
                                        data-id="{{ $log->id }}"
                                        data-created="{{ $log->created_at?->format('d-m-Y H:i:s') ?? '-' }}"
                                        data-status="{{ $log->status_label }}"
                                        data-status-class="{{ $log->status_badge_class }}"
                                        data-filename="{{ $log->filename ?? '-' }}"
                                        data-extension="{{ strtoupper($log->extension) }}"
                                        data-doctype="{{ $log->document_type_name ?? '-' }}"
                                        data-docnum="{{ $log->document_number ?? '-' }}"
                                        data-tanggal="{{ $log->tanggal ?? '-' }}"
                                        data-vendor="{{ $log->vendor_name ?? '-' }}"
                                        data-keterangan="{{ $log->keterangan ?? '-' }}"
                                        data-uraian="{{ is_array($log->uraian_decoded) ? htmlspecialchars(json_encode($log->uraian_decoded), ENT_QUOTES, 'UTF-8') : htmlspecialchars($log->uraian ?? '-', ENT_QUOTES, 'UTF-8') }}"
                                        data-ftp="{{ $log->ftp_path ?? '-' }}"
                                        data-size="{{ $log->file_size }}"
                                        data-processing="{{ $log->processing_time_ms }}"
                                        data-message="{{ $log->message ?? '-' }}"
                                        title="Lihat Detail">
                                        <i class="mdi mdi-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
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

<div class="modal fade" id="logDetailModal" tabindex="-1" aria-labelledby="logDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title text-dark fw-bold" id="logDetailModalLabel">
                    <i class="mdi mdi-information-outline text-info me-1"></i> Detail Log File
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-0">Waktu Scan</label>
                        <p class="fw-semibold mb-0" id="detail-created">-</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-0">Status</label>
                        <p class="mb-0" id="detail-status">-</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-0">Nama File</label>
                        <p class="fw-semibold mb-0" id="detail-filename">-</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-0">Jenis Dokumen</label>
                        <p class="mb-0" id="detail-doctype">-</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-0">Nomor Dokumen</label>
                        <p class="mb-0" id="detail-docnum">-</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-0">Tanggal</label>
                        <p class="mb-0" id="detail-tanggal">-</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-0">Vendor</label>
                        <p class="mb-0" id="detail-vendor">-</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-0">Ukuran File</label>
                        <p class="mb-0" id="detail-size">-</p>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small text-muted mb-0">Keterangan</label>
                        <p class="mb-0" id="detail-keterangan">-</p>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small text-muted mb-0">Uraian</label>
                        <div id="detail-uraian">-</div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small text-muted mb-0">Lokasi File Final (FTP Path)</label>
                        <p class="mb-0 text-break" id="detail-ftp">-</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-0">Waktu Proses</label>
                        <p class="mb-0" id="detail-processing">-</p>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small text-muted mb-0">Pesan</label>
                        <p class="mb-0" id="detail-message">-</p>
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

        function formatBytes(bytes) {
            if (!bytes || bytes === 0) return '-';
            var units = ['B', 'KB', 'MB', 'GB'];
            var i = 0;
            var size = parseFloat(bytes);
            while (size >= 1024 && i < units.length - 1) {
                size /= 1024;
                i++;
            }
            return size.toFixed(i === 0 ? 0 : 1) + ' ' + units[i];
        }

        $(document).on('click', '.btn-view-log', function() {
            var $btn = $(this);
            $('#detail-created').text($btn.data('created'));
            $('#detail-status').html('<span class="badge ' + $btn.data('status-class') + '">' + $btn.data('status') + '</span>');
            $('#detail-filename').html('<i class="mdi mdi-file me-1 text-muted"></i>' + $btn.data('filename') + ($btn.data('extension') ? ' <span class="badge bg-secondary ms-1">' + $btn.data('extension') + '</span>' : ''));
            $('#detail-doctype').text($btn.data('doctype'));
            $('#detail-docnum').text($btn.data('docnum'));
            $('#detail-tanggal').text($btn.data('tanggal'));
            $('#detail-vendor').text($btn.data('vendor'));
            $('#detail-size').text(formatBytes($btn.data('size')));
            $('#detail-keterangan').text($btn.data('keterangan'));
            $('#detail-ftp').text($btn.data('ftp'));
            $('#detail-processing').text($btn.data('processing') ? number_format($btn.data('processing')) + ' ms' : '-');
            $('#detail-message').text($btn.data('message'));

            var uraianRaw = $btn.data('uraian');
            var uraianHtml = '-';
            try {
                var uraianData = JSON.parse(uraianRaw);
                if (Array.isArray(uraianData) && uraianData.length > 0) {
                    uraianHtml = '<ul class="mb-0 ps-3">';
                    uraianData.forEach(function(item) {
                        uraianHtml += '<li>' + $('<span>').text(item).html() + '</li>';
                    });
                    uraianHtml += '</ul>';
                } else if (typeof uraianData === 'string' && uraianData !== '') {
                    uraianHtml = $('<span>').text(uraianData).html();
                }
            } catch (e) {
                if (uraianRaw && uraianRaw !== '-') {
                    uraianHtml = $('<span>').text(uraianRaw).html();
                }
            }
            $('#detail-uraian').html(uraianHtml);

            $('#logDetailModal').modal('show');
        });

        function number_format(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }
    });
</script>
@endsection
