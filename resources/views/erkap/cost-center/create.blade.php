@extends('layouts.Erkap')

@section('title', $pageName)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">{{ $pageName }}</h5>
                    <a href="{{ route('erkap.cost-centers.index') }}" class="btn btn-secondary">
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

                <form action="{{ route('erkap.cost-centers.store') }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Kode <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="cth: F0120215109100" required maxlength="50">
                            <div class="form-text">
                                Format: [Kategori][Lokasi][Departemen][Swa/Non Swa][Elemen Biaya] &mdash; cth: F 01 20210 510 9100. 4 digit terakhir harus sesuai kode elemen biaya.
                            </div>
                            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Divisi <span class="text-danger">*</span></label>
                            <select name="division_id" class="form-select @error('division_id') is-invalid @enderror" required>
                                <option value="" disabled selected>Pilih Divisi</option>
                                @foreach($divisions as $division)
                                    <option value="{{ $division->id }}" {{ old('division_id') == $division->id ? 'selected' : '' }}>
                                        {{ $division->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('division_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Nama <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required maxlength="255">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Pemilik <span class="text-danger">*</span></label>
                            <input type="text" name="owner" class="form-control @error('owner') is-invalid @enderror" value="{{ old('owner') }}" required maxlength="255">
                            @error('owner') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-12">
                            <div class="form-check form-switch form-check-inline">
                                <input type="checkbox" name="is_swakelola" value="1" class="form-check-input @error('is_swakelola') is-invalid @enderror" id="is_swakelola" {{ old('is_swakelola') ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="is_swakelola">Swakelola</label>
                            </div>
                            <div class="form-text">
                                Centang jika cost center termasuk kategori Swakelola (segmen kode ke-4 = 510).
                            </div>
                            @error('is_swakelola') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-12">
                            <div class="form-check form-switch form-check-inline">
                                <input type="checkbox" name="is_centralized" value="1" class="form-check-input @error('is_centralized') is-invalid @enderror" id="is_centralized" {{ old('is_centralized') ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="is_centralized">Biaya Tersentralisasi</label>
                            </div>
                            <div class="form-text">
                                Biaya terpusat (mis. gaji &rarr; HR, TI &rarr; Departemen IT) hanya dapat diinput oleh departemen koordinator.
                            </div>
                            @error('is_centralized') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            @error('coordinating_division_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-12" id="coordinator-field" style="{{ old('is_centralized') ? '' : 'display: none;' }}">
                            <label class="form-label fw-bold">Departemen Koordinator <span class="text-danger">*</span></label>
                            <select name="coordinating_division_id" class="form-select @error('coordinating_division_id') is-invalid @enderror">
                                <option value="" disabled selected>Pilih Departemen Koordinator</option>
                                @foreach($divisions as $division)
                                    <option value="{{ $division->id }}" {{ old('coordinating_division_id') == $division->id ? 'selected' : '' }}>
                                        {{ $division->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('coordinating_division_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-4 border-top pt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save me-1"></i> Simpan
                        </button>
                        <a href="{{ route('erkap.cost-centers.index') }}" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('plugin')
<script>
    $(document).ready(function() {
        function toggleCoordinator() {
            if ($('#is_centralized').is(':checked')) {
                $('#coordinator-field').show();
            } else {
                $('#coordinator-field').hide();
                $('#coordinator-field select').val('');
            }
        }
        $('#is_centralized').on('change', toggleCoordinator);
        toggleCoordinator();
    });
</script>
@endsection