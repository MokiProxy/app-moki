@extends('layouts.Erkap')

@section('title', $pageName)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">{{ $pageName }}</h5>
                    <a href="{{ route('erkap.department-risk-strategies.index') }}" class="btn btn-secondary">
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

                <form action="{{ route('erkap.department-risk-strategies.store') }}" method="POST">
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
                                    <h6 class="mb-0 fw-bold">Strategi Mitigasi</h6>
                                    <button type="button" class="btn btn-outline-primary btn-sm btn-add-strategy">
                                        <i class="mdi mdi-plus me-1"></i> Tambah Strategi
                                    </button>
                                </div>
                                <div id="strategy-rows">
                                    @php
                                    $strategyRows = old('strategies');
                                    if (!is_array($strategyRows) || $strategyRows === []) { $strategyRows = ['']; }
                                    @endphp
                                    @foreach($strategyRows as $index => $value)
                                    <div class="strategy-row d-flex align-items-start gap-2 mb-2">
                                        <div class="flex-grow-1">
                                            <input type="text" class="form-control strategy-input" name="strategies[{{ $index }}]" placeholder="Masukkan strategi..." value="{{ is_string($value) ? $value : '' }}" maxlength="255">
                                        </div>
                                        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-strategy" title="Hapus strategi">
                                            <i class="mdi mdi-minus"></i>
                                        </button>
                                    </div>
                                    @endforeach
                                </div>
                                <div class="text-muted small mt-1">Strategi mitigasi dapat diisi lebih dari satu.</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 border-top pt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save me-1"></i> Simpan
                        </button>
                        <a href="{{ route('erkap.department-risk-strategies.index') }}" class="btn btn-secondary">Batal</a>
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
        function renumberStrategyRows() {
            $('.strategy-row').each(function(index) {
                $(this).find('.strategy-input').attr('name', 'strategies[' + index + ']');
            });
        }

        $(document).on('click', '.btn-add-strategy', function() {
            var $row = $('.strategy-row:last').clone();
            $row.find('.strategy-input').val('').removeClass('is-invalid');
            $('#strategy-rows').append($row);
            renumberStrategyRows();
            $row.find('.strategy-input').focus();
        });

        $(document).on('click', '.btn-remove-strategy', function() {
            var $rows = $('.strategy-row');
            if ($rows.length <= 1) {
                var $last = $rows.first();
                $last.find('.strategy-input').val('').removeClass('is-invalid');
                $last.find('.strategy-input').focus();
            } else {
                $(this).closest('.strategy-row').remove();
                renumberStrategyRows();
            }
        });
    });
</script>
@endsection
