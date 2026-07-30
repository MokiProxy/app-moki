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
    .breadcrumb-item + .breadcrumb-item::before {
        content: "\F0142";
        font-family: "Material Design Icons";
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
                <h6 class="text-muted text-uppercase fw-bold mb-3 mt-1">
                    <i class="mdi mdi-file-outline me-1"></i> File
                    <span class="badge bg-secondary ms-1">{{ count($files) }}</span>
                </h6>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100 mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 50px">No</th>
                                <th>Nama File</th>
                                <th style="width: 100px">Ukuran</th>
                                <th style="width: 70px">Tipe</th>
                                <th style="width: 200px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($files as $key => $file)
                            <tr>
                                <td class="text-center">{{ $key + 1 }}</td>
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
                                <td class="text-center">
                                    @if($file['extension'] === 'pdf')
                                    <a href="{{ route('dokter.file-managements.view', ['path' => $file['path']]) }}" class="btn btn-info btn-sm" title="Lihat PDF">
                                        <i class="mdi mdi-eye"></i> Lihat
                                    </a>
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
@endsection

@section('plugin')
<script>
    $(document).ready(function() {
        $('#btn-refresh').click(function(e) {
            e.preventDefault();
            location.reload();
        });
    });
</script>
@endsection
