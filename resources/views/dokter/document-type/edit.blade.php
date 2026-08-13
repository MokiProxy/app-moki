@extends('layouts.Dokter')

@section('title', $pageName)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">{{ $pageName }}</h5>
                    <a href="{{ route('dokter.document-types.index') }}" class="btn btn-secondary">
                        <i class="mdi mdi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong><i class="mdi mdi-alert-circle me-1"></i>Error:</strong> {{ session('error') }}
                    @if(session('error_detail'))
                    <hr>
                    <small class="text-muted">
                        <strong>File:</strong> {{ session('error_detail.file') }}<br>
                        <strong>Line:</strong> {{ session('error_detail.line') }}
                    </small>
                    <details class="mt-2">
                        <summary class="text-muted" style="cursor:pointer">Stack Trace</summary>
                        <pre class="mt-1 p-2 bg-light border rounded" style="font-size:11px;max-height:200px;overflow:auto">{{ session('error_detail.trace') }}</pre>
                    </details>
                    @endif
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                <form action="{{ route('dokter.document-types.update', $documentType->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nama <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $documentType->name) }}" required maxlength="255">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Deskripsi</label>
                            <input type="text" name="description" class="form-control @error('description') is-invalid @enderror" value="{{ old('description', $documentType->description) }}" maxlength="1000">
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Filename Template</label>
                            <input type="text" name="filename_template" class="form-control @error('filename_template') is-invalid @enderror" value="{{ old('filename_template', $documentType->filename_template) }}" maxlength="255">
                            @error('filename_template') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">Variabel: <code>{vendor}</code>, <code>{number}</code>, <code>{ext}</code>, <code>{filename}</code></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">FTP Folder Template</label>
                            <input type="text" name="ftp_folder_template" class="form-control @error('ftp_folder_template') is-invalid @enderror" value="{{ old('ftp_folder_template', $documentType->ftp_folder_template) }}" maxlength="255">
                            @error('ftp_folder_template') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">Variabel: <code>{document_type}</code>, <code>{vendor}</code>, <code>{number}</code>, <code>{filename}</code></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">FTP Failed Folder</label>
                            <input type="text" name="ftp_failed_folder" class="form-control @error('ftp_failed_folder') is-invalid @enderror" value="{{ old('ftp_failed_folder', $documentType->ftp_failed_folder) }}" maxlength="255">
                            @error('ftp_failed_folder') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <div class="mt-4">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="vendor_search_enabled" value="0">
                                    <input type="checkbox" name="vendor_search_enabled" class="form-check-input" id="vendor_search_enabled" value="1" {{ old('vendor_search_enabled', $documentType->vendor_search_enabled) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="vendor_search_enabled">Vendor Search Enabled</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 border-top pt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save me-1"></i> Update
                        </button>
                        <a href="{{ route('dokter.document-types.index') }}" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
