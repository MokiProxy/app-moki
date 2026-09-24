@extends('layouts.Erkap')

@section('title', $pageName)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">{{ $pageName }}</h5>
                    <a href="{{ route('erkap.performance-scorecards.index') }}" class="btn btn-secondary">
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

                <form action="{{ route('erkap.performance-scorecards.store') }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Tahun RKAP <span class="text-danger">*</span></label>
                            <select name="erkap_rkap_id" class="form-select @error('erkap_rkap_id') is-invalid @enderror" required>
                                <option value="" disabled selected>Pilih Tahun RKAP</option>
                                @foreach($rkaps as $rkap)
                                    <option value="{{ $rkap->id }}" {{ old('erkap_rkap_id') == $rkap->id ? 'selected' : '' }}>{{ $rkap->year }}</option>
                                @endforeach
                            </select>
                            @error('erkap_rkap_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Sasaran Departemen <span class="text-danger">*</span></label>
                            <select name="erkap_department_target_id" class="form-select @error('erkap_department_target_id') is-invalid @enderror" required>
                                <option value="" disabled selected>Pilih Sasaran Departemen</option>
                                @foreach($departmentTargets as $target)
                                    @php
                                        $rkapYear = optional($target->companyTarget?->rkap)->year;
                                    @endphp
                                    <option value="{{ $target->id }}" {{ old('erkap_department_target_id') == $target->id ? 'selected' : '' }}>
                                        [#{{ $target->id }}] RKAP {{ $rkapYear ?? '-' }} - {{ $target->division->name ?? '-' }} - {{ Str::limit($target->target, 70) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('erkap_department_target_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-bold">Triwulan <span class="text-danger">*</span></label>
                            <select name="quarter" class="form-select @error('quarter') is-invalid @enderror" required>
                                @for($q = 1; $q <= 4; $q++)
                                    <option value="{{ $q }}" {{ old('quarter') == $q ? 'selected' : '' }}>Triwulan {{ $q }}</option>
                                @endfor
                            </select>
                            @error('quarter') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-bold">Tahun <span class="text-danger">*</span></label>
                            <input type="number" name="year" class="form-control @error('year') is-invalid @enderror" value="{{ old('year', date('Y')) }}" required>
                            @error('year') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nama KPI <span class="text-danger">*</span></label>
                            <input type="text" name="kpi_name" class="form-control @error('kpi_name') is-invalid @enderror" value="{{ old('kpi_name') }}" placeholder="contoh: Penyelesaian Program Kerja" required>
                            @error('kpi_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-bold">Target KPI <span class="text-danger">*</span></label>
                            <input type="text" inputmode="decimal" name="kpi_target" class="form-control @error('kpi_target') is-invalid @enderror" value="{{ old('kpi_target') }}" placeholder="100">
                            @error('kpi_target') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-bold">Actual KPI <span class="text-danger">*</span></label>
                            <input type="text" inputmode="decimal" name="kpi_actual" class="form-control @error('kpi_actual') is-invalid @enderror" value="{{ old('kpi_actual') }}" placeholder="0">
                            @error('kpi_actual') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-bold">Bobot (%) <span class="text-danger">*</span></label>
                            <input type="text" inputmode="decimal" name="weight" class="form-control @error('weight') is-invalid @enderror" value="{{ old('weight') }}" placeholder="0-100">
                            @error('weight') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-4 border-top pt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save me-1"></i> Simpan
                        </button>
                        <a href="{{ route('erkap.performance-scorecards.index') }}" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection