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
                <form action="{{ route('dokter.document-types.store') }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nama <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required maxlength="255">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Deskripsi</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3" maxlength="1000">{{ old('description') }}</textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Number Regex</label>
                            <input type="text" name="number_regex" class="form-control @error('number_regex') is-invalid @enderror" value="{{ old('number_regex', '/No\\s+Inv\\s*\\n?\\s*:\\s*(.+)/i') }}" maxlength="255" placeholder="Contoh: /^DOC-\d+$/">
                            @error('number_regex') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Number Label</label>
                            <input type="text" name="number_label" class="form-control @error('number_label') is-invalid @enderror" value="{{ old('number_label', 'invoice_number') }}" maxlength="255" placeholder="Contoh: Nomor Dokumen">
                            @error('number_label') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">S3 Filename Template</label>
                            <input type="text" name="s3_filename_template" class="form-control @error('s3_filename_template') is-invalid @enderror" value="{{ old('s3_filename_template', '{vendor}_{number}.{ext}') }}" maxlength="255">
                            @error('s3_filename_template') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">Variabel: <code>{vendor}</code>, <code>{number}</code>, <code>{ext}</code>, <code>{filename}</code></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">FTP Folder Template</label>
                            <input type="text" name="ftp_folder_template" class="form-control @error('ftp_folder_template') is-invalid @enderror" value="{{ old('ftp_folder_template', '{document_type}/{vendor}') }}" maxlength="255">
                            @error('ftp_folder_template') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">Variabel: <code>{document_type}</code>, <code>{vendor}</code>, <code>{number}</code>, <code>{filename}</code></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">FTP Failed Folder</label>
                            <input type="text" name="ftp_failed_folder" class="form-control @error('ftp_failed_folder') is-invalid @enderror" value="{{ old('ftp_failed_folder', 'FAILED') }}" maxlength="255">
                            @error('ftp_failed_folder') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <div class="mt-4">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="vendor_search_enabled" value="0">
                                    <input type="checkbox" name="vendor_search_enabled" class="form-check-input" id="vendor_search_enabled" value="1" {{ old('vendor_search_enabled') ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="vendor_search_enabled">Vendor Search Enabled</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 border-top pt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save me-1"></i> Simpan
                        </button>
                        <a href="{{ route('dokter.document-types.index') }}" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
