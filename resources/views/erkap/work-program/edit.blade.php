@extends('layouts.Erkap')

@section('title', $pageName)

@section('content')
@php
$months = [
    'jan_plan' => 'Januari',
    'feb_plan' => 'Februari',
    'mar_plan' => 'Maret',
    'apr_plan' => 'April',
    'may_plan' => 'Mei',
    'jun_plan' => 'Juni',
    'jul_plan' => 'Juli',
    'aug_plan' => 'Agustus',
    'sep_plan' => 'September',
    'oct_plan' => 'Oktober',
    'nov_plan' => 'November',
    'dec_plan' => 'Desember',
];
@endphp
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">{{ $pageName }}</h5>
                    <a href="{{ route('erkap.work-programs.index') }}" class="btn btn-secondary">
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

                <form action="{{ route('erkap.work-programs.update', $workProgram->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Identifikasi Risiko <span class="text-danger">*</span></label>
                            <select name="erkap_risk_identification_id" class="form-select @error('erkap_risk_identification_id') is-invalid @enderror" required>
                                <option value="" disabled {{ old('erkap_risk_identification_id', $workProgram->erkap_risk_identification_id) ? '' : 'selected' }}>Pilih Identifikasi Risiko</option>
                                @foreach($riskIdentifications as $riskIdentification)
                                    <option value="{{ $riskIdentification->id }}" {{ old('erkap_risk_identification_id', $workProgram->erkap_risk_identification_id) == $riskIdentification->id ? 'selected' : '' }}>
                                        {{ $riskIdentification->risk }}
                                    </option>
                                @endforeach
                            </select>
                            @error('erkap_risk_identification_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Program Kerja <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $workProgram->name) }}" placeholder="Nama program kerja" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Satuan <span class="text-danger">*</span></label>
                            <input type="text" name="units" class="form-control @error('units') is-invalid @enderror" value="{{ old('units', $workProgram->units) }}" placeholder="Contoh: Kegiatan, Armada, Unit" required>
                            @error('units') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Rencana Tahunan</label>
                            <input type="number" step="0.01" min="0" name="year_plan" class="form-control @error('year_plan') is-invalid @enderror" value="{{ old('year_plan', $workProgram->year_plan) }}">
                            @error('year_plan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6 class="text-uppercase fw-bold text-muted mb-3"><i class="mdi mdi-calendar-month me-1"></i> Rencana Bulanan</h6>
                    </div>

                    <div class="row g-3">
                        @foreach($months as $field => $label)
                        <div class="col-md-2">
                            <label class="form-label fw-bold">{{ $label }}</label>
                            <input type="number" step="0.01" min="0" name="{{ $field }}" class="form-control @error($field) is-invalid @enderror" value="{{ old($field, $workProgram->{$field}) }}">
                            @error($field) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        @endforeach
                    </div>

                    <div class="mt-4 border-top pt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save me-1"></i> Update
                        </button>
                        <a href="{{ route('erkap.work-programs.index') }}" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection