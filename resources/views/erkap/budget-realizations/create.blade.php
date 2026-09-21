@extends('layouts.Erkap')

@section('title', $pageName)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">{{ $pageName }}</h5>
                    <a href="{{ route('erkap.budget-realizations.index') }}" class="btn btn-secondary">
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

                <h6 class="text-uppercase fw-bold text-muted mb-3">
                    <i class="mdi mdi-file-upload me-1"></i> Import dari File Excel
                </h6>
                <form action="{{ route('erkap.budget-realizations.import') }}" method="POST" enctype="multipart/form-data" class="row g-3 mb-5">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Tahun RKAP <span class="text-danger">*</span></label>
                        <select name="erkap_rkap_id" class="form-select" required>
                            <option value="" disabled selected>Pilih Tahun RKAP</option>
                            @foreach($rkaps as $rkap)
                                <option value="{{ $rkap->id }}">{{ $rkap->year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Bulan <span class="text-danger">*</span></label>
                        <select name="month" class="form-select" required>
                            <option value="" disabled selected>Pilih Bulan</option>
                            @foreach($monthLabels as $key => $label)
                                <option value="{{ $key + 1 }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Tahun <span class="text-danger">*</span></label>
                        <input type="number" name="year" class="form-control" value="{{ old('year', date('Y')) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">File (xlsx/xls/csv) <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-info w-100"><i class="mdi mdi-upload me-1"></i> Import</button>
                    </div>
                    <div class="col-md-12">
                        <small class="text-muted">
                            Format kolom: <code>erkap_routine_cost_id</code>, <code>erkap_investment_plan_id</code>, <code>realized</code>. Baris pertama (header) dilewati.
                        </small>
                    </div>
                </form>

                <hr>

                <h6 class="text-uppercase fw-bold text-muted mb-3">
                    <i class="mdi mdi-pencil-plus me-1"></i> Input Manual
                </h6>

                <form action="{{ route('erkap.budget-realizations.store') }}" method="POST">
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
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Bulan <span class="text-danger">*</span></label>
                            <select name="month" class="form-select @error('month') is-invalid @enderror" required>
                                <option value="" disabled selected>Pilih Bulan</option>
                                @foreach($monthLabels as $key => $label)
                                    <option value="{{ $key + 1 }}" {{ old('month') == $key + 1 ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('month') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Tahun <span class="text-danger">*</span></label>
                            <input type="number" name="year" class="form-control @error('year') is-invalid @enderror" value="{{ old('year', date('Y')) }}" required>
                            @error('year') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Sumber <span class="text-danger">*</span></label>
                            <select name="source" class="form-select @error('source') is-invalid @enderror" required>
                                <option value="manual" {{ old('source', 'manual') == 'manual' ? 'selected' : '' }}>Manual</option>
                                <option value="accounting" {{ old('source') == 'accounting' ? 'selected' : '' }}>Sistem Akuntansi</option>
                            </select>
                            @error('source') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Biaya Rutin (opsional)</label>
                            <select name="erkap_routine_cost_id" class="form-select @error('erkap_routine_cost_id') is-invalid @enderror">
                                <option value="">-- Pilih Biaya Rutin --</option>
                                @foreach($routineCosts as $cost)
                                    @php
                                        $division = optional($cost->workProgram?->riskIdentification?->departmentTarget?->division);
                                        $rkapYear = optional($cost->workProgram?->riskIdentification?->departmentTarget?->companyTarget?->rkap)->year;
                                    @endphp
                                    <option value="{{ $cost->id }}" {{ old('erkap_routine_cost_id') == $cost->id ? 'selected' : '' }}>
                                        [#{{ $cost->id }}] RKAP {{ $rkapYear ?? '-' }} - {{ $division->name ?? '-' }} - {{ Str::limit($cost->need, 60) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('erkap_routine_cost_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Rencana Investasi (opsional)</label>
                            <select name="erkap_investment_plan_id" class="form-select @error('erkap_investment_plan_id') is-invalid @enderror">
                                <option value="">-- Pilih Rencana Investasi --</option>
                                @foreach($investmentPlans as $plan)
                                    @php
                                        $division = optional($plan->workProgram?->riskIdentification?->departmentTarget?->division);
                                        $rkapYear = optional($plan->workProgram?->riskIdentification?->departmentTarget?->companyTarget?->rkap)->year;
                                    @endphp
                                    <option value="{{ $plan->id }}" {{ old('erkap_investment_plan_id') == $plan->id ? 'selected' : '' }}>
                                        [#{{ $plan->id }}] RKAP {{ $rkapYear ?? '-' }} - {{ $division->name ?? '-' }} - {{ Str::limit($plan->name, 60) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('erkap_investment_plan_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Realisasi (Rp) <span class="text-danger">*</span></label>
                            <input type="text" inputmode="decimal" name="realized" id="realized" class="form-control rupiah-input @error('realized') is-invalid @enderror" value="{{ old('realized') }}" placeholder="0" required>
                            @error('realized') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-4 border-top pt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save me-1"></i> Simpan
                        </button>
                        <a href="{{ route('erkap.budget-realizations.index') }}" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('plugin')
@include('erkap.partials.rupiah')
@endsection