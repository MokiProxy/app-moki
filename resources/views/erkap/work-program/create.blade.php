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

$selectedRiskIdentification = $riskIdentification ?? null;
$riskInfoMap = [];
foreach ($riskIdentifications as $riskIdentificationItem) {
    $deptTarget = optional($riskIdentificationItem->departmentTarget);
    $ratingCriteria = optional($deptTarget->ratingCriteria);
    $riskInfoMap[$riskIdentificationItem->id] = [
        'sasaran' => $deptTarget->target ?? '-',
        'rating' => $ratingCriteria->rating ?? '-',
        'qualification' => $ratingCriteria->qualification ?? '-',
        'risk' => $riskIdentificationItem->risk,
    ];
}
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

                <form action="{{ route('erkap.work-programs.store') }}" method="POST" id="form-work-program">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Identifikasi Risiko <span class="text-danger">*</span></label>
                            <select name="erkap_risk_identification_id" id="risk-identification-select" class="form-select @error('erkap_risk_identification_id') is-invalid @enderror" required>
                                <option value="" disabled selected>Pilih Identifikasi Risiko</option>
                                @foreach($riskIdentifications as $riskIdentificationOption)
                                    <option value="{{ $riskIdentificationOption->id }}" {{ old('erkap_risk_identification_id', $selectedRiskIdentification->id ?? null) == $riskIdentificationOption->id ? 'selected' : '' }}>
                                        {{ $riskIdentificationOption->risk }}
                                    </option>
                                @endforeach
                            </select>
                            @error('erkap_risk_identification_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">
                                <i class="mdi mdi-information-outline me-1"></i>Program kerja hanya bisa dibuat untuk sasaran dengan rating A ke atas.
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="border rounded p-3 bg-light @if(empty($selectedRiskIdentification)) d-none @endif" id="sasaran-rating-info">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <small class="text-muted text-uppercase fw-bold">Sasaran</small>
                                        <div class="fw-semibold" id="info-target">{{ optional(optional($selectedRiskIdentification)->departmentTarget)->target ?? '-' }}</div>
                                    </div>
                                    <div class="col-3">
                                        <small class="text-muted text-uppercase fw-bold">Rating</small>
                                        <div><span class="badge bg-primary" id="info-rating">{{ optional(optional(optional($selectedRiskIdentification)->departmentTarget)->ratingCriteria)->rating ?? '-' }}</span></div>
                                    </div>
                                    <div class="col-3">
                                        <small class="text-muted text-uppercase fw-bold">Kualifikasi</small>
                                        <div class="fw-semibold" id="info-qualification">{{ optional(optional(optional($selectedRiskIdentification)->departmentTarget)->ratingCriteria)->qualification ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Program Kerja <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Nama program kerja" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Satuan <span class="text-danger">*</span></label>
                            <input type="text" name="units" class="form-control @error('units') is-invalid @enderror" value="{{ old('units') }}" placeholder="Contoh: Kegiatan, Armada, Unit" required>
                            @error('units') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Rencana Tahunan <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="year_plan" id="input-year-plan" class="form-control @error('year_plan') is-invalid @enderror" value="{{ old('year_plan') }}" required>
                            @error('year_plan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Jumlah rencana tahunan harus sama dengan total rencana bulanan.</div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6 class="text-uppercase fw-bold text-muted mb-3"><i class="mdi mdi-calendar-month me-1"></i> Rencana Bulanan</h6>
                    </div>

                    <div class="row g-3">
                        @foreach($months as $field => $label)
                        <div class="col-md-2">
                            <label class="form-label fw-bold">{{ $label }} <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="{{ $field }}" class="form-control monthly-plan @error($field) is-invalid @enderror" value="{{ old($field) }}" required>
                            @error($field) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        @endforeach
                    </div>

                    <div class="mt-3">
                        <small class="text-muted"><i class="mdi mdi-calculator me-1"></i>Total Rencana Bulanan: <strong id="monthly-total">0</strong></small>
                    </div>

                    <div class="mt-3">
                        <small class="text-muted fw-bold"><i class="mdi mdi-percent me-1"></i>Kumulatif Rencana (%)</small>
                        <div class="d-flex flex-wrap gap-2 mt-2" id="cumulative-preview"></div>
                    </div>

                    <div class="mt-4 border-top pt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save me-1"></i> Simpan
                        </button>
                        <a href="{{ route('erkap.work-programs.index') }}" class="btn btn-secondary">Batal</a>
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
        var months = ['jan_plan','feb_plan','mar_plan','apr_plan','may_plan','jun_plan','jul_plan','aug_plan','sep_plan','oct_plan','nov_plan','dec_plan'];
        var monthShorts = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        var riskInfoMap = @json($riskInfoMap);
        var allowedRatings = ['AAA', 'AA', 'A'];

        function monthlyTotal() {
            var total = 0;
            months.forEach(function(m) {
                var val = parseFloat($('input[name="' + m + '"]').val());
                if (!isNaN(val)) { total += val; }
            });
            return total;
        }

        function updateTotal() {
            $('#monthly-total').text(monthlyTotal().toFixed(2));
            updateCumulativePreview();
        }

        function updateCumulativePreview() {
            var yearPlan = parseFloat($('#input-year-plan').val());
            var running = 0;
            var html = '';
            months.forEach(function(m, i) {
                var val = parseFloat($('input[name="' + m + '"]').val());
                if (!isNaN(val)) { running += val; }
                var pct = (!isNaN(yearPlan) && yearPlan > 0) ? ((running / yearPlan) * 100).toFixed(2) : '0.00';
                html += '<span class="badge bg-primary-subtle text-primary border">' + monthShorts[i] + ': ' + pct + '%</span>';
            });
            $('#cumulative-preview').html(html);
        }

        function updateRiskInfo() {
            var id = $('#risk-identification-select').val();
            var info = id ? riskInfoMap[id] : null;
            if (info) {
                $('#info-target').text(info.sasaran);
                $('#info-rating').text(info.rating);
                $('#info-qualification').text(info.qualification);
                $('#sasaran-rating-info').removeClass('d-none');

                if (allowedRatings.indexOf(info.rating) === -1) {
                    $('#sasaran-rating-info').addClass('border-danger');
                    $('#sasaran-rating-info').append('<div class="text-danger small mt-1" id="rating-warning"><i class="mdi mdi-alert me-1"></i>Rating di bawah A tidak dapat dibuatkan program kerja.</div>');
                } else {
                    $('#sasaran-rating-info').removeClass('border-danger');
                    $('#rating-warning').remove();
                }
            } else {
                $('#sasaran-rating-info').addClass('d-none');
                $('#rating-warning').remove();
            }
        }

        $('.monthly-plan').on('input', updateTotal);
        $('#input-year-plan').on('input', updateTotal);
        $('#risk-identification-select').on('change', updateRiskInfo);

        $('#form-work-program').on('submit', function(e) {
            var total = monthlyTotal();
            var yearPlan = parseFloat($('#input-year-plan').val());
            var id = $('#risk-identification-select').val();
            var info = id ? riskInfoMap[id] : null;

            if (info && allowedRatings.indexOf(info.rating) === -1) {
                e.preventDefault();
                Swal.fire('Gagal', 'Program kerja hanya bisa dibuat untuk sasaran dengan rating A ke atas.', 'error');
                return false;
            }

            if (isNaN(yearPlan) || Math.abs(total - yearPlan) > 0.01) {
                e.preventDefault();
                Swal.fire('Gagal', 'Rencana tahunan harus sama dengan jumlah rencana bulanan.', 'error');
                return false;
            }
        });

        updateTotal();
        updateRiskInfo();
    });
</script>
@endsection