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
                <a href="{{ route('erkap.zbb-reviews.index') }}" class="btn btn-light">
                    <i class="mdi mdi-arrow-left me-1"></i> Kembali
                </a>
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

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="card border shadow-sm mb-4">
                    <div class="card-header bg-light py-2 fw-bold">Detail Pos Anggaran</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Pos Anggaran</small>
                                <strong>{{ $review->display_name ?: '-' }}</strong>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Jenis</small>
                                <span class="badge bg-light text-dark border">{{ $review->subjectTypeLabel() }}</span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Periode</small>
                                <strong>RKAP {{ $review->rkap?->year }}</strong>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Divisi</small>
                                <strong>{{ $review->division?->name ?: '-' }}</strong>
                            </div>
                            <div class="col-md-4 mb-3">
                                <small class="text-muted d-block">Nilai Tahun Lalu</small>
                                <strong>Rp {{ number_format($review->prior_year_amount, 0, ',', '.') }}</strong>
                            </div>
                            <div class="col-md-4 mb-3">
                                <small class="text-muted d-block">Nilai Diusulkan</small>
                                <strong>Rp {{ number_format($review->proposed_amount, 0, ',', '.') }}</strong>
                            </div>
                            <div class="col-md-4 mb-3">
                                <small class="text-muted d-block">Selisih ({{ number_format($review->delta_percent, 2, ',', '.') }}%)</small>
                                <strong class="{{ $review->delta_amount >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $review->delta_amount >= 0 ? '+' : '-' }}
                                    Rp {{ number_format(abs($review->delta_amount), 0, ',', '.') }}
                                </strong>
                            </div>
                        </div>

                        @if($review->reviewer)
                        <hr>
                        <small class="text-muted d-block">Direview oleh: <strong>{{ $review->reviewer->name }}</strong>
                            pada {{ $review->reviewed_at?->format('d M Y H:i') }}</small>
                        @endif
                    </div>
                </div>

                @can('erkap.zbb-reviews.edit')
                <div class="card border shadow-sm">
                    <div class="card-header bg-light py-2 fw-bold">Review</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('erkap.zbb-reviews.update', $review->id) }}">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                @if($review->isIncrease())
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">
                                        Justifikasi Kenaikan <span class="text-danger">*</span>
                                    </label>
                                    <textarea name="increase_rationale" rows="4"
                                              class="form-control @error('increase_rationale') is-invalid @enderror"
                                              placeholder="Alasan kenaikan anggaran diusulkan...">{{ old('increase_rationale', $review->increase_rationale) }}</textarea>
                                    @error('increase_rationale')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Wajib diisi karena terjadi kenaikan dari tahun sebelumnya.</small>
                                </div>
                                @endif

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Status Review</label>
                                    <select name="zbb_status" class="form-select @error('zbb_status') is-invalid @enderror">
                                        @foreach (\App\Models\Erkap\ZBBReview::STATUS_LABELS as $value => $label)
                                        <option value="{{ $value }}" @selected(old('zbb_status', $review->zbb_status) == $value)>
                                            {{ $label }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('zbb_status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 mb-3">
                                    <label class="form-label fw-bold">Catatan Review</label>
                                    <textarea name="review_notes" rows="3" class="form-control"
                                              placeholder="Catatan tambahan (opsional)">{{ old('review_notes', $review->review_notes) }}</textarea>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-content-save me-1"></i> Simpan Review
                            </button>
                        </form>
                    </div>
                </div>
                @else
                <div class="alert alert-info" role="alert">
                    Anda tidak memiliki izin untuk melakukan review. Hubungi Departemen Anggaran / Controller.
                </div>
                @endcan
            </div>
        </div>
    </div>
</div>
@endsection