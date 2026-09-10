@extends('layouts.Erkap')

@section('title', $pageName)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">{{ $pageName }}</h5>
                    <a href="{{ route('erkap.risk-identifications.index') }}" class="btn btn-secondary">
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

                <form action="{{ route('erkap.risk-identifications.update', $riskIdentification->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Sasaran Departemen <span class="text-danger">*</span></label>
                            <select name="erkap_department_target_id" class="form-select @error('erkap_department_target_id') is-invalid @enderror" required>
                                <option value="" disabled {{ old('erkap_department_target_id', $riskIdentification->erkap_department_target_id) ? '' : 'selected' }}>Pilih Sasaran Departemen</option>
                                @foreach($departmentTargets as $departmentTarget)
                                    <option value="{{ $departmentTarget->id }}" {{ old('erkap_department_target_id', $riskIdentification->erkap_department_target_id) == $departmentTarget->id ? 'selected' : '' }}>
                                        {{ $departmentTarget->target }}
                                    </option>
                                @endforeach
                            </select>
                            @error('erkap_department_target_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Risk Type <span class="text-danger">*</span></label>
                            <select name="erkap_risk_type_id" class="form-select @error('erkap_risk_type_id') is-invalid @enderror" required>
                                <option value="" disabled {{ old('erkap_risk_type_id', $riskIdentification->erkap_risk_type_id) ? '' : 'selected' }}>Pilih Risk Type</option>
                                @foreach($riskTypes as $riskType)
                                    <option value="{{ $riskType->id }}" {{ old('erkap_risk_type_id', $riskIdentification->erkap_risk_type_id) == $riskType->id ? 'selected' : '' }}>
                                        {{ $riskType->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('erkap_risk_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Risk Taxonomy <span class="text-danger">*</span></label>
                            <select name="erkap_risk_taxonomy_id" class="form-select @error('erkap_risk_taxonomy_id') is-invalid @enderror" required>
                                <option value="" disabled {{ old('erkap_risk_taxonomy_id', $riskIdentification->erkap_risk_taxonomy_id) ? '' : 'selected' }}>Pilih Risk Taxonomy</option>
                                @foreach($riskTaxonomies as $riskTaxonomy)
                                    <option value="{{ $riskTaxonomy->id }}" {{ old('erkap_risk_taxonomy_id', $riskIdentification->erkap_risk_taxonomy_id) == $riskTaxonomy->id ? 'selected' : '' }}>
                                        {{ $riskTaxonomy->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('erkap_risk_taxonomy_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Risk <span class="text-danger">*</span></label>
                            <input type="text" name="risk" class="form-control @error('risk') is-invalid @enderror" value="{{ old('risk', $riskIdentification->risk) }}" required maxlength="255">
                            @error('risk') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-4 border-top pt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save me-1"></i> Update
                        </button>
                        <a href="{{ route('erkap.risk-identifications.index') }}" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
