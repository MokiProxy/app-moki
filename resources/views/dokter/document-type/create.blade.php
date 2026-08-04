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

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Header Regex</label>
                            <input type="text" name="header_regex" class="form-control @error('header_regex') is-invalid @enderror" value="{{ old('header_regex') }}" maxlength="255" placeholder="Contoh: /^PEMBAYARAN$/mi">
                            @error('header_regex') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">Regex untuk mencocokkan judul/header dokumen. Ini adalah <strong>primary identifier</strong> untuk deteksi jenis dokumen.</small>
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
                            <label class="form-label fw-bold">Keterangan Regex</label>
                            <input type="text" name="keterangan_regex" class="form-control @error('keterangan_regex') is-invalid @enderror" value="{{ old('keterangan_regex', '/Keterangan\\s*:\\s*(.+)/i') }}" maxlength="255" placeholder="Contoh: /Keterangan\s*:\s*(.+)/i">
                            @error('keterangan_regex') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">Regex untuk menangkap data Keterangan dari hasil OCR.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Keterangan Label</label>
                            <input type="text" name="keterangan_label" class="form-control @error('keterangan_label') is-invalid @enderror" value="{{ old('keterangan_label', 'keterangan') }}" maxlength="255" placeholder="Contoh: keterangan">
                            @error('keterangan_label') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Uraian Regex</label>
                            <input type="text" name="uraian_regex" class="form-control @error('uraian_regex') is-invalid @enderror" value="{{ old('uraian_regex', '/URAIAN\\s*\\n(.+?)\\n\\s*TOTAL/si') }}" maxlength="255" placeholder="Contoh: /URAIAN\s*\n(.+?)\n\s*TOTAL/si">
                            @error('uraian_regex') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">Regex untuk menangkap data Uraian (multi-baris, antara URAIAN dan TOTAL) dari hasil OCR.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Uraian Label</label>
                            <input type="text" name="uraian_label" class="form-control @error('uraian_label') is-invalid @enderror" value="{{ old('uraian_label', 'uraian') }}" maxlength="255" placeholder="Contoh: uraian">
                            @error('uraian_label') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tanggal Regex</label>
                            <input type="text" name="tanggal_regex" class="form-control @error('tanggal_regex') is-invalid @enderror" value="{{ old('tanggal_regex', '/Tgl\\s*\\n?\\s*:\\s*(.+)/i') }}" maxlength="255" placeholder="Contoh: /Tgl\s*\n?\s*:\s*(.+)/i">
                            @error('tanggal_regex') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">Regex untuk menangkap data Tgl (tanggal) dari hasil OCR.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tanggal Label</label>
                            <input type="text" name="tanggal_label" class="form-control @error('tanggal_label') is-invalid @enderror" value="{{ old('tanggal_label', 'tanggal') }}" maxlength="255" placeholder="Contoh: tanggal">
                            @error('tanggal_label') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Filename Template</label>
                            <input type="text" name="filename_template" class="form-control @error('filename_template') is-invalid @enderror" value="{{ old('filename_template', '{vendor}_{number}.{ext}') }}" maxlength="255">
                            @error('filename_template') <div class="invalid-feedback">{{ $message }}</div> @enderror
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

                        <div class="col-md-6">
                            <div class="mt-4">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="keterangan_enabled" value="0">
                                    <input type="checkbox" name="keterangan_enabled" class="form-check-input" id="keterangan_enabled" value="1" {{ old('keterangan_enabled') ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="keterangan_enabled">Keterangan Regex Enabled</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mt-4">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="uraian_enabled" value="0">
                                    <input type="checkbox" name="uraian_enabled" class="form-check-input" id="uraian_enabled" value="1" {{ old('uraian_enabled') ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="uraian_enabled">Uraian Regex Enabled</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mt-4">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="tanggal_enabled" value="0">
                                    <input type="checkbox" name="tanggal_enabled" class="form-check-input" id="tanggal_enabled" value="1" {{ old('tanggal_enabled', true) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="tanggal_enabled">Tanggal Regex Enabled</label>
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
