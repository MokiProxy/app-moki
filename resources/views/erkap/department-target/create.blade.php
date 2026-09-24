@extends('layouts.Erkap')

@section('title', $pageName)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">{{ $pageName }}</h5>
                    <a href="{{ route('erkap.department-targets.index') }}" class="btn btn-secondary">
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

                <form action="{{ route('erkap.department-targets.store') }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Sasaran Perusahaan <span class="text-danger">*</span></label>
                            <select name="erkap_company_target_id" class="form-select @error('erkap_company_target_id') is-invalid @enderror" required>
                                <option value="" disabled selected>Pilih Sasaran Perusahaan</option>
                                @foreach($companyTargets as $companyTarget)
                                    <option value="{{ $companyTarget->id }}" {{ old('erkap_company_target_id') == $companyTarget->id ? 'selected' : '' }}>
                                        {{ $companyTarget->target }}
                                    </option>
                                @endforeach
                            </select>
                            @error('erkap_company_target_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Divisi <span class="text-danger">*</span></label>
                            @if(isset($userDivision))
                            <input type="hidden" name="division_id" value="{{ old('division_id', $userDivision->id) }}">
                            <input type="text" class="form-control" value="{{ $userDivision->name }}" disabled>
                            <div class="form-text text-muted">Divisi otomatis sesuai divisi Anda.</div>
                            @else
                            <select name="division_id" class="form-select @error('division_id') is-invalid @enderror" required>
                                <option value="" disabled selected>Pilih Divisi</option>
                                @foreach($divisions as $division)
                                    <option value="{{ $division->id }}" {{ old('division_id') == $division->id ? 'selected' : '' }}>
                                        {{ $division->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('division_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Rating Criteria <span class="text-danger">*</span></label>
                            <select name="erkap_rating_criteria_id" class="form-select @error('erkap_rating_criteria_id') is-invalid @enderror" required>
                                <option value="" disabled selected>Pilih Rating Criteria</option>
                                @foreach($ratingCriterias as $ratingCriteria)
                                    <option value="{{ $ratingCriteria->id }}" {{ old('erkap_rating_criteria_id') == $ratingCriteria->id ? 'selected' : '' }}>
                                        {{ $ratingCriteria->rating }}
                                    </option>
                                @endforeach
                            </select>
                            @error('erkap_rating_criteria_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Prioritas <span class="text-muted">(opsional)</span></label>
                            <input type="number" name="priority" min="1" class="form-control @error('priority') is-invalid @enderror" value="{{ old('priority') }}" placeholder="1 = prioritas tertinggi">
                            @error('priority') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Target <span class="text-danger">*</span></label>
                            <textarea name="target" class="form-control @error('target') is-invalid @enderror" rows="3" required>{{ old('target') }}</textarea>
                            @error('target') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-4 border-top pt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save me-1"></i> Simpan
                        </button>
                        <a href="{{ route('erkap.department-targets.index') }}" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
