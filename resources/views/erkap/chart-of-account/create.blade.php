@extends('layouts.Erkap')

@section('title', $pageName)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">{{ $pageName }}</h5>
                    <a href="{{ route('erkap.chart-of-accounts.index') }}" class="btn btn-secondary">
                        <i class="mdi mdi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
            <div class="card-body">
                @include('erkap.partials.form-alerts')

                <form action="{{ route('erkap.chart-of-accounts.store') }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="cost_center_id">Pusat Biaya (a..d) <span class="text-danger">*</span></label>
                            <select name="cost_center_id" id="cost_center_id" class="form-select @error('cost_center_id') is-invalid @enderror" required>
                                <option value="">— Pilih Pusat Biaya</option>
                                @foreach($costCenters as $costCenter)
                                    <option value="{{ $costCenter['id'] }}" data-code="{{ $costCenter['code'] }}" @selected((string) old('cost_center_id') === (string) $costCenter['id'])>
                                        {{ $costCenter['label'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('cost_center_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Kode Pusat Biaya berisi segmen a (1) + b (2) + c (5) + d (3).</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="cost_element_id">Elemen Biaya (e) <span class="text-danger">*</span></label>
                            <select name="cost_element_id" id="cost_element_id" class="form-select @error('cost_element_id') is-invalid @enderror" required disabled>
                                <option value="">— Pilih Pusat Biaya dulu —</option>
                            </select>
                            @error('cost_element_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Seluruh Elemen Biaya aktif tersedia; kombinasi yang sudah ada akan ditolak saat disimpan.</div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Kode Chart of Account</label>
                            <div class="d-flex align-items-center gap-2">
                                <code id="erkap_code_preview" class="fs-5 px-2 py-1 bg-light border rounded">—</code>
                                <span class="form-text mb-0">
                                    15 karakter = kode Pusat Biaya (11) + <code>e</code> (4).
                                    Disusun otomatis; tidak dapat diedit manual.
                                </span>
                            </div>
                            @error('code') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="type">Tipe</label>
                            <select name="type" id="type" class="form-select @error('type') is-invalid @enderror">
                                <option value="">Otomatis dari Elemen Biaya</option>
                                <option value="revenue" @selected(old('type') === 'revenue')>Pendapatan (Revenue)</option>
                                <option value="expense" @selected(old('type') === 'expense')>Beban (Expense)</option>
                            </select>
                            @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="name">Nama Akun</label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" maxlength="255" placeholder="Kosongkan untuk memakai nama Elemen Biaya">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold" for="description">Deskripsi</label>
                            <textarea name="description" id="description" rows="2" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-4 border-top pt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save me-1"></i> Simpan
                        </button>
                        <a href="{{ route('erkap.chart-of-accounts.index') }}" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('plugin')
<script>
    window.ERKAP_COA_OPTIONS_URL = @json(route('erkap.coa-options.index'));
</script>
<script src="{{ asset('js/erkap-cascade.js') }}"></script>
<script>
    $(document).ready(function () {
        ErkapCascade.init({
            chain: ['cost_center', 'cost_element'],
            selectors: {
                cost_center: '#cost_center_id',
                cost_element: '#cost_element_id',
            },
            // Form ini membuat kombinasi baru, jadi seluruh Elemen Biaya aktif
            // ditawarkan — bukan hanya yang sudah punya COA di Pusat Biaya ini.
            parentQuery: {
                cost_element: function () {
                    return {};
                },
            },
        });
    });
</script>
@endsection
