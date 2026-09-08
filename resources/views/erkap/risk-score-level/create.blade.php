@extends('layouts.Erkap')

@section('title', $pageName)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">{{ $pageName }}</h5>
                    <a href="{{ route('erkap.risk-score-levels.index') }}" class="btn btn-secondary">
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

                <form action="{{ route('erkap.risk-score-levels.store') }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Risk Probability <span class="text-danger">*</span></label>
                            <select name="erkap_risk_probability_id" class="form-select @error('erkap_risk_probability_id') is-invalid @enderror" required>
                                <option value="" disabled {{ old('erkap_risk_probability_id') ? '' : 'selected' }}>Pilih Risk Probability</option>
                                @foreach($probabilities as $probability)
                                    <option value="{{ $probability->id }}" {{ old('erkap_risk_probability_id') == $probability->id ? 'selected' : '' }}>
                                        {{ $probability->name }} ({{ $probability->point }})
                                    </option>
                                @endforeach
                            </select>
                            @error('erkap_risk_probability_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Risk Impact <span class="text-danger">*</span></label>
                            <select name="erkap_risk_impact_id" class="form-select @error('erkap_risk_impact_id') is-invalid @enderror" required>
                                <option value="" disabled {{ old('erkap_risk_impact_id') ? '' : 'selected' }}>Pilih Risk Impact</option>
                                @foreach($impacts as $impact)
                                    <option value="{{ $impact->id }}" {{ old('erkap_risk_impact_id') == $impact->id ? 'selected' : '' }}>
                                        {{ $impact->name }} ({{ $impact->point }})
                                    </option>
                                @endforeach
                            </select>
                            @error('erkap_risk_impact_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Score <span class="text-danger">*</span></label>
                            <input type="number" name="score" class="form-control @error('score') is-invalid @enderror" value="{{ old('score') }}" required min="0" step="1">
                            @error('score') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Level <span class="text-danger">*</span></label>
                            <select name="level" class="form-select @error('level') is-invalid @enderror" required>
                                <option value="" disabled {{ old('level') ? '' : 'selected' }}>Pilih Level</option>
                                @foreach(['Low', 'Low To Moderate', 'Moderate', 'Moderate To High', 'High'] as $option)
                                    <option value="{{ $option }}" {{ old('level') == $option ? 'selected' : '' }}>
                                        {{ $option }}
                                    </option>
                                @endforeach
                            </select>
                            @error('level') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-4 border-top pt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save me-1"></i> Simpan
                        </button>
                        <a href="{{ route('erkap.risk-score-levels.index') }}" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection