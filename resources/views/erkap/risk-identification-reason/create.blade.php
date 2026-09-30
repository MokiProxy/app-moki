@extends('layouts.Erkap')

@section('title', $pageName)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">{{ $pageName }}</h5>
                    <a href="{{ route('erkap.risk-identification-reasons.index') }}" class="btn btn-secondary">
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

                <form action="{{ route('erkap.risk-identification-reasons.store') }}" method="POST">
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
                    </div>

                    <div class="mt-4">
                        <div class="card border">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h6 class="mb-0 fw-bold">Penyebab Identifikasi Risiko</h6>
                                    <button type="button" class="btn btn-outline-primary btn-sm btn-add-reason">
                                        <i class="mdi mdi-plus me-1"></i> Tambah Penyebab
                                    </button>
                                </div>
                                <div id="reason-rows">
                                    @php
                                    $reasonRows = old('reasons');
                                    if (!is_array($reasonRows) || $reasonRows === []) { $reasonRows = ['']; }
                                    @endphp
                                    @foreach($reasonRows as $index => $value)
                                    <div class="reason-row d-flex align-items-start gap-2 mb-2">
                                        <div class="flex-grow-1">
                                            <input type="text" class="form-control reason-input" name="reasons[{{ $index }}]" placeholder="Masukkan penyebab..." value="{{ is_string($value) ? $value : '' }}" maxlength="255">
                                        </div>
                                        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-reason" title="Hapus penyebab">
                                            <i class="mdi mdi-minus"></i>
                                        </button>
                                    </div>
                                    @endforeach
                                </div>
                                <div class="text-muted small mt-1">Penyebab dapat diisi lebih dari satu.</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 border-top pt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save me-1"></i> Simpan
                        </button>
                        <a href="{{ route('erkap.risk-identification-reasons.index') }}" class="btn btn-secondary">Batal</a>
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
        function renumberReasonRows() {
            $('.reason-row').each(function(index) {
                $(this).find('.reason-input').attr('name', 'reasons[' + index + ']');
            });
        }

        $(document).on('click', '.btn-add-reason', function() {
            var $row = $('.reason-row:last').clone();
            $row.find('.reason-input').val('').removeClass('is-invalid');
            $('#reason-rows').append($row);
            renumberReasonRows();
            $row.find('.reason-input').focus();
        });

        $(document).on('click', '.btn-remove-reason', function() {
            var $rows = $('.reason-row');
            if ($rows.length <= 1) {
                var $last = $rows.first();
                $last.find('.reason-input').val('').removeClass('is-invalid');
                $last.find('.reason-input').focus();
            } else {
                $(this).closest('.reason-row').remove();
                renumberReasonRows();
            }
        });
    });
</script>
@endsection