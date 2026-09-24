@extends('layouts.Erkap')

@section('title', $pageName)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">{{ $pageName }}</h5>
                    <a href="{{ route('erkap.risk-treatments.index') }}" class="btn btn-secondary">
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

                <form action="{{ route('erkap.risk-treatments.store') }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Identifikasi Risiko <span class="text-danger">*</span></label>
                            <select name="erkap_risk_identification_id" id="risk-select" class="form-select @error('erkap_risk_identification_id') is-invalid @enderror" required>
                                <option value="" disabled selected>Pilih Identifikasi Risiko</option>
                                @foreach($riskIdentifications as $riskIdentification)
                                    <option value="{{ $riskIdentification->id }}" {{ old('erkap_risk_identification_id') == $riskIdentification->id ? 'selected' : '' }}>
                                        {{ $riskIdentification->risk }}
                                    </option>
                                @endforeach
                            </select>
                            @error('erkap_risk_identification_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Strategi Risiko</label>
                            <select name="erkap_department_risk_strategy_id" id="strategy-select" class="form-select @error('erkap_department_risk_strategy_id') is-invalid @enderror">
                                <option value="">Tidak Ada (Opsional)</option>
                                @foreach($strategies as $strategy)
                                    <option value="{{ $strategy->id }}" data-risk-id="{{ $strategy->erkap_risk_identification_id }}" {{ old('erkap_department_risk_strategy_id') == $strategy->id ? 'selected' : '' }}>
                                        {{ \App\Models\Erkap\DepartmentRiskStrategy::getStrategies()[$strategy->strategy] ?? $strategy->strategy }}
                                    </option>
                                @endforeach
                            </select>
                            @error('erkap_department_risk_strategy_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Jenis Perlakuan <span class="text-danger">*</span></label>
                            <select name="treatment_type" class="form-select @error('treatment_type') is-invalid @enderror" required>
                                <option value="" disabled selected>Pilih Jenis Perlakuan</option>
                                @foreach(\App\Models\Erkap\RiskTreatment::getTreatmentTypes() as $key => $typeLabel)
                                    <option value="{{ $key }}" {{ old('treatment_type') == $key ? 'selected' : '' }}>
                                        {{ $typeLabel }}
                                    </option>
                                @endforeach
                            </select>
                            @error('treatment_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Penanggung Jawab (PIC) <span class="text-danger">*</span></label>
                            <input type="text" name="responsible_party" value="{{ old('responsible_party') }}" class="form-control @error('responsible_party') is-invalid @enderror" required>
                            @error('responsible_party') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Target Penyelesaian <span class="text-danger">*</span></label>
                            <input type="date" name="target_date" value="{{ old('target_date') }}" class="form-control @error('target_date') is-invalid @enderror" required>
                            @error('target_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="" disabled selected>Pilih Status</option>
                                @foreach([
                                    'planned' => 'Direncanakan',
                                    'in_progress' => 'Berjalan',
                                    'completed' => 'Selesai',
                                    'cancelled' => 'Dibatalkan',
                                ] as $statusKey => $statusLabel)
                                    <option value="{{ $statusKey }}" {{ old('status') == $statusKey ? 'selected' : '' }}>
                                        {{ $statusLabel }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Deskripsi Perlakuan <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3" required>{{ old('description') }}</textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Hasil / Tindak Lanjut</label>
                            <textarea name="result" class="form-control @error('result') is-invalid @enderror" rows="2">{{ old('result') }}</textarea>
                            @error('result') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-4 border-top pt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save me-1"></i> Simpan
                        </button>
                        <a href="{{ route('erkap.risk-treatments.index') }}" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('plugin')
<script>
    $(document).ready(function() {
        $('#risk-select').on('change', function() {
            var riskId = $(this).val();
            var selectedStrategyId = $('#strategy-select').val();
            $('#strategy-select option').hide();
            $('#strategy-select option[value=""]').show();
            if (riskId) {
                $('#strategy-select option[data-risk-id="' + riskId + '"]').show();
            }
            if (selectedStrategyId && $('#strategy-select option[value="' + selectedStrategyId + '"]:visible').length === 0) {
                $('#strategy-select').val('');
            }
        });

        $('#risk-select').trigger('change');
    });
</script>
@endsection