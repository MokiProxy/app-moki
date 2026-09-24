@extends('layouts.Erkap')

@section('title', $pageName)

@section('css')
<style>
    .text-dark { color: #000000 !important; }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title text-dark fw-bold">{{ $pageName }}</h5>
                <div class="d-flex gap-1">
                    <a href="{{ route('erkap.form1.template') }}" class="btn btn-outline-info">
                        <i class="mdi mdi-file-download me-1"></i> Template Excel
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

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-body">
                                <h6 class="card-title fw-bold"><i class="mdi mdi-tray-arrow-up me-1"></i> Import Form 1</h6>
                                <p class="text-muted small">Unggah file Excel sesuai template. Seluruh baris diproses transaksional dengan validasi aturan bisnis.</p>
                                <form action="{{ route('erkap.form1.import') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label">Tahun RKAP</label>
                                        <select name="erkap_rkap_id" class="form-select" required>
                                            @foreach($rkaps as $rkap)
                                                <option value="{{ $rkap->id }}">{{ $rkap->year }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Satuan Kerja (Divisi)</label>
                                        <select name="division_id" class="form-select" required>
                                            @foreach($divisions as $division)
                                                <option value="{{ $division->id }}">{{ $division->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">File Excel</label>
                                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="mdi mdi-upload me-1"></i> Import
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-body">
                                <h6 class="card-title fw-bold"><i class="mdi mdi-tray-arrow-down me-1"></i> Export Form 1</h6>
                                <p class="text-muted small">Unduh data Form 1 menjadi file Excel dengan format yang sama.</p>
                                <form action="{{ route('erkap.form1.export') }}" method="GET">
                                    <div class="mb-3">
                                        <label class="form-label">Tahun RKAP</label>
                                        <select name="erkap_rkap_id" class="form-select" required>
                                            @foreach($rkaps as $rkap)
                                                <option value="{{ $rkap->id }}">{{ $rkap->year }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Satuan Kerja (Divisi) <span class="text-muted">(opsional)</span></label>
                                        <select name="division_id" class="form-select">
                                            <option value="">Semua Divisi</option>
                                            @foreach($divisions as $division)
                                                <option value="{{ $division->id }}">{{ $division->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-success">
                                        <i class="mdi mdi-download me-1"></i> Export
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection