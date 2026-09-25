@extends('layouts.Erkap')

@section('title', $pageName)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">{{ $pageName }}</h5>
                    <a href="{{ route('erkap.risk-analysis.index') }}" class="btn btn-secondary">
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

                <form action="{{ route('erkap.risk-analysis.store') }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Identifikasi Risiko <span class="text-danger">*</span></label>
                            <select name="erkap_risk_identification_id" class="form-select @error('erkap_risk_identification_id') is-invalid @enderror" required>
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
                            <label class="form-label fw-bold">Probabilitas <span class="text-danger">*</span></label>
                            <select name="erkap_risk_probability_id" class="form-select @error('erkap_risk_probability_id') is-invalid @enderror" required>
                                <option value="" disabled selected>Pilih Probabilitas</option>
                                @foreach($riskProbabilities as $riskProbability)
                                    <option value="{{ $riskProbability->id }}" {{ old('erkap_risk_probability_id') == $riskProbability->id ? 'selected' : '' }}>
                                        {{ $riskProbability->point }} - {{ $riskProbability->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('erkap_risk_probability_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Dampak <span class="text-danger">*</span></label>
                            <select name="erkap_risk_impact_id" class="form-select @error('erkap_risk_impact_id') is-invalid @enderror" required>
                                <option value="" disabled selected>Pilih Dampak</option>
                                @foreach($riskImpacts as $riskImpact)
                                    <option value="{{ $riskImpact->id }}" {{ old('erkap_risk_impact_id') == $riskImpact->id ? 'selected' : '' }}>
                                        {{ $riskImpact->point }} - {{ $riskImpact->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('erkap_risk_impact_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Skor & Level <span class="text-danger">*</span></label>
                            <select id="erkap_risk_score_value_id" class="form-select" required disabled>
                                <option value="" disabled selected>Pilih Probabilitas & Dampak terlebih dahulu</option>
                            </select>
                            <input type="hidden" name="erkap_risk_score_value_id" id="erkap_risk_score_value_id_hidden" value="{{ old('erkap_risk_score_value_id') }}">
                            @error('erkap_risk_score_value_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-4 border-top pt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save me-1"></i> Simpan
                        </button>
                        <a href="{{ route('erkap.risk-analysis.index') }}" class="btn btn-secondary">Batal</a>
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
    var scoreLevelUrl = '{{ route("erkap.risk-analysis.get-score-level", ["probabilityId" => "__PROBABILITY_ID__", "impactId" => "__IMPACT_ID__"]) }}';

    function fetchScoreLevel() {
        var probabilityId = $('select[name="erkap_risk_probability_id"]').val();
        var impactId = $('select[name="erkap_risk_impact_id"]').val();
        var $scoreSelect = $('#erkap_risk_score_value_id');
        var $scoreHidden = $('#erkap_risk_score_value_id_hidden');

        if (!probabilityId || !impactId) {
            $scoreSelect.prop('disabled', true)
                .html('<option value="" disabled selected>Pilih Probabilitas & Dampak terlebih dahulu</option>');
            $scoreHidden.val('');
            return;
        }

        var url = scoreLevelUrl.replace('__PROBABILITY_ID__', probabilityId).replace('__IMPACT_ID__', impactId);

        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                if (response.found) {
                    $scoreSelect.prop('disabled', false)
                        .html('<option value="' + response.id + '" selected>' + response.score + ' - ' + response.level + '</option>');
                    $scoreHidden.val(response.id);
                } else {
                    $scoreSelect.prop('disabled', true)
                        .html('<option value="" disabled selected>Tidak ditemukan kombinasi yang sesuai</option>');
                    $scoreHidden.val('');
                }
            },
            error: function() {
                $scoreSelect.prop('disabled', true)
                    .html('<option value="" disabled selected>Gagal memuat data</option>');
                $scoreHidden.val('');
            }
        });
    }

    $('select[name="erkap_risk_probability_id"]').on('change', fetchScoreLevel);
    $('select[name="erkap_risk_impact_id"]').on('change', fetchScoreLevel);

    fetchScoreLevel();
});
</script>
@endsection
