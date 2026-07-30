@extends('layouts.Dokter')

@section('title', $filename)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <a href="{{ route('dokter.file-managements.index', ['path' => dirname($path)]) }}" class="btn btn-secondary me-3" title="Kembali">
                        <i class="mdi mdi-arrow-left-bold"></i>
                    </a>
                    <div>
                        <h5 class="mb-0 card-title text-dark fw-bold">
                            <i class="mdi mdi-file-pdf-box text-danger me-1"></i>
                            {{ $filename }}
                        </h5>
                    </div>
                </div>
                <div class="d-flex gap-1">
                    <a href="{{ route('dokter.file-managements.download', ['path' => $path]) }}" class="btn btn-success">
                        <i class="mdi mdi-download me-1"></i> Download
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <iframe src="{{ route('dokter.file-managements.view', ['path' => $path, 'raw' => true]) }}"
                        style="width: 100%; height: 80vh; border: none;"
                        title="{{ $filename }}">
                </iframe>
            </div>
        </div>
    </div>
</div>
@endsection
