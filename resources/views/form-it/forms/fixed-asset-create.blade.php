@extends('layouts.FormIT')

@section('title', $pageName)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">{{ $pageName }}</h5>
                    <a href="{{ route('form-it.forms.fixed-asset.my-submissions') }}" class="btn btn-secondary">
                        <i class="mdi mdi-arrow-left me-1"></i> Kembali
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

                <form action="{{ route('form-it.forms.fixed-asset.store') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-12">
                            <h6 class="fw-bold text-muted mb-3"><i class="mdi mdi-account-circle me-1"></i> Data Pemohon</h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nama Pemohon</label>
                            <input type="text" class="form-control" value="{{ $pemohon->name }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Jabatan Pemohon</label>
                            <input type="text" class="form-control" value="{{ $pemohon->jabatan }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Departemen Pemohon</label>
                            <input type="text" class="form-control" value="{{ $pemohon->division->name ?? '-' }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Area / Job Site Pemohon</label>
                            <input type="text" class="form-control" value="{{ $pemohon->regional->name ?? '-' }}" readonly>
                        </div>

                        <div class="col-md-12 mt-4">
                            <h6 class="fw-bold text-muted mb-3"><i class="mdi mdi-laptop me-1"></i> Data Pengajuan</h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tanggal Pengajuan (Dari) <span class="text-danger">*</span></label>
                            <input type="date" name="date_start" class="form-control @error('date_start') is-invalid @enderror" value="{{ old('date_start') }}" required>
                            @error('date_start') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tanggal Pengajuan (Sampai) <span class="text-danger">*</span></label>
                            <input type="date" name="date_end" class="form-control @error('date_end') is-invalid @enderror" value="{{ old('date_end') }}" required>
                            @error('date_end') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tujuan Lokasi <span class="text-danger">*</span></label>
                            <input type="text" name="tujuan_lokasi" class="form-control @error('tujuan_lokasi') is-invalid @enderror" value="{{ old('tujuan_lokasi') }}" required maxlength="255" placeholder="Masukkan tujuan lokasi">
                            @error('tujuan_lokasi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tipe Perangkat <span class="text-danger">*</span></label>
                            <input type="text" name="tipe_perangkat" class="form-control @error('tipe_perangkat') is-invalid @enderror" value="{{ old('tipe_perangkat') }}" required maxlength="255" placeholder="Contoh: Laptop, Monitor, Keyboard">
                            @error('tipe_perangkat') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Keperluan <span class="text-danger">*</span></label>
                            <textarea name="keperluan" class="form-control @error('keperluan') is-invalid @enderror" rows="3" required maxlength="1000" placeholder="Jelaskan keperluan peminjaman...">{{ old('keperluan') }}</textarea>
                            @error('keperluan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="mt-4 border-top pt-3">
                        <button type="submit" class="btn btn-primary" onclick="return confirm('Apakah Anda yakin ingin mengirim pengajuan ini?')">
                            <i class="mdi mdi-send me-1"></i> Kirim Pengajuan
                        </button>
                        <a href="{{ route('form-it.forms.fixed-asset.my-submissions') }}" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
