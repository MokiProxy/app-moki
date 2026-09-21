@extends('layouts.Erkap')

@section('title', $pageName)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">{{ $pageName }}</h5>
                    <a href="{{ route('erkap.risk-assessments-monthly.index') }}" class="btn btn-secondary">
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

                <form action="{{ route('erkap.risk-assessments-monthly.store') }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Identifikasi Risiko <span class="text-danger">*</span></label>
                            <select name="erkap_risk_identification_id" class="form-select @error('erkap_risk_identification_id') is-invalid @enderror" required>
                                <option value="" disabled selected>Pilih Risiko</option>
                                @foreach($riskIdentifications as $risk)
                                    @php
                                        $division = optional($risk->departmentTarget?->division);
                                        $rkapYear = optional($risk->departmentTarget?->companyTarget?->rkap)->year;
                                    @endphp
                                    <option value="{{ $risk->id }}" {{ old('erkap_risk_identification_id') == $risk->id ? 'selected' : '' }}>
                                        [#{{ $risk->id }}] RKAP {{ $rkapYear ?? '-' }} - {{ $division->name ?? '-' }} - {{ Str::limit($risk->risk, 80) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('erkap_risk_identification_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">Bulan <span class="text-danger">*</span></label>
                            <select name="month" class="form-select @error('month') is-invalid @enderror" required>
                                <option value="" disabled selected>Pilih Bulan</option>
                                @foreach($monthLabels as $key => $label)
                                    <option value="{{ $key + 1 }}" {{ old('month') == $key + 1 ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('month') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">Tahun <span class="text-danger">*</span></label>
                            <input type="number" name="year" class="form-control @error('year') is-invalid @enderror" value="{{ old('year', date('Y')) }}" required>
                            @error('year') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6 class="text-uppercase fw-bold text-muted mb-3"><i class="mdi mdi-chart-bubble me-1"></i> Skor Risiko (Skala 1-10)</h6>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Inherent Probability <span class="text-danger">*</span></label>
                            <input type="number" name="inherent_probability" min="1" max="10" class="form-control @error('inherent_probability') is-invalid @enderror" value="{{ old('inherent_probability') }}" required>
                            @error('inherent_probability') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Inherent Impact <span class="text-danger">*</span></label>
                            <input type="number" name="inherent_impact" min="1" max="10" class="form-control @error('inherent_impact') is-invalid @enderror" value="{{ old('inherent_impact') }}" required>
                            @error('inherent_impact') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Current Probability</label>
                            <input type="number" name="current_probability" min="1" max="10" class="form-control @error('current_probability') is-invalid @enderror" value="{{ old('current_probability') }}">
                            @error('current_probability') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Current Impact</label>
                            <input type="number" name="current_impact" min="1" max="10" class="form-control @error('current_impact') is-invalid @enderror" value="{{ old('current_impact') }}">
                            @error('current_impact') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Residual Probability</label>
                            <input type="number" name="residual_probability" min="1" max="10" class="form-control @error('residual_probability') is-invalid @enderror" value="{{ old('residual_probability') }}">
                            @error('residual_probability') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Residual Impact</label>
                            <input type="number" name="residual_impact" min="1" max="10" class="form-control @error('residual_impact') is-invalid @enderror" value="{{ old('residual_impact') }}">
                            @error('residual_impact') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Status Mitigasi</label>
                            <select name="mitigation_status" class="form-select">
                                <option value="on_progress" {{ old('mitigation_status', 'on_progress') == 'on_progress' ? 'selected' : '' }}>On Progress</option>
                                <option value="done" {{ old('mitigation_status') == 'done' ? 'selected' : '' }}>Done</option>
                                <option value="overdue" {{ old('mitigation_status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Risk Owner</label>
                            <input type="text" name="risk_owner" class="form-control @error('risk_owner') is-invalid @enderror" value="{{ old('risk_owner') }}" placeholder="Nama/Divisi pemilik risiko">
                            @error('risk_owner') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Rencana Mitigasi</label>
                            <textarea name="mitigation_plan" class="form-control @error('mitigation_plan') is-invalid @enderror" rows="3" placeholder="Keterangan rencana mitigasi">{{ old('mitigation_plan') }}</textarea>
                            @error('mitigation_plan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-4 border-top pt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save me-1"></i> Simpan
                        </button>
                        <a href="{{ route('erkap.risk-assessments-monthly.index') }}" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection