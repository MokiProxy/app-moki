@extends('layouts.Dokter')

@section('title', $pageName)

@php
function formatBytes($bytes, $precision = 2)
{
$units = ['B', 'KB', 'MB', 'GB', 'TB'];
$bytes = max($bytes, 0);
$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
$pow = min($pow, count($units) - 1);
$bytes /= pow(1024, $pow);
return round($bytes, $precision) . ' ' . $units[$pow];
}
@endphp

@section('css')
<style>
    .folder-card {
        cursor: pointer;
        transition: all 0.2s ease;
        border: 1px solid #e9ecef;
    }

    .folder-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.12);
        border-color: #f6c23e;
    }

    .folder-icon {
        font-size: 2.8rem;
        line-height: 1;
    }

    .file-icon-table {
        font-size: 1.2rem;
        line-height: 1;
    }

    .breadcrumb-item+.breadcrumb-item::before {
        content: "\F0142";
        font-family: "Material Design Icons";
    }

    #pdfViewerModal .modal-dialog {
        max-width: 95%;
        height: 95vh;
    }

    #pdfViewerModal .modal-content {
        height: 100%;
    }

    #pdfViewerModal .modal-body {
        padding: 0;
        overflow: hidden;
    }

    #pdfViewerModal #pdfjsContainer {
        width: 100%;
        height: 100%;
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title text-dark fw-bold">
                    @isset($breadcrumbs)
                    <i class="mdi mdi-file-find me-1"></i>
                    @else
                    <i class="mdi mdi-folder-network me-1"></i>
                    @endisset
                    {{ $pageName }}
                </h5>
                <a href="#!" class="btn btn-light" id="btn-refresh" title="Refresh">
                    <i class="mdi mdi-refresh"></i>
                </a>
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

                @isset($breadcrumbs)
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb bg-light p-2 rounded mb-0">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dokter.file-managements.index') }}">
                                <i class="mdi mdi-home"></i>
                            </a>
                        </li>
                        @foreach($breadcrumbs as $crumb)
                        <li class="breadcrumb-item {{ $loop->last ? 'active fw-bold' : '' }}">
                            @if($loop->last)
                            {{ $crumb['label'] }}
                            @else
                            <a href="{{ route('dokter.file-managements.index', ['path' => $crumb['path']]) }}">
                                {{ $crumb['label'] }}
                            </a>
                            @endif
                        </li>
                        @endforeach
                    </ol>
                </nav>
                @endisset

                @if(count($directories) > 0)
                <h6 class="text-muted text-uppercase fw-bold mb-3">
                    <i class="mdi mdi-folder-outline me-1"></i> Folder
                    <span class="badge bg-secondary ms-1">{{ count($directories) }}</span>
                </h6>
                <div class="row">
                    @foreach($directories as $dir)
                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                        <a href="{{ route('dokter.file-managements.index', ['path' => $dir['path']]) }}" class="text-decoration-none">
                            <div class="card folder-card h-100">
                                <div class="card-body text-center py-3">
                                    <div class="folder-icon text-warning mb-2">
                                        <i class="mdi mdi-folder"></i>
                                    </div>
                                    <h6 class="mb-1 text-dark text-truncate fw-semibold">{{ $dir['name'] }}</h6>
                                </div>
                            </div>
                        </a>
                    </div>
                    @endforeach
                </div>
                @elseif(isset($breadcrumbs))
                <div class="text-center text-muted py-4">
                    <i class="mdi mdi-folder-remove text-secondary" style="font-size: 3.5rem;"></i>
                    <p class="mt-2 fw-semibold">Tidak ada folder</p>
                </div>
                @endif

                @isset($files)
                @if(count($files) > 0)
                @php
                $validatedCount = collect($files)->where('is_validated', true)->count();
                $totalCount = count($files);
                @endphp
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-muted text-uppercase fw-bold mb-0">
                        <i class="mdi mdi-file-outline me-1"></i> File
                        <span class="badge bg-secondary ms-1">{{ $totalCount }}</span>
                    </h6>
                    <small class="text-muted">
                        <i class="mdi mdi-check-circle text-success me-1"></i>
                        {{ $validatedCount }}/{{ $totalCount }} divalidasi
                    </small>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100 mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 50px">No</th>
                                @if(auth()->user()->hasPermissionTo('dokter.file-managements.validate'))
                                <th class="text-center" style="width: 60px">
                                    <input type="checkbox" id="checkAll" class="form-check-input" title="Validated Semua">
                                </th>
                                @endif
                                <th>Nama File</th>
                                <th style="width: 100px">Ukuran</th>
                                <th style="width: 70px">Tipe</th>
                                @if(auth()->user()->hasPermissionTo('dokter.file-managements.validate'))
                                <th class="text-center" style="width: 110px">Validated</th>
                                <th style="width: 160px">Divalidasi Oleh</th>
                                @endif
                                <th style="width: 200px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($files as $key => $file)
                            <tr>
                                <td class="text-center">{{ $key + 1 }}</td>
                                @if(auth()->user()->hasPermissionTo('dokter.file-managements.validate'))
                                <td class="text-center">
                                    <input type="checkbox"
                                        class="form-check-input file-validate-checkbox"
                                        data-path="{{ $file['path'] }}"
                                        data-name="{{ $file['name'] }}"
                                        data-folder="{{ $path ?? '' }}"
                                        {{ $file['is_validated'] ? 'checked' : '' }}
                                        title="Validated">
                                </td>
                                @endif
                                <td>
                                    @php
                                    $icon = 'mdi-file';
                                    $iconColor = 'text-info';
                                    if ($file['extension'] === 'pdf') {
                                    $icon = 'mdi-file-pdf-box';
                                    $iconColor = 'text-danger';
                                    } elseif (in_array($file['extension'], ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'])) {
                                    $icon = 'mdi-file-image';
                                    $iconColor = 'text-success';
                                    } elseif (in_array($file['extension'], ['doc', 'docx'])) {
                                    $icon = 'mdi-file-word';
                                    $iconColor = 'text-primary';
                                    } elseif (in_array($file['extension'], ['xls', 'xlsx', 'csv'])) {
                                    $icon = 'mdi-file-excel';
                                    $iconColor = 'text-success';
                                    } elseif (in_array($file['extension'], ['zip', 'rar', '7z', 'tar', 'gz'])) {
                                    $icon = 'mdi-file-archive';
                                    $iconColor = 'text-secondary';
                                    }
                                    @endphp
                                    <i class="mdi {{ $icon }} {{ $iconColor }} file-icon-table me-1"></i>
                                    {{ $file['name'] }}
                                </td>
                                <td class="text-nowrap">{{ formatBytes($file['size']) }}</td>
                                <td><span class="badge bg-secondary">{{ strtoupper($file['extension']) }}</span></td>
                                @if(auth()->user()->hasPermissionTo('dokter.file-managements.validate'))
                                <td class="text-center validation-status">
                                    @if($file['is_validated'])
                                    <span class="badge bg-success"><i class="mdi mdi-check-circle me-1"></i> Validated</span>
                                    @else
                                    <span class="badge bg-secondary">Belum</span>
                                    @endif
                                </td>
                                <td class="text-nowrap validated-info">
                                    @if($file['validated_by'])
                                    <small class="text-muted">
                                        {{ $file['validated_by'] }}<br>
                                        {{ $file['validated_at']?->format('d M Y H:i') }}
                                    </small>
                                    @else
                                    <small class="text-muted">-</small>
                                    @endif
                                </td>
                                @endif
                                <td class="text-center">
                                    @if($file['extension'] === 'pdf')
                                    <button type="button" class="btn btn-info btn-sm btn-view-pdf"
                                        data-path="{{ $file['path'] }}"
                                        data-filename="{{ $file['name'] }}"
                                        title="Lihat PDF">
                                        <i class="mdi mdi-eye"></i> Lihat
                                    </button>
                                    @endif
                                    <a href="{{ route('dokter.file-managements.download', ['path' => $file['path']]) }}" class="btn btn-success btn-sm" title="Download">
                                        <i class="mdi mdi-download"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center text-muted py-4">
                    <i class="mdi mdi-file-remove text-secondary" style="font-size: 3.5rem;"></i>
                    <p class="mt-2 fw-semibold">Tidak ada file</p>
                </div>
                @endif
                @endisset

                @if(!isset($breadcrumbs) && count($directories) === 0)
                <div class="text-center text-muted py-5">
                    <i class="mdi mdi-folder-off text-secondary" style="font-size: 4rem;"></i>
                    <h5 class="mt-3 fw-bold">Belum Ada Folder</h5>
                    <p class="mb-0">Belum ada folder yang tersedia di penyimpanan FTP.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="pdfViewerModal" tabindex="-1" aria-labelledby="pdfViewerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title text-dark fw-bold" id="pdfViewerModalLabel">
                    <i class="mdi mdi-file-pdf-box text-danger me-1"></i>
                    <span id="pdfModalFileName"></span>
                </h5>
                <div class="d-flex gap-1">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body">
                <div id="pdfjsContainer" class="pdfjs-viewer-container"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('plugin')
@include('components.pdf-viewer')
<script>
    $(document).ready(function() {
        $('#btn-refresh').click(function(e) {
            e.preventDefault();
            location.reload();
        });

        $(document).on('click', '.btn-view-pdf', function() {
            var path = $(this).data('path');
            var filename = $(this).data('filename');
            var rawUrl = '{!! route("dokter.file-managements.view", ["path" => "__PATH__", "raw" => true]) !!}'.replace('__PATH__', encodeURIComponent(path));

            $('#pdfModalFileName').text(filename);
            $('#pdfViewerModal').modal('show');
            renderPdfIntoContainer('pdfjsContainer', rawUrl);
        });

        $('#pdfViewerModal').on('hidden.bs.modal', function() {
            document.getElementById('pdfjsContainer').innerHTML = '';
        });

        // Disable right-click on PDF viewer
        $(document).on('contextmenu', '#pdfjsContainer', function(e) {
            e.preventDefault();
        });

        // Disable keyboard shortcuts for download/print
        $(document).on('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'p' || e.key === 'u')) {
                e.preventDefault();
                return false;
            }
        });

        // Check All
        $('#checkAll').on('change', function() {
            var isChecked = $(this).is(':checked');
            $('.file-validate-checkbox').prop('checked', isChecked).trigger('change');
        });

        // Toggle validasi via AJAX
        $(document).on('change', '.file-validate-checkbox', function() {
            var $checkbox = $(this);
            var filePath = $checkbox.data('path');
            var fileName = $checkbox.data('name');
            var folderPath = $checkbox.data('folder');
            var is_checked = $checkbox.is(':checked');

            var url = is_checked ?
                '{{ route("dokter.file-managements.validate") }}' :
                '{{ route("dokter.file-managements.unvalidate") }}';

            var data = {
                file_path: filePath,
                file_name: fileName,
                folder_path: folderPath,
                _token: '{{ csrf_token() }}'
            };

            $.ajax({
                url: url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        var $row = $checkbox.closest('tr');
                        var $statusBadge = $row.find('.validation-status');
                        var $validatedInfo = $row.find('.validated-info');

                        if (is_checked) {
                            $statusBadge.html('<span class="badge bg-success"><i class="mdi mdi-check-circle me-1"></i> Validated</span>');
                            $validatedInfo.html('<small class="text-muted">{{ auth()->user()->employee_id }}<br>{{ now()->format("d M Y H:i") }}</small>');
                        } else {
                            $statusBadge.html('<span class="badge bg-secondary">Belum</span>');
                            $validatedInfo.html('<small class="text-muted">-</small>');
                        }

                        showToast(response.message, 'success');
                    }
                },
                error: function(xhr) {
                    $checkbox.prop('checked', !is_checked);
                    showToast('Gagal memproses validasi.', 'error');
                }
            });
        });
    });
</script>
@endsection
