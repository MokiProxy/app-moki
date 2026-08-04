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

                <form action="{{ route('dokter.document-types.update', $documentType->id) }}" method="POST" id="document-type-form">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nama <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $documentType->name) }}" required maxlength="255">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Header Regex</label>
                            <input type="hidden" name="header_regex" id="header_regex" value="{{ old('header_regex', $documentType->header_regex) }}">
                            <div id="header_regex-builder-container" data-regex-builder="header_regex" data-regex-builder-options='{"label":"Header Regex","sampleText":"INVOICE\nPEMBAYARAN\nNo: INV-001\nTgl: 12/03/2026"}'></div>
                            @error('header_regex') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">Regex untuk mencocokkan judul/header dokumen. Ini adalah <strong>primary identifier</strong> untuk deteksi jenis dokumen.</small>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Deskripsi</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3" maxlength="1000">{{ old('description', $documentType->description) }}</textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Number Regex</label>
                            <input type="hidden" name="number_regex" id="number_regex" value="{{ old('number_regex', $documentType->number_regex) }}">
                            <div id="number_regex-builder-container" data-regex-builder="number_regex" data-regex-builder-options='{"label":"Number Regex","sampleText":"No: INV-001\nNomor: 12345"}'></div>
                            @error('number_regex') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Number Label</label>
                            <input type="text" name="number_label" class="form-control @error('number_label') is-invalid @enderror" value="{{ old('number_label', $documentType->number_label) }}" maxlength="255" placeholder="Contoh: Nomor Dokumen">
                            @error('number_label') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Keterangan Regex</label>
                            <input type="hidden" name="keterangan_regex" id="keterangan_regex" value="{{ old('keterangan_regex', $documentType->keterangan_regex) }}">
                            <div id="keterangan_regex-builder-container" data-regex-builder="keterangan_regex" data-regex-builder-options='{"label":"Keterangan Regex","sampleText":"Keterangan: Pembayaran supplier ABC"}'></div>
                            @error('keterangan_regex') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">Regex untuk menangkap data Keterangan dari hasil OCR.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Keterangan Label</label>
                            <input type="text" name="keterangan_label" class="form-control @error('keterangan_label') is-invalid @enderror" value="{{ old('keterangan_label', $documentType->keterangan_label) }}" maxlength="255" placeholder="Contoh: keterangan">
                            @error('keterangan_label') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Uraian Regex</label>
                            <input type="hidden" name="uraian_regex" id="uraian_regex" value="{{ old('uraian_regex', $documentType->uraian_regex) }}">
                            <div id="uraian_regex-builder-container" data-regex-builder="uraian_regex" data-regex-builder-options='{"label":"Uraian Regex","sampleText":"URAIAN\nBarang A x 10\nBarang B x 5\nTOTAL Rp 1.000.000"}'></div>
                            @error('uraian_regex') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">Regex untuk menangkap data Uraian (multi-baris, antara URAIAN dan TOTAL) dari hasil OCR.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Uraian Label</label>
                            <input type="text" name="uraian_label" class="form-control @error('uraian_label') is-invalid @enderror" value="{{ old('uraian_label', $documentType->uraian_label) }}" maxlength="255" placeholder="Contoh: uraian">
                            @error('uraian_label') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tanggal Regex</label>
                            <input type="hidden" name="tanggal_regex" id="tanggal_regex" value="{{ old('tanggal_regex', $documentType->tanggal_regex) }}">
                            <div id="tanggal_regex-builder-container" data-regex-builder="tanggal_regex" data-regex-builder-options='{"label":"Tanggal Regex","sampleText":"Tgl: 12/03/2026\nTanggal: 2026-03-12"}'></div>
                            @error('tanggal_regex') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">Regex untuk menangkap data Tgl (tanggal) dari hasil OCR.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tanggal Label</label>
                            <input type="text" name="tanggal_label" class="form-control @error('tanggal_label') is-invalid @enderror" value="{{ old('tanggal_label', $documentType->tanggal_label) }}" maxlength="255" placeholder="Contoh: tanggal">
                            @error('tanggal_label') <div class="invalid-feedback">{{ $message }}</div> @enderror
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

                        <div class="col-md-6">
                            <div class="mt-4">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="keterangan_enabled" value="0">
                                    <input type="checkbox" name="keterangan_enabled" class="form-check-input" id="keterangan_enabled" value="1" {{ old('keterangan_enabled', $documentType->keterangan_enabled) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="keterangan_enabled">Keterangan Regex Enabled</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mt-4">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="uraian_enabled" value="0">
                                    <input type="checkbox" name="uraian_enabled" class="form-check-input" id="uraian_enabled" value="1" {{ old('uraian_enabled', $documentType->uraian_enabled) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="uraian_enabled">Uraian Regex Enabled</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mt-4">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="tanggal_enabled" value="0">
                                    <input type="checkbox" name="tanggal_enabled" class="form-check-input" id="tanggal_enabled" value="1" {{ old('tanggal_enabled', $documentType->tanggal_enabled) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="tanggal_enabled">Tanggal Regex Enabled</label>
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

@section('plugin')
<script src="{{ asset('js/regex-builder.js') }}"></script>
<script>
$(document).ready(function() {
    // Form submit validation
    $('#document-type-form').on('submit', function(e) {
        var isValid = true;
        var errors = [];
        
        // Validate all regex fields
        var regexFields = ['header_regex', 'number_regex', 'keterangan_regex', 'uraian_regex', 'tanggal_regex'];
        
        regexFields.forEach(function(field) {
            var value = $('#' + field).val();
            if (value) {
                // Validate format /pattern/flags
                var regexMatch = value.match(/^\/(.+)\/([gimsuy]*)$/);
                if (!regexMatch) {
                    isValid = false;
                    errors.push(field + ': Format harus /pattern/flags');
                } else {
                    // Try to create regex to check validity
                    try {
                        new RegExp(regexMatch[1], regexMatch[2]);
                    } catch (ex) {
                        isValid = false;
                        errors.push(field + ': ' + ex.message);
                    }
                }
            }
        });
        
        if (!isValid) {
            alert('Error validasi regex:\n' + errors.join('\n'));
            e.preventDefault();
            return false;
        }
        
        return true;
    });
});
</script>
@endsection
