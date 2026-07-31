@extends('layouts.FormIT')

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
                <form action="{{ route('form-it.forms.software-installation.create') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nama Pemohon</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') ?? $pemohon->name }}" required maxlength="255" readonly>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Jabatan</label>
                            <input type="text" name="jabatan" class="form-control @error('jabatan') is-invalid @enderror" value="{{ old('jabatan') ?? $pemohon->jabatan }}" required maxlength="255" readonly>
                            @error('jabatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Departemen</label>
                            <input type="text" name="departemen" class="form-control @error('departemen') is-invalid @enderror" value="{{ old('departemen') ?? $pemohon->division->name }}" required maxlength="255" readonly>
                            @error('departemen') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Area / Job Site</label>
                            <input type="text" name="regional" class="form-control @error('regional') is-invalid @enderror" value="{{ old('regional') ?? $pemohon->regional->name }}" required maxlength="255" readonly>
                            @error('regional') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6 d-flex flex-column align-items-start justify-content-start">
                            <label class="form-label fw-bold">Software Yang Diajukan</label>
                            @foreach ($softwareOptions as $software)
                            <div class="d-flex align-items-center gap-1">
                                <input type="checkbox" name="softwares[]" value="{{ $software['slug'] }}">
                                <label class="form-label m-0 p-0">{{ $software["title"] }}</label>
                            </div>
                            @endforeach
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Keterangan</label>
                            <textarea name="keterangan" class="form-control @error('keterangan') is-invalid @enderror">{{ old('keterangan') }}</textarea>
                            @error('keterangan') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
